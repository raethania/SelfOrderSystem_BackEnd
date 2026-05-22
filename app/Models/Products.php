<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Products extends Model
{
    use SoftDeletes;
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
