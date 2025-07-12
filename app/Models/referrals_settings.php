<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class referrals_settings extends Model
{
    protected $table = 'referrals_settings';
    protected $fillable = [
      'invest_level_1',
        'roi_level_1',
        'roi_level_2',
        'roi_level_3',
    ];

}
