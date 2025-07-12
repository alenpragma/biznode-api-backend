<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array $array)
 */
class Investor extends Model
{
    protected $table = 'investors';
    protected $fillable = [
        'user_id',
        'package_name',
        'package_id',
        'return_type',
        'investment',
        'payable_amount',
        'duration',
        'total_receive',
        'total_receive_day',
        'total_due_day',
        'start_date',
        'next_cron',
        'last_cron',
        'status',
    ];
}
