<?php
/**
 * @package evas-php/evas-orm
 */
namespace Evas\Orm\Models;

use Evas\Orm\Base\QueryResult;
use Evas\Orm\Models\OrmModel;

/**
 * Реализация ActiveRecord.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 1.0
 */
abstract class ActiveRecord extends OrmModel
{
    /**
     * @var array дополнительные значения объекта
     */
    protected $additionalValues = [];

    /**
     * Сохранение записи.
     * @param array|null имена сохраняемых полей
     * @return self
     */
    public function save(array $columns = null): object
    {
        return static::baseSave($this, $columns);
    }

    /**
     * Удаление записи.
     */
    public function delete(): QueryResult
    {
        return static::baseDelete($this);
    }

    /**
     * Создание объекта записи.
     * @param array|null значения записи
     * @return object
     */
    public static function create(array $values = null): object
    {
        return static::baseCreate(static::class, $values);
    }

    /**
     * Создание объекта записи с записью в базу.
     * @param string имя класса объекта
     * @param array|null значения записи
     * @return object
     */
    public static function insert(array $values = null): object
    {
        return static::create($values)->save();
    }

    /**
     * Поиск записи.
     * @param int|array|null первичный ключ или массив первичных ключей
     * @return static|array|QueryBuilder
     */
    public static function find($id = null)
    {
        return static::baseFind($id, get_called_class());
    }
}
