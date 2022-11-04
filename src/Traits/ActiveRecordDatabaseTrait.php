<?php
/**
 * Трейт соединения базы данных для ActiveRecord.
 * @package evas-php\evas-orm
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Orm\Traits;

use Evas\Base\App;
use Evas\Db\Interfaces\DatabaseInterface;

trait ActiveRecordDatabaseTrait
{
    /** @var string имя соединения с базой данных */
    public static $dbname;
    /** @var string имя соединения с базой данных только для записи */
    public static $dbnameWrite;

    /**
     * Получение соединения с базой данных.
     * @param bool использовать ли соединение для записи
     * @return DatabaseInterface
     */
    public static function getDb(bool $write = false): DatabaseInterface
    {
        $dbname = static::getDbName($write);
        return App::db($dbname);
    }

    /**
     * Получение имени соединения с базой данных.
     * @param bool использовать ли соединение для записи
     * @return string|null
     */
    public static function getDbName(bool $write = false): ?string
    {
        return true === $write && !empty(static::$dbnameWrite)
            ? static::$dbnameWrite : static::$dbname;
    }
}
