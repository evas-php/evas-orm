<?php
/**
 * Трейт связей для модели.
 * @package evas-php\evas-orm
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Orm\Traits;

use Evas\Base\Help\PhpHelp;
use Evas\Orm\ActiveRecord;
use Evas\Orm\OrmException;
use Evas\Orm\RelatedCollection;
use Evas\Orm\Relation;

trait RelationsTrait
{
    /** @static array связи */
    protected static $relations;
    /** @var array связанные коллекции моделей */
    protected $relatedCollections = [];

    /**
     * Установка связанной коллекции модели.
     * @param string имя связи
     * @param array связанные модели
     * @return self
     */
    public function setRelatedCollection(string $name, array $relateds)
    {
        unset($this->relatedCollections[$name]);
        foreach ($relateds as &$related) {
            $this->addRelated($name, $related);
        }
        return $this;
    }

    /**
     * Добавление связанной модели в коллекцию.
     * @param string имя связи
     * @param ActiveRecord модель
     * @return self
     */
    public function addRelated(string $name, ActiveRecord $related)
    {
        $relation = static::getRelation($name);
        if ($relation->type === 'hasOne') {
            if (!isset($this->relatedCollections[$name]) && $related->primaryValue()) {
                $this->relatedCollections[$name] = &$related;
            }
            return $this;
        }
        if (!isset($this->relatedCollections[$name])) {
            $this->relatedCollections[$name] = new RelatedCollection($this, static::getRelation($name));
        }
        if ($related->primaryValue()) {
            $this->relatedCollections[$name]->add($related);
        }
        return $this;
    }

    /**
     * Инициализация связей.
     * @throws \InvalidArgumentException
     */
    protected static function initRelations()
    {
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
        if (is_null(static::$relations)) {
            static::initRelations();
        }
        return static::$relations[$name] ?? null;
    }

    /**
     * Проверка наличия связи.
     * @param string имя связи
     * @return bool
     */
    public static function hasRelation(string $name): bool
    {
        if (is_null(static::$relations)) {
            static::initRelations();
        }
        return isset(static::$relations[$name]);
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
}
