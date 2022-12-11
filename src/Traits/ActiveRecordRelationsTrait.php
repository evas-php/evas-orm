<?php
/**
 * Трейт связей для ActiveRecord.
 * @package evas-php\evas-orm
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Orm\Traits;

use Evas\Base\Help\PhpHelp;
use Evas\Orm\Relation;
use Evas\Orm\RelationsMap;

trait ActiveRecordRelationsTrait
{
    /**
     * Описание связей модели.
     * @return array
     */
    public static function relations(): array
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
        return new Relation(
            'hasMany', static::class, $foreignModel, $foreignKey, $localKey
        );
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
        return new Relation(
            'hasOne', static::class, $foreignModel, $foreignKey, $localKey
        );
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
        return new Relation(
            'belongsTo', static::class, $foreignModel, $foreignKey, $localKey
        );
    }


    /**
     * Получение связи по имени.
     * @param string имя связи
     * @return Relation|null
     */
    public static function getRelation(string $name): ?Relation
    {
        return RelationsMap::getRelation(static::class, $name);
    }

    /**
     * Проверка наличия связи.
     * @param string имя связи
     * @return bool
     */
    public static function hasRelation(string $name): bool
    {
        return RelationsMap::hasRelation(static::class, $name);
    }
}
