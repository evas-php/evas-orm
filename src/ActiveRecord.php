<?php
/**
 * Модель данных Active Record.
 * @package evas-php\evas-orm
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Orm;

use \JsonSerializable;
use Evas\Base\App;
use Evas\Base\Help\HooksTrait;
use Evas\Db\Interfaces\DatabaseInterface;
use Evas\Db\Table;
use Evas\Orm\Exceptions\LastInsertIdUndefinedException;
use Evas\Orm\RelatedCollection;
use Evas\Orm\Traits\RelationsTrait;
use Evas\Orm\QueryBuilder;

abstract class ActiveRecord implements JsonSerializable
{
    // подключаем поддержку произвольных хуков в наследуемых классах
    use HooksTrait;

    // подключаем поддержку связей
    use RelationsTrait;

    /** @var string имя соединения с базой данных */
    public static $dbname;
    /** @var string имя соединения с базой данных только для записи */
    public static $dbnameWrite;
    /** @var string кастомное имя таблицы */
    public static $tableName;

    /** @var array данные модели */
    protected $modelData = [];

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
        $className = static::class;
        $lastSlash = strrpos($className, '\\');
        if ($lastSlash > 0) {
            $className = substr($className, $lastSlash + 1);
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
        else $this->hook('beforeGet', $props);

        if (!empty($props)) $this->fill($props);
        
        if ($creating) $this->hook('afterCreate');
        else $this->hook('afterGet');
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
     * Получение значения первичного ключа модели.
     * @return int|string|null
     */
    public function primaryValue()
    {
        return $this->{static::primaryKey()};
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
        if ($state) foreach ($state as $name => &$value) {
            if (!isset($value) || !in_array($name, static::columns())) {
                unset($state[$name]);
            }
        }
        return $state ?? [];
    }

    /**
     * Получение маппинга измененных свойств записи.
     * @return array
     */
    public function getUpdatedProperties(): array
    {
        $props = $this->getRowProperties();
        if (empty($this->primaryValue())) {
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
            static::getDb(true)->table(static::tableName())
                ->where($pk, $this->$pk)->update($this->getUpdatedProperties());
            $this->hook('afterUpdate');
        }
        $this->hook('afterSave');
        return static::getDb()->identityMapUpdate($this, $pk);
    }

    /**
     * Сохранение модели и её связей.
     */
    public function push()
    {
        $this->save();
        foreach ($this->relatedCollections as $models) {
            if (!is_array($models)) $models = [$models];
            foreach ($models as $model) {
                $model->push();
            }
        }
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
        $qr = static::getDb(true)->table(static::tableName())
            ->where($pk, $this->$pk)->limit(1)->delete();
        $this->hook('afterDelete', $qr->rowCount());
        if (0 < $qr->rowCount()) {
            static::getDb()->identityMapUnset($this, $pk);
            $this->$pk = null;
        }
        return $this;
    }

    /**
     * Обновление данных модели из базы.
     */
    public function reload()
    {
        $pv = $this->primaryValue();
        if (!$pv) return;
        $this->fill((array) static::find($pv));
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
     * Поиск записи/записей.
     * @param array|int столбец или столбцы
     * @return static|static[]
     */
    public static function find($ids)
    {
        $ids = func_num_args() > 1 ? func_get_args() : $ids;
        $db = static::getDb();
        return (new QueryBuilder($db, static::class))->find($ids);
    }

    /**
     * Поиск по sql-запросу.
     * @param string sql-запрос
     * @param array|null значения запроса для экранирования
     * @return static|static[]
     */
    public static function query(string $sql, array $values = null)
    {
        $qr = static::getDb()->query($sql, $values);
        return strstr($qr->stmt()->queryString, 'LIMIT 1') 
            ? $qr->object(static::class)
            : $qr->objectAll(static::class);
    }

    /**
     * Получение или создание новой записи.
     * @param array свойства поиска
     * @param array свойства вставки
     * @return static
     */
    public static function firstOrCreate(array $findProps, array $createProps = null)
    {
        $keys = array_keys($findProps);
        $vals = array_values($findProps);
        foreach (static::columns() as $i => $key) {
            if (!in_array($key, $keys)) {
                unset($keys[$i]);
                unset($vals[$i]);
            }
        }
        $record = static::whereRowValues($keys, $vals)->one();
        if ($record) return $record;
        return static::create(array_merge($findProps, $createProps));
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
            $db = static::getDb();
            return (new QueryBuilder($db, static::class))->$name(...$args);
        }
        throw new \BadMethodCallException(sprintf(
            'Call to undefined method %s::%s()', __CLASS__, $name
        ));
    }

    /**
     * Описание связей модели.
     * @return array
     */
    protected static function relations(): array
    {
        return [];
    }

    /**
     * Подгрузка связанных записей.
     * @param string имя связи
     * @param array|null столбцы
     * @return self
     */
    public function loadRelated(string $name, array $columns = null, QueryBuilder $query = null)
    {
        $relation = static::getRelation($name);
        if ($query) {
            $qb = $query->where($relation->foreignKey, $this->{$relation->localKey});
        } else {
            $qb = $relation->foreignModel::where($relation->foreignKey, $this->{$relation->localKey});
        }
        if ($relation->type === 'hasOne') $qb->limit(1);
        $models = $qb->get($columns);
        $this->setRelatedCollection($relation->name, $models);
        return $this;
    }

    public function load(...$props)
    {
        foreach ($props as &$prop) {
            if (is_string($prop)) {
                @list($name, $columns) = explode(':', $prop);
                $this->loadRelated($name, $columns);
            } else if (is_array($prop)) {
                foreach ($prop as $name => &$sub) {
                    $columns = null;
                    $query = null;
                    if (is_string($name)) {
                        if (is_string($sub)) {
                            $columns = $sub;
                        } else if (is_callable($sub)) {
                            @list($name, $columns) = explode(':', $name);
                            $relation = $this->getRelation($name);
                            if (!$relation) continue;
                            $db = static::getDb();
                            $query = new QueryBuilder($db, $relation->foreignModel);
                            $sub($query);
                        }
                    } else if (is_string($sub)) {
                        @list($name, $columns) = explode(':', $sub);
                    } else {
                        throw new \InvalidArgumentException('Incorrect load syntax');
                    }
                    $this->loadRelated($name, $columns, $query);
                }
            }
        }
    }

    /**
     * Получение данных модели.
     * @return array
     */
    public function getData(): array
    {
        return $this->modelData;
    }

    /**
     * Получение всех связанных записей модели.
     * @return array
     */
    public function getRelatedCollections(): array
    {
        return $this->relatedCollections;
    }

    /**
     * Получение конкретных связанных записей модели.
     * @param string имя связи
     * @return RelatedCollection|null
     */
    public function getRelatedCollection(string $name): ?RelatedCollection
    {
        if (static::hasRelation($name)) {
            if (!isset($this->relatedCollections[$name])) {
                $this->loadRelated($name);
            }
            return $this->relatedCollections[$name] ?? null;
        }
        return null;
    }

    /**
     * Установка свойства.
     * @param string имя
     * @param mixed значение
     */
    public function __set(string $name, $value)
    {
        $this->modelData[$name] = $value;
    }

    /**
     * Получение свойства или связанных моделей.
     * @param string имя
     * @return mixed значение
     */
    public function __get(string $name)
    {
        if (static::hasRelation($name)) {
            return $this->getRelatedCollection($name);
        } else {
            return $this->modelData[$name] ?? null;
        }
    }

    /**
     * Проверка наличия свойства.
     * @param string имя свойства
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return isset($this->modelData[$name]);
    }

    /**
     * Очистка свойства.
     * @param string имя свойства
     */
    public function __unset(string $name)
    {
        unset($this->modelData[$name]);
    }

    // Конвертация

    /**
     * Конвертация данных и связей модели в массив.
     * @return array
     */
    public function toArray(): array
    {
        return array_merge($this->getData(), $this->getRelatedCollections());
    }
    
    /**
     * Конвертация модели в JSON сериализацию.
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Конвертация данных и связей модели в JSON.
     * @param int|null опции для json_encode
     * @return string
     */
    public function toJson(int $options = JSON_UNESCAPED_UNICODE): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Конвертация модели в строку.
     * @return string
     */
    public function __toString(): string
    {
        return $this->toJson();
    }

    // protected static $list = [];
    // protected static $map = [];

    // public static function list(): array
    // {
    //     return static::$list;
    // }

    // public static function map(): array
    // {
    //     return static::$map;
    // }

    // public static function size(): int
    // {
    //     return count(static::$map);
    // }

    // public static function getCached($id = null)
    // {
    //     if (null === $id) {
    //         return static::list();
    //     } else if (is_array($id)) {
    //         $result = [];
    //         foreach ($id as $sub) {
    //             $result[] = static::getCached($sub);
    //         }
    //         return $result;
    //     } else {
    //         return static::$map[$id];
    //     }
    // }
}
