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
        Schema::create('setlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Link to the user who owns it
            $table->foreignId('gig_id')->constrained()->onDelete('cascade'); // Link to the specific gig
            $table->string('setlist_id')->nullable(); // Setlist.fm's internal ID for reference
            $table->string('artist_name'); // Denormalized for easier display/search
            $table->string('venue_name'); // Denormalized
            $table->date('gig_date'); // Denormalized date part for easier querying

            $table->json('sets'); // Store the sets data (songs, encores) as JSON
            $table->string('setlist_url')->nullable(); // Link back to Setlist.fm

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('setlists');
    }
};