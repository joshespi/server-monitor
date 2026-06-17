<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerSnapshot extends Model
{
    use HasFactory;
    protected $fillable = [
        'server_id',
        'online',
        'cpu_percent',
        'memory_percent',
        'memory_used_mb',
        'memory_total_mb',
        'disks',
        'load_avg',
        'uptime_seconds',
        'containers',
    ];

    protected $casts = [
        'online' => 'boolean',
        'load_avg' => 'array',
        'containers' => 'array',
        'cpu_percent' => 'float',
        'memory_percent' => 'float',
        'memory_used_mb' => 'float',
        'memory_total_mb' => 'float',
        'disks' => 'array',
        'uptime_seconds' => 'integer',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
