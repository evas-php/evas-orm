<?php
/**
* @package evas-php/evas-orm
*/
namespace Evas\Orm;

/**
* Простая реализация ActiveRecord.
* @author Egor Vasyakin <e.vasyakin@itevas.ru>
*/
abstract class ActiveRecord
{
    /**
    * @var array Скрытые параметры записи, которые не будут записаны в базу.
    */
    protected $_hidden = [];

    /**
    * Получение названия таблицы записей.
    * @return string
    */
    abstract public static function tableName(): string;

    /**
    * Получение соединения с базой данных.
    * @return Connection
    */
    abstract public static function getDb();

    /**
    * Первичный ключ.
    * @return string
    */
    public static function primaryKey(): string
    {
        return 'id';
    }

    /**
    * Получение id последней записи.
    * @return string
    */
    public static function lastInsertId()
    {
        return static::getDb()->lastInsertId(static::tableName()); 
    }




    /**
    * Добавление записи.
    * @param array|object параметры записи
    */
    public static function insert($params)
    {
        assert(is_array($params) || is_object($params));
        (new static($params))->save();
        return static::lastInsertId();
    }

    /**
    * Конструктор.
    * @param array|object параметры записи
    */
    public function __construct($params = null)
    {
        if ($params) {
            assert(is_array($params) || is_object($params));
            foreach ($params as $name => $value) {
                $this->$name = $value;
            }
        }
    }

    /**
    * Сеттер скрытого параметра записи.
    * @param string name
    * @param mixed value
    */
    public function __set(string $name, $value)
    {
        $this->_hidden[$name] = $value;
    }

    /**
    * Геттер скрытого параметра записи.
    * @param string name
    * @return mixed
    */
    public function __get(string $name)
    {
        return $this->_hidden[$name] ?? null;
    }

    /**
    * Проверка наличия скрытого параметра записи.
    * @param string name
    * @return bool
    */
    public function __isset(string $name): bool
    {
        return isset($this->_hidden[$name]) ? true : false;
    }

    /**
    * Удаление скрытого параметра записи.
    * @param string name
    */
    public function __unset(string $name)
    {
        unset($this->_hidden[$name]);
    }

    /**
    * Получение данных записи.
    * @param array|null запрашиваемые параметры
    * @return array
    */
    public function getValues(array $names = null): array
    {
        $values = get_object_vars($this);
        unset($values['_hidden']);
        if ($names) {
            foreach ($values as $name => $value) {
                if (! in_array($name, $names)) {
                    unset($values[$name]); 
                }
            }
        }
        return $values;
    }

    /**
    * Получение скрытых параметров записи.
    * @param array|null запрашиваемые параметры
    * @return array
    */
    public function getHideValues(array $names = null): array
    {
        $values = $values['_hidden'];
        if ($names) {
            foreach ($values as $name => $value) {
                if (! in_array($name, $names)) {
                    unset($values[$name]); 
                }
            }
        }
        return $values;
    }

    /**
    * Сохранение записи.
    * @param array|null параметры для сохранения
    * @return QueryResult
    * @return none
    */
    public function save(array $names = null)
    {
        $primaryKey = static::primaryKey();
        $primaryValue = $this->$primaryKey;
        if (null !== $primaryValue) {
            return static::getDb()->update(static::tableName(), $this->getValues($names))
                ->where("$primaryKey = ?", [$primaryValue])
                ->one();
        } else {
            $values = $this->getValues();
            unset($values[$primaryKey]);
            static::getDb()->insert(static::tableName(), $values);
            $this->$primaryKey = static::lastInsertId();
        }
    }

    /**
    * Удаление записи.
    * @return QueryResult
    */
    public function delete()
    {
        $primaryKey = static::primaryKey();
        $primaryValue = $this->$primaryKey;
        return static::getDb()->delete(static::tableName())
            ->where("$primaryKey = ?", [$primaryValue])
            ->one();
    }


    /**
    * Поиск записи.
    * @param string запрашиваемые поля
    * @return QueryBuilder
    */
    public static function find(string $fields = '*')
    {
        return static::getDb()->select(static::tableName(), $fields);
    }

    /**
    * Поиск записи по PRIMARY_KEY.
    * @param string|int значение
    * @return self
    */
    public static function findByPrimary($value)
    {
        $primaryKey = static::primaryKey();
        return static::getDb()->select(static::tableName())
            ->where("$primaryKey = ?", [$value])
            ->one()
            ->classObject(get_called_class());
    }

    /**
    * Поиск записи по id.
    * @param string|int id
    * @return self
    */
    public static function findById($id)
    {
        return static::getDb()->select(static::tableName())
            ->where('id = ?', [$id])
            ->one()
            ->classObject(get_called_class());
    }

    /**
    * Поиск записей по sql.
    * @param string sql
    * @param array|null параметры запроса
    * @return QueryResult
    */
    public static function findBySql(string $sql, array $params = null)
    {
        return static::getDb()->query($sql, $params);
    }
}
