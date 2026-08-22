<?php

namespace App\Models;

use App\Models\ProductAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'category', 'price', 'stock', 'image_color'];

    protected function casts(): array
    {
        return ['price' => 'integer', 'stock' => 'integer'];
    }

    public function accounts()
    {
        return $this->hasMany(ProductAccount::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}