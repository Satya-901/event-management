<?php
/**
 * Utsavam EventService
 */

require_once __DIR__ . '/../storage/DataStoreFactory.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../auth.php';

class EventService {
    public static function getEvents(?string $clientId = null): array {
        return getDataStore()->getEvents($clientId);
    }

    public static function getEvent(string $id, ?string $clientId = null): ?array {
        $event = getDataStore()->getEventById($id);
        if (!$event) return null;

        // Backend tenant check
        if ($clientId !== null && ($event['client_id'] ?? '') !== $clientId) {
            return null; // Tenant barrier
        }

        return $event;
    }

    public static function getEventBySlug(string $clientId, string $slug): ?array {
        return getDataStore()->getEventBySlug($clientId, slugify($slug));
    }

    public static function createEvent(array $data, ?array $actor = null): array {
        $store = getDataStore();
        $event = $store->createEvent($data);

        // Seed default dynamic form fields if none provided
        $defaultFields = [
            [
                'field_key' => 'full_name',
                'field_label' => 'Full Name',
                'field_type' => 'text',
                'placeholder' => 'Enter your full name',
                'required' => 1
            ],
            [
                'field_key' => 'email_address',
                'field_label' => 'Email Address',
                'field_type' => 'email',
                'placeholder' => 'you@example.com',
                'required' => 1
            ],
            [
                'field_key' => 'mobile_number',
                'field_label' => 'Phone / WhatsApp Number',
                'field_type' => 'phone',
                'placeholder' => '+91 9876543210',
                'required' => 1
            ],
            [
                'field_key' => 'attendees_count',
                'field_label' => 'Number of Attendees / Passes',
                'field_type' => 'number',
                'placeholder' => '1',
                'required' => 1
            ],
            [
                'field_key' => 'city_locality',
                'field_label' => 'City / Locality',
                'field_type' => 'text',
                'placeholder' => 'e.g. Indiranagar, Bengaluru',
                'required' => 0
            ],
            [
                'field_key' => 'special_notes',
                'field_label' => 'Special Requests or Dietary Requirements',
                'field_type' => 'textarea',
                'placeholder' => 'Optional notes for organizer',
                'required' => 0
            ]
        ];
        $store->saveFormFields($event['id'], $defaultFields);

        $action = ($actor && $actor['role'] === 'super_admin') ? 'admin_created_event' : 'client_created_event';
        $store->logActivity([
            'actor_id' => $actor['id'] ?? 'system',
            'actor_role' => $actor['role'] ?? 'system',
            'client_id' => $event['client_id'],
            'action' => $action,
            'entity_type' => 'event',
            'entity_id' => $event['id'],
            'description' => "Created event {$event['name']}"
        ]);

        return $event;
    }

    public static function updateEvent(string $id, array $data, ?string $clientId = null, ?array $actor = null): bool {
        $event = self::getEvent($id, $clientId);
        if (!$event) return false;

        $store = getDataStore();
        $success = $store->updateEvent($id, $data);
        if ($success) {
            $action = ($actor && $actor['role'] === 'super_admin') ? 'admin_updated_event' : 'client_updated_event';
            $store->logActivity([
                'actor_id' => $actor['id'] ?? 'system',
                'actor_role' => $actor['role'] ?? 'system',
                'client_id' => $event['client_id'],
                'action' => $action,
                'entity_type' => 'event',
                'entity_id' => $id,
                'description' => "Updated event {$event['name']}"
            ]);
        }
        return $success;
    }

    public static function deleteEvent(string $id, ?string $clientId = null, ?array $actor = null): bool {
        $event = self::getEvent($id, $clientId);
        if (!$event) return false;

        $store = getDataStore();
        $success = $store->deleteEvent($id);
        if ($success) {
            $store->logActivity([
                'actor_id' => $actor['id'] ?? 'system',
                'actor_role' => $actor['role'] ?? 'system',
                'client_id' => $event['client_id'],
                'action' => 'deleted_event',
                'entity_type' => 'event',
                'entity_id' => $id,
                'description' => "Deleted event {$event['name']}"
            ]);
        }
        return $success;
    }

    public static function getFormFields(string $eventId): array {
        return getDataStore()->getFormFieldsByEventId($eventId);
    }

    public static function saveFormFields(string $eventId, array $fields, ?string $clientId = null): bool {
        $event = self::getEvent($eventId, $clientId);
        if (!$event) return false;

        return getDataStore()->saveFormFields($eventId, $fields);
    }
}
