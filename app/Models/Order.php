<?php

namespace App\Models;

use App\Models\ProductAccount;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['invoice_number', 'user_id', 'product_id', 'product_account_id', 'quantity', 'total', 'payment_method', 'payment_number', 'payment_expires_at', 'status'];

    protected function casts(): array
    {
        return ['total' => 'integer', 'quantity' => 'integer', 'payment_expires_at' => 'datetime'];
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function productAccount()
    {
        return $this->belongsTo(ProductAccount::class);
    }
}