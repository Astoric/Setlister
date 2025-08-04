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
        // Add new columns to 'gigs' table
        Schema::table('gigs', function (Blueprint $table) {
            $table->string('setlist_id_setlistfm')->nullable()->after('artist_image_url'); // Setlist.fm's internal ID
            $table->string('setlist_url')->nullable()->after('setlist_id_setlistfm'); // Link back to Setlist.fm
            $table->json('sets')->nullable()->after('setlist_url'); // Store the sets data as JSON
        });

        \DB::table('setlists')->get()->each(function ($setlist) {
            \DB::table('gigs')->where('id', $setlist->gig_id)->update([
                'setlist_id_setlistfm' => $setlist->setlist_id,
                'setlist_url' => $setlist->setlist_url,
                'sets' => $setlist->sets,
            ]);
        });

        // Drop the 'setlists' table
        Schema::dropIfExists('setlists');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-create the 'setlists' table (for rollback)
        Schema::create('setlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('gig_id')->constrained()->onDelete('cascade');
            $table->string('setlist_id')->nullable();
            $table->string('artist_name');
            $table->string('venue_name');
            $table->date('gig_date');
            $table->json('sets');
            $table->string('setlist_url')->nullable();
            $table->timestamps();
        });

        // Drop columns from 'gigs' table
        Schema::table('gigs', function (Blueprint $table) {
            $table->dropColumn(['setlist_id_setlistfm', 'setlist_url', 'sets']);
        });
    }
};