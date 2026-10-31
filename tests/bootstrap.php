<?php

// Fail before Laravel can bootstrap or RefreshDatabase can touch a real database.
require dirname(__DIR__).'/vendor/autoload.php';
if (getenv('APP_ENV') !== 'testing' || getenv('DB_URL')) {
    throw new RuntimeException('Tests require APP_ENV=testing and an empty DB_URL.');
}
$driver = getenv('DB_CONNECTION');
$database = getenv('DB_DATABASE');
$safe = ($driver === 'sqlite' && $database === ':memory:')
    || ($driver === 'mysql' && preg_match('/^sportmanado_(?:readiness_)?test(?:_|$)/', (string) $database));
if (!$safe || is_file(dirname(__DIR__).'/bootstrap/cache/config.php')) {
    throw new RuntimeException('Unsafe test database or cached configuration. Use isolated SQLite memory or a sportmanado_test_* database in a separate checkout.');
}
