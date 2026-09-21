<?php
/**
 * Utsavam LeadService
 * Handles Event Listing Inquiries / Lead Capture from prospective organizers.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../storage/DataStoreFactory.php';

class LeadService {
    private static function getFilePath(): string {
        $dir = STORAGE_PATH;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return rtrim($dir, '/') . '/leads.json';
    }

    private static function readLeads(): array {
        $file = self::getFilePath();
        if (!file_exists($file)) {
            self::writeLeads([]);
            return [];
        }

        $fp = @fopen($file, 'r');
        if (!$fp) return [];

        @flock($fp, LOCK_SH);
        $content = '';
        while (!feof($fp)) {
            $content .= fread($fp, 8192);
        }
        @flock($fp, LOCK_UN);
        @fclose($fp);

        if (empty(trim($content))) return [];
        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    private static function writeLeads(array $leads): bool {
        $file = self::getFilePath();
        $encoded = json_encode($leads, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        $fp = @fopen($file, 'c+');
        if (!$fp) return false;

        if (@flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, $encoded);
            fflush($fp);
            @flock($fp, LOCK_UN);
            @fclose($fp);
            return true;
        }

        @fclose($fp);
        return false;
    }

    /**
     * Get all leads with optional status and search filtering
     */
    public static function getAllLeads(?string $status = null, ?string $search = null): array {
        $leads = self::readLeads();

        // Sort newest first
        usort($leads, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));

        if ($status && $status !== 'all') {
            $leads = array_filter($leads, fn($l) => strtolower($l['status'] ?? '') === strtolower($status));
        }

        if ($search) {
            $searchLower = strtolower(trim($search));
            $leads = array_filter($leads, function($l) use ($searchLower) {
                return str_contains(strtolower($l['organizer_name'] ?? ''), $searchLower)
                    || str_contains(strtolower($l['organization_name'] ?? ''), $searchLower)
                    || str_contains(strtolower($l['event_title'] ?? ''), $searchLower)
                    || str_contains(strtolower($l['email'] ?? ''), $searchLower)
                    || str_contains(strtolower($l['phone'] ?? ''), $searchLower)
                    || str_contains(strtolower($l['venue_city'] ?? ''), $searchLower)
                    || str_contains(strtolower($l['inquiry_number'] ?? ''), $searchLower);
            });
        }

        return array_values($leads);
    }

    /**
     * Get single lead by ID
     */
    public static function getLeadById(string $id): ?array {
        $leads = self::readLeads();
        foreach ($leads as $l) {
            if (($l['id'] ?? '') === $id) {
                return $l;
            }
        }
        return null;
    }

    /**
     * Create a new event listing inquiry / lead
     */
    public static function createLead(array $data): array {
        $leads = self::readLeads();

        $id = generateId('lead');
        $inquiryNumber = 'INQ-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));

        $lead = [
            'id' => $id,
            'inquiry_number' => $inquiryNumber,
            'organizer_name' => trim($data['organizer_name'] ?? ''),
            'organization_name' => trim($data['organization_name'] ?? ($data['organizer_name'] ?? '')),
            'email' => trim($data['email'] ?? ''),
            'phone' => trim($data['phone'] ?? ''),
            'event_title' => trim($data['event_title'] ?? ''),
            'event_category' => trim($data['event_category'] ?? 'Festival'),
            'expected_attendees' => trim($data['expected_attendees'] ?? '500 - 2,000'),
            'event_date' => trim($data['event_date'] ?? ''),
            'venue_city' => trim($data['venue_city'] ?? ''),
            'ticketing_type' => trim($data['ticketing_type'] ?? 'Free Registration'),
            'requirements' => trim($data['requirements'] ?? ''),
            'status' => 'new', // new, contacted, converted, archived
            'admin_notes' => '',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        array_unshift($leads, $lead);
        self::writeLeads($leads);

        // Also log activity
        try {
            getDataStore()->logActivity([
                'actor_id' => 'public_lead',
                'actor_role' => 'lead',
                'client_id' => null,
                'action' => 'admin_received_lead',
                'entity_type' => 'lead',
                'entity_id' => $id,
                'description' => "New event listing inquiry [{$inquiryNumber}] received from {$lead['organizer_name']} for '{$lead['event_title']}'"
            ]);
        } catch (Exception $e) {
            // non-fatal
        }

        return $lead;
    }

    /**
     * Update lead status & notes
     */
    public static function updateLeadStatus(string $id, string $status, ?string $adminNotes = null): bool {
        $leads = self::readLeads();
        $updated = false;

        foreach ($leads as &$l) {
            if (($l['id'] ?? '') === $id) {
                $l['status'] = $status;
                if ($adminNotes !== null) {
                    $l['admin_notes'] = $adminNotes;
                }
                $l['updated_at'] = date('Y-m-d H:i:s');
                $updated = true;
                break;
            }
        }

        if ($updated) {
            self::writeLeads($leads);
        }
        return $updated;
    }

    /**
     * Delete lead
     */
    public static function deleteLead(string $id): bool {
        $leads = self::readLeads();
        $filtered = array_filter($leads, fn($l) => ($l['id'] ?? '') !== $id);
        if (count($filtered) !== count($leads)) {
            self::writeLeads(array_values($filtered));
            return true;
        }
        return false;
    }

    /**
     * Get aggregate statistics
     */
    public static function getStats(): array {
        $leads = self::readLeads();
        $stats = [
            'total' => count($leads),
            'new' => 0,
            'contacted' => 0,
            'converted' => 0,
            'archived' => 0
        ];

        foreach ($leads as $l) {
            $st = strtolower($l['status'] ?? 'new');
            if (isset($stats[$st])) {
                $stats[$st]++;
            }
        }

        return $stats;
    }
}
