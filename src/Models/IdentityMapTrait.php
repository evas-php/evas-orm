<?php
/**
 * @package evas-php/evas-orm
 */
namespace Evas\Orm\Models;

use Evas\Orm\Base\Database;
use Evas\Orm\Models\Exception\IdentityMapEntityHasAlreadyException;
use Evas\Orm\Models\Exception\IdentityMapNotFoundEntityPrimaryValueException;

/**
 * Реализация IdentityMap.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 1.0
 */
trait IdentityMapTrait
{
    /**
     * @var array хранилище объектов записей 
     *  [
     *      className => [
     *          primary => [
     *              'object' => object, // актуальный объект записи
     *              'state' => assoc, // снимок последнего состояния объекта
     *          ],
     *      ], 
     *      ...
     *  ]
     */
    protected $states = [];

    /**
     * @var Database
     */
    protected $connection;

    /**
     * Получение ключа объекта записи.
     * @param object
     * @param string первичный ключ
     * @throws IdentityMapNotFoundEntityPrimaryValueException
     * @return array
     */
    public static function getKey(object &$object, string $primaryKey): array
    {
        $primary = $object->$primaryKey ?? null;
        if (empty($primary)) {
            throw new IdentityMapNotFoundEntityPrimaryValueException;
        }
        return [get_class($object), $primary];
    }

    /**
     * Конструктор.
     * @param Database
     */
    public function __construct(Database &$connection)
    {
        $this->connection = $connection;
    }


    // Работа с объектами

    /**
     * Установка объекта и состояния.
     * @param object
     * @param string первичный ключ
     * @throws IdentityMapEntityHasAlreadyException
     * @return object
     */
    public function set(object &$object, string $primaryKey): object
    {
        $className = get_class($object);
        list($className, $primary) = static::getKey($object, $primaryKey);
        if ($this->has($className, $primary)) {
            throw new IdentityMapEntityHasAlreadyException();
        }
        $this->states[$className][$primary] = [
            'object' => &$object,
            'state' => get_object_vars($object),
        ];
        return $object;
    }

    /**
     * Установка состояния.
     * @param array состояние
     * @param string имя класса
     * @param string|numeric значение первичного ключа
     */
    public function setState(array $state, string $className, $primary)
    {
        assert(is_numeric($primary) || is_string($primary));
        $this->states[$className][$primary]['state'] = $state;
    }

    /**
     * Установка состояния по объекту.
     * @param object
     * @param string первичный ключ
     */
    public function setStateByObject(object &$object, string $primaryKey)
    {
        list($className, $primary) = static::getKey($object, $primaryKey);
        $this->setState(get_object_vars($object), $className, $primary);
    }

    /**
     * Удаление объекта и состояния.
     * @param string имя класса
     * @param string|numeric значение первичного ключа
     * @return self
     */
    public function unset(string $className, string $primary)
    {
        $object = $this->get($className, $primary);
        if (isset($object)) {
            unset($object);
            unset($this->states[$className][$primary]);
        }
        return $this;
    }

    /**
     * Удаление объекта и состояния по объекту.
     * @param object
     * @param string первичный ключ
     * @return self
     */
    public function unsetByObject(object &$object, string $primaryKey)
    {
        list($className, $primary) = static::getKey($object, $primaryKey);
        return $this->unset($className, $primary);
    }

    /**
     * Получение объекта и состояния.
     * @param string имя класса
     * @param string|numeric значение первичного ключа
     * @return array|null
     */
    public function get(string $className, $primary): ?array
    {
        assert(is_numeric($primary) || is_string($primary));
        $state = $this->states[$className] ?? null;
        if (!empty($state)) {
            $state = $state[$primary] ?? null;
        }
        return $state;
    }

    /**
     * Получение объекта и состояния по объекту.
     * @param object
     * @param string первичный ключ
     * @return array|null
     */
    public function getByObject(object &$object, string $primaryKey): ?array
    {
        assert(is_numeric($primary) || is_string($primary));
        list($className, $primary) = static::getKey($object, $primaryKey);
        return $this->get($className, $primary);
    }

    /**
     * Получение состояния.
     * @param string имя класса
     * @param string|numeric значение первичного ключа
     * @return array|null
     */
    public function getState(string $className, string $primary): ?array
    {
        assert(is_numeric($primary) || is_string($primary));
        $state = $this->get($className, $primary);
        return $state['state'] ?? null;
    }

    /**
     * Получение состояния по объекту.
     * @param object
     * @param string первичный ключ
     * @return array|null
     */
    public function getStateByObject(object &$object, string $primaryKey): ?array
    {
        list($className, $primary) = static::getKey($object, $primaryKey);
        return $this->getState($className, $primary);
    }

    /**
     * Получение объекта.
     * @param string имя класса
     * @param string|numeric значение первичного ключа
     * @return object|null
     */
    public function getObject(string $className, $primary): ?object
    {
        assert(is_numeric($primary) || is_string($primary));
        $state = $this->get($className, $primary);
        return $state['object'] ?? null;
    }

    /**
     * Получение объекта по объекту.
     * @param object
     * @param string первичный ключ
     * @return object|null
     */
    public function getObjectByObject(object &$object, string $primaryKey): ?object
    {
        list($className, $primary) = static::getKey($object, $primaryKey);
        return $this->getObject($className, $primary);
    }

    /**
     * Проверка наличия объекта или состояния.
     * @param string имя класса
     * @param string|numeric значение первичного ключа
     * @return bool
     */
    public function has(string $className, $primary): bool
    {
        assert(is_numeric($primary) || is_string($primary));
        return $this->get($className, $primary) ? true : false;
    }

    /**
     * Проверка наличия объекта или состояния по объекту.
     * @param object
     * @param string первичный ключ
     * @return bool
     */
    public function hasByObject(object &$object, string $primaryKey): bool
    {
        list($className, $primary) = static::getKey($object, $primaryKey);
        return $this->has($className, $primary);
    }

    /**
     * Обвновление состояния.
     * @param array состояние
     * @param string имя класса
     * @param string|numeric значение первичного ключа
     */
    public function updateState(array $state, string $className, $primary)
    {
        assert(is_numeric($primary) || is_string($primary));
        return $this->setState($state, $className, $primary);
    }

    /**
     * Обновление состояние по объекту.
     * @param object
     * @param string первичный ключ
     */
    public function updateStateByObject(object &$object, string $primaryKey)
    {
        return $this->setStateByObject($object, $primaryKey);
    }

    /**
     * Обновление объекта и состояния.
     * @param object
     * @param string первичный ключ
     * @return object
     */
    public function update(object &$object, string $primaryKey): object
    {
        list($className, $primary) = static::getKey($object, $primaryKey);
        $old = $this->getObject($className, $primary);
        if (!empty($old)) {
            foreach ($object as $name => $value) {
                $old->$name = $value;
            }
            $this->updateStateByObject($old, $primaryKey);
            $object = &$old;
        } else {
            $this->set($object, $primaryKey);
        }
        return $object;
    }
}
