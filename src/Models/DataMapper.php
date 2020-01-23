<?php
/**
 * @package evas-php/evas-orm
 */
namespace Evas\Orm\Models;

use Evas\Orm\Base\QueryResult;
use Evas\Orm\Models\OrmModel;

/**
 * Реализация DataMapper.
 * @author Egor Vasyakin <e.vasyakin@itevas.ru>
 * @since 1.0
 */
abstract class DataMapper extends OrmModel
{
    /**
     * @var string имя класса модели
     */
    public static $dataClassName;

    /**
     * Создание объекта записи.
     * @param array|null значения записи
     * @return object
     */
    public static function create(array $values = null): object
    {
        return static::baseCreate(static::$dataClassName, $values);
    }

    /**
     * Создание объекта записи с записью в базу.
     * @param array|null значения записи
     * @return object
     */
    public static function insert(array $values = null): object
    {
        $object = static::create($values);
        return static::save($object);
    }

    /**
     * Сохранение записи.
     * @param object
     * @param array|null имена сохраняемых полей
     */
    public static function save(object &$object, array $columns = null): object
    {
        return static::baseSave($object, $columns);
    }

    /**
     * Удаление записи.
     * @param object
     */
    public static function delete(object &$object): QueryResult
    {
        return static::baseDelete($object);
    }

    /**
     * Поиск записи.
     * @param int|array|null первичный ключ или массив первичных ключей
     * @return QueryBuilder|QueryResult|array
     */
    public static function find($id = null)
    {
        return static::baseFind($id, static::$dataClassName);
    }
}
