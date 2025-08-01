<?php

namespace App\Http\Controllers;

use App\Models\Gig;
use App\Http\Controllers\SpotifyAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

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
        $gigs = Auth::user()->gigs()
            ->where('gig_date_time', '<', now())
            ->orderByDesc('gig_date_time')
            ->get();

        $gigs->each(function ($gig) {
            $gig->support_acts = is_string($gig->support_acts) ? json_decode($gig->support_acts, true) : $gig->support_acts;
            $gig->people_attending = is_string($gig->people_attending) ? json_decode($gig->people_attending, true) : $gig->people_attending;
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
            ]);

            if ($gig->artist_band_name !== $validated['artist_band_name'] || is_null($gig->artist_image_url)) {
                $artistImageUrl = SpotifyAuthController::searchSpotifyArtistImage($validated['artist_band_name']);
                if ($artistImageUrl) {
                    $validated['artist_image_url'] = $artistImageUrl;
                } else {
                    $validated['artist_image_url'] = null;
                }
            }

            $gig->update($validated);

            session()->flash('success', 'Gig updated successfully!');

            $gigs = Auth::user()->gigs()
                ->where('gig_date_time', '>=', now())
                ->orderBy('gig_date_time')
                ->get();

            $gigs->each(function ($gig) {
                $gig->support_acts = is_string($gig->support_acts) ? json_decode($gig->support_acts, true) : $gig->support_acts;
                $gig->people_attending = is_string($gig->people_attending) ? json_decode($gig->people_attending, true) : $gig->people_attending;
            });

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
}