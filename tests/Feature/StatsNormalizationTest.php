<?php

namespace Tests\Feature;

use App\Models\Gig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class StatsNormalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_stats_page_normalizes_venue_names(): void
    {
        $user = User::factory()->create();

        // Add gigs with slightly different venue names
        Gig::factory()->create([
            'user_id' => $user->id,
            'venue' => 'The Forum',
            'gig_date_time' => now()->subDays(1),
        ]);
        Gig::factory()->create([
            'user_id' => $user->id,
            'venue' => 'the forum',
            'gig_date_time' => now()->subDays(2),
        ]);
        Gig::factory()->create([
            'user_id' => $user->id,
            'venue' => ' The Forum ',
            'gig_date_time' => now()->subDays(3),
        ]);

        $response = $this->actingAs($user)->get('/stats');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('stats.topVenue', 'The Forum (3 gigs)')
        );
    }

    public function test_stats_page_normalizes_band_names(): void
    {
        $user = User::factory()->create();

        // Add gigs with slightly different band names
        Gig::factory()->create([
            'user_id' => $user->id,
            'artist_band_name' => 'Radiohead',
            'gig_date_time' => now()->subDays(1),
        ]);
        Gig::factory()->create([
            'user_id' => $user->id,
            'artist_band_name' => 'radiohead',
            'gig_date_time' => now()->subDays(2),
        ]);

        $response = $this->actingAs($user)->get('/stats');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('stats.topBand', 'Radiohead (2 times)')
        );
    }

    public function test_stats_page_normalizes_attendee_names(): void
    {
        $user = User::factory()->create();

        // Add gigs with slightly different attendee names
        Gig::factory()->create([
            'user_id' => $user->id,
            'people_attending' => json_encode(['Alice', 'Bob']),
            'gig_date_time' => now()->subDays(1),
        ]);
        Gig::factory()->create([
            'user_id' => $user->id,
            'people_attending' => json_encode(['alice', 'Charlie']),
            'gig_date_time' => now()->subDays(2),
        ]);

        $response = $this->actingAs($user)->get('/stats');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('stats.topAttendee', 'Alice (2 gigs)')
        );
    }
}
