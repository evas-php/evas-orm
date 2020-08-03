<?php
/**
 * @package evas-php\evas-orm
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
     * @return QueryResult
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
     * @param array|null значения записи
     * @return object
     */
    public static function insert(array $values = null): object
    {
        return static::create($values)->save();
    }

    /**
     * Поиск записи по первичному ключу.
     * @param int|string|array значение первичного ключа или массив значений первичного ключа
     * @return static|array of static
     */
    public static function findByPrimary($id)
    {
        return static::baseFindByPrimary(get_called_class(), $id);
    }
}
