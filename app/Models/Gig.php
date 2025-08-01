<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
    ];

    /**
     * Get the user that owns the gig.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    /**
     * Get the setlist associated with the gig.
     */
    public function setlist(): HasOne
    {
        return $this->hasOne(Setlist::class);
    }
}