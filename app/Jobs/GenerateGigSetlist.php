<?php

namespace App\Jobs;

use App\Models\Gig;
use App\Http\Controllers\SetlistController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter; // Keep this facade
use Carbon\Carbon;
use App\Models\User;

class GenerateGigSetlist implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $gig;
    protected $user;

    public $tries = 3;

    public function __construct(Gig $gig, User $user)
    {
        $this->gig = $gig->withoutRelations();
        $this->user = $user->withoutRelations();
    }

    public function handle()
    {
        $rateLimitKey = 'spotify_api_auto_generation';
        $maxAttempts = 50;
        $decaySeconds = 60;

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            $availableIn = RateLimiter::availableIn($rateLimitKey);
            Log::warning("Spotify API rate limit hit during auto-generation for Gig ID: {$this->gig->id}. Releasing job for retry in {$availableIn} seconds.");
            $this->release($availableIn + 5);
            return;
        }
        RateLimiter::hit($rateLimitKey, $decaySeconds);

        $gig = Gig::find($this->gig->id);

        if (!$gig) {
            Log::warning("GenerateGigSetlist job: Gig ID {$this->gig->id} not found. Skipping.");
            return;
        }

        // IMPORTANT: Ensure $gig->sets is properly an array and not just null
        // The GigController ensures this for the response, but here we work directly from DB.
        // Even if it's stored as null, $gig->sets will return null, so check if it's effectively empty.
        if (!empty($gig->sets)) { // Checks if it's null, or an empty array, or an array with content
             Log::info("Gig ID {$this->gig->id} already has a setlist. Skipping auto-generation.");
             return;
        }


        try {
            $setlistController = new SetlistController(); // Instantiate the controller

            $artistName = $gig->artist_band_name; // Use raw name for logging, urlencode for URL
            $venueName = $gig->venue;
            $gigDateTime = $gig->gig_date_time; // Carbon instance

            // --- CRITICAL DEBUGGING LOGGING ---
            Log::info("Processing Gig ID: {$gig->id} - Artist: {$artistName}, Venue: {$venueName}, Date: {$gigDateTime->toDateString()}");

            // Format for Setlist.fm API: dd-mm-YYYY
            $gigDateFormatted = $gigDateTime->format('d-m-Y');
            $artistNameEncoded = urlencode($artistName);

            $url = "https://api.setlist.fm/rest/1.0/search/setlists?artistName={$artistNameEncoded}&date={$gigDateFormatted}";

            Log::debug("Setlist.fm Search URL: {$url}");
            Log::debug("Setlist.fm Search Artist Name (original): {$artistName}");
            Log::debug("Setlist.fm Search Gig Date (formatted): {$gigDateFormatted}");

            $setlistFmApiKey = config('services.setlistfm.key');
            if (!$setlistFmApiKey) {
                Log::warning('Setlist.fm API key not configured for auto-generation job. Gig ID: ' . $gig->id);
                return;
            }

            $response = Http::withHeaders([
                'x-api-key' => $setlistFmApiKey,
                'Accept' => 'application/json',
            ])->get($url);

            if ($response->status() === 404) {
                Log::info("Setlist.fm returned 404 (Not Found) for Gig ID {$gig->id}. Marking as 'No Setlist Found'.");
                $gig->update([
                    'sets' => [['name' => 'Main Set', 'songs' => [['name' => 'No Setlist Found', 'spotify_id' => null, 'duration_ms' => null]]]],
                    'setlist_id_setlistfm' => 'NOT_FOUND', // A distinct ID to signify "not found"
                    'setlist_url' => null, // Clear URL
                ]);
                return; // Job successfully completed by marking it as not found
            }

            $response->throw();

            $data = $response->json();
            $foundSetlists = collect($data['setlist'] ?? []);

            Log::debug("Setlist.fm Raw Response for Gig ID {$gig->id}: " . json_encode($data));
            Log::debug("Found Setlists count for Gig ID {$gig->id}: " . $foundSetlists->count());


            // CRITICAL CHECK: What exactly is 'eventDate' in the response?
            // And how is it compared?
            $exactMatchSetlist = $foundSetlists->first(function ($item) use ($gigDateFormatted, $gigDateTime) {
                // Ensure Setlist.fm's eventDate is also parsed correctly for comparison
                $setlistEventDateString = $item['eventDate']; // This is dd-mm-YYYY from Setlist.fm
                $setlistDateObj = \DateTime::createFromFormat('d-m-Y', $setlistEventDateString);

                Log::debug("   Comparing Setlist.fm EventDate '{$setlistEventDateString}' to Gig Date '{$gigDateFormatted}' (parsed to Carbon: {$gigDateTime->toDateString()})");

                // Compare based on Carbon date objects for robust comparison
                return $setlistDateObj && Carbon::instance($setlistDateObj)->toDateString() === $gigDateTime->toDateString();
            });

            if ($exactMatchSetlist) {
                Log::info("Exact match found for Gig ID {$gig->id}. Setlist ID: {$exactMatchSetlist['id']}");

                $preparedSetlistData = $setlistController->prepareSetlistDataForGigUpdate(
                    $exactMatchSetlist['id'],
                    $exactMatchSetlist['artist']['name'],
                    $exactMatchSetlist['venue']['name'] ?? 'N/A',
                    $exactMatchSetlist['eventDate'],
                    $exactMatchSetlist['url'],
                    $this->user
                );

                if ($preparedSetlistData) {
                    $gig->update([
                        'setlist_id_setlistfm' => $preparedSetlistData['setlist_id'],
                        'setlist_url' => $preparedSetlistData['setlist_url'],
                        'sets' => $preparedSetlistData['sets'],
                    ]);
                    Log::info("Successfully auto-generated setlist for gig: {$gig->id} - {$gig->artist_band_name}");
                } else {
                    Log::warning("Could not prepare setlist data from Spotify for auto-generation for gig: {$gig->id}. Likely Spotify API issue or no tracks found.");
                }
            } else {
                Log::info("No exact match setlist found for past gig in job: {$gig->id} - Artist: {$artistName} on {$gigDateFormatted}. Response had " . $foundSetlists->count() . " setlists.");
                // Log the IDs of found setlists if any, to see what was returned
                if ($foundSetlists->count() > 0) {
                     Log::debug("Setlist.fm found these non-exact matches for Gig ID {$gig->id}: " . $foundSetlists->pluck('id', 'eventDate')->toJson());
                }
            }
        } catch (\Illuminate\Http\Client\RequestException $e) {
            if ($e->response && $e->response->status() === 429) {
                 Log::warning("Setlist.fm API rate limit hit for gig: {$gig->id}. Releasing job for retry. Message: " . $e->getMessage());
                 $this->release(60);
            } else {
                Log::error('Setlist.fm API Error during auto-generation job (non-429):', [
                    'gig_id' => $gig->id,
                    'status' => $e->response->status(),
                    'response' => $e->response->body(),
                    'message' => $e->getMessage(),
                ]);
                $this->fail($e);
            }
        } catch (\Exception $e) {
            Log::error('Error during auto-setlist generation job for gig:', [
                'gig_id' => $gig->id,
                'error' => $e->getMessage(),
            ]);
            $this->fail($e);
        }
    }

    public function backoff(): array
    {
        return [1, 5, 10];
    }
}