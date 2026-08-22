<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MultiUserPurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_admin_can_update_product_stock(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::create(['name' => 'Test Product', 'category' => 'MUSIC', 'price' => 10000, 'stock' => 1, 'image_color' => '2563EB']);

        $this->actingAs($admin)->patch(route('admin.products.stock', $product), ['stock' => 9])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 9]);
    }

    public function test_user_purchase_creates_invoice_and_reduces_stock(): void
    {
        config(['services.pakasir.project' => 'demo-project', 'services.pakasir.api_key' => 'sandbox-key']);
        Http::fake([
            'app.pakasir.com/api/transactioncreate/qris' => Http::response(['payment' => ['payment_number' => '000201010212', 'expired_at' => now()->addHour()->toISOString()]], 200),
            'app.pakasir.com/api/paymentsimulation' => Http::response(['message' => 'Payment simulated'], 200),
            'app.pakasir.com/api/transactiondetail*' => Http::response(['transaction' => ['status' => 'completed']], 200),
        ]);
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::create(['name' => 'Test Product', 'category' => 'MUSIC', 'price' => 10000, 'stock' => 1, 'image_color' => '2563EB']);
        ProductAccount::create(['product_id' => $product->id, 'login' => 'test@example.com', 'password' => 'test-password']);

        $response = $this->actingAs($user)->post(route('orders.store', $product));

        $response->assertRedirect(route('orders.show', $user->orders()->latest()->first()));
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 1]);
        $order = $user->orders()->latest()->first();
        $this->assertSame('awaiting_payment', $order->status);
        $this->actingAs($user)->post(route('orders.confirm-payment', $order))->assertRedirect();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 0]);
        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'product_id' => $product->id, 'total' => 10000, 'status' => 'delivered']);
        $this->assertDatabaseHas('product_accounts', ['product_id' => $product->id, 'status' => 'sold']);
    }

    public function test_confirm_payment_returns_friendly_error_when_account_is_unavailable(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::create(['name' => 'Empty Product', 'category' => 'MUSIC', 'price' => 10000, 'stock' => 1, 'image_color' => '2563EB']);
        $order = $user->orders()->create(['invoice_number' => 'TL-TEST-EMPTY', 'product_id' => $product->id, 'quantity' => 1, 'total' => 10000, 'payment_method' => 'qris', 'status' => 'awaiting_payment']);

        $this->actingAs($user)->post(route('orders.confirm-payment', $order))
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHasErrors('payment');
    }

    public function test_pending_invoice_backfills_qris_from_pakasir(): void
    {
        config(['services.pakasir.project' => 'demo-project', 'services.pakasir.api_key' => 'sandbox-key']);
        Http::fake(['app.pakasir.com/api/transactioncreate/qris' => Http::response(['payment' => ['payment_number' => '000201QRIS-STRING', 'expired_at' => now()->addHour()->toISOString()]], 200)]);
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::create(['name' => 'Pending Product', 'category' => 'MUSIC', 'price' => 10000, 'stock' => 1, 'image_color' => '2563EB']);
        $order = $user->orders()->create(['invoice_number' => 'TL-TEST-BACKFILL', 'product_id' => $product->id, 'quantity' => 1, 'total' => 10000, 'payment_method' => 'qris', 'status' => 'awaiting_payment']);

        $this->actingAs($user)->get(route('orders.show', $order))->assertOk();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_number' => '000201QRIS-STRING']);
    }

    public function test_pakasir_webhook_delivers_account_only_once(): void
    {
        config(['services.pakasir.project' => 'demo-project']);
        Http::fake(['app.pakasir.com/api/transactiondetail*' => Http::response(['transaction' => ['status' => 'completed']], 200)]);
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::create(['name' => 'Webhook Product', 'category' => 'MUSIC', 'price' => 10000, 'stock' => 1, 'image_color' => '2563EB']);
        ProductAccount::create(['product_id' => $product->id, 'login' => 'webhook@example.com', 'password' => 'webhook-password']);
        $order = $user->orders()->create(['invoice_number' => 'TL-TEST-WEBHOOK', 'product_id' => $product->id, 'quantity' => 1, 'total' => 10000, 'payment_method' => 'qris', 'status' => 'awaiting_payment']);
        $payload = ['amount' => 10000, 'order_id' => $order->invoice_number, 'project' => 'demo-project', 'status' => 'completed', 'payment_method' => 'qris'];

        $this->postJson(route('webhooks.pakasir'), $payload)->assertOk();
        $this->postJson(route('webhooks.pakasir'), $payload)->assertOk();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 0]);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('product_accounts', 1);
        $this->assertDatabaseHas('product_accounts', ['product_id' => $product->id, 'status' => 'sold']);
    }

    public function test_forged_pakasir_webhook_does_not_deliver_account(): void
    {
        config(['services.pakasir.project' => 'demo-project']);
        Http::fake(['app.pakasir.com/api/transactiondetail*' => Http::response(['transaction' => ['status' => 'pending']], 200)]);
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::create(['name' => 'Protected Product', 'category' => 'MUSIC', 'price' => 10000, 'stock' => 1, 'image_color' => '2563EB']);
        ProductAccount::create(['product_id' => $product->id, 'login' => 'protected@example.com', 'password' => 'protected-password']);
        $order = $user->orders()->create(['invoice_number' => 'TL-TEST-FORGED', 'product_id' => $product->id, 'quantity' => 1, 'total' => 10000, 'payment_method' => 'qris', 'status' => 'awaiting_payment']);

        $payload = ['amount' => 10000, 'order_id' => $order->invoice_number, 'project' => 'demo-project', 'status' => 'completed', 'payment_method' => 'qris'];

        $this->postJson(route('webhooks.pakasir'), $payload)->assertStatus(422);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'awaiting_payment']);
        $this->assertDatabaseHas('product_accounts', ['product_id' => $product->id, 'status' => 'available']);
    }

    public function test_sandbox_payment_can_be_simulated(): void
    {
        config(['services.pakasir.sandbox' => true, 'services.pakasir.project' => 'demo-project', 'services.pakasir.api_key' => 'sandbox-key']);
        Http::fake(['app.pakasir.com/api/paymentsimulation' => Http::response(['message' => 'Payment simulated'], 200)]);
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::create(['name' => 'Simulation Product', 'category' => 'MUSIC', 'price' => 10000, 'stock' => 1, 'image_color' => '2563EB']);
        $order = $user->orders()->create(['invoice_number' => 'TL-TEST-SIMULATION', 'product_id' => $product->id, 'quantity' => 1, 'total' => 10000, 'payment_method' => 'qris', 'status' => 'awaiting_payment']);

        $this->actingAs($user)->post(route('orders.simulate-payment', $order))
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('success');
    }

    public function test_admin_can_delete_product_without_order_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::create(['name' => 'Delete Me', 'category' => 'MUSIC', 'price' => 10000, 'stock' => 0, 'image_color' => '2563EB']);

        $this->actingAs($admin)->delete(route('admin.products.destroy', $product))->assertRedirect()->assertSessionHas('success');
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_admin_can_archive_product_with_order_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::create(['name' => 'Protected Product', 'category' => 'MUSIC', 'price' => 10000, 'stock' => 0, 'image_color' => '2563EB']);
        $user->orders()->create(['invoice_number' => 'TL-TEST-PROTECTED', 'product_id' => $product->id, 'quantity' => 1, 'total' => 10000, 'payment_method' => 'qris', 'status' => 'delivered']);

        $this->actingAs($admin)->delete(route('admin.products.destroy', $product))->assertRedirect()->assertSessionHas('success');
        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertSame('Protected Product', Product::withTrashed()->find($product->id)->name);
    }
}
