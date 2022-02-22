<?php
/**
 * Связь моделей.
 * @package evas-php\evas-orm
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Orm;

use Evas\Orm\ActiveRecord;

class Relation
{
    /** @var string тип связи */
    public $type;
    /** @var string класс локальной модели */
    public $localModel;
    /** @var string локальный ключ */
    public $localKey;
    /** @var string класс внешней модели */
    public $foreignModel;
    /** @var string внешний ключ */
    public $foreignKey;
    /** @var string имя связи */
    public $name;

    /**
     * Конструктор.
     * @param string тип связи
     * @param string класс локальной модели
     * @param string класс внешней модели
     * @param string|null локальный ключ
     * @param string|null внешний ключ
     */
    public function __construct(
        string $type, string $localModel, string $foreignModel, 
        string $foreignKey = null, string $localKey = null
    ) {
        if (!$foreignKey) $foreignKey = static::generateForeignKey($localModel);
        if (!$localKey) $localKey = $localModel::primaryKey();
        $this->type = $type;
        $this->localModel = $localModel;
        $this->localKey = $localKey;
        $this->foreignModel = $foreignModel;
        $this->foreignKey = $foreignKey;
        $this->localTable = $localModel::tableName();
        $this->foreignTable = $foreignModel::tableName();
        $this->localFullKey = "$this->localTable.$this->localKey";
        $this->foreignFullKey = "$this->foreignTable.$this->foreignKey";
        $this->name = $this->foreignTable;
    }

    /**
     * Генерация внешнего ключа.
     * @param string класс локальной модели
     * @return string сгенерированный ключ
     */
    protected static function generateForeignKey(string $localModel): string
    {
        $pk = $localModel::primaryKey();
        if ('id' === $pk) {
            $pk = $localModel::tableName();
            if (strlen($pk) > 1 && strrpos($pk, 's') == strlen($pk) - 1) {
                $pk = substr($pk, 0, strlen($pk) - 1);
            }
            $pk .= '_id';
        }
        return $pk;
    }

    /**
     * Установка имени связей модели.
     * @param string имя
     * @return self
     */
    public function setName(string $name)
    {
        $this->name = $name;
        $this->foreignFullKey = "$this->name.$this->foreignKey";
        return $this;
    }

    /**
     * Добавление связи с моделью.
     * @param ActiveRecord модель
     * @param array данные внешней модели
     * @return ActiveRecord модель
     */
    public function addRelated(ActiveRecord $model, array $foreignData)
    {
        return $model->addRelated($this->name, new $this->foreignModel($foreignData));
    }
}
