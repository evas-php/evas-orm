<?php
/**
 * Трейт связей для сборщика запросов.
 * @package evas-php\evas-orm
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Orm\Traits;

use Evas\Orm\ActiveRecord;
use Evas\Orm\Relation;

trait QueryBuilderRelationsTrait
{
    /** @var string разделитель для полей связанных записей */
    public static $relationDataSeparator = '_-_';

    protected $withs = [];
    protected $withsAfter = [];

    protected function getRelation(string $name): ?Relation
    {
        return $this->model::getRelation($name);
    }

    protected function addWith(string $name, $columns, $query = null)
    {
        $relation = $this->getRelation($name);
        if ($relation) {
            if ($relation->isOne()) $list = &$this->withs;
            else $list = &$this->withsAfter;
            // if (!isset($list[$relation->name])) {
            //     $list[$relation->name] = [$relation, $columns, $query];
            // }
            $list[$relation->name] = [$relation, $columns, $query];
        }
        echo title('addWith: ' . $name, 3);// . dumpOrm($relation);
        // echo dumpOrm($this->model::$relations);
        return $this;
    }

    public function with(...$props)
    {
        foreach ($props as &$prop) {
            if (is_string($prop)) {
                @[$name, $columns] = explode(':', $prop);
                $this->addWith($name, $columns);

            } else if (is_array($prop)) {
                foreach ($prop as $name => &$sub) {
                    $columns = null;
                    $query = null;

                    if (is_string($name)) {
                        if (is_string($sub) || is_array($sub)) {
                            $columns = $sub;
                        } else if (is_callable($sub)) {
                            @[$name, $columns] = explode(':', $name);
                            $relation = $this->getRelation($name);
                            if (!$relation) return;
                            $query = new static($this->db, $relation->foreignModel);
                            $sub($query);
                        }

                    } else if (is_string($sub)) {
                        @[$name, $columns] = explode(':', $sub);
                    } else {
                        throw new \InvalidArgumentException(sprintf(
                            'Incorrect arguments in method %s()',
                            __METHOD__
                        ));
                    }
                    $this->addWith($name, $columns, $query);
                }
            }
        }
        return $this;
    }

    protected function addHas(
        bool $isNot, bool $isWith, string $relationName, 
        $operator = null, $value = null
    ) {
        @[$relationName, $columns] = explode(':', $relationName);
        $relation = $this->getRelation($relationName);
        if ($relation) {
            $this->select(static::prepareModelColumns($this->columns, $this->model));
            if ($columns) {
                $columns = explode(',', $columns);
                foreach ($columns as $column) {
                    $this->whereNotNull($relation->foreignColumn($column, true));
                }
            }
            if (func_num_args() > 3) {
                if ($this->isQueryable($operator)) {
                    if ($operator instanceof \Closure) {
                        call_user_func($operator, $operator = $this->newQuery());
                    }
                    foreach ($operator->wheres as $where) {
                        if (isset($where['columns'])) 
                            foreach ($where['columns'] as &$column) {
                                $this->addColumnTablePrefix($column, $relationName);
                            }
                        if (isset($where['column'])) 
                            $where['column'] = $this->addColumnTablePrefix(
                                $where['column'], $relationName
                            );
                        $this->pushWhere($where['type'], $where);
                    }
                } else {
                    $this->prepareValueAndOperator(
                        $value, $operator, func_num_args() === 4
                    );
                    $foreignPrimary = $relation->foreignPrimary(true);
                    if ($columns) {
                        // isHas
                        $this->where(
                            $relation->foreignColumn($column, true), 
                            $operator, $value
                        );
                    } else {
                        $this->count($foreignPrimary);
                        $this->havingAggregate(
                            'count', $foreignPrimary, $operator, $value
                        );
                        if ($value === 0) $isNot = true;
                    }
                }
            }

            $args = [
                $relation->foreignTable, $relation->name, 
                $relation->foreignKey(), $relation->localFullKey
            ];

            if ($isNot) {
                if (func_num_args() > 3) {
                    $this->leftJoinSub(...$args);
                    $this->groupBy($relation->localFullKey);
                } else {
                    $this->leftOuterJoinSub(...$args);
                    $this->whereNull($relation->foreignKey());
                }
            } else {
                $this->joinSub(...$args);
                $this->groupBy($relation->localFullKey);
            }
        }

        return $this;
    }

    public function has(string $relationName, $operator = null, $value = null)
    {
        return $this->addHas(false, false, ...func_get_args());
    }

    public function notHas(string $relationName, $operator = null, $value = null)
    {
        return $this->addHas(true, false, ...func_get_args());
    }


    protected static function prepareColumns($columns): ?array
    {
        return (empty($columns) || '*' == $columns
        || (is_array($columns) && in_array('*', $columns))) 
        ? null : explode(',', str_replace(' ', '', $columns));
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

    protected function applyWiths()
    {
        // $withs = array_filter($this->withs, fn($with) => $with->isOne());
        if (1 > count($this->withs)) return;
        echo title('applyWiths', 3);
        $columns = static::prepareModelColumns($this->columns, $this->model);
        $this->select($columns);
        foreach ($this->withs as [$relation, $columns, $query]) {
            $this->applyWith($relation, $columns, $query);
        }
    }

    protected function applyWith(
        Relation $relation, $columns = null, self $query = null
    ) {
        $columns = static::prepareModelColumns(
            $columns, $relation->foreignModel, $relation->name
        );
        $this->select($columns);
        $this->leftJoinSub(
            $query ?? $relation->foreignTable, 
            $relation->name, 
            "{$relation->name}.{$relation->foreignKey}", 
            $relation->localFullKey
        );
        return $this;
    }

    protected function parseWiths(ActiveRecord &$model)
    {
        if (1 > count($this->withs)) return;
        // echo title('parseWiths', 3) . dumpOrm($model);
        $foreigns = [];
        foreach ($model->toArray() as $key => $value) {
            @[$fname, $fkey] = explode(static::$relationDataSeparator, $key, 2);
            if ($fkey && in_array($fname, array_keys($this->withs))) {
                $foreigns[$fname][$fkey] = $value;
                unset($model->$key);
            }
        }
        foreach ($foreigns as $fname => $subs) {
            // $this->withs[$fname][0]->addRelated($result, $subs);
            $model->addRelatedData($fname, $subs);
        }
    }
}
