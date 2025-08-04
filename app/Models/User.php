<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'spotify_access_token',
        'spotify_refresh_token',
        'spotify_token_expires_at',
        'spotify_profile_picture_url',
        'spotify_app_client_id',
        'spotify_app_client_secret',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'spotify_refresh_token',
        'spotify_app_client_id',
        'spotify_app_client_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'spotify_token_expires_at' => 'datetime',
        ];
    }

    protected $appends = [
        'spotify_profile_picture_url',
    ];

    public function gigs(): HasMany
    {
        return $this->hasMany(Gig::class);
    }

    /**
     * Remove the setlists relationship (it's no longer a separate table)
     * public function setlists(): HasMany // NEW method
     * {
     *    return $this->hasMany(Setlist::class);
     * }
     */

    /**
     * Accessor to get the Spotify profile picture URL.
     * This will automatically be included in toArray() if added to $appends.
     * It handles the case where the stored URL might be null or empty.
     */
    public function getSpotifyProfilePictureUrlAttribute(): ?string
    {
        return $this->attributes['spotify_profile_picture_url'];
    }

    /**
     * Get the spotify_app_client_id attribute.
     */
    public function getSpotifyAppClientIdAttribute(): ?string
    {
        return $this->attributes['spotify_app_client_id'];
    }

    /**
     * Get the spotify_app_client_secret attribute.
     */
    public function getSpotifyAppClientSecretAttribute(): ?string
    {
        return $this->attributes['spotify_app_client_secret'];
    }
}