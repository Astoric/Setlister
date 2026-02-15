<?php

namespace App\Http\Controllers;

use App\Models\Gig; // Make sure Gig model is imported
// use App\Models\Setlist; // REMOVE THIS LINE: Setlist model is gone
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class StatsController extends Controller
{
    /**
     * Display the user's music statistics.
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Fetch all gigs for the current user
        $allGigs = $user->gigs()->get();

        // Ensure JSON fields are properly decoded into arrays for calculation
        // This is a safety net in case model casting doesn't fully handle all scenarios (e.g., nulls)
        $allGigs->each(function ($gig) {
            $gig->support_acts = is_string($gig->support_acts) ? json_decode($gig->support_acts, true) : ($gig->support_acts ?? []);
            $gig->people_attending = is_string($gig->people_attending) ? json_decode($gig->people_attending, true) : ($gig->people_attending ?? []);
            $gig->sets = is_string($gig->sets) ? json_decode($gig->sets, true) : ($gig->sets ?? []); // Ensure sets is an array
        });

        // 1. Total Gigs
        $totalUpcomingGigs = $allGigs->where('gig_date_time', '>=', now())->count();
        $totalPastGigs = $allGigs->where('gig_date_time', '<', now())->count();
        $totalGigs = $totalUpcomingGigs + $totalPastGigs;

        // 2. Average Rating (Placeholder if no rating column)
        // This will remain 0 unless you add a rating column to your `gigs` table.
        $averageRating = 0;

        // 3. Total Songs and Total Music Time (from saved setlists on gigs)
        $totalSongs = 0;
        $totalDurationMs = 0;
        // Iterate through all gigs, and then through their 'sets' if available
        $allGigs->each(function ($gig) use (&$totalSongs, &$totalDurationMs) {
            if (isset($gig->sets) && is_array($gig->sets)) {
                foreach ($gig->sets as $set) {
                    if (isset($set['songs']) && is_array($set['songs'])) {
                        foreach ($set['songs'] as $song) {
                            $totalSongs += 1;
                            if (isset($song['duration_ms']) && is_numeric($song['duration_ms'])) {
                                $totalDurationMs += $song['duration_ms'];
                            }
                        }
                    }
                }
            }
        });

        // 4. Total Music Time (Calculate from totalDurationMs)
        $totalSeconds = floor($totalDurationMs / 1000);
        $totalMinutes = floor($totalSeconds / 60);
        $totalHours = floor($totalMinutes / 60);
        $remainingMinutes = $totalMinutes % 60;
        $totalDurationDisplay = "{$totalHours}h {$remainingMinutes}m";

        // 5. Most Frequent Venue (normalized)
        $venueGroups = $allGigs->groupBy(fn($gig) => strtolower(trim($gig->venue)));
        $topVenueGroup = $venueGroups->sortByDesc->count()->first();
        $topVenue = $topVenueGroup ? $topVenueGroup->count() : 0;
        $topVenueName = $topVenueGroup ? $topVenueGroup->first()->venue : 'N/A';
        $topVenueDisplay = $topVenueName !== 'N/A' ? "{$topVenueName} ({$topVenue} gigs)" : 'N/A';

        // 6. Most Frequent Band (normalized)
        $bandGroups = $allGigs->groupBy(fn($gig) => strtolower(trim($gig->artist_band_name)));
        $topBandGroup = $bandGroups->sortByDesc->count()->first();
        $topBand = $topBandGroup ? $topBandGroup->count() : 0;
        $topBandName = $topBandGroup ? $topBandGroup->first()->artist_band_name : 'N/A';
        $topBandDisplay = $topBandName !== 'N/A' ? "{$topBandName} ({$topBand} times)" : 'N/A';

        // 7. Most Frequent Attendee (normalized)
        $attendeeData = collect(); // map of normalized name => ['count' => X, 'nice_name' => Y]
        $allGigs->each(function ($gig) use (&$attendeeData) {
            if (is_array($gig->people_attending)) {
                foreach ($gig->people_attending as $person) {
                    $normalized = strtolower(trim($person));
                    $item = $attendeeData->get($normalized, ['count' => 0, 'nice_name' => trim($person)]);
                    $item['count']++;
                    $attendeeData->put($normalized, $item);
                }
            }
        });
        $topAttendeeInfo = $attendeeData->sortByDesc('count')->first();
        $topAttendee = $topAttendeeInfo ? $topAttendeeInfo['count'] : 0;
        $topAttendeeName = $topAttendeeInfo ? $topAttendeeInfo['nice_name'] : 'N/A';
        $topAttendeeDisplay = $topAttendeeName !== 'N/A' ? "{$topAttendeeName} ({$topAttendee} gigs)" : 'N/A';

        // 8. Recent Activity (based on latest gig updated_at or created_at)
        $latestActivity = $allGigs->max('updated_at'); // Gigs are now the source of updates for their own setlists.
        $lastUpdatedDisplay = $latestActivity ? $latestActivity->diffForHumans() : 'N/A';

        // 9. This Year Gigs Attended / Upcoming
        $gigsThisYear = $allGigs->filter(fn($gig) => $gig->gig_date_time->year === Carbon::now()->year);
        $attendedThisYear = $gigsThisYear->where('gig_date_time', '<', now())->count();
        $upcomingThisYear = $gigsThisYear->where('gig_date_time', '>=', now())->count();

        // 10. Total Setlists (count gigs that actually have setlist data)
        $totalSetlists = $allGigs->filter(function ($gig) {
            // A gig has a setlist if its 'sets' array is not empty
            return isset($gig->sets) && is_array($gig->sets) && count($gig->sets) > 0;
        })->count();

        // Achievements (Placeholder for now)
        $achievements = [
            ['label' => 'Music Explorer', 'unlocked' => true],
            ['label' => 'Concert Regular', 'unlocked' => ($totalPastGigs >= 5)],
            ['label' => 'Venue Master', 'unlocked' => ($venueGroups->count() >= 15)],
        ];

        return Inertia::render('Stats', [
            'stats' => [
                'totalPastGigs' => $totalPastGigs,
                'totalUpcomingGigs' => $totalUpcomingGigs,
                'totalGigs' => $totalGigs,
                'totalSetlists' => $totalSetlists, // Updated to count gigs with setlist data
                'averageRating' => number_format($averageRating, 1),
                'totalSongs' => $totalSongs,
                'totalDuration' => $totalDurationDisplay,
                'topVenue' => $topVenueDisplay,
                'topBand' => $topBandDisplay,
                'topAttendee' => $topAttendeeDisplay,
                'lastUpdated' => $lastUpdatedDisplay,
                'gigsAttendedThisYear' => $attendedThisYear,
                'upcomingGigsThisYear' => $upcomingThisYear,
                'achievements' => $achievements,
            ]
        ]);
    }
}