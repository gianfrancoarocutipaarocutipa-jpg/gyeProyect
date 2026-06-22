<?php
require_once 'vendor/autoload.php';

try {
    $db = GemMotors\Config\Database::getInstance();
    $db->exec('ALTER TABLE vehiculos ADD COLUMN foto_url VARCHAR(500);');
    echo 'Done';
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
