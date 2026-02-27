<?php

namespace App\Http\Controllers;

use App\Models\Gig;
use App\Http\Controllers\SpotifyAuthController;
use App\Jobs\GenerateGigSetlist; // Import the new Job
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Carbon\Carbon;

class GigController extends Controller
{
    /**
     * Display a listing of the gigs.
     */
    public function index()
    {
        $gigs = Auth::user()->gigs()
            ->where('gig_date_time', '>=', now())
            ->orderBy('gig_date_time')
            ->get();

        $gigs->each(function ($gig) {
            $gig->support_acts = is_string($gig->support_acts) ? json_decode($gig->support_acts, true) : $gig->support_acts;
            $gig->people_attending = is_string($gig->people_attending) ? json_decode($gig->people_attending, true) : $gig->people_attending;
        });

        return Inertia::render('UpcomingGigs', [
            'gigs' => $gigs,
        ]);
    }

    /**
     * Display a listing of the past gigs.
     */
    public function pastGigs()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user(); // Get the authenticated user here

        $gigs = $user->gigs()
            ->where('gig_date_time', '<', now())
            ->orderByDesc('gig_date_time')
            ->get();

        // Dispatch jobs for past gigs that do not have setlist data
        $gigs->each(function ($gig) use ($user) {
            $gig->support_acts = is_string($gig->support_acts) ? json_decode($gig->support_acts, true) : $gig->support_acts;
            $gig->people_attending = is_string($gig->people_attending) ? json_decode($gig->people_attending, true) : $gig->people_attending;
            if (is_null($gig->sets) || (is_array($gig->sets) && empty($gig->sets))) { // Ensure it's truly null or empty array
                GenerateGigSetlist::dispatch($gig, $user);
            }
        });

        return Inertia::render('PastGigs', [
            'gigs' => $gigs,
        ]);
    }

    /**
     * Store a newly created gig in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'artist_band_name' => ['required', 'string', 'max:255'],
                'venue' => ['required', 'string', 'max:255'],
                'gig_date_time' => ['required', 'date'],
                'support_acts' => ['nullable', 'json'],
                'people_attending' => ['nullable', 'json'],
            ]);

            $artistImageUrl = SpotifyAuthController::searchSpotifyArtistImage($validated['artist_band_name']);
            if ($artistImageUrl) {
                $validated['artist_image_url'] = $artistImageUrl;
            }

            Auth::user()->gigs()->create($validated);

            session()->flash('success', 'Gig added successfully!');

            $gigs = Auth::user()->gigs()
                ->where('gig_date_time', '>=', now())
                ->orderBy('gig_date_time')
                ->get();

            $gigs->each(function ($gig) {
                $gig->support_acts = is_string($gig->support_acts) ? json_decode($gig->support_acts, true) : $gig->support_acts;
                $gig->people_attending = is_string($gig->people_attending) ? json_decode($gig->people_attending, true) : $gig->people_attending;
            });

            return redirect()->route('dashboard');

        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            \Log::error('Error adding gig:', ['error' => $e->getMessage()]);

            return redirect()->back()->withErrors(['message' => 'An unexpected error occurred while adding the gig.']);
        }
    }

    /**
     * Display the specified gig with its setlist details.
     */
    public function show(Gig $gig)
    {
        if ($gig->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // The Gig model already casts 'sets' as an array, so it should be available directly.
        // No need to load 'setlist' relationship anymore.

        // If you need any derived properties like total duration for initial load,
        // you might add an accessor to the Gig model or calculate it here.
        // However, SetlistDetail.vue already calculates it client-side.

        return Inertia::render('SetlistDetail', [
            'gig' => $gig, // Pass the entire gig object
        ]);
    }

    /**
     * Update the specified gig in storage.
     */
    public function update(Request $request, Gig $gig)
    {
        if ($gig->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $validated = $request->validate([
                'artist_band_name' => ['required', 'string', 'max:255'],
                'venue' => ['required', 'string', 'max:255'],
                'gig_date_time' => ['required', 'date'],
                'support_acts' => ['nullable', 'json'],
                'people_attending' => ['nullable', 'json'],
                // Allow setlist fields to be updated if coming from SetlistGeneratorModal's direct save
                'setlist_id_setlistfm' => ['nullable', 'string'],
                'setlist_url' => ['nullable', 'url'],
                'sets' => ['nullable', 'array'],
            ]);

            if ($gig->artist_band_name !== $validated['artist_band_name'] || is_null($gig->artist_image_url)) {
                $artistImageUrl = SpotifyAuthController::searchSpotifyArtistImage($validated['artist_band_name']);
                if ($artistImageUrl) {
                    $validated['artist_image_url'] = $artistImageUrl;
                } else {
                    $validated['artist_image_url'] = null;
                }
            }

            $gigs = Auth::user()->gigs()
                ->where('gig_date_time', '>=', now())
                ->orderBy('gig_date_time')
                ->get();

            $gigs->each(function ($gig) {
                $gig->support_acts = is_string($gig->support_acts) ? json_decode($gig->support_acts, true) : $gig->support_acts;
                $gig->people_attending = is_string($gig->people_attending) ? json_decode($gig->people_attending, true) : $gig->people_attending;
            });

            $gig->update($validated);

            session()->flash('success', 'Gig updated successfully!');

            $updatedGig = $gig->fresh();

            if ($updatedGig->gig_date_time->isFuture()) {
                return redirect()->route('dashboard');
            } else {
                return redirect()->route('past-gigs');
            }

        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            \Log::error('Error updating gig:', ['error' => $e->getMessage(), 'gig_id' => $gig->id]);

            return redirect()->back()->withErrors(['message' => 'An unexpected error occurred while updating the gig.']);
        }
    }

    /**
     * Delete the specified gig from storage.
     */
    public function destroy(Gig $gig)
    {
        if ($gig->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $gig->delete();

            session()->flash('success', 'Gig deleted successfully!');

            return redirect()->route('dashboard');
        } catch (\Exception $e) {
            \Log::error('Error deleting gig:', ['error' => $e->getMessage(), 'gig_id' => $gig->id]);

            return redirect()->back()->withErrors(['message' => 'An unexpected error occurred while deleting the gig.']);
        }
    }

public function searchApi(Request $request)
{
    $query = $request->input('q');
    $user  = Auth::user();

    $upcoming = collect();
    $past     = collect();

    if ($query && strlen($query) > 1) {
        $gigs = $user->gigs()
            ->where(function ($q2) use ($query) {
                $q2->where('artist_band_name', 'like', "%{$query}%")
                   ->orWhere('venue', 'like', "%{$query}%")
                   ->orWhereJsonContains('support_acts', $query)
                   ->orWhereJsonContains('people_attending', $query);
            })
            ->orderBy('gig_date_time')
            ->get();

        // normalize to arrays
        $gigs->each(function ($gig) {
            $gig->support_acts = is_array($gig->support_acts)
                ? $gig->support_acts
                : (empty($gig->support_acts) ? [] : json_decode($gig->support_acts, true));
            $gig->people_attending = is_array($gig->people_attending)
                ? $gig->people_attending
                : (empty($gig->people_attending) ? [] : json_decode($gig->people_attending, true));
        });

        $upcoming = $gigs->filter(fn ($gig) => $gig->gig_date_time->isFuture())->values();
        $past     = $gigs->filter(fn ($gig) => $gig->gig_date_time->isPast())->values();
    }

    return response()->json([
        'upcoming' => $upcoming,
        'past'     => $past,
    ]);
}

public function venuesApi(Request $request)
{
    $venues = Auth::user()->gigs()
        ->select('venue')
        ->distinct()
        ->orderBy('venue')
        ->pluck('venue');

    return response()->json($venues);
}
}
