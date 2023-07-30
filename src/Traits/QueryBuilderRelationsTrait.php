<?php
/**
 * Трейт связей для сборщика запросов.
 * @package evas-php\evas-orm
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Orm\Traits;

use Evas\Orm\ActiveRecord;
use Evas\Orm\Relation;
use Evas\Orm\Traits\QueryBuilderRelationsHasTrait;
use Evas\Orm\Traits\QueryBuilderRelationsWithTrait;

trait QueryBuilderRelationsTrait
{
    use QueryBuilderRelationsHasTrait;
    use QueryBuilderRelationsWithTrait;

    /** @var string разделитель для полей связанных записей */
    public static $relationDataSeparator = '_-_';

    protected function getRelation(string $name): ?Relation
    {
        return $this->model::getRelation($name);
    }


    protected static function prepareColumns($columns): ?array
    {
        // if (empty($columns) || '*' == $columns) {
        //     return null;
        // }
        // if (is_array($columns)) {
        //     if (in_array('*', $columns)) return null;
        //     else return $columns;
        // }

        return (empty($columns) || '*' === $columns) ? null : (
            is_array($columns) ? (in_array('*', $columns) ? null : $columns)
            : explode(',', str_replace(' ', '', $columns))
        );

        // return (empty($columns) || '*' == $columns
        // || (is_array($columns) && in_array('*', $columns))) 
        // ? null : explode(',', str_replace(' ', '', $columns));
    }

    protected static function prepareModelColumns(
        $columns, string $model, string $asPrefix = null
    ) {
        $columns = static::prepareColumns($columns) ?? $model::columns();
        $table = $model::tableName();
        $keys = [];
        foreach ($columns as &$column) {
            if (!empty($asPrefix)) {
                $as = $asPrefix . static::$relationDataSeparator . $column;
                $col = "{$asPrefix}.{$column}";
            } else {
                $as = $column;
                $col = "{$table}.{$column}";
            }
            $keys[$as] = $col;
        }
        return $keys;
    }

    protected function addColumnTablePrefix(string $column, string $table)
    {
        $parts = explode('.', $column);
        if (count($parts) < 2) {
            array_unshift($parts, $table);
            $column = implode('.', $parts);
        }
        return $column;
    }

    protected function applyRelationsBefore()
    {
        // // if (1 > count($this->withOne) && 1 > count($this->has)) return;
        // if (1 > count($this->withs) && 1 > count($this->has)) return;
        // $columns = static::prepareModelColumns($this->columns, $this->model);
        // $this->select($columns);
        // $this->applyWiths();
        $this->applyHases();
    }

    protected function applyRelationsAfter(array $ids, array &$models)
    {
        if (1 > count($this->withs)) return;
        $this->applyWiths($ids, $models);
        // if (1 > count($this->withMany)) return;
        // foreach ($this->withMany as [$relation, $columns, $query]) {
        //     $qb = (new static($this->db, $relation->foreignModel));
        //     $subModels = $qb->whereIn($relation->foreignKey, $ids)->get();
        //     if (!$subModels) continue;
        //     $ids = [];
        //     foreach ($subModels as $subModel) {
        //         $ids[] = $subModel->primaryValue();
        //         foreach ($models as $model) {
        //             if ($model->{$relation->localKey} == $subModel->{$relation->foreignKey}) {
        //                 $model->addRelated($relation->name, $subModel);
        //                 break;
        //             }
        //         }
        //     }
        //     if (0 < count($ids)) {
        //         $this->applyRelationsAfter($ids, $subModels);
        //     }
        // }
    }
}
