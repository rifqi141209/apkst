<?php

namespace App\Http\Controllers;

use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $products = Product::withCount(['accounts as available_stock' => fn ($query) => $query->where('status', 'available')])
            ->withCount(['orders as sold_count' => fn ($query) => $query->where('status', 'delivered')])
            ->orderBy('name')->get()->map(fn (Product $product) => [
            'id' => $product->id,
            'name' => $product->name,
            'category' => $product->category,
            'price' => $product->price,
            'stock' => $product->available_stock,
            'color' => $product->image_color,
            'sold' => max(1000, $product->sold_count),
        ])->values()->all();

        return view('welcome', compact('products'));
    }
}