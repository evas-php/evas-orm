<?php
/**
 * @package evas-php\evas-orm
 */
namespace Evas\Orm;

use Evas\Orm\Base\Database as BaseDatabase;
use Evas\Orm\Builders\DatabaseBuildersTrait;

use Evas\Orm\Models\DatabaseIdentityMapTrait;
use Evas\Orm\Models\DatabaseTableTrait;
use Evas\Orm\QueryResult;
use Evas\Orm\Scheme\DatabaseScheme;


/**
 * Константы для свойств класса по умолчанию.
 */
if (!defined('EVAS_DATABASE_QUERY_RESULT_CLASS')) {
    define('EVAS_DATABASE_QUERY_RESULT_CLASS', QueryResult::class);
}


/**
 * Класс соединения с базой данных.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 1.0
 */
class Database extends BaseDatabase
{
    /**
     * Подключаем поддержку сборщиков запросов.
     * Подключаем поддержку классов таблиц.
     * Подключаем поддержку IdentityMap.
     */
    use DatabaseBuildersTrait, DatabaseTableTrait, DatabaseIdentityMapTrait;
}
