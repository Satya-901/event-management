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
        $driver = defined('DATA_DRIVER') ? DATA_DRIVER : 'json';
        if ($driver === 'sql') {
            try {
                $instance = new SqlDataStore();
            } catch (Exception $e) {
                // Fallback to JSON if SQL fails
                error_log("SqlDataStore failed, falling back to JsonDataStore: " . $e->getMessage());
                $instance = new JsonDataStore();
            }
        } else {
            $instance = new JsonDataStore();
        }
    }
    return $instance;
}
