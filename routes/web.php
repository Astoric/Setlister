<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
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

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

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

    /**
     * Setlist Management
     */
    Route::get('/saved-setlists', [SetlistController::class, 'index'])->name('saved-setlists');
    Route::get('/setlists/search', [SetlistController::class, 'search'])->name('setlists.search');
    Route::post('/setlists', [SetlistController::class, 'store'])->name('setlists.store');
    Route::get('/setlists/{setlistId}/details', [SetlistController::class, 'fetchDetailedSetlist'])->name('setlists.details');
    Route::get('/setlists/{setlist}', [SetlistController::class, 'show'])->name('setlists.show');
    Route::post('/setlists/{setlist}/generate-spotify-playlist', [SetlistController::class, 'generateSpotifyPlaylist'])->name('setlists.generate-spotify-playlist');

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
});

/**
 * Logout Route
 */
Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->name('logout');