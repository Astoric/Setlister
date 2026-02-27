<?php

namespace Tests\Feature;

use App\Models\Gig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenuesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_fetch_unique_venues(): void
    {
        $user = User::factory()->create();

        Gig::factory()->create([
            'user_id' => $user->id,
            'venue' => 'Barrowlands',
        ]);

        Gig::factory()->create([
            'user_id' => $user->id,
            'venue' => 'Barrowlands', // Duplicate
        ]);

        Gig::factory()->create([
            'user_id' => $user->id,
            'venue' => 'OVO Hydro',
        ]);

        $response = $this->actingAs($user)->getJson(route('venues.api'));

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJson(['Barrowlands', 'OVO Hydro']);
    }

    public function test_unauthenticated_user_cannot_fetch_venues(): void
    {
        $response = $this->getJson(route('venues.api'));

        $response->assertStatus(401);
    }
}
