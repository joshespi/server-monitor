<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->boolean('online');
            $table->float('cpu_percent')->nullable();
            $table->float('memory_percent')->nullable();
            $table->float('memory_used_mb')->nullable();
            $table->float('memory_total_mb')->nullable();
            $table->json('disks')->nullable();
            $table->json('load_avg')->nullable();
            $table->unsignedBigInteger('uptime_seconds')->nullable();
            $table->json('containers')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_snapshots');
    }
};
