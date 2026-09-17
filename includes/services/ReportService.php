<?php
/**
 * Utsavam ReportService
 * Multi-tenant analytical reporting and statistics.
 */

require_once __DIR__ . '/../storage/DataStoreFactory.php';
require_once __DIR__ . '/../helpers.php';

class ReportService {
    /**
     * Admin Overview Report
     */
    public static function getAdminReport(?string $selectedClientId = null, ?string $fromDate = null, ?string $toDate = null): array {
        $store = getDataStore();
        $clients = $store->getClients();
        $events = $store->getEvents($selectedClientId);
        $bookings = $store->getBookings($selectedClientId);

        // Date filtering for bookings
        if ($fromDate || $toDate) {
            $bookings = array_filter($bookings, function($b) use ($fromDate, $toDate) {
                $date = $b['booking_date'] ?? '';
                if ($fromDate && $date < $fromDate) return false;
                if ($toDate && $date > $toDate) return false;
                return true;
            });
            $bookings = array_values($bookings);
        }

        $totalClients = count($clients);
        $activeClients = count(array_filter($clients, fn($c) => ($c['status'] ?? '') === 'active'));

        $totalEvents = count($events);
        $activeEvents = count(array_filter($events, fn($e) => ($e['status'] ?? '') === 'published'));

        $totalBookings = count($bookings);
        $confirmed = 0;
        $checkedIn = 0;
        $pending = 0;
        $cancelled = 0;
        $totalPasses = 0;

        foreach ($bookings as $b) {
            $status = strtolower($b['status'] ?? '');
            $passes = (int)($b['pass_count'] ?? 1);
            $totalPasses += $passes;

            if ($status === 'confirmed') $confirmed++;
            elseif ($status === 'checked_in') $checkedIn++;
            elseif ($status === 'pending') $pending++;
            elseif ($status === 'cancelled' || $status === 'rejected') $cancelled++;
        }

        // Client-wise stats
        $clientStats = [];
        foreach ($clients as $c) {
            $cid = $c['id'];
            $cEvents = array_filter($events, fn($e) => ($e['client_id'] ?? '') === $cid);
            $cBookings = array_filter($bookings, fn($b) => ($b['client_id'] ?? '') === $cid);
            $cCheckedIn = array_filter($cBookings, fn($b) => strtolower($b['status'] ?? '') === 'checked_in');

            $clientStats[] = [
                'client_id' => $cid,
                'name' => $c['name'],
                'company' => $c['company_name'],
                'code' => $c['code'],
                'events_count' => count($cEvents),
                'bookings_count' => count($cBookings),
                'checkins_count' => count($cCheckedIn)
            ];
        }

        // Event-wise stats
        $eventStats = [];
        foreach ($events as $e) {
            $eid = $e['id'];
            $eBookings = array_filter($bookings, fn($b) => ($b['event_id'] ?? '') === $eid);
            $eConfirmed = 0;
            $ePending = 0;
            $eCancelled = 0;
            $eCheckedIn = 0;

            foreach ($eBookings as $b) {
                $st = strtolower($b['status'] ?? '');
                if ($st === 'confirmed') $eConfirmed++;
                elseif ($st === 'checked_in') $eCheckedIn++;
                elseif ($st === 'pending') $ePending++;
                elseif ($st === 'cancelled') $eCancelled++;
            }

            $eventStats[] = [
                'event_id' => $eid,
                'name' => $e['name'],
                'client_id' => $e['client_id'],
                'total_bookings' => count($eBookings),
                'confirmed' => $eConfirmed,
                'checked_in' => $eCheckedIn,
                'pending' => $ePending,
                'cancelled' => $eCancelled,
                'max_capacity' => $e['max_capacity'],
                'available_seats' => $e['available_seats'],
                'remaining_capacity' => max(0, $e['available_seats'])
            ];
        }

        return [
            'total_clients' => $totalClients,
            'active_clients' => $activeClients,
            'total_events' => $totalEvents,
            'active_events' => $activeEvents,
            'total_bookings' => $totalBookings,
            'total_passes' => $totalPasses,
            'confirmed' => $confirmed,
            'checked_in' => $checkedIn,
            'pending' => $pending,
            'cancelled' => $cancelled,
            'client_stats' => $clientStats,
            'event_stats' => $eventStats
        ];
    }

    /**
     * Client-Specific Report (Tenant Isolated)
     */
    public static function getClientReport(string $clientId, ?string $eventId = null, ?string $fromDate = null, ?string $toDate = null): array {
        $store = getDataStore();
        $events = $store->getEvents($clientId);
        $bookings = $store->getBookings($clientId, $eventId);

        // Date filtering
        if ($fromDate || $toDate) {
            $bookings = array_filter($bookings, function($b) use ($fromDate, $toDate) {
                $date = $b['booking_date'] ?? '';
                if ($fromDate && $date < $fromDate) return false;
                if ($toDate && $date > $toDate) return false;
                return true;
            });
            $bookings = array_values($bookings);
        }

        $totalBookings = count($bookings);
        $confirmed = 0;
        $checkedIn = 0;
        $pending = 0;
        $cancelled = 0;
        $totalPasses = 0;

        // Daily trend map (last 14 days or filtered range)
        $dailyTrend = [];
        foreach ($bookings as $b) {
            $st = strtolower($b['status'] ?? '');
            $passes = (int)($b['pass_count'] ?? 1);
            $totalPasses += $passes;

            if ($st === 'confirmed') $confirmed++;
            elseif ($st === 'checked_in') $checkedIn++;
            elseif ($st === 'pending') $pending++;
            elseif ($st === 'cancelled' || $st === 'rejected') $cancelled++;

            $day = $b['booking_date'] ?? substr($b['created_at'] ?? '', 0, 10);
            if (!empty($day)) {
                $dailyTrend[$day] = ($dailyTrend[$day] ?? 0) + 1;
            }
        }
        ksort($dailyTrend);

        // Calculate overall capacity & checkin progress
        $totalCapacity = 0;
        $availableSeats = 0;
        foreach ($events as $ev) {
            if ($eventId === null || $ev['id'] === $eventId) {
                $totalCapacity += (int)($ev['max_capacity'] ?? 0);
                $availableSeats += (int)($ev['available_seats'] ?? 0);
            }
        }

        $checkInPercentage = ($confirmed + $checkedIn > 0) ? round(($checkedIn / ($confirmed + $checkedIn)) * 100, 1) : 0;

        return [
            'total_events' => count($events),
            'total_bookings' => $totalBookings,
            'total_passes' => $totalPasses,
            'confirmed' => $confirmed,
            'checked_in' => $checkedIn,
            'pending' => $pending,
            'cancelled' => $cancelled,
            'total_capacity' => $totalCapacity,
            'available_seats' => $availableSeats,
            'remaining_capacity' => $availableSeats,
            'checkin_progress_pct' => $checkInPercentage,
            'daily_trend' => $dailyTrend
        ];
    }
}
