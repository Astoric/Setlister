<?php

namespace App\Http\Controllers;

// use App\Models\Gig; // Already imported
use App\Models\Gig; // Ensure Gig model is imported
// use App\Models\Setlist; // REMOVE THIS LINE
use App\Http\Controllers\SpotifyAuthController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use App\Models\User;

class SetlistController extends Controller
{
    // REMOVED: index() method (no more saved setlists page in this form)

    /**
     * Search Setlist.fm for setlists for a given artist and optionally gig date.
     * This method remains largely the same as it's for searching.
     */
    public function search(Request $request)
    {
        $request->validate([
            'artistName' => ['required', 'string', 'max:255'],
            'gigDate' => ['nullable', 'date'],
            'gigId' => ['required', 'exists:gigs,id'],
        ]);

        $artistName = urlencode($request->artistName);
        $rawGigDate = $request->gigDate;

        $setlistFmApiKey = config('services.setlistfm.key');
        if (!$setlistFmApiKey) {
            return response()->json(['error' => 'Setlist.fm API key not configured.'], 500);
        }

        $url = "https://api.setlist.fm/rest/1.0/search/setlists?artistName={$artistName}";

        $isPastGig = false;
        $gigDateObj = null;

        if ($rawGigDate) {
            $gigDateTime = new \DateTime($rawGigDate);
            $gigDateObj = $gigDateTime;
            if ($gigDateTime < new \DateTime()) {
                $isPastGig = true;
                $url .= '&date=' . $gigDateTime->format('d-m-Y');
            }
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $setlistFmApiKey,
                'Accept' => 'application/json',
            ])->get($url);

            $response->throw();

            $data = $response->json();

            $setlists = collect($data['setlist'] ?? [])
                ->map(function ($setlistItem) use ($gigDateObj, $isPastGig) {
                    $setlistDateObj = \DateTime::createFromFormat('d-m-Y', $setlistItem['eventDate']);
                    $isExactDateMatch = $isPastGig && $setlistDateObj && $gigDateObj && $setlistDateObj->format('Y-m-d') === $gigDateObj->format('Y-m-d');

                    return [
                        'setlist_id' => $setlistItem['id'],
                        'event_date' => $setlistItem['eventDate'],
                        'artist_name' => $setlistItem['artist']['name'],
                        'venue_name' => $setlistItem['venue']['name'] ?? 'N/A',
                        'city_name' => $setlistItem['venue']['city']['name'] ?? 'N/A',
                        'url' => $setlistItem['url'],
                        'is_exact_date_match' => $isExactDateMatch,
                    ];
                });

            // Take only 1 for past gigs if exact match, otherwise up to 10
            if ($isPastGig && $setlists->firstWhere('is_exact_date_match')) {
                $setlists = $setlists->filter(fn ($s) => $s['is_exact_date_match'])->take(1);
            } else {
                $setlists = $setlists->take(10);
            }

            return response()->json(['setlists' => $setlists->values()->all()]);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            \Log::error('Setlist.fm API Error:', [
                'status' => $e->response->status(),
                'response' => $e->response->body(),
                'message' => $e->getMessage(),
            ]);
            if ($e->response->status() >= 400 && $e->response->status() < 500) {
                return response()->json(['error' => 'Setlist.fm API error: ' . ($e->response->json()['message'] ?? 'Check your API key or request parameters.')], $e->response->status());
            }

            return response()->json(['error' => 'Could not fetch setlists from Setlist.fm. Please try again later.'], $e->response->status());
        } catch (\Exception $e) {
            \Log::error('Setlist search error:', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'An unexpected error occurred during setlist search.'], 500);
        }
    }


    // NEW METHOD to handle saving setlist data directly to a Gig
    /**
     * Saves a generated setlist directly to the associated Gig record.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws ValidationException
     */
    public function saveSetlistToGig(Request $request)
    {
        $validated = $request->validate([
            'gig_id' => ['required', 'exists:gigs,id'],
            'setlist_id' => ['required', 'string'], // Setlist.fm ID
            'artist_name' => ['required', 'string'],
            'venue_name' => ['required', 'string'],
            'gig_date' => ['required', 'date'], // Event date from Setlist.fm
            'setlist_url' => ['nullable', 'url'],
            'sets' => ['required', 'array'],
            'sets.*.name' => 'nullable|string', // Set name like 'Main Set', 'Encore'
            'sets.*.songs' => 'array',
            'sets.*.songs.*.name' => ['required', 'string'],
            'sets.*.songs.*.spotify_id' => ['nullable', 'string'],
            'sets.*.songs.*.duration_ms' => ['nullable', 'numeric'],
        ]);

        $gig = Gig::findOrFail($validated['gig_id']);

        if ($gig->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // Update the gig directly
        $gig->update([
            'setlist_id_setlistfm' => $validated['setlist_id'],
            'setlist_url' => $validated['setlist_url'],
            'sets' => $validated['sets'],
            // Note: artist_name, venue_name, gig_date are primarily on Gig itself
            // so we don't update them here unless explicitly allowed/needed for consistency
        ]);

        session()->flash('success', 'Setlist saved successfully to gig!');

        // Redirect back to the detail page of the gig, or dashboard/past gigs
        if ($gig->gig_date_time->isFuture()) {
            return Inertia::location(route('dashboard'));
        } else {
            return Inertia::location(route('past-gigs'));
        }
    }

    // REMOVED: show() method (no more individual setlist pages in this form, data is on Gig)

    /**
     * Fetch detailed setlist data from Setlist.fm for a specific setlist ID.
     * This remains for the modal to fetch details before saving.
     */
    public function fetchDetailedSetlist(Request $request, string $setlistId)
    {
        $setlistFmApiKey = config('services.setlistfm.key');

        if (!$setlistFmApiKey) {
            return response()->json(['error' => 'Setlist.fm API key not configured.'], 500);
        }

        $url = "https://api.setlist.fm/rest/1.0/setlist/{$setlistId}";

        try {
            $response = Http::withHeaders([
                'x-api-key' => $setlistFmApiKey,
                'Accept' => 'application/json',
            ])->get($url);

            $response->throw();

            return response()->json($response->json());
        } catch (\Illuminate\Http\Client\RequestException $e) {
            \Log::error('Setlist.fm Detailed API Error:', [
                'status' => $e->response->status(),
                'response' => $e->response->body(),
                'message' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Could not fetch detailed setlist from Setlist.fm. Please try again later.'], $e->response->status());
        } catch (\Exception $e) {
            \Log::error('Detailed setlist fetch error:', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'An unexpected error occurred during detailed setlist fetch.'], 500);
        }
    }

    /**
     * Fetches detailed setlist data and Spotify track details, structuring it for Gig update.
     *
     * @param string $setlistFmId The Setlist.fm setlist ID.
     * @param string $artistName The artist's name.
     * @param string $venueName The venue's name.
     * @param string $eventDate The event date (d-m-Y from Setlist.fm).
     * @param string|null $setlistUrl The URL to Setlist.fm.
     * @param \App\Models\User|null $user The authenticated user model.
     * @return array|null Structured setlist data for the 'sets' JSON column, or null on failure.
     */
    public function prepareSetlistDataForGigUpdate(string $setlistFmId, string $artistName, string $venueName, string $eventDate, ?string $setlistUrl, ?User $user = null): ?array
    {
        try {
            $setlistFmApiKey = config('services.setlistfm.key');
            if (!$setlistFmApiKey) {
                \Log::error('Setlist.fm API key not configured in prepareSetlistDataForGigUpdate.');
                return null;
            }

            $setlistDetailResponse = Http::withHeaders([
                'x-api-key' => $setlistFmApiKey,
                'Accept' => 'application/json',
            ])->get("https://api.setlist.fm/rest/1.0/setlist/{$setlistFmId}");

            $setlistDetailResponse->throw();
            $fullSetlistData = $setlistDetailResponse->json();

            $songDetailPromises = [];
            $allFilteredSongs = [];

            if (isset($fullSetlistData['sets']['set']) && is_array($fullSetlistData['sets']['set'])) {
                foreach ($fullSetlistData['sets']['set'] as $set) {
                    if (isset($set['song']) && is_array($set['song'])) {
                        foreach ($set['song'] as $song) {
                            if (
                                !isset($song['tape']) || !$song['tape'] &&
                                isset($song['name']) && $song['name'] && trim($song['name']) !== '' &&
                                ![
                                    "Intro", "Outro", "Interlude", "Speech",
                                    "Taped", "Snippet", "Acoustic Snippet"
                                ]->some(fn($excluded) => str_contains(strtolower($song['name']), strtolower($excluded)))
                            ) {
                                $allFilteredSongs[] = $song;
                                // Crucially, ensure $user is passed to the static method call here
                                $songDetailPromises[] = SpotifyAuthController::searchSpotifyTrackWithDetails(
                                    $song['name'],
                                    $artistName,
                                    $user // <--- ENSURE $user IS PASSED HERE!
                                );
                            }
                        }
                    }
                }
            }

            $resolvedSongDetails = array_values(array_filter(array_map(function($promise) {
                // Handle promises if they're still promises, otherwise just return the value.
                // This 'wait()' is for GuzzleHttp promises if they're not immediately resolved.
                return $promise instanceof \GuzzleHttp\Promise\PromiseInterface ? $promise->wait() : $promise;
            }, $songDetailPromises)));

            $finalSongs = [];
            foreach ($allFilteredSongs as $index => $song) {
                $details = $resolvedSongDetails[$index] ?? null;
                $finalSongs[] = [
                    'name' => $song['name'],
                    'spotify_id' => $details['id'] ?? null,
                    'duration_ms' => $details['duration_ms'] ?? null,
                ];
            }

            $preparedSets = [];
            if (!empty($finalSongs)) {
                $preparedSets = [
                    ['name' => 'Main Set', 'songs' => $finalSongs]
                ];
            }

            return [
                'setlist_id' => $setlistFmId,
                'setlist_url' => $setlistUrl,
                'sets' => $preparedSets,
            ];

        } catch (\Exception $e) {
            \Log::error('Error preparing setlist data for gig update:', ['error' => $e->getMessage(), 'setlist_id' => $setlistFmId]);
            // Re-throw if you want the job to fail immediately, or return null
            return null;
        }
    }


    /**
     * Generate a Spotify playlist from a setlist stored on a Gig.
     * This method needs to be updated to pull data directly from Gig.
     */
    public function generateSpotifyPlaylist(Gig $gig)
    {
        if ($gig->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->spotify_access_token) {
            return redirect()->back()->with('error', 'Please connect your Spotify account in your profile to generate playlists.');
        }

        if (Carbon::now()->greaterThanOrEqualTo($user->spotify_token_expires_at)) {
            $refreshed = SpotifyAuthController::refreshSpotifyToken();
            if (!$refreshed) {
                return redirect()->back()->with('error', 'Could not refresh Spotify token. Please re-connect Spotify in your profile.');
            }
            $user->refresh();
        }

        $spotifyUserId = SpotifyAuthController::getSpotifyUserId();
        if (!$spotifyUserId) {
            return redirect()->back()->with('error', 'Could not retrieve Spotify user ID. Please re-connect Spotify.');
        }

        $playlistTrackUris = [];

        // Use $gig->sets directly
        if ($gig->sets && is_array($gig->sets)) {
            foreach ($gig->sets as $set) {
                if (isset($set['songs']) && is_array($set['songs'])) {
                    foreach ($set['songs'] as $song) {
                        // If spotify_id is already present, use it directly
                        if (isset($song['spotify_id']) && $song['spotify_id']) {
                            $playlistTrackUris[] = "spotify:track:{$song['spotify_id']}";
                        } else {
                            // Fallback to search if spotify_id is missing (should not happen if `prepareSetlistDataForGigUpdate` worked)
                            $trackId = SpotifyAuthController::searchSpotifyTrack(
                                $song['name'],
                                $gig->artist_band_name // Use gig's artist name
                            );

                            if ($trackId) {
                                $playlistTrackUris[] = "spotify:track:{$trackId}";
                            }
                        }
                    }
                }
            }
        }


        if (empty($playlistTrackUris)) {
            return redirect()->back()->with('error', 'No songs found on Spotify for this setlist to create a playlist.');
        }

        try {
            $playlistName = "{$gig->artist_band_name} Setlist";

            $createPlaylistResponse = Http::withToken($user->spotify_access_token)
                ->post("https://api.spotify.com/v1/users/{$spotifyUserId}/playlists", [
                    'name' => $playlistName,
                    'description' => "Setlist from {$gig->artist_band_name}'s gig at {$gig->venue} on {$gig->gig_date_time->format('M d, Y')}. Generated by Setlister.",
                    'public' => false,
                ]);

            $createPlaylistResponse->throw();
            $playlistId = $createPlaylistResponse->json('id');
            $playlistUrl = $createPlaylistResponse->json('external_urls.spotify');

            if (!$playlistId) {
                return redirect()->back()->with('error', 'Failed to create Spotify playlist.');
            }

            foreach (array_chunk($playlistTrackUris, 100) as $chunk) {
                $addItemsResponse = Http::withToken($user->spotify_access_token)
                    ->post("https://api.spotify.com/v1/playlists/{$playlistId}/tracks", [
                        'uris' => $chunk,
                    ]);
                $addItemsResponse->throw();
            }

            $successMessage = "Spotify playlist created successfully! <a href=\"{$playlistUrl}\" target=\"_blank\" class=\"font-semibold underline\">Open in Spotify</a>";

            return redirect()->back()->with('success', $successMessage);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            \Log::error('Spotify Playlist Creation/Add Items Error:', [
                'user_id' => $user->id,
                'gig_id' => $gig->id,
                'status' => $e->response->status(),
                'response' => $e->response->body(),
                'message' => $e->getMessage(),
            ]);

            if ($e->response->status() === 401) {
                $user->spotify_access_token = null;
                $user->spotify_refresh_token = null;
                $user->spotify_token_expires_at = null;
                $user->save();

                return redirect()->back()->with('error', 'Your Spotify connection expired. Please re-connect Spotify in your profile.');
            }

            return redirect()->back()->with('error', 'An error occurred while creating the Spotify playlist. Please check logs.');
        } catch (\Exception $e) {
            \Log::error('General Spotify Playlist Error:', ['user_id' => $user->id, 'gig_id' => $gig->id, 'error' => $e->getMessage()]);

            return redirect()->back()->with('error', 'An unexpected error occurred during playlist generation.');
        }
    }

    // REMOVED: destroy() method (Setlist model deleted)

    /**
     * Fetches Spotify track details (ID and duration) for a given track name and artist.
     * This method acts as a proxy for the frontend to get track details without direct Spotify API calls.
     */
    public function fetchSpotifyTrackDetails(Request $request)
    {
        $request->validate([
            'trackName' => ['required', 'string', 'max:255'],
            'artistName' => ['nullable', 'string', 'max:255'],
        ]);

        $trackName = $request->input('trackName');
        $artistName = $request->input('artistName');

        $trackDetails = SpotifyAuthController::searchSpotifyTrackWithDetails($trackName, $artistName);

        if (!$trackDetails) {
            \Log::info("Spotify track details not found for '{$trackName}' by '{$artistName}'");
            return response()->json(null, 200);
        }

        return response()->json($trackDetails, 200);
    }

    /**
     * Helper method to calculate total duration of a setlist from the 'sets' JSON.
     * This method remains, but now works on Gig->sets.
     */
    protected function calculateTotalDuration(Gig $gig): string
    {
        $totalDurationMs = 0;
        if (is_array($gig->sets)) { // Use $gig->sets directly
            foreach ($gig->sets as $set) {
                if (isset($set['songs']) && is_array($set['songs'])) {
                    foreach ($set['songs'] as $song) {
                        if (isset($song['duration_ms']) && is_numeric($song['duration_ms'])) {
                            $totalDurationMs += $song['duration_ms'];
                        }
                    }
                }
            }
        }

        $totalSeconds = floor($totalDurationMs / 1000);
        $totalMinutes = floor($totalSeconds / 60);
        $totalHours = floor($totalMinutes / 60);
        $remainingMinutes = $totalMinutes % 60;

        return "{$totalHours}h {$remainingMinutes}m";
    }
}