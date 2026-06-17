<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Server extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'host',
        'port',
        'token',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'port' => 'integer',
    ];

    public function snapshots(): HasMany
    {
        return $this->hasMany(ServerSnapshot::class);
    }

    public function latestSnapshot(): HasOne
    {
        return $this->hasOne(ServerSnapshot::class)->latestOfMany();
    }
}
