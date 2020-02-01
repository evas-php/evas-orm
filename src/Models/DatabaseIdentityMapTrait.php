<?php
/**
 * @package evas-php/evas-orm
 */
namespace Evas\Orm\Models;

use Evas\Orm\Models\IdentityMap;

/**
 * Константы для свойств класс базы данных по умолчанию.
 */
if (!defined('EVAS_DATABASE_IDENTITY_MAP_CLASS')) {
    define('EVAS_DATABASE_IDENTITY_MAP_CLASS', IdentityMap::class);
}

/**
 * Трейт расширения базы данных IdentityMap.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 1.0
 */
trait DatabaseIdentityMapTrait
{
    /**
     * @var string класс IdentityMap
     */
    protected $identityMapClass = EVAS_DATABASE_IDENTITY_MAP_CLASS;

    /**
     * @var IdentityMap
     */
    protected $identityMap;

    /**
     * Получение IdentityMap соединения.
     * @return IdentityMap
     */
    public function identityMap(): IdentityMap
    {
        if (empty($this->identityMap)) {
            $this->identityMap = new $this->identityMapClass($this);
        }
        return $this->identityMap;
    }

    /**
     * Обновление записи в IdentityMap и возвращение объекта.
     * @param object
     * @param string первичный ключ
     * @return object
     */
    public function identityMapUpdate(object &$object, string $primaryKey): object
    {
        return $this->identityMap()->update($object, $primaryKey);
    }

    /**
     * Получение состояния объекта из IdentityMap.
     * @param object
     * @param string первичный ключ
     * @return array|null
     */
    public function identityMapGetStateByObject(object &$object, string $primaryKey): ?array
    {
        return $this->identityMap()->getStateByObject($object, $primaryKey);
    }

    /**
     * Удаление записи из IdentityMap.
     * @param object
     * @param string первичный ключ
     */
    public function identityMapUnsetByObject(object &$object, string $primaryKey)
    {
        $this->identityMap()->unsetByObject($object, $primaryKey);
    }

    /**
     * Очистка IdentityMap.
     */
     public function identityMapClear()
     {
        $this->identityMap = null;
     }
}
