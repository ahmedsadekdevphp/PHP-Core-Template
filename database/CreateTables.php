<?php
require dirname(__DIR__) . '/vendor/autoload.php';
require_once  dirname(__DIR__) . '/app/services/Helpers.php';

use Database\Migrate;

$tableFiles = [
    'user' => 'database/structure/create_user_table.sql',
];

$db = new Migrate();
// Iterate through each  file and execute SQL
foreach ($tableFiles as $table => $filePath) {
    $sql = file_get_contents($filePath);
    $result = $db->executeSql($sql);
    echo "Result for $table table: $result\n";
}
