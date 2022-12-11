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

    // public function with(...$props)
    // {
    //     $this->withs = array_merge($this->withs, $props);
    //     echo dumpOrm($this->withs);
    //     return $this;
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
