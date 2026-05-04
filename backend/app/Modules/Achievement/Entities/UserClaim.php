<?php

namespace App\Modules\Achievement\Entities;

use Illuminate\Database\Eloquent\Relations\Pivot;

class UserClaim extends Pivot
{
    protected $table = 'user_claims';

    protected $casts = [
        'claimed_at' => 'datetime',
    ];

    public $timestamps = true;
}
