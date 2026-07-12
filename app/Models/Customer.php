<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'customer_code',
        'customer_name',
        'province',
        'city',
        'district',
        'sub_district',
        'address',
        'type_of_business',
        'market',
        'customer_type',
        'is_active',
    ];
}
