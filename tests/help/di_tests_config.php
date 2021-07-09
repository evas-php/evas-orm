<?php
/**
 * Конфиг Di для тестов.
 */
use Evas\Di;

use Evas\Db\Database;

return [
    'db' => Di\createOnce(Database::class, [
        Di\includeFile(dirname(dirname(dirname(__DIR__))) . '/evas-db/tests/help/db_tests_config.php')
    ]),
];
