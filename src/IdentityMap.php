<?php
/**
 * IdentityMap.
 * @package evas-php\evas-orm
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Orm;

use Evas\Orm\Identity\ModelIdentity;
use Evas\Orm\ActiveRecord;

class IdentityMap
{
    /** @static self единственный экземляр IdentityMap */
    protected static $instance;
    /** @var ActiveRecord[] модели */
    protected $models = [];

    /**
     * Получение экземпляра IdentityMap.
     * @return self
     */
    public static function instance()
    {
        if (!static::$instance) static::$instance = new static;
        return static::$instance;
    }

    /**
     * Конструктор.
     */
    protected function __construct()
    {
        $this->resetModels();
    }

    /**
     * Очистка моделей.
     */
    public function resetModels()
    {
        $this->models = [];
        return $this;
    }

    /**
     * Получение количества моделей в IdentityMap.
     * @return int
     */
    public static function count(): int
    {
        return count(static::instance()->models);
    }

    /**
     * Получение моделей IdentityMap.
     * @return array
     */
    public static function models(): array
    {
        return static::instance()->models;
    }

    /**
     * Проверка наличия модели в IdentityMap.
     * @param ActiveRecord модель
     * @return bool
     */
    public static function has(ActiveRecord $model): bool
    {
        return isset(static::instance()->models[$model->identity()]);
    }

    /**
     * Добавление модели в IdentityMap.
     * @param ActiveRecord модель
     * @return self
     */
    public static function set(ActiveRecord $model)
    {
        static::instance()->models[$model->identity()] = $model;
        return static::instance();
    }

    /**
     * Получение модели из IdentityMap или null.
     * @param ActiveRecord модель
     * @return ActiveRecord|null модель или null
     */
    public static function get(ActiveRecord $model)
    {
        return static::instance()->models[$model->identity()] ?? null;
    }

    /**
     * Получение модели с установкой в случае отсутствия.
     * @param ActiveRecord модель
     * @return ActiveRecord модель
     */
    public static function getWithSave(ActiveRecord $model)
    {
        if (!static::has($model)) {
            static::set($model);
            return $model;
        } else {
            $old = static::get($model);
            // sync state
            $props = $old->getUpdatedProps();
            $old->fill($model->getData());
            $old->saveState();
            $old->fill($props);
            return $old;
        }
    }

    /**
     * Удаление модели из IdentityMap.
     * @param ActiveRecord модель
     * @return self
     */
    public static function unset(ActiveRecord $model)
    {
        unset(static::instance()->models[$model->identity()]);
        return static::instance();
    }

    /**
     * Удаление всех моделей из IdentityMap.
     * @return self
     */
    public static function unsetAll()
    {
        return static::instance()->resetModels();
    }

    /**
     * Приведение IdentityMap к строке.
     * @return string
     */
    public function __toString()
    {
        return json_encode(['models_count' => static::count(), 'models' => $this->models]);
    }
}
