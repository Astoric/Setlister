<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user()->fresh();

        $userArray = array_merge($user->toArray(), [
            'spotify_profile_picture_url' => $user->spotify_profile_picture_url,
        ]);
        
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'auth' => [
                'user' => $request->user()->fresh()->toArray(),
            ],
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ],
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Update the user's Spotify App credentials.
     */
    public function updateSpotifyAppCredentials(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        try {
            $validated = $request->validate([
                'spotify_app_client_id' => ['nullable', 'string', 'max:255'],
                'spotify_app_client_secret' => ['nullable', 'string', 'max:255'],
            ]);

            $user->fill($validated);

            if ($user->isDirty('spotify_app_client_id') || $user->isDirty('spotify_app_client_secret')) {
                $user->spotify_access_token = null;
                $user->spotify_refresh_token = null;
                $user->spotify_token_expires_at = null;
                $user->save();
                
                session()->flash('success', 'Spotify App credentials updated! Please re-connect your Spotify account below.');
            } else {
                $user->save();
                session()->flash('success', 'Spotify App credentials saved.');
            }

            return Redirect::route('profile.edit');
        } catch (ValidationException $e) {
            return Redirect::back()
                           ->withErrors($e->errors(), 'updateSpotifyAppCredentials')
                           ->withInput();
        } catch (\Exception $e) {
            \Log::error('Error updating Spotify App credentials for user ' . $user->id . ': ' . $e->getMessage());
            return Redirect::back()->withErrors(['message' => 'An unexpected error occurred while saving Spotify App credentials.'], 'updateSpotifyAppCredentials');
        }
    }
}
