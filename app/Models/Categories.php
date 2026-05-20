<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Categories extends Model
{
    use SoftDeletes;
    protected $fillable = ['name', 'slug'];

    public function products()
    {
        return $this->hasMany(Products::class, 'category_id');
    }
}
