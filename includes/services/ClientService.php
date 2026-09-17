<?php
/**
 * Utsavam ClientService
 */

require_once __DIR__ . '/../storage/DataStoreFactory.php';
require_once __DIR__ . '/../helpers.php';

class ClientService {
    public static function getAllClients(): array {
        return getDataStore()->getClients();
    }

    public static function getClient(string $id): ?array {
        return getDataStore()->getClientById($id);
    }

    public static function getClientBySlug(string $slug): ?array {
        return getDataStore()->getClientBySlug(slugify($slug));
    }

    public static function getClientByCode(string $code): ?array {
        return getDataStore()->getClientByCode(trim($code));
    }

    public static function createClient(array $data, ?array $adminActor = null): array {
        $store = getDataStore();
        $client = $store->createClient($data);

        // Also create client user if login credentials provided
        if (!empty($data['username']) && !empty($data['password'])) {
            $store->createUser([
                'client_id' => $client['id'],
                'name' => $data['name'] ?? $data['username'],
                'username' => $data['username'],
                'email' => $data['email'] ?? '',
                'password' => $data['password'],
                'role' => 'client',
                'status' => 'active'
            ]);
        }

        // Log activity
        $store->logActivity([
            'actor_id' => $adminActor['id'] ?? 'super_admin',
            'actor_role' => $adminActor['role'] ?? 'super_admin',
            'client_id' => $client['id'],
            'action' => 'admin_created_client',
            'entity_type' => 'client',
            'entity_id' => $client['id'],
            'description' => "Created client {$client['name']} ({$client['code']})"
        ]);

        return $client;
    }

    public static function updateClient(string $id, array $data, ?array $adminActor = null): bool {
        $store = getDataStore();
        $success = $store->updateClient($id, $data);
        if ($success) {
            $store->logActivity([
                'actor_id' => $adminActor['id'] ?? 'super_admin',
                'actor_role' => $adminActor['role'] ?? 'super_admin',
                'client_id' => $id,
                'action' => 'admin_updated_client',
                'entity_type' => 'client',
                'entity_id' => $id,
                'description' => "Updated client profile for ID {$id}"
            ]);
        }
        return $success;
    }

    public static function deleteClient(string $id, ?array $adminActor = null): bool {
        $store = getDataStore();
        $client = $store->getClientById($id);
        $success = $store->deleteClient($id);
        if ($success && $client) {
            $store->logActivity([
                'actor_id' => $adminActor['id'] ?? 'super_admin',
                'actor_role' => $adminActor['role'] ?? 'super_admin',
                'client_id' => $id,
                'action' => 'admin_deleted_client',
                'entity_type' => 'client',
                'entity_id' => $id,
                'description' => "Deleted client {$client['name']}"
            ]);
        }
        return $success;
    }
}
