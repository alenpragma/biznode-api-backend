<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $table = 'package';
    protected $fillable = ['name', 'min_amount', 'max_amount','interest_rate','duration','return_type','active'];
}
