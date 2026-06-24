<?php
/**
 * Трейт связанных записей и коллекций связанных записей для ActiveRecord.
 * @package evas-php\evas-orm
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Orm\Traits;

use Evas\Db\Builders\QueryBuilder;
use Evas\Orm\ActiveRecord;
use Evas\Orm\RelatedCollection;

trait ActiveRecordRelatedsTrait
{
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
        $related = $related->identityMapSave();
        if (!$relation) return $this;
        // if (false) {
        if ($relation->isOne()) {
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

    public function addRelatedData(string $name, array $data)
    {
        $relation = static::getRelation($name);
        if (!$relation) return $this;
        return $this->addRelated($name, new $relation->foreignModel($data));
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
    public function getRelatedCollection(string $name)//: ?RelatedCollection
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
     * Подгрузка связанной/связанных записей.
     * @param string $name имя связи
     * @return void
     */
    public function loadRelated(string $name)
    {
        $relation = $this->getRelation($name);
        $fModel = $relation->foreignModel;
        $fKey = $relation->foreignKey;
        $lKey = $relation->localKey;

        $query = $fModel::where($fKey, $this->$lKey);
        $relateds = $relation->isMany() ? $query->get() : $query->one();
        if (!$relateds || (is_array($relateds) && count($relateds) < 1)) return;
        
        if (!is_array($relateds)) $relateds = [$relateds];
        foreach ($relateds as $related) {
            if ($this->$lKey !== $related->$fKey) continue;
            $this->addRelated($relation->name, $related);
        }
    }
}
