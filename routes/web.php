<?php

namespace App\Http\Controllers;

use App\Http\Controllers\GigController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/**
 * Public Routes (Welcome, Login, Register, Password Reset)
 */
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'formType' => 'welcome',
        'status' => session('status'),
        'errors' => session('errors') ? session('errors')->getBag('default')->toArray() : [],
    ]);
})->name('welcome');


/**
 * Authenticated & Verified Routes
 */
Route::middleware(['auth', 'verified'])->group(function () {
    /**
     * Dashboard & Gig Management
     */
    Route::get('/dashboard', [GigController::class, 'index'])->name('dashboard');
    Route::get('/past-gigs', [GigController::class, 'pastGigs'])->name('past-gigs');
    Route::post('/gigs', [GigController::class, 'store'])->name('gigs.store');
    Route::patch('/gigs/{gig}', [GigController::class, 'update'])->name('gigs.update');
    Route::delete('/gigs/{gig}', [GigController::class, 'destroy'])->name('gigs.destroy');
    Route::get('/gigs/{gig}', [GigController::class, 'show'])->name('gigs.show');
    Route::post('/gigs/{gig}/generate-spotify-playlist', [SetlistController::class, 'generateSpotifyPlaylist'])->name('setlists.generate-spotify-playlist');

    /**
     * Setlist Management (updated to reflect denormalization)
     */
    Route::get('/setlists/search', [SetlistController::class, 'search'])->name('setlists.search');
    Route::post('/setlists/save-to-gig', [SetlistController::class, 'saveSetlistToGig'])->name('setlists.save-to-gig');
    Route::get('/setlists/track-details', [SetlistController::class, 'fetchSpotifyTrackDetails'])->name('setlists.fetch-track-details');
    Route::get('/setlists/{setlistId}/details', [SetlistController::class, 'fetchDetailedSetlist'])->name('setlists.details');

    /**
     * Spotify Authentication
     */
    Route::get('/auth/spotify/redirect', [SpotifyAuthController::class, 'redirectToSpotify'])->name('spotify.redirect');
    Route::get('/auth/spotify/callback', [SpotifyAuthController::class, 'handleSpotifyCallback'])->name('spotify.callback');

    /**
     * User Profile
     */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::patch('/profile/spotify-app-credentials', [ProfileController::class, 'updateSpotifyAppCredentials'])->name('profile.update-spotify-app-credentials');

    /**
     * Statistics Page
     */
    Route::get('/stats', [StatsController::class, 'index'])->name('stats.index');

    /**
     * Search Feature
     */
    Route::get('/api/gigs/search', [GigController::class, 'searchApi'])
    ->name('gigs.search.api');

    Route::get('/api/venues', [GigController::class, 'venuesApi'])
    ->name('venues.api');
});

Route::get('/gigs/search/test', function () {
    return 'Search route works without auth';
});

require __DIR__.'/auth.php';
