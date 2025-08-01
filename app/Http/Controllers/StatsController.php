<?php

namespace App\Http\Controllers;

use App\Models\Gig;
use App\Models\Setlist;
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

        // Fetch all gigs and setlists for the current user
        $allGigs = $user->gigs()->get();
        // Eager load 'gig' relationship on setlists to potentially get artist_image_url etc.
        $allSetlists = $user->setlists()->get();


        // 1. Total Gigs
        $totalUpcomingGigs = $allGigs->where('gig_date_time', '>=', now())->count();
        $totalPastGigs = $allGigs->where('gig_date_time', '<', now())->count();
        $totalGigs = $totalUpcomingGigs + $totalPastGigs;

        // 2. Average Rating (Placeholder if no rating column)
        $averageRating = 0; // Placeholder

        // 3. Total Songs (from saved setlists)
        $totalSongs = 0;
        $totalDurationMs = 0; // NEW: Initialize total duration in milliseconds
        $allSetlists->each(function ($setlist) use (&$totalSongs, &$totalDurationMs) { // Pass $totalDurationMs by reference
            foreach ($setlist->sets as $set) {
                if (isset($set['songs']) && is_array($set['songs'])) {
                    foreach ($set['songs'] as $song) { // Iterate through each song to sum duration
                        $totalSongs += 1;
                        if (isset($song['duration_ms']) && is_numeric($song['duration_ms'])) {
                            $totalDurationMs += $song['duration_ms'];
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


        // 5. Most Frequent Venue
        $venueCounts = $allGigs->groupBy('venue')->map->count();
        $topVenue = $venueCounts->isNotEmpty() ? $venueCounts->sortDesc()->first() : 0;
        $topVenueName = $venueCounts->isNotEmpty() ? $venueCounts->sortDesc()->keys()->first() : 'N/A';
        $topVenueDisplay = $topVenueName !== 'N/A' ? "{$topVenueName} ({$topVenue} gigs)" : 'N/A';

        // 6. Most Frequent Attendee
        $attendeeCounts = collect();
        $allGigs->each(function ($gig) use (&$attendeeCounts) {
            if (is_array($gig->people_attending)) {
                foreach ($gig->people_attending as $person) {
                    $attendeeCounts->put($person, ($attendeeCounts->get($person) ?? 0) + 1);
                }
            }
        });
        $topAttendee = $attendeeCounts->isNotEmpty() ? $attendeeCounts->sortDesc()->first() : 0;
        $topAttendeeName = $attendeeCounts->isNotEmpty() ? $attendeeCounts->sortDesc()->keys()->first() : 'N/A';
        $topAttendeeDisplay = $topAttendeeName !== 'N/A' ? "{$topAttendeeName} ({$topAttendee} gigs)" : 'N/A';

        // 7. Recent Activity (based on latest updated_at or created_at)
        $latestActivity = null;
        $latestGigUpdate = $allGigs->max('updated_at');
        $latestSetlistUpdate = $allSetlists->max('updated_at');
        
        if ($latestGigUpdate && $latestSetlistUpdate) {
            $latestActivity = $latestGigUpdate->greaterThan($latestSetlistUpdate) ? $latestGigUpdate : $latestSetlistUpdate;
        } elseif ($latestGigUpdate) {
            $latestActivity = $latestGigUpdate;
        } elseif ($latestSetlistUpdate) {
            $latestActivity = $latestSetlistUpdate;
        }

        $lastUpdatedDisplay = $latestActivity ? $latestActivity->diffForHumans() : 'N/A';


        // 8. This Year Gigs Attended / Upcoming
        $gigsThisYear = $allGigs->filter(fn($gig) => $gig->gig_date_time->year === Carbon::now()->year);
        $attendedThisYear = $gigsThisYear->where('gig_date_time', '<', now())->count();
        $upcomingThisYear = $gigsThisYear->where('gig_date_time', '>=', now())->count();


        // Achievements (Placeholder for now)
        $achievements = [
            ['label' => 'Music Explorer', 'unlocked' => true],
            ['label' => 'Concert Regular', 'unlocked' => ($totalPastGigs >= 5)],
            ['label' => 'Venue Master', 'unlocked' => ($venueCounts->count() >= 15)],
        ];


        return Inertia::render('Stats', [
            'stats' => [
                'totalPastGigs' => $totalPastGigs,
                'totalUpcomingGigs' => $totalUpcomingGigs,
                'totalGigs' => $totalGigs,
                'totalSetlists' => $allSetlists->count(),
                'averageRating' => number_format($averageRating, 1),
                'totalSongs' => $totalSongs,
                'totalDuration' => $totalDurationDisplay, // Use the calculated display string
                'topVenue' => $topVenueDisplay,
                'topAttendee' => $topAttendeeDisplay,
                'lastUpdated' => $lastUpdatedDisplay,
                'gigsAttendedThisYear' => $attendedThisYear,
                'upcomingGigsThisYear' => $upcomingThisYear,
                'achievements' => $achievements,
            ]
        ]);
    }
}