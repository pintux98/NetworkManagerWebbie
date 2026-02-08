<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('server_cache', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('monitored_servers')->onDelete('cascade');
            $table->json('cached_data'); // Store server ping data as JSON
            $table->timestamp('last_updated')->useCurrent();
            $table->timestamps();
            
            $table->unique('server_id'); // One cache entry per server
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_cache');
    }
};
