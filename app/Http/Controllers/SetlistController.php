<?php

namespace App\Http\Controllers;

use App\Http\Controllers\SpotifyAuthController;
use App\Models\Gig;
use App\Models\Setlist;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class SetlistController extends Controller
{
    /**
     * Display a listing of all saved setlists.
     */
    public function index()
    {
        $setlists = Auth::user()->setlists()
            ->orderByDesc('gig_date')
            ->get();

        return Inertia::render('SavedSetlists', [
            'setlists' => $setlists,
        ]);
    }

    /**
     * Search Setlist.fm for setlists for a given artist and optionally gig date.
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

    /**
     * Store a newly created setlist in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'gig_id' => ['required', 'exists:gigs,id'],
            'setlist_id' => ['required', 'string'],
            'artist_name' => ['required', 'string'],
            'venue_name' => ['required', 'string'],
            'gig_date' => ['required', 'date'],
            'setlist_url' => ['nullable', 'url'],
            'sets' => ['required', 'array'],
            'sets.*.name' => ['required', 'string'],
        ]);

        $gig = Gig::findOrFail($request->gig_id);

        if ($gig->setlist()->exists()) {
            return redirect()->back()->withErrors(['setlist' => 'A setlist already exists for this gig.']);
        }

        Auth::user()->setlists()->create([
            'gig_id' => $gig->id,
            'setlist_id' => $request->setlist_id,
            'artist_name' => $request->artist_name,
            'venue_name' => $request->venue_name,
            'gig_date' => $request->gig_date,
            'setlist_url' => $request->setlist_url,
            'sets' => $request->sets,
        ]);

        session()->flash('success', 'Setlist saved successfully!');

        return Inertia::location(route('saved-setlists'));
    }

    /**
     * Display the specified setlist.
     */
    public function show(Setlist $setlist)
    {
        if ($setlist->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        return Inertia::render('SetlistDetail', [
            'setlist' => $setlist,
        ]);
    }

    /**
     * Fetch detailed setlist data from Setlist.fm for a specific setlist ID.
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
     * Generate a Spotify playlist from a saved setlist.
     */
    public function generateSpotifyPlaylist(Setlist $setlist)
    {
        if ($setlist->user_id !== Auth::id()) {
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

        foreach ($setlist->sets as $set) {
            if (isset($set['songs']) && is_array($set['songs'])) {
                foreach ($set['songs'] as $song) {
                    $trackId = SpotifyAuthController::searchSpotifyTrack(
                        $song['name'],
                        $setlist->artist_name
                    );

                    if ($trackId) {
                        $playlistTrackUris[] = "spotify:track:{$trackId}";
                    }
                }
            }
        }

        if (empty($playlistTrackUris)) {
            return redirect()->back()->with('error', 'No songs found on Spotify for this setlist to create a playlist.');
        }

        try {
            $playlistName = "{$setlist->artist_name} Setlist";

            $createPlaylistResponse = Http::withToken($user->spotify_access_token)
                ->post("https://api.spotify.com/v1/users/{$spotifyUserId}/playlists", [
                    'name' => $playlistName,
                    'description' => "Setlist from {$setlist->artist_name}'s gig at {$setlist->venue_name} on {$setlist->gig_date->format('M d, Y')}. Generated by Setlister.",
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
                'setlist_id' => $setlist->id,
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
            \Log::error('General Spotify Playlist Error:', ['user_id' => $user->id, 'setlist_id' => $setlist->id, 'error' => $e->getMessage()]);

            return redirect()->back()->with('error', 'An unexpected error occurred during playlist generation.');
        }
    }

    /**
     * Delete the specified setlist from storage.
     */
    public function destroy(Setlist $setlist)
    {
        if ($setlist->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $setlist->delete();

            session()->flash('success', 'Setlist deleted successfully!');

            return redirect()->route('saved-setlists');
        } catch (\Exception $e) {
            \Log::error('Error deleting setlist:', ['error' => $e->getMessage(), 'setlist_id' => $setlist->id]);

            return redirect()->back()->withErrors(['message' => 'An unexpected error occurred while deleting the setlist.']);
        }
    }
}