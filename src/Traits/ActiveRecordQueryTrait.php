<?php
/**
 * Трейт запросов из ActiveRecord.
 * @package evas-php\evas-orm
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Orm\Traits;

use Evas\Orm\QueryBuilder;

trait ActiveRecordQueryTrait
{
    /**
     * Поиск по sql-запросу.
     * @param string sql-запрос
     * @param array|null значения запроса для экранирования
     * @return static|static[]
     */
    public static function query(string $sql, array $values = null)
    {
        $qr = static::db()->query($sql, $values);
        return strstr($qr->stmt()->queryString, 'LIMIT 1') 
            ? $qr->object(static::class)
            : $qr->objectAll(static::class);
    }

    /**
     * Проброс сборщика запросов через магический вызов статического метода.
     * @param string имя метода
     * @param array|null аргументы
     * @return mixed результат выполнения метода
     * @throws \BadMethodCallException
     */
    public static function __callStatic(string $name, array $args = null)
    {
        if (method_exists(QueryBuilder::class, $name)) {
            $db = static::db();
            return (new QueryBuilder($db, static::class))->$name(...$args);
        }
        throw new \BadMethodCallException(sprintf(
            'Call to undefined method %s::%s()', __CLASS__, $name
        ));
    }

    /**
     * Поиск записи/записей.
     * @param array|int столбец или столбцы
     * @return static|static[]
     */
    public static function find($ids)
    {
        $ids = func_num_args() > 1 ? func_get_args() : $ids;
        $db = static::db();
        return (new QueryBuilder($db, static::class))->find($ids);
    }
}
