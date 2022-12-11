<?php
namespace Evas\Orm\Traits;

use Evas\Orm\ActiveRecord;
use Evas\Orm\Relation;

trait QueryBuilderRelationsWithTrait
{
    protected $withOne = [];
    protected $withMany = [];

    // protected function addWith(string $name, $columns, self $query = null)
    // {
    //     $relation = $this->getRelation($name);
    //     if ($relation) {
    //         if ($relation->isOne()) $list = &$this->withOne;
    //         else $list = &$this->withMany;
    //         $list[$relation->name] = [$relation, $columns, $query];
    //     }
    //     // echo title('addWith: ' . $name, 3);// . dumpOrm($relation);
    //     // echo dumpOrm($this->model::$relations);
    //     return $this;
    // }

    // public function old_with(...$props)
    // {
    //     foreach ($props as &$prop) {
    //         if (is_string($prop)) {
    //             @[$name, $columns] = explode(':', $prop);
    //             $this->addWith($name, $columns);

    //         } else if (is_array($prop)) {
    //             foreach ($prop as $name => &$sub) {
    //                 $columns = null;
    //                 $query = null;

    //                 if (is_string($name)) {
    //                     if (is_string($sub) || is_array($sub)) {
    //                         $columns = $sub;
    //                     } else if (is_callable($sub)) {
    //                         @[$name, $columns] = explode(':', $name);
    //                         $relation = $this->getRelation($name);
    //                         if (!$relation) return;
    //                         $query = new static($this->db, $relation->foreignModel);
    //                         $sub($query);
    //                     }

    //                 } else if (is_string($sub)) {
    //                     @[$name, $columns] = explode(':', $sub);
    //                 } else {
    //                     throw new \InvalidArgumentException(sprintf(
    //                         'Incorrect arguments in method %s()',
    //                         __METHOD__
    //                     ));
    //                 }
    //                 $this->addWith($name, $columns, $query);
    //             }
    //         }
    //     }
    //     return $this;
    // }

    protected $withs = [];

    // public function with(...$props)
    // {
    //     $this->withs = array_merge($this->withs, $props);
    //     echo dumpOrm($this->withs);
    //     return $this;
    // }

    public function with(...$props)
    {
        // echo dumpOrm($props);
        $withs = [];
        foreach ($props as &$prop) {
            if (is_string($prop)) {
                $withs[] = $this->levels(explode('.', $prop));
            } else if (is_array($prop)) {
                $withs[] = $this->recursiveArrayWith($prop);
            }
        }
        $this->withs = array_merge_recursive(...$withs);
        // echo dumpOrm($this->withs);
        return $this;
    }

    protected function levels($levels, $value = [])
    {
        $levels = array_reverse($levels);
        $with = $value;
        foreach ($levels as $level) {
            $with = [$level => $with];
        }
        return $with;
    }

    protected function recursiveArrayWith(array $props)
    {
        $withs = [];
        foreach ($props as $i => $prop) {
            $key = is_string($i) ? $i : $prop;
            $val = is_string($i) ? $prop : [];

            if (is_array($val)) $val = $this->recursiveArrayWith($val);
            else if (is_string($val)) $val = [$val => []];
            
            // вложенность связей в ключе
            if (is_string($key)) {
                $levels = explode('.', $key);
                if (count($levels) > 1) {
                    $key = array_shift($levels);
                    $val = $this->levels($levels, $val);
                }
            }
            // слияние значений уже существующего ключа
            if (isset($withs[$key]) && is_array($withs[$key])) {
                if (is_numeric($val)) return;
                if (is_string($val)) $val = [$val];
                $val = array_merge($withs[$key], $val);
            }
            $withs[$key] = $val;
        }
        return $withs;
    }

    // protected function recursiveArrayWith(array $props)
    // {
    //     $withs = [];
    //     foreach ($props as $i => $prop) {
    //         $names = is_string($i) ? explode('.', $i) : [];
    //         if (is_string($prop)) {
    //             $withs[] = array_merge($names, explode('.', $prop));
    //         } else if (is_array($prop)) {
    //             $subs = $this->recursiveArrayWith($prop);
    //             foreach ($subs as $sub) {
    //                 $withs[] = array_merge($names, $sub);
    //             }
    //         }
    //     }
    //     return $withs;
    // }


    protected function applyWiths(array $ids, array &$models)
    {
        // // $withOne = array_filter($this->withOne, fn($with) => $with->isOne());
        // if (1 > count($this->withOne)) return;
        // // echo title('applyWiths', 3);
        // // $columns = static::prepareModelColumns($this->columns, $this->model);
        // // $this->select($columns);
        // foreach ($this->withOne as [$relation, $columns, $query]) {
        //     $this->applyWith($relation, $columns, $query);
        // }
        // return;
        if (1 > count($this->withs)) return;
        $keys = array_keys($this->withs);
        foreach ($keys as $key) {
            $this->applyWith($key, $this->withs[$key], $ids, $models);
        }
    }

    protected function applyWith($key, $val, array $ids, array &$models)
    {
        $relation = $this->getRelation($key);
        
        $qb = (new static($this->db, $relation->foreignModel));
        $qb->whereIn($relation->foreignKey, $ids);
        if (!empty($val)) $qb->with($val);
        $subModels = $qb->get();
        if (!$subModels) return;

        // $idsLocal = [];
        foreach ($subModels as $subModel) {
            // $idsLocal[] = $subModel->primaryValue();
            foreach ($models as $model) {
                if ($model->{$relation->localKey} == $subModel->{$relation->foreignKey}) {
                    $model->addRelated($relation->name, $subModel);
                    break;
                }
            }
        }
    }

    // protected function applyWith(
    //     Relation $relation, $columns = null, self $query = null
    // ) {
    //     $columns = static::prepareModelColumns(
    //         $columns, $relation->foreignModel, $relation->name
    //     );
    //     $this->select($columns);
    //     // $this->leftJoinSub(
    //     //     $query ?? $relation->foreignTable, 
    //     //     $relation->name, 
    //     //     "{$relation->name}.{$relation->foreignKey}", 
    //     //     $relation->localKey(true)
    //     // );
    //     $this->leftJoinSub(...$relation->joinArgs($query, true));
    //     return $this;
    // }

    protected function parseWiths(ActiveRecord &$model)
    {
        if (1 > count($this->withOne)) return;
        // echo title('parseWiths', 3) . dumpOrm($model);
        $foreigns = [];
        foreach ($model->toArray() as $key => $value) {
            @[$fname, $fkey] = explode(static::$relationDataSeparator, $key, 2);
            if ($fkey && in_array($fname, array_keys($this->withOne))) {
                $foreigns[$fname][$fkey] = $value;
                unset($model->$key);
            }
        }
        foreach ($foreigns as $fname => $subs) {
            // $this->withOne[$fname][0]->addRelated($result, $subs);
            $model->addRelatedData($fname, $subs);
        }
    }
}
