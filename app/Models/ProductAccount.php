<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAccount extends Model
{
    protected $fillable = ['product_id', 'login', 'password', 'status'];

    protected function casts(): array
    {
        return ['password' => 'encrypted'];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order()
    {
        return $this->hasOne(Order::class);
    }
}