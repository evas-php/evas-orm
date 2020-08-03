<?php
/**
 * @package evas-php\evas-orm
 */
namespace Evas\Orm\Models;

use Evas\Orm\Base\QueryResult;
use Evas\Orm\Builders\QueryBuilder;
use Evas\Orm\Database;

/**
 * Базовая абстрактная модель ORM для ActiveRecord и DataMapper.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 1.0
 */
abstract class OrmModel
{
    /**
     * @var string кастомное имя таблицы
     */
    public static $tableName;

    /**
     * Получение соединения с базой данных.
     * Создайте свою обертку для ActiveRecord/DataMapper и в ней сделайте реализацию этого метода.
     * @return Database
     */
    abstract public static function getDb();

    /**
     * Генерация имени таблицы из имени класса.
     * @return string
     */
    public static function generateTableName(): string
    {
        $className = get_called_class();
        $lastSlash = strrpos($className, '\\');
        if ($lastSlash > 0) {
            $className = substr($className, $lastSlash + 1);
        }
        $mapperPos = strrpos($className, 'Mapper');
        if (false !== $mapperPos) {
            $className = substr($className, 0, $mapperPos);
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
     * @return Table
     */
    public static function table(): Table
    {
        return static::getDb()->table(static::tableName());
    }

    /**
     * Получение первичного ключа таблицы.
     * @return string
     */
    public static function primaryKey(): string
    {
        return static::table()->primaryKey();
    }

    /**
     * Получение столбцов таблицы.
     * @return array
     */
    public static function columns(): array
    {
        return static::table()->columns();
    }

    /**
     * Получение id последней записи.
     * @return int
     */
    public static function lastInsertId(): int
    {
        return static::table()->lastInsertId(); 
    }

    /**
     * Дефолтные значения новой записи.
     * @return array
     */
    public static function default(): array
    {
        return [];
    }


    /**
     * Получение значений объекта совместимых со столбцами таблицы.
     * @param object объект
     * @return array значения
     */
    public static function values(object &$object): array
    {
        $columns = static::columns();
        $values = [];
        foreach ($columns as $column) {
            if (isset($object->$column)) {
                $values[$column] = $object->$column;
            }
        }
        return $values;
    }


    /**
     * Получение обновленных значений в объекте.
     * @param object объект
     * @return array значения
     */
    public static function getUpdated(object &$object): array
    {
        $primaryKey = static::primaryKey();
        $primaryValue = $object->$primaryKey ?? null;
        $values = static::values($object);
        if (empty($primaryValue)) {
            return $values;
        }
        $state = static::getDb()->identityMapGetStateByObject($object, $primaryKey);
        return array_diff($values, $state ?? []);
    }


    // Базовая реализация операций с записями

    /**
     * Установка значений записи в объект записи.
     * @param object объект записи
     * @param array|null значения записи
     * @return object
     */
    public static function bindParams(&$object, array $values = []): object
    {
        $values = array_merge(static::default(), $values);
        if ($values) foreach ($values as $name => $value) {
            $object->$name = $value;
        }
        return $object;
    }

    /**
     * Базовая реализация создания объекта записи.
     * @param string имя класса объекта
     * @param array|null значения записи
     * @return object
     */
    public static function baseCreate(string $className, array $values = []): object
    {
        $object = new $className;
        return static::bindParams($object, $values);
    }

    /**
     * Создание нескольких объектов записей.
     * @param array параметры объектов
     * @return array of objects
     */
    public static function batchCreate(array $rows)
    {
        $objects = [];
        foreach ($rows as &$row) {
            $objects[] = static::create($row);
        }
        return $objects;
    }

    /**
     * Создание нескольких объектов записей с сохранением.
     * @param array параметры объектов
     * @return array of objects
     */
    public static function batchInsert(array $rows)
    {
        $objects = [];
        foreach ($rows as &$row) {
            $objects[] = static::insert($row);
        }
        return $objects;
    }

    /**
     * Базовое сохранение записи.
     * @param object
     * @param array|null сохраняемые поля
     * @return object
     */
    public static function baseSave(object &$object, array $columns = null): object
    {
        $primaryKey = static::primaryKey();
        $primaryValue = $object->$primaryKey;
        $values = [];
        if ($columns) foreach ($columns as $field) {
            $values[$field] = $object->$field;
        } else {
            // get updated
            $values = static::getUpdated($object);
        }

        if (null !== $primaryValue) {
            static::getDb()->update(static::tableName(), $values)
                ->where("$primaryKey = ?", [$primaryValue])->one();
        } else {
            unset($values[$primaryKey]);
            static::getDb()->insert(static::tableName(), $values);
            $object->$primaryKey = static::lastInsertId();
        }
        return static::getDb()->identityMapUpdate($object, $primaryKey);
    }

    /**
     * Базовое удаление записи.
     * @param object
     * @return QueryResult
     */
    public static function baseDelete(object &$object): QueryResult
    {
        $primaryKey = static::primaryKey();
        $primaryValue = $object->$primaryKey;
        $queryResult = static::getDb()->delete(static::tableName())
            ->where("$primaryKey = ?", [$primaryValue])->one();
        if (0 < $queryResult->rowCount()) {
            static::getDb()->identityMapUnsetByObject($object, $primaryKey);
        }
        return $queryResult;
    }

    /**
     * Базовый поиск записи.
     * @param string имя класса сущности/сущностей
     * @param int|string|array значение первичного ключа или массив значений первичного ключа
     * @return static|array|QueryBuilder
     */
    protected static function baseFindByPrimary(string $className, $primary)
    {
        $qb = static::find();
        $primaryKey = static::primaryKey();
        return is_array($primary) 
            ? $qb->whereIn($primaryKey, $primary)
                ->query(count($primary))
                ->classObjectAll($className)
            : $qb->where("$primaryKey = ?", [$primary])
                ->one()
                ->classObject($className);
    }

    /**
     * Поиск записи.
     * @param string|null столбцы
     * @return QueryBuilder
     */
    public static function find(string $columns = null): QueryBuilder
    {
        return static::getDb()->select(static::tableName(), $columns);
    }

    /**
     * Поиск записей по sql.
     * @param string sql
     * @param array|null значения запроса
     * @return QueryResult
     */
    public static function findBySql(string $sql, array $values = null)
    {
        return static::getDb()->query($sql, $values);
    }
}
