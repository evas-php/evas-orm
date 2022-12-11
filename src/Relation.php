<?php
/**
 * Связь моделей.
 * @package evas-php\evas-orm
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Orm;

use Evas\Orm\ActiveRecord;
use Evas\Orm\QueryBuilder;

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
        if (!$foreignKey) {
            // $foreignKey = static::generateForeignKey($localModel);
            $foreignKey = ('belongsTo' === $type)
            ? $foreignModel::primaryKey()
            : static::generateForeignKey($localModel);
        }
        if (!$localKey) {
            // $localKey = $localModel::primaryKey();
            $localKey = ('belongsTo' === $type)
            ? static::generateForeignKey($foreignModel)
            : $localModel::primaryKey();
        }
        $this->type = $type;
        $this->localModel = $localModel;
        $this->foreignModel = $foreignModel;

        $this->localKey = $localKey;
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
            if (($len = strlen($pk)) > 1 && strrpos($pk, 's') === $len - 1) {
                $pk = substr($pk, 0, $len - 1);
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
        return $this;
    }

    /**
     * Получение внешнего столбца.
     * @param string столбец
     * @param bool|null исользовать ли имя связи вместо имение таблицы
     * @return string
     */
    public function foreignColumn(string $column, bool $useName = false): string
    {
        return ($useName ? $this->name : $this->foreignTable) .'.'. $column;
    }

    /**
     * Получение внешнего первичного ключа.
     * @param bool|null исользовать ли имя связи вместо имение таблицы
     * @return string
     */
    public function foreignPrimary(bool $useName = false): string
    {
        return $this->foreignColumn($this->foreignModel::primaryKey(), $useName);
    }

    /**
     * Получение внешнего ключа связи.
     * @param bool|null исользовать ли имя связи вместо имение таблицы
     * @return string
     */
    public function foreignKey(bool $useName = false): string
    {
        return $this->foreignColumn($this->foreignKey, $useName);
    }

    /**
     * Получение локального столбца.
     * @param bool|null исользовать ли полный ключ
     * @return string
     */
    public function localColumn(string $column, bool $useFull = true): string
    {
        return ($useFull ? ($this->localTable.'.') : '') . $column;
    }

    /**
     * Получение локального ключа связи.
     * @param bool|null исользовать ли полный ключ
     * @return string
     */
    public function localKey(bool $useFull = true): string
    {
        return $this->localColumn($this->localKey, $useFull);
    }

    /**
     * Получение аргументов для join.
     * @param QueryBuilder
     */
    public function joinArgs(QueryBuilder $query = null, bool $useName = false): array
    {
        return [
            $query ?? $this->foreignTable, $this->name, 
            $this->foreignKey($useName), $this->localKey(true)
        ];
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

    /**
     * Является ли связь связью к одному.
     * @return bool
     */
    public function isOne(): bool
    {
        return in_array($this->type, ['hasOne', 'belongsTo']);
    }

    /**
     * Является ли связь связью ко многим.
     * @return bool
     */
    public function isMany(): bool
    {
        return !$this->isOne();
    }


    /**
     * Приведение к строке.
     * @return string
     */
    public function __toString()
    {
        return json_encode([
            'localFullKey' => $this->localFullKey, 
            'foreignFullKey' => $this->foreignFullKey
        ], JSON_UNESCAPED_UNICODE);
        // return json_encode(get_object_vars($this), JSON_UNESCAPED_UNICODE);
    }
}
