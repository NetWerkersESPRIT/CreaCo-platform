<?php
date_default_timezone_set('UTC');
echo "PHP Timezone: " . date_default_timezone_get() . "\n";
echo "PHP Current Time: " . date('Y-m-d H:i:s') . "\n";

$dsn = "mysql:host=127.0.0.1;dbname=creaco;charset=utf8mb4";
try {
    $pdo = new PDO($dsn, 'root', '', [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+00:00'"
    ]);
    $row = $pdo->query('SELECT @@session.time_zone as session_tz, @@global.time_zone as global_tz, NOW() as db_now')->fetch();
    echo "MySQL Session TZ: " . $row['session_tz'] . "\n";
    echo "MySQL Global TZ: " . $row['global_tz'] . "\n";
    echo "MySQL NOW(): " . $row['db_now'] . "\n";
} catch (PDOException $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
}
