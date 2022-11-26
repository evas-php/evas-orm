<?php
/**
 * RelationsMap.
 * Хранит связи классов моделей.
 * @package evas-php\evas-orm
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Orm;

use Evas\Base\Help\PhpHelp;
use Evas\Orm\Exceptions\OrmException;
use Evas\Orm\Relation;

class RelationsMap
{
    /** @static Relation[] by models */
    protected static $relations = [];

    /**
     * Инициализция связей класса модели.
     * @param string класс модели
     * @throws OrmException
     */
    public static function initRelations(string $modelClass)
    {
        $relations = $modelClass::relations();
        if ($relations) foreach ($relations as $name => &$relation) {
            if (!$relation instanceof Relation) {
                throw new OrmException(sprintf(
                    'Relation for model %s must be instance of %s, %s given', 
                    $modelClass, Relation::class, PhpHelp::getType($relation)
                ));
            }
            $relation->setName($name);
        }
        static::$relations[$modelClass] = [];
        static::setRelations($modelClass, $relations);
    }

    /**
     * Получение связей класса модели.
     * @param string класс модели
     * @return array|null
     */
    public static function getRelations(string $modelClass): ?array
    {
        $relations = static::$relations[$modelClass] ?? null;
        if (is_null($relations)) {
            static::initRelations($modelClass);
        }
        return static::$relations[$modelClass] ?? null;
    }

    /**
     * Получение связи класса модели.
     * @param string класс модели
     * @param string имя связи
     * @return array|null
     */
    public static function getRelation(string $modelClass, string $name): ?Relation
    {
        return static::getRelations($modelClass)[$name] ?? null;
    }

    /**
     * Провеерка наличия связей класса модели.
     * @param string класс модели
     * @return bool
     */
    public static function hasRelations(string $modelClass): bool
    {
        return !is_null(static::getRelations($modelClass));
        // return isset(static::$relations[$modelClass]);
    }

    /**
     * Провеерка наличия связи класса модели.
     * @param string класс модели
     * @param string имя связи
     * @return bool
     */
    public static function hasRelation(string $modelClass, string $name): bool
    {
        return !is_null(static::getRelation($modelClass, $name));
    }

    /**
     * Установка связей класса модели.
     * @param string класс модели
     * @param Relation[] связи
     */
    public static function setRelations(string $modelClass, array $relations)
    {
        foreach ($relations as $relation) {
            static::setRelation($modelClass, $relation);
        }
    }

    /**
     * Установка связи класса модели.
     * @param string класс модели
     * @param Relation связь
     */
    public static function setRelation(string $modelClass, Relation $relation)
    {
        if (!static::hasRelations($modelClass)) {
            static::$relations[$modelClass] = []; 
        }
        static::$relations[$modelClass][$relation->name] = &$relation;
    }

    /**
     * Удаление связей класса модели или всех связей.
     * @param string|null класс модели
     */
    public static function unsetRelations(string $modelClass = null)
    {
        if (is_null($modelClass)) unset(static::$relations);
        else unset(static::$relations[$modelClass]);
    }

    /**
     * Удаление связи класса модели.
     * @param string класс модели
     * @param string имя связи
     */
    public static function unsetRelation(string $modelClass, string $name)
    {
        unset(static::$relations[$modelClass][$name]);
    }
}
