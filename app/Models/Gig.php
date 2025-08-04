<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// Remove HasOne if it's there
// use Illuminate\Database\Eloquent\Relations\HasOne; // Remove this line

class Gig extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'artist_band_name',
        'venue',
        'gig_date_time',
        'support_acts',
        'people_attending',
        'user_id',
        'artist_image_url',
        // Add new fillable attributes
        'setlist_id_setlistfm',
        'setlist_url',
        'sets',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'gig_date_time' => 'datetime',
        'support_acts' => 'array',
        'people_attending' => 'array',
        // Cast 'sets' to array
        'sets' => 'array',
    ];

    /**
     * Get the user that owns the gig.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Remove the setlist relationship as it's denormalized now.
     * public function setlist(): HasOne { return $this->hasOne(Setlist::class); }
     */
}