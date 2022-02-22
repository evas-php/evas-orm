<?php
/**
 * Коллекция моделей связанных с моделью.
 * @package evas-php\evas-orm
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Orm;

use Evas\Base\Help\Collection;
use Evas\Base\Help\PhpHelp;
use Evas\Orm\ActiveRecord;
use Evas\Orm\Relation;

class RelatedCollection extends Collection
{
    /** @var ActiveRecord модель */
    protected $model;
    /** @var string связь */
    protected $relation;
    /** @var array значения первичных ключей моделей коллекции */
    protected $ids = [];

    /**
     * Получение текущей модели коллекции.
     * @return ActiveRecord|null модель
     */
    public function current(): ?ActiveRecord
    {
        return parent::current();
    }

    /**
     * Проверка наличия модели в коллекции.
     * @param int|string значение первичного ключа
     * @return bool
     */
    public function has(int $id): bool
    {
        return in_array($id, $this->ids);
    }

    /**
     * Добавление элемента в коллекцию.
     * @param mixed элемент
     * @return self
     */
    public function add($item)
    {
        if (!($item instanceof $this->relation->foreignModel)) {
            $item = new $this->relation->foreignModel($item);
        }
        if (!($id = $item->primaryValue()) || !$this->has($id)) {
            $this->ids[] = $id;
            return parent::add($item);
        }
        return $this;
    }

    /**
     * Конструктор.
     * @param array|Collection|null элементы для коллекции
     */
    public function __construct(ActiveRecord $model, Relation $relation, $items = null)
    {
        $this->model = &$model;
        $this->relation = $relation;
        parent::__construct($items);
    }

    /**
     * Перезапрос связанных записей.
     * @return self
     */
    public function reload()
    {
        $this->items = $this->relation->foreignModel
        ::where($this->relation->foreignKey, $this->model->{$this->relation->localKey})
        ->get();
        return $this;
    }

    /**
     * Сохраенение связанных записей.
     * @return self
     */
    public function save()
    {
        foreach ($this->items as &$item) {
            $item->save();
        }
        return $this;
    }
}
