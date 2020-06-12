<?php
/**
 * @package evas-php/evas-orm
 */
namespace Evas\Orm\Models;

use Evas\Orm\Base\QueryResult;
use Evas\Orm\Models\Exception\DataClassNameEmptyException;
use Evas\Orm\Models\OrmModel;

/**
 * Реализация DataMapper.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 1.0
 */
abstract class DataMapper extends OrmModel
{
    /**
     * @var string имя класса модели
     */
    public static $dataClassName;

    /**
     * Получение имени класса модели.
     * @throws DataClassNameEmptyException
     * @return string
     */
    protected static function dataClassName(): string
    {
        if (empty(static::$dataClassName)) {
            throw new DataClassNameEmptyException();
        }
        return static::$dataClassName;
    }

    /**
     * Создание объекта записи.
     * @param array|null значения записи
     * @return object
     */
    public static function create(array $values = null): object
    {
        return static::baseCreate(static::dataClassName(), $values);
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
     * @return QueryResult
     */
    public static function delete(object &$object): QueryResult
    {
        return static::baseDelete($object);
    }

    /**
     * Поиск записи.
     * @param int|array|null значение первичного ключа или массив значений первичного ключа
     * @return QueryBuilder|QueryResult|array
     */
    public static function find($id = null)
    {
        return static::baseFind(static::dataClassName(), $id);
    }
}
