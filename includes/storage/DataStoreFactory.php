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
        if (($driver === 'sql' || $driver === 'mysql') && extension_loaded('pdo_mysql') && in_array('mysql', PDO::getAvailableDrivers())) {
            try {
                $instance = new SqlDataStore();
            } catch (Throwable $e) {
                // Fallback to JSON if SQL connection fails
                $instance = new JsonDataStore();
            }
        } else {
            $instance = new JsonDataStore();
        }
    }
    return $instance;
}
