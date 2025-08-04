<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class SpotifyAuthController extends Controller
{
    /**
     * Helper to get the correct Spotify client ID and secret for the current user.
     * Prioritizes user's own credentials, falls back to global .env credentials.
     * @return array{clientId: string, clientSecret: string, redirectUri: string}
     */
    protected static function getClientCredentials(): array
    {
        /** @var User $user */
        $user = Auth::user();

        // Use user's credentials if they have provided them
        if ($user && $user->spotify_app_client_id && $user->spotify_app_client_secret) {
            return [
                'clientId' => $user->spotify_app_client_id,
                'clientSecret' => $user->spotify_app_client_secret,
                'redirectUri' => config('services.spotify.redirect_uri'), // Redirect URI is always global
            ];
        }

        // Fallback to global .env credentials
        return [
            'clientId' => config('services.spotify.client_id'),
            'clientSecret' => config('services.spotify.client_secret'),
            'redirectUri' => config('services.spotify.redirect_uri'),
        ];
    }

    /**
     * Redirect the user to Spotify's authorization page.
     */
    public function redirectToSpotify()
    {
        $credentials = self::getClientCredentials();
        $redirectUri = config('services.spotify.redirect_uri');
        $scopes = 'user-read-private user-read-email playlist-modify-private playlist-modify-public';

        $url = 'https://accounts.spotify.com/authorize?' . http_build_query([
            'client_id' => $credentials['clientId'],
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'scope' => $scopes,
            'show_dialog' => true,
        ]);

        return redirect($url);
    }

    /**
     * Handle Spotify's callback and exchange the authorization code for tokens.
     */
    public function handleSpotifyCallback(Request $request)
    {
        if ($request->has('error')) {
            \Log::error('Spotify authorization error:', ['error' => $request->error, 'reason' => $request->error_description]);

            return redirect()->route('profile.edit')->with('error', 'Spotify authorization failed: ' . ($request->error_description ?? 'Unknown error.'));
        }

        $code = $request->input('code');
        $credentials = self::getClientCredentials();
        $redirectUri = config('services.spotify.redirect_uri');

        try {
            $response = Http::asForm()->post('https://accounts.spotify.com/api/token', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $redirectUri,
                'client_id' => $credentials['clientId'],
                'client_secret' => $credentials['clientSecret'],
            ]);

            $response->throw();

            $data = $response->json();

            $user = Auth::user();
            $user->spotify_access_token = $data['access_token'];
            $user->spotify_refresh_token = $data['refresh_token'] ?? null;
            $user->spotify_token_expires_at = Carbon::now()->addSeconds($data['expires_in']);
            try {
                $spotifyProfileResponse = Http::withToken($data['access_token'])
                    ->get('https://api.spotify.com/v1/me');
                $spotifyProfileResponse->throw();
                $profileData = $spotifyProfileResponse->json();

                if (! empty($profileData['images'][0]['url'])) {
                    $user->spotify_profile_picture_url = $profileData['images'][0]['url'];
                } else {
                    $user->spotify_profile_picture_url = null;
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to fetch Spotify profile picture for user '.$user->id.': '.$e->getMessage());
                $user->spotify_profile_picture_url = null;
            }
            $user->save();

            return redirect()->route('profile.edit')->with('success', 'Successfully connected to Spotify!');
        } catch (\Illuminate\Http\Client\RequestException $e) {
            \Log::error('Spotify Token Exchange Error:', [
                'status' => $e->response->status(),
                'response' => $e->response->body(),
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('profile.edit')->with('error', 'Failed to connect to Spotify. Please try again.');
        } catch (\Exception $e) {
            \Log::error('Spotify Callback Error:', ['error' => $e->getMessage()]);

            return redirect()->route('profile.edit')->with('error', 'An unexpected error occurred during Spotify connection.');
        }
    }

    /**
     * Refresh the Spotify access token using the refresh token.
     */
    public static function refreshSpotifyToken()
    {
        $user = Auth::user();
        $refreshToken = $user->spotify_refresh_token;

        if (! $refreshToken) {
            \Log::warning('Attempted to refresh Spotify token without a refresh token.');

            return false;
        }

        $credentials = self::getClientCredentials();

        try {
            $response = Http::asForm()->post('https://accounts.spotify.com/api/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => $credentials['clientId'],
                'client_secret' => $credentials['clientSecret'],
            ]);

            $response->throw();

            $data = $response->json();

            $user->spotify_access_token = $data['access_token'];
            $user->spotify_token_expires_at = Carbon::now()->addSeconds($data['expires_in']);
            $user->save();

            return true;
        } catch (\Illuminate\Http\Client\RequestException $e) {
            \Log::error('Spotify Token Refresh Error:', [
                'status' => $e->response->status(),
                'response' => $e->response->body(),
                'message' => $e->getMessage(),
            ]);

            $user->spotify_access_token = null;
            $user->spotify_refresh_token = null;
            $user->spotify_token_expires_at = null;
            $user->save();

            return false;
        } catch (\Exception $e) {
            \Log::error('General Spotify Refresh Error:', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Get the authenticated user's Spotify ID.
     */
    public static function getSpotifyUserId()
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user || ! $user->spotify_access_token) {
            return null;
        }

        if (Carbon::now()->greaterThanOrEqualTo($user->spotify_token_expires_at)) {
            $refreshed = self::refreshSpotifyToken();
            if (! $refreshed) {
                \Log::error('Failed to refresh Spotify token for user '.$user->id);

                return null;
            }
            $user->refresh();
        }

        try {
            $response = Http::withToken($user->spotify_access_token)
                ->get('https://api.spotify.com/v1/me');

            $response->throw();

            return $response->json('id');
        } catch (\Illuminate\Http\Client\RequestException $e) {
            \Log::error('Spotify API Error (getSpotifyUserId):', [
                'user_id' => $user->id,
                'status' => $e->response->status(),
                'response' => $e->response->body(),
                'message' => $e->getMessage(),
            ]);

            if ($e->response->status() === 401) {
                $user->spotify_access_token = null;
                $user->spotify_refresh_token = null;
                $user->spotify_token_expires_at = null;
                $user->save();
            }

            return null;
        } catch (\Exception $e) {
            \Log::error('Spotify API Error (getSpotifyUserId - General):', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Search for a track on Spotify.
     *
     * @param  string  $trackName
     * @param  string|null  $artistName
     * @return string|null Spotify Track ID on success, null if not found or error.
     */
    public static function searchSpotifyTrack(string $trackName, ?string $artistName = null)
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user || ! $user->spotify_access_token) {
            return null;
        }

        if (Carbon::now()->greaterThanOrEqualTo($user->spotify_token_expires_at)) {
            $refreshed = self::refreshSpotifyToken();
            if (! $refreshed) {
                return null;
            }
            $user->refresh();
        }

        $query = "track:\"{$trackName}\"";
        if ($artistName) {
            $query .= " artist:\"{$artistName}\"";
        }

        try {
            $response = Http::withToken($user->spotify_access_token)
                ->get('https://api.spotify.com/v1/search', [
                    'q' => $query,
                    'type' => 'track',
                    'limit' => 10,
                ]);

            $response->throw();

            $data = $response->json();

            if (empty($data['tracks']['items'])) {
                return null;
            }

            $foundTracks = collect($data['tracks']['items']);

            $filteredTracks = $foundTracks->filter(function ($item) {
                $name = mb_strtolower($item['name']);

                return ! str_contains($name, 'instrumental') &&
                       ! str_contains($name, 'karaoke') &&
                       ! str_contains($name, '(live)') &&
                       ! str_contains($name, 'acapella');
            });

            if ($filteredTracks->isEmpty()) {
                $filteredTracks = $foundTracks;
            }

            if ($artistName) {
                $exactArtistMatches = $filteredTracks->filter(function ($item) use ($artistName) {
                    foreach ($item['artists'] as $artist) {
                        if (mb_strtolower($artist['name']) === mb_strtolower($artistName)) {
                            return true;
                        }
                    }

                    return false;
                });

                if (! $exactArtistMatches->isEmpty()) {
                    return $exactArtistMatches->first()['id'];
                }
            }

            return $filteredTracks->first()['id'];
        } catch (\Illuminate\Http\Client\RequestException $e) {
            \Log::error('Spotify API Error (searchSpotifyTrack):', [
                'user_id' => $user->id,
                'track' => $trackName,
                'artist' => $artistName,
                'status' => $e->response->status(),
                'response' => $e->response->body(),
                'message' => $e->getMessage(),
            ]);

            if ($e->response->status() === 401) {
                $user->spotify_access_token = null;
                $user->spotify_refresh_token = null;
                $user->spotify_token_expires_at = null;
                $user->save();
            }

            return null;
        } catch (\Exception $e) {
            \Log::error('Spotify API Error (searchSpotifyTrack - General):', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Search for an artist on Spotify and return their image URL.
     *
     * @param  string  $artistName
     * @return string|null Image URL (smallest available) on success, null if not found or error.
     */
    public static function searchSpotifyArtistImage(string $artistName)
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user || ! $user->spotify_access_token) {
            return null;
        }

        if (Carbon::now()->greaterThanOrEqualTo($user->spotify_token_expires_at)) {
            $refreshed = self::refreshSpotifyToken();
            if (! $refreshed) {
                \Log::warning('Failed to refresh Spotify token for image fetch for user '.$user->id);

                return null;
            }
            $user->refresh();
        }

        try {
            $response = Http::withToken($user->spotify_access_token)
                ->get('https://api.spotify.com/v1/search', [
                    'q' => "artist:\"{$artistName}\"",
                    'type' => 'artist',
                    'limit' => 1,
                ]);

            $response->throw();

            $data = $response->json();

            if (! empty($data['artists']['items'])) {
                $artist = $data['artists']['items'][0];
                if (! empty($artist['images'])) {
                    return collect($artist['images'])->sortBy('width')->first()['url'] ?? null;
                }
            }

            return null;
        } catch (\Illuminate\Http\Client\RequestException $e) {
            if ($e->response->status() === 401) {
                \Log::error('Spotify API Error (searchSpotifyArtistImage - 401): Invalid Token for user '.$user->id);
                $user->spotify_access_token = null;
                $user->spotify_refresh_token = null;
                $user->spotify_token_expires_at = null;
                $user->save();
            } else {
                \Log::error('Spotify API Error (searchSpotifyArtistImage):', [
                    'user_id' => $user->id,
                    'artist' => $artistName,
                    'status' => $e->response->status(),
                    'response' => $e->response->body(),
                    'message' => $e->getMessage(),
                ]);
            }

            return null;
        } catch (\Exception $e) {
            \Log::error('Spotify API Error (searchSpotifyArtistImage - General):', ['user_id' => $user->id, 'artist' => $artistName, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Search for a track on Spotify and return its ID and duration in milliseconds.
     * This is a new helper method specifically for obtaining track details.
     *
     * @param  string  $trackName
     * @param  string|null  $artistName
     * @return array|null Format: ['id' => 'spotify_id', 'duration_ms' => 123456] on success, null if not found or error.
     */
    public static function searchSpotifyTrackWithDetails(string $trackName, ?string $artistName = null, ?User $user = null): ?array
    {
        $user = $user ?? Auth::user();
        
        // Use the passed $user instead of Auth::user()
        if (!$user || !$user->spotify_access_token) {
            \Log::warning('No user model or Spotify token provided to search for track details.');
            return null;
        }

        // Check if token needs refreshing for the passed $user
        if (Carbon::now()->greaterThanOrEqualTo($user->spotify_token_expires_at)) {
            $refreshed = static::refreshSpotifyTokenForUser($user); // <--- NEW STATIC METHOD
            if (!$refreshed) {
                \Log::error('Could not refresh Spotify token for user ID: ' . $user->id . ' during track search.');
                return null;
            }
            // User model will be refreshed by refreshSpotifyTokenForUser
        }

        $query = "track:\"{$trackName}\"";
        if ($artistName) {
            $query .= " artist:\"{$artistName}\"";
        }

        try {
            $response = Http::withToken($user->spotify_access_token) // Use $user's token
                ->get('https://api.spotify.com/v1/search', [
                    'q' => $query,
                    'type' => 'track',
                    'limit' => 1,
                ]);

            $response->throw();

            $data = $response->json();

            if (isset($data['tracks']['items'][0])) {
                $track = $data['tracks']['items'][0];
                return [
                    'id' => $track['id'],
                    'duration_ms' => $track['duration_ms'],
                ];
            }
        } catch (RequestException $e) {
            if ($e->response && $e->response->status() === 401) {
                // If token is invalid/expired, log and nullify for the specific user
                Log::error('Spotify 401 Unauthorized during track search for user ' . $user->id . '. Token may be invalid. Nullifying tokens.');
                $user->spotify_access_token = null;
                $user->spotify_refresh_token = null;
                $user->spotify_token_expires_at = null;
                $user->save();
            } else {
                \Log::error("Spotify API error during track search for '{$trackName}' by '{$artistName}' (User: {$user->id}): " . $e->getMessage(), ['status' => $e->response?->status()]);
            }
        } catch (\Exception $e) {
            \Log::error("General error during Spotify track search for '{$trackName}' by '{$artistName}' (User: {$user->id}): " . $e->getMessage());
        }

        return null;
    }
}