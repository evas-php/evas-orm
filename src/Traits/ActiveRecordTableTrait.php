<?php
/**
 * Трейт таблицы для ActiveRecord.
 * @package evas-php\evas-orm
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Orm\Traits;

use Evas\Db\Interfaces\TableInterface;

trait ActiveRecordTableTrait
{
    /** @var string имя таблицы */
    public static $tableName;

    /**
     * Генерация имени таблицы из имени класса.
     * @return string
     */
    public static function generateTableName(): string
    {
        $className = static::class;
        $lastSlash = strrpos($className, '\\');
        if ($lastSlash > 0) {
            $className = substr($className, $lastSlash + 1);
        }
        return strtolower(preg_replace('/([a-z0-9]+)([A-Z]{1})/', '$1_$2', $className)) . 's';
    }

    /**
     * Получение имени таблицы.
     * @return string
     */
    public static function tableName(): string
    {
        if (empty(static::$tableName)) {
            static::$tableName = static::generateTableName();
        }
        return static::$tableName;
    }

    /**
     * Получение объекта таблицы.
     * @param bool использовать ли соединение с БД для записи
     * @return TableInterface
     */
    public static function table(bool $write = false): TableInterface
    {
        return static::getDb($write)->table(static::tableName());
    }

    /**
     * Получение первичного ключа.
     * @param bool|null переполучить из схемы заново
     * @return string
     */
    public static function primaryKey(bool $reload = false): string
    {
        return static::table()->primaryKey($reload);
    }

    /**
     * Получение столбцов таблицы.
     * @param bool|null переполучить из схемы заново
     * @return array
     */
    public static function columns(bool $reload = false): array
    {
        return static::table()->columns($reload);
    }

    /**
     * Получение id последней записи.
     * @return int|null
     */
    public static function lastInsertId(): ?int
    {
        return static::table()->lastInsertId(); 
    }
}
