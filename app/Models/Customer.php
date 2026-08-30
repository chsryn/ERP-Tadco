<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_code',
        'customer_name',
        'province',
        'city',
        'district',
        'sub_district',
        'address',
        'is_active',
    ];
}
