<?php
/**
 * Трейт связей для ActiveRecord.
 * @package evas-php\evas-orm
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Orm\Traits;

use Evas\Base\Help\PhpHelp;
use Evas\Orm\Relation;

trait ActiveRecordRelationsTrait
{
    /** @static Relation[] связи */
    // protected static $relations;
    protected static $relations;

    /**
     * Описание связей модели.
     * @return array
     */
    protected static function relations(): array
    {
        return [];
    }
    

    /**
     * Установка множественной связи.
     * @param string класс внешней модели
     * @param string|null внешний ключ
     * @param string|null локальный ключ
     * @return Relation
     */
    public static function hasMany(
        string $foreignModel, string $foreignKey = null, string $localKey = null
    ): Relation {
        return new Relation('hasMany', static::class, $foreignModel, $foreignKey, $localKey);
    }

    /**
     * Установка единичной связи.
     * @param string класс внешней модели
     * @param string|null внешний ключ
     * @param string|null локальный ключ
     * @return Relation
     */
    public static function hasOne(
        string $foreignModel, string $foreignKey = null, string $localKey = null
    ): Relation {
        return new Relation('hasOne', static::class, $foreignModel, $foreignKey, $localKey);
    }

    /**
     * Установка единичной связи к родителю.
     * @param string класс внешней модели
     * @param string|null внешний ключ
     * @param string|null локальный ключ
     * @return Relation
     */
    public static function belongsTo(
        string $foreignModel, string $foreignKey = null, string $localKey = null
    ): Relation {
        return new Relation('belongsTo', static::class, $foreignModel, $foreignKey, $localKey);
    }


    /**
     * Инициализация связей.
     * @throws \InvalidArgumentException
     */
    protected static function initRelations()
    {
        if (!is_null(static::$relations)) return;
        $relations = static::relations();
        if ($relations) foreach ($relations as $name => &$relation) {
            if (!$relation instanceof Relation) {
                throw new \InvalidArgumentException(sprintf(
                    'Relation must be instance of %s, %s given', 
                    Relation::class, PhpHelp::getType($relation)
                ));
            }
            $relation->setName($name);
        }
        static::$relations = &$relations;
    }

    /**
     * Получение связи по имени.
     * @param string имя связи
     * @return Relation|null
     */
    public static function getRelation(string $name): ?Relation
    {
        static::initRelations();
        return static::$relations[$name] ?? null;
        // return static::relations()[$name] ?? null;
    }

    /**
     * Проверка наличия связи.
     * @param string имя связи
     * @return bool
     */
    public static function hasRelation(string $name): bool
    {
        static::initRelations();
        return isset(static::$relations[$name]);
        // return isset(static::relations()[$name]);
    }
}
