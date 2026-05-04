<?php

namespace App\Modules\World\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Multiverse extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'slug', 'config'];

    protected $casts = [
        'config' => 'array',
    ];

    public function worlds(): HasMany
    {
        return $this->hasMany(World::class);
    }

    public function universes(): HasMany
    {
        return $this->hasMany(Universe::class);
    }
}
