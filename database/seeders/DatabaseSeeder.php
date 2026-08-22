<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\PaymentGateway;
use App\Models\User;
use App\Models\ProductAccount;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(['email' => 'admin@topuplabs.test'], ['name' => 'Admin Topuplabs', 'password' => Hash::make('password'), 'role' => 'admin']);
        User::updateOrCreate(['email' => 'user@topuplabs.test'], ['name' => 'Pengguna Demo', 'password' => Hash::make('password'), 'role' => 'user']);
        foreach ([['Netflix Premium', 'STREAMING', 35000, 10, 'E50914'], ['Spotify Premium', 'MUSIC', 12000, 15, '1DB954'], ['YouTube Premium', 'STREAMING', 9000, 12, 'FF0000'], ['Canva Pro', 'PRODUCTIVITY', 5000, 0, '00A9A5'], ['ChatGPT Plus', 'PRODUCTIVITY', 35000, 0, '10A37F'], ['Gemini Advanced', 'PRODUCTIVITY', 30000, 5, '4285F4'], ['Adobe Creative Cloud', 'PRODUCTIVITY', 15000, 5, 'FF0000'], ['Apple Music', 'MUSIC', 10000, 8, 'FA243C'], ['Scribd Premium', 'EDUCATION', 13000, 3, '1A7BBA'], ['DeepL Pro', 'EDUCATION', 15000, 4, '0F2B46']] as [$name, $category, $price, $stock, $color]) {
            $product = Product::updateOrCreate(['name' => $name], compact('name', 'category', 'price', 'stock') + ['image_color' => $color]);
            for ($index = 1; $index <= $stock; $index++) {
                ProductAccount::firstOrCreate(['product_id' => $product->id, 'login' => "demo-{$product->id}-{$index}@topuplabs.test"], ['password' => "demo-password-{$index}", 'status' => 'available']);
            }
        }
        foreach ([['QRIS', 'qris', 'Pembayaran melalui QRIS', true], ['Transfer Bank', 'bank-transfer', 'Transfer otomatis melalui bank', false], ['E-Wallet', 'ewallet', 'Pembayaran melalui dompet digital', false]] as [$name, $code, $description, $active]) {
            PaymentGateway::updateOrCreate(['code' => $code], compact('name', 'code', 'description', 'active'));
        }
    }
}
