<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User; // Add this so it knows what a User is!

class Order extends Model
{
    protected $guarded = []; 

    // Connects an order to the specific items inside it
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Connects an order to the customer who placed it
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}