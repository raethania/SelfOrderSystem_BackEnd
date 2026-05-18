<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItems extends Model
{

    public $timestamps = false;
    protected $fillable = ['order_id', 'product_id', 'quantity', 'price', 'notes'];

    public function order()
    {
        return $this->belongsTo(Orders::class, 'order_id');
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }
}
