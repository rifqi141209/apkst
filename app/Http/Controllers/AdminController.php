<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PaymentGateway;
use App\Models\ProductAccount;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', ['products' => Product::latest()->get()]);
    }

    public function storeProduct(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'category' => ['required', 'string', 'max:40'], 'price' => ['required', 'integer', 'min:100'], 'stock' => ['required', 'integer', 'min:0'], 'image_color' => ['nullable', 'regex:/^[0-9A-Fa-f]{6}$/']]);
        Product::create([...$data, 'image_color' => $data['image_color'] ?? '2563EB']);

        return back()->with('success', 'Produk berhasil ditambahkan.');
    }

    public function updateStock(Request $request, Product $product)
    {
        $data = $request->validate(['stock' => ['required', 'integer', 'min:0']]);
        $product->update($data);

        return back()->with('success', "Stok {$product->name} diperbarui.");
    }

    public function addAccount(Request $request, Product $product)
    {
        $data = $request->validate(['login' => ['required', 'string', 'max:255'], 'password' => ['required', 'string', 'max:255']]);
        ProductAccount::create(['product_id' => $product->id, ...$data]);
        $product->increment('stock');

        return back()->with('success', "Akun stok untuk {$product->name} berhasil ditambahkan.");
    }

    public function destroyProduct(Product $product)
    {
        $product->delete();

        return back()->with('success', "Produk {$product->name} berhasil dihapus.");
    }

    public function paymentGateways()
    {
        return view('admin.payment-gateways', ['gateways' => PaymentGateway::orderBy('name')->get()]);
    }

    public function togglePaymentGateway(PaymentGateway $paymentGateway)
    {
        $paymentGateway->update(['active' => ! $paymentGateway->active]);

        return back()->with('success', "Status {$paymentGateway->name} diperbarui.");
    }
}