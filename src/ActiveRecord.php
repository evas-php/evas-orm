<?php
/**
 * Модель данных Active Record.
 * @package evas-php\evas-orm
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Orm;

use Evas\Base\App;
use Evas\Base\Help\HooksTrait;
use Evas\Db\Interfaces\DatabaseInterface;
use Evas\Db\Interfaces\QueryBuilderInterface;
use Evas\Db\Table;
use Evas\Orm\Exceptions\LastInsertIdUndefinedException;

abstract class ActiveRecord
{
    // подключаем поддержку произвольных хуков в наследуемых классах
    use HooksTrait;

    /** @var string имя соединения с базой данных*/
    public static $dbname;
    /** @var string имя соединения с базой данных только для записи */
    public static $dbnameWrite;
    /*** @var string кастомное имя таблицы */
    public static $tableName;

    /**
     * Получение соединения с базой данных.
     * @param bool использовать ли соединение для записи
     * @return DatabaseInterface
     */
    // abstract public static function getDb(): DatabaseInterface;
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
     * Получение имени таблицы из маппинга моделей таблиц.
     * @return string|null имя таблицы
     */
    public static function tableNameFromMap(): ?string
    {
        return static::getDb()->modelTablesMap()->getModelTable(get_called_class());
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

    /**
     * Дефолтные значения новой записи.
     * @return array
     */
    public static function default(): array
    {
        return [];
    }

    /**
     * Конструктор.
     * @param array|null свойства модели
     */
    public function __construct(array $props = null)
    {
        $this->hook('beforeConstruct', $props);
        
        $pk = static::primaryKey();
        $creating = empty($props[$pk]);
        
        if ($creating) $this->hook('beforeCreate', $props);
        else $this->hoot('beforeGet', $props);

        if (!empty($props)) $this->fill($props);
        
        if ($creating) $this->hook('afterCreate');
        else $this->hoot('afterGet');
        $this->hook('afterConstruct');
    }

    /**
     * Заполнение модели свойствами.
     * @param array свойства модели
     * @return self
     */
    public function fill(array $props): ActiveRecord
    {
        foreach ($props as $name => $value) {
            $this->$name = $value;
        }
        return $this;
    }


    /**
     * Получение маппинга данных записи для базы данных.
     * @return array
     */
    public function getRowProperties(): array
    {
        $props = [];
        foreach (static::columns() as &$column) {
            if (isset($this->$column)) {
                $props[$column] = $this->$column;
            }
        }
        return $props;
    }

    /**
     * Получение состояния сохраняемых полей.
     * @return array
     */
    public function getState(): array
    {
        $state = static::getDb()->identityMapGetState($this, static::primaryKey());
        foreach ($state as $name => &$value) {
            if (!in_array($name, static::columns())) {
                unset($state[$name]);
            }
        }
        return $state;
    }

    /**
     * Получение маппинга измененных свойств записи.
     * @return array
     */
    public function getUpdatedProperties(): array
    {
        $props = $this->getRowProperties();
        $pk = static::primaryKey();
        if (empty($this->$pk)) {
            return $props;
        } else {
            // $state = static::getDb()->identityMapGetState($this, $pk);
            $state = $this->getState();
            // return array_diff($props, $state ?? []);

            return array_merge(
                array_fill_keys(array_keys(array_diff($state ?? [], $props)), null),
                array_diff_assoc($props, $state ?? [])
            );
        }
    }


    /**
     * Сохранение записи.
     * @return self
     * @throws LastInsertIdUndefinedException
     */
    public function save()
    {
        if (empty($this->getUpdatedProperties())) {
            $this->hook('nothingSave');
            return $this;
        }

        $this->hook('beforeSave');
        $pk = static::primaryKey();
        if (empty($this->$pk)) {
            $this->hook('beforeInsert');
            static::getDb(true)->insert(static::tableName(), $this->getUpdatedProperties());
            $this->$pk = static::lastInsertId();
            if (empty($this->$pk)) {
                throw new LastInsertIdUndefinedException();
            }
            $this->hook('afterInsert');
        } else {
            $this->hook('beforeUpdate');
            static::getDb(true)->update(static::tableName(), $this->getUpdatedProperties())
                ->where("$pk = ?", [$this->$pk])->one();
            $this->hook('afterUpdate');
        }
        $this->hook('afterSave');
        return static::getDb()->identityMapUpdate($this, $pk);
    }

    /**
     * Удаление записи.
     */
    public function delete()
    {
        $pk = static::primaryKey();
        if (empty($this->$pk)) {
            $this->hook('nothingDelete');
            return $this;
        }
        $this->hook('beforeDelete');
        $qr = static::getDb(true)->delete(static::tableName())
            ->where("$pk = ?", [$this->$pk])->one();
        if (0 < $qr->rowCount()) {
            static::getDb()->identityMapUnset($this, $pk);
            $this->id = null;
        }
        $this->hook('afterDelete', $qr->rowCount());
        return $this;
    }

    /**
     * Создание модели записи.
     * @param array|null значения записи
     * @return static
     */
    public static function create(array $props = null): ActiveRecord
    {
        return new static($props);
    }

    /**
     * Создание модели записи с вставкой в базу.
     * @param array|null значения записи
     * @return static
     */
    public static function insert(array $props = null): ActiveRecord
    {
        return static::create($props)->save();
    }

    /**
     * Поиск записи через сборщик запроса.
     * @return QueryBuilderInterface
     */
    public static function find(): QueryBuilderInterface
    {
        return static::getDb()->select(static::tableName());
    }

    /**
     * Поиск по первичному ключу.
     * @param int|string значение первичного ключа, перечисление
     * @return static|array of static
     */
    public static function findByPK(...$ids)
    {
        $pk = static::primaryKey();
        $qb = static::find();
        if (count($ids) > 1) {
            return $qb->whereIn("`$pk`", $ids)
            ->query(count($ids))->classObjectAll(static::class);
        } else {
            return $qb->where("`$pk` = ?", $ids)
            ->one()->classObject(static::class);
        }
    }

    /**
     * Поиск по id, алиас для findByPK.
     * @param int id, перечисление
     * @return static|array of static
     */
     public static function findById(int ...$ids)
     {
        return static::findByPK(...$ids);
     }

    /**
     * Поиск записи по sql-запросу.
     * @param string sql-запрос
     * @param array|null значения запроса для экранирования
     * @return static|static[]
     */
    public static function findBySql(string $sql, array $values = null)
    {
        $qr = static::getDb()->query($sql, $values);
        return strstr($qr->stmt()->queryString, 'LIMIT 1') 
            ? $qr->classObject(static::class)
            : $qr->classObjectAll(static::class);
    }
}
