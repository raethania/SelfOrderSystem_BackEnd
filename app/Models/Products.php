<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Products extends Model
{
    protected $fillable = ['category_id', 'name', 'description', 'price', 'stock', 'image', 'status'];

    public function category()
    {
        return $this->belongsTo(Categories::class, 'category_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItems::class, 'product_id');
    }
}
