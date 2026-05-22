<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transactions extends Model
{
    const UPDATED_AT = null;
    protected $fillable = ['order_id', 'payment_method', 'amount_paid', 'change', 'processed_by'];

    public function order()
    {
        return $this->belongsTo(Orders::class, 'order_id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
