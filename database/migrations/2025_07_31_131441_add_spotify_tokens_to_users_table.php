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
        Schema::table('users', function (Blueprint $table) {
            $table->string('spotify_access_token', 500)->nullable(); // Access token (can be long, use 500 chars)
            $table->string('spotify_refresh_token', 500)->nullable(); // Refresh token (can also be long)
            $table->timestamp('spotify_token_expires_at')->nullable(); // When the access token expires
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'spotify_access_token',
                'spotify_refresh_token',
                'spotify_token_expires_at',
            ]);
        });
    }
};