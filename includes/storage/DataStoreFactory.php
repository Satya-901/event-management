<?php
/**
 * Utsavam DataStore Factory
 */

require_once __DIR__ . '/DataStore.php';
require_once __DIR__ . '/JsonDataStore.php';
require_once __DIR__ . '/SqlDataStore.php';

function getDataStore(): DataStore {
    static $instance = null;
    if ($instance === null) {
        $driver = strtolower(defined('DATA_DRIVER') ? DATA_DRIVER : 'json');
        if ($driver === 'sql' || $driver === 'mysql') {
            try {
                $instance = new SqlDataStore();
            } catch (Throwable $e) {
                // Fallback to JSON if SQL connection/driver fails
                error_log("SqlDataStore failed, falling back to JsonDataStore: " . $e->getMessage());
                $instance = new JsonDataStore();
            }
        } else {
            $instance = new JsonDataStore();
        }
    }
    return $instance;
}
