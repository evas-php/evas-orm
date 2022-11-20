<?php
/**
 * Расширенный сборщик заросов для ActiveRecord.
 * @package evas-php\evas-orm
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Orm;

use Evas\Db\Builders\QueryBuilder as DbQueryBuilder;
use Evas\Db\Interfaces\DatabaseInterface;
use Evas\Orm\Traits\QueryBuilderRelationsTrait;

class QueryBuilder extends DbQueryBuilder
{
    use QueryBuilderRelationsTrait;

    /** @var string класс модели данных */
    protected $model;

    /**
     * Расширяем конструктор передачей класса модели.
     * @param DatabaseInterface
     * @param string|null класс модели
     */
    public function __construct(DatabaseInterface &$db, string $model = null)
    {
        parent::__construct($db);
        if ($model) $this->fromModel($model);
    }

    /**
     * Установка модели и проброс таблицы модели.
     * @param string модель
     * @return self
     * @throws \InvalidArgumentException
     */
    public function fromModel(string $model)
    {
        if (!class_exists($model, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Class by name "%s" passed to 1 argument of %s() does not exist', 
                $model, __METHOD__
            ));
        }
        $this->model = $model;
        $this->from($model::tableName());
        return $this;
    }


    /**
     * Переопределяем получение первичного ключа таблицы.
     * @return string
     */
    protected function primaryKey(): string
    {
        return $this->model::primaryKey();
    }

    /**
     * Выполнение select-запроса с получением нескольких записей.
     * @param array|null столбцы для получения
     * @return array найденные записи
     */
    public function get($columns = null): array
    {
        $this->applyWiths();
        if ($columns) $this->select(...func_get_args());
        $models = $this->query()->objectAll($this->model);
        foreach ($models as &$model) {
            $model = $model->identityMapSave();
            if (0 < count($this->withs)) $this->parseWiths($model);
        }
        $models = array_unique($models);
        return $models;
    }

     /**
     * Выполнение select-запроса с получением одной записи.
     * @param array|null столбцы для получения
     * @return array|null найденная запись
     */
    public function one($columns = null)
    {
        $this->applyWiths();
        if ($columns) $this->select(...func_get_args());
        $model = $this->limit(1)->query()->object($this->model);
        if ($model) {
            if (0 < count($this->withs)) $this->parseWiths($model);
            $model = $model->identityMapSave();
        }
        return $model;
        // return is_null($model) ? $model : $model->identityMapSave();
    }
}
