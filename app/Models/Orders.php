<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Orders extends Model
{
    protected $fillable = ['user_id', 'order_number', 'table_number', 'status', 'total', 'notes'];

    /**
     * Generate order number with format: ORD-YYYYMMDD-XXXX
     */
    public static function generateOrderNumber(): string
    {
        $today = Carbon::today()->format('Ymd');
        $prefix = 'ORD-' . $today . '-';

        // Get the latest order number for today
        $lastOrder = self::where('order_number', 'like', $prefix . '%')
            ->orderBy('order_number', 'desc')
            ->first();

        if ($lastOrder) {
            // Extract the counter part and increment
            $lastCounter = (int) substr($lastOrder->order_number, -4);
            $newCounter = $lastCounter + 1;
        } else {
            $newCounter = 1;
        }

        return $prefix . str_pad($newCounter, 4, '0', STR_PAD_LEFT);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItems::class, 'order_id');
    }

    public function transaction()
    {
        return $this->hasOne(Transactions::class, 'order_id');
    }
}
