<?php
namespace Evas\Orm\Traits;

use Evas\Orm\ActiveRecord;
use Evas\Orm\Relation;

trait QueryBuilderRelationsHasTrait
{
    protected $has = [];

    protected function addHas(
        bool $isNot, string $relationName, 
        $operator = null, $value = null
    ) {
        $this->has[] = func_get_args();
        return $this;
    }

    public function has(string $relationName, $operator = null, $value = null)
    {
        return $this->addHas(false, false, ...func_get_args());
    }

    public function orHas(string $relationName, $operator = null, $value = null)
    {
        return $this->addHas(true, false, ...func_get_args());
    }

    public function notHas(string $relationName, $operator = null, $value = null)
    {
        return $this->addHas(false, true, ...func_get_args());
    }

    public function orNotHas(string $relationName, $operator = null, $value = null)
    {
        return $this->addHas(true, true, ...func_get_args());
    }

    protected function applyHases()
    {
        if (1 > count($this->has)) return;
        // $columns = static::prepareModelColumns($this->columns, $this->model);
        // $this->select($columns);
        foreach ($this->has as $has) {
            $this->applyHas(...$has);
        }
    }

    protected function applyHas(
        bool $isOr, bool $isNot, string $relationName, $operator = null, $value = null
    ) {
        $relationNames = explode('.', $relationName);
        $model = $this->model;
        // $relations = [];
        // foreach ($relationNames as $name) {
        //     $relation = $model::getRelation($name);
        //     $model = $relation->foreignModel;
        //     $relations[] = $relation;
        // }
        // if (!count($relations)) return;

        $relationGroups = [];
        $dbName = null;
        $relations = [];
        foreach ($relationNames as $name) {
            $relation = $model::getRelation($name);
            $model = $relation->foreignModel;

            if ($dbName !== $model::dbName()) {
                if (!empty($relations)) {
                    $relationGroups[] = $relations;
                }
                $dbName = $model::dbName();
                $relations = [];
            }

            $relations[] = $relation;
        }
        if (!empty($relations)) {
            $relationGroups[] = $relations;
        }
        if (!count($relationGroups)) return;
        // foreach ($relationGroups as $relations) {
        //     foreach ($relations as $relation) {
        //         echo dumpOrm($relation);
        //     }
        //     echo '<hr>';
        // }
        // exit();


        if (func_num_args() > 3) {
            $this->prepareValueAndOperator(
                $value, $operator, func_num_args() === 4
            );
            $opval = [$operator, $value];
        } else {
            $opval = null;
        }

        
        // exit();

        // $relations = array_reverse($relations);
        // $query = $this->realApplyHas($relations, $operator, $value, $isCount);

        $last = count($relationGroups) - 1;
        if (0 === $last) {
            $relations = array_reverse($relationGroups[0]);
            $query = $this->realApplyHas($relations, $opval);

            if (count($relations) < 2 && $opval) {
                $this->where($query, ...$opval);
                echo dumpOrm($this->getSql());
                echo dumpOrm($this->getBindings());
            } else {
                $this->whereExists($query);
            }
        } else {
            $ids = null;
            foreach ($relationGroups as $i => $relations) {
                $ids = $this->applyGroupHas(
                    $relations, $ids, $last === $i ? $opval : null
                );
                // $relations = array_reverse($relations);
                // $query = $this->realApplyHas($relations, $last === $i ? $opval : null);
                // var_dump($query);
                // echo '<br>';
            }
            $this->whereIn($this->primaryKey(), $ids);
        }


        // if (count($relations) < 2 && $opval) {
        //     $this->where($query, ...$opval);
        //     echo dumpOrm($this->getSql());
        //     echo dumpOrm($this->getBindings());
        // } else {
        //     $this->whereExists($query);
        // }


        // if (in_array($operator, ['<', '<='])) {
        //     $query = $this->realApplyNotHas($relations, $operator);
        //     $this->orWhereNotExists($query);
        // }

        // $this->resetSelect('*');
        // $this->whereRaw('EXISTS (SELECT COUNT(`id`) AS `count_id` FROM `company` WHERE `user`.`id` = `company`.`user_id` AND (SELECT COUNT(`id`) AS `count_id` FROM `user` WHERE `company`.`user_id` = `user`.`id`) > 100)');
    }

    protected function applyGroupHas($relations, array $ids = null, array $opval = null)
    {
        echo dumpOrm($relations);
        $relations = array_reverse($relations);
        $last = count($relations) - 1;
        $q = clone $this;
        $q->db = $relations[0]->foreignModel::db();
        $q->withs = [];
        $q->has = [];
        $q->resetFrom($relations[0]->localModel::tableName());
        if ($ids) {
            $q->whereIn($relations[0]->localKey(true), $ids);
            $ids = null;
            // $q->resetSelect(['iid' => $relations[0]->foreignKey(true)]);
            array_pop($relations);
        } else {
            // 
        }
        $query = $q->realApplyHas($relations, $opval);
        if (count($relations) < 2 && $opval) {
            $q->where($query, ...$opval);
        } else {
            $q->whereExists($query);
        }
        echo '<div class="block gray"><h3 class="title">SQL:</h3>';
        echo dumpOrm($q->getSql());
        echo dumpOrm($q->getBindings());
        echo '</div>';
        $models = $q->get();
        echo dumpOrm($models);
        $ids = [];
        foreach ($models as $model) {
            $ids[] = $model->id;
        }
        echo dumpOrm($ids);
        return $ids;
    }

    protected function realApplyHas($relations, array $opval = null)
    {
        $query = null;
        foreach ($relations as $i => $relation) {
            $query = function ($q) use ($relation, $query, $opval, $i) {
                $q->from($relation->foreignTable);
                $q->whereColumn($relation->localKey(true), $relation->foreignKey(false));
                if ($query) {
                    if (1 == $i && $opval) $q->where($query, ...$opval);
                    else $q->whereExists($query);
                } else if (0 == $i && $opval) {
                    $q->count('id');
                }
            };
        }
        return $query;
    }

    // protected function realApplyHas($relations, $operator, $value, bool $isCount = false)
    // {
    //     $query = null;
    //     foreach ($relations as $i => $relation) {
    //         $query = function ($q) use (
    //             $relation, $query, $value, $operator, $isCount, $i
    //         ) {
    //             $q->from($relation->foreignTable);
    //             $q->whereColumn($relation->localKey(true), $relation->foreignKey(false));
    //             if ($query) {
    //                 if (1 == $i && $isCount) {
    //                     $q->where($query, $operator, $value);
    //                 } else {
    //                     $q->whereExists($query);
    //                 }
    //             } else if (0 == $i && $isCount) {
    //                 $q->count('id');
    //             }
    //         };
    //     }
    //     return $query;
    // }

    protected function realApplyNotHas($relations, $operator, bool $isAnd = false)
    {
        $query = null;
        foreach ($relations as $relation) {
            $query = (function ($q) use ($relation, $query) {
                $q->from($relation->foreignTable);
                $q->whereColumn($relation->localKey(true), $relation->foreignKey(false));
                if ($query) $q->whereExists($query);
            });
        }
        return $query;
    }


    //     @[$relationName, $columns] = explode('.', $relationName);
    //     $relation = $this->getRelation($relationName);
    //     if (!$relation) return;
    //     $hasOneWith = $this->withOne[$relationName] ?? null;
    //     // if ($hasOneWith)
    //     if ($columns) {
    //         $this->whereExists(function ($q) use ($relation, $columns) {
    //             $q->from($relation->foreignTable);
    //             foreach ($columns as $column) {
    //                 $q->whereColumn(
    //                     $relation->localColumn($column, true), 
    //                     $relation->foreignColumn($column, false)
    //                 );
    //             }
    //         });
    //     } else {
    //         $this->whereExists(function ($q) use ($relation) {
    //             $q->from($relation->foreignTable);
    //             $q->whereColumn($relation->localKey(true), $relation->foreignKey(false));
    //         });
    //     }
    // }


    // protected function applyHas(
    //     bool $isNot, string $relationName, $operator = null, $value = null
    // ) {
    //     @[$relationName, $columns] = explode(':', $relationName);
    //     $relation = $this->getRelation($relationName);
    //     if (!$relation) return;

    //     $this->select(static::prepareModelColumns($this->columns, $this->model));
    //     if ($columns) {
    //         $columns = explode(',', $columns);
    //         foreach ($columns as $column) {
    //             $this->whereNotNull($relation->foreignColumn($column, true));
    //         }
    //     }
    //     if (func_num_args() > 3) {
    //         if ($this->isQueryable($operator)) {
    //             if ($operator instanceof \Closure) {
    //                 call_user_func($operator, $operator = $this->newQuery());
    //             }
    //             foreach ($operator->wheres as $where) {
    //                 if (isset($where['columns'])) 
    //                     foreach ($where['columns'] as &$column) {
    //                         $this->addColumnTablePrefix($column, $relationName);
    //                     }
    //                 if (isset($where['column'])) 
    //                     $where['column'] = $this->addColumnTablePrefix(
    //                         $where['column'], $relationName
    //                     );
    //                 $this->pushWhere($where['type'], $where);
    //             }
    //         } else {
    //             $this->prepareValueAndOperator(
    //                 $value, $operator, func_num_args() === 4
    //             );
    //             $foreignPrimary = $relation->foreignPrimary(true);
    //             if ($columns) {
    //                 // isHas
    //                 foreach ($columns as $column) {
    //                     $this->where(
    //                         $relation->foreignColumn($column, true), 
    //                         $operator, $value
    //                     );
    //                 }
    //             } else {
    //                 $this->count($foreignPrimary);
    //                 $this->havingAggregate(
    //                     'count', $foreignPrimary, $operator, $value
    //                 );
    //                 if ($value === 0) $isNot = true;
    //             }
    //         }
    //     }

    //     $args = $relation->joinArgs(null, false);

    //     if ($isNot) {
    //         if (func_num_args() > 3) {
    //             $this->leftJoinSub(...$args);
    //             $this->groupBy($relation->localKey(true));
    //         } else {
    //             $this->leftOuterJoinSub(...$args);
    //             $this->whereNull($relation->foreignKey());
    //         }
    //     } else {
    //         $this->joinSub(...$args);
    //         $this->groupBy($relation->localKey(true));
    //     }
    // }
}
