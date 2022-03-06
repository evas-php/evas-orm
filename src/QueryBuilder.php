<?php
/**
 * Сборщик запросов для моделей ORM.
 * @package evas-php\evas-orm
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Orm;

use Evas\Db\Builders\QueryBuilder as DbQueryBuilder;
use Evas\Db\Interfaces\DatabaseInterface;
// use Evas\Orm\Model;
use Evas\Orm\ActiveRecord;
use Evas\Orm\Relation;

class QueryBuilder extends DbQueryBuilder
{
    /** @var string разделитель для полей связанных записей */
    public static $relationDataSeparator = '_-_';

    /** @var string класс модели данных */
    protected $model;

    /** @var array обработанный результат запроса в виде моделей */
    protected $result = [];


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
     */
    public function fromModel(string $model)
    {
        $this->model = $model;
        $this->from($model::tableName());
        return $this;
    }

    /**
     * Преобразование полученых записей.
     * @param array|null записи в виде массива
     * @return array записи в виде моделей
     */
    private function prepareGetResult(array $rows): array
    {
        foreach ($rows as $row) {
            $this->prepareOneResult($row);
        }
        return array_values($this->result);
    }

    /**
     * Преобразование полученой записи.
     * @param array|null запись в виде массива
     * @return ActiveRecord запись в виде модели
     */
    private function prepareOneResult(array $row = null)
    {
        if (!$row) return $row;
        $foreigns = [];
        if (count($this->withs) > 0) {
            foreach ($row as $key => $value) {
                @[$fname, $fkey] = explode(static::$relationDataSeparator, $key, 2);
                if ($fkey && in_array($fname, array_keys($this->withs))) {
                    $foreigns[$fname][$fkey] = $value;
                    unset($row[$key]);
                }
            }
        }
        $id = $row[$this->model::primaryKey()];
        if (!isset($this->result[$id])) {
            $this->result[$id] = new $this->model($row);
        }
        $result = $this->result[$id];
        foreach ($foreigns as $fname => $subs) {
            $this->withs[$fname][0]->addRelated($result, $subs);
        }
        return $result;
    }

    /**
     * Выполнение select-запроса с получением результирующих строк в виде массива моделей.
     * @param array|null столбцы
     * @return array модели
     */
    public function get($columns = null): ?array
    {
        $this->result = [];
        $this->applyWiths();
        $rows = parent::get($columns);
        return (
            count($this->aggregates) < 1 && count($this->groups) < 1 
            && count($this->withs) >= count($this->joins)
        ) 
        ? $this->prepareGetResult($rows)
        : $rows;
    }

    /**
     * Выполнение select-запроса с получением результирующей строки в виде модели.
     * @param array|null столбцы
     * @return ActiveRecord|null
     */
    public function one($columns = null): ?ActiveRecord
    {
        $this->result = [];
        if (count($this->withs)) {
            $rows = $this->limit(1)->get($columns);
            return count($rows) ? $rows[0] : null;
        }
        $row = parent::one($columns);
        return $this->prepareOneResult($row);
    }

    protected function applyWiths()
    {
        if (count($this->withs)) {
            if ($this->limit) {
                // $this->fromSub(function ($query) {
                //     $query->from($this->from)->limit($this->limit);
                // }, $this->from);
                // $this->fromSub($this->getSql(), $this->from);
                $this->fromSub($this, $this->from);
                $this->bindings['where'] = [];
                $this->wheres = [];
                $this->limit(null);
            }
            $columns = static::prepareModelColumns($this->columns, $this->model);
            $this->addSelect($columns);
            foreach ($this->withs as [$relation, $columns, $query]) {
                $this->applyWith($relation, $columns, $query);
            }
        }
    }

    protected function applyWith(Relation $relation, array $columns = null, self $query = null)
    {
        $columns = static::prepareModelColumns($columns, $relation->foreignModel, $relation->name);
        $this->addSelect($columns);
        $this->leftJoinSub($query ?? $relation->foreignTable, $relation->name, $relation->foreignFullKey, $relation->localFullKey);
        return $this;
    }

    protected $withs = [];

    protected function addWith(string $name, array $columns = null, self $query = null)
    {
        $relation = $this->getRelation($name);
        if ($relation) {
            if (!isset($this->withs[$relation->name])) {
                $this->withs[$relation->name] = [$relation, $columns, $query];
            }
        }
        return $this;
    }

    public function with(...$props)
    {
        foreach ($props as &$prop) {
            if (is_string($prop)) {
                @list($name, $columns) = explode(':', $prop);
                $this->addWith($name, $columns);

            } else if (is_array($prop)) {
                foreach ($prop as $name => &$sub) {
                    $columns = null;
                    $query = null;
                    if (is_string($name)) {
                        if (is_string($sub)) {
                            $columns = $sub;
                        } else if (is_callable($sub)) {
                            @list($name, $columns) = explode(':', $name);
                            $relation = $this->getRelation($name);
                            if (!$relation) continue;
                            $query = new static($this->db, $relation->foreignModel);
                            $sub($query);
                        }
                    } else if (is_string($sub)) {
                        @list($name, $columns) = explode(':', $sub);
                    } else {
                        throw new \InvalidArgumentException('Incorrect with syntax');
                    }
                    $this->addWith($name, $columns, $query);
                }
            }
        }
        return $this;
    }

    protected function addHas(
        bool $isNot, bool $isWith, string $relationName, $operator = null, $value = null
    ) {
        @[$relationName, $columns] = explode(':', $relationName);
        $relation = $this->getRelation($relationName);
        if ($relation) {
            $selfColumns = static::prepareModelColumns($this->columns, $this->model);
            $this->select($selfColumns);
            if ($isNot) {
                $this->leftOuterJoinSub($relation->foreignTable, $relation->name, $relation->foreignFullKey, $relation->localFullKey);
                $this->whereNull($relation->foreignFullKey);
            } else {
                if (func_num_args() > 3) {
                    if ($this->isQueryable($operator)) {
                        if ($operator instanceof \Closure) {
                            $cb = $operator;
                            $cb($operator = $this->forSubQuery());
                        }
                        foreach ($operator->wheres as $where) {
                            if (isset($where['columns'])) foreach($where['columns'] as &$column) {
                                $parts = explode('.', $column);
                                if (count($parts) < 2 || $parts[0] != $relationName) {
                                    array_unshift($parts, $relation);
                                    $column = implode('.', $parts);
                                }
                            }
                            $this->pushWhere($where['type'], $where);
                        }
                        $this->addBindings('where', $operator->getBindings('where'));
                    } else {
                        [$value, $operator] = $this->prepareValueAndOperator(
                            $value, $operator, func_num_args() === 4
                        );
                    }
                    if ($columns) {
                        $columns = explode(',', $columns);
                        foreach ($columns as $column) {
                            $column = $relation->name . '.' . $column;
                            // $this->count($column);
                            // $this->havingAggregate('count', $column, $operator, $value);
                            $this->where($column, $operator, $value);
                            // $this->whereNotNull($column);
                            // $this->where($column, '!=', 0);
                        }
                    } else {
                        $foreignFullPrimary = $relation->name . '.' . $relation->foreignModel::primaryKey();
                        $this->count($foreignFullPrimary);
                        $this->havingAggregate('count', $foreignFullPrimary, $operator, $value);
                    }
                } else if ($columns) {
                    $columns = explode(',', $columns);
                    foreach ($columns as $column) {
                        $column = $relation->name . '.' . $column;
                        // $this->count($column);
                        // $this->havingAggregate('count', $column, $operator, $value);
                        // $this->where($column, $operator, $value);
                        $this->whereNotNull($column);
                        // $this->where($column, '!=', 0);
                    }
                    // $columns = static::prepareModelColumns($columns, $relation->foreignModel, $relation->name);
                }
                $this->joinSub($relation->foreignTable, $relation->name, $relation->foreignFullKey, $relation->localFullKey);
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

    public function withHas(string $relationName, $operator = null, $value = null)
    {
        return $this->addHas(false, true, ...func_get_args());
    }


    protected function getRelation(string $name): ?Relation
    {
        return $this->model::getRelation($name);
    }

    // protected function parseTableWithColumns(string $value)
    // {
    //     @list($table, $columns) = explode(':', $value);
    //     return [$table, $columns];
    // }

    protected static function prepareModelColumns($columns, string $model, string $asPrefix = null)
    {
        $columns = static::prepareColumns($columns);
        if (!$columns) $columns = $model::columns();
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

    protected static function prepareColumns($columns): ?array
    {
        return (empty($columns) || $columns = '*' || (is_array($columns) && in_array('*', $columns))) 
        ? null 
        : explode(',', str_replace(' ', '', $columns));
    }

    // public function with(...$props)
    // {
    //     // $relations = [];
    //     foreach ($props as &$prop) {
    //         if (is_string($prop)) {
    //             @list($name, $columns) = explode(':', $prop);
    //             $columns = $this->prepareColumns($columns)
    //             $relation = $this->model->$name();
    //             // $this->base->leftJoin($relation->name)
    //             // $sql = 'SELECT * FROM `user`'
    //             // . ' LEFT JOIN `group` ON `group`.`user_id` = `user`.`id`';
    //             // $relation = $this->getRelation($name);
    //         } else if (is_array($prop)) {
    //             foreach ($prop as $name => &$sub) {
    //                 $columns = null;
    //                 if (is_string($name)) {
    //                     if (is_string($sub)) {
    //                         $columns = $sub;
    //                     } else if (is_callable($sub)) {
    //                         @list($name, $columns) = $name;
    //                         $relation = $this->model->$name();
    //                         $sub(new static($relation));
    //                     }
    //                 } else if (is_string($sub)) {
    //                     @list($name, $columns) = explode(':', $sub);
    //                 } else {
    //                     throw new \Exception('Incorrect!');
    //                 }
    //                 $columns = $this->prepareColumns($columns);
    //                 $relation = $this->model->$name();
    //                 // $relation = $this->getRelation($name);
    //             }
    //         }
    //     }
    // }

    public function withCount(...$props)
    {}

    public function withSum(...$props)
    {}

    public function withMax(...$props)
    {}

    public function withMin(...$props)
    {}

    public function withAvg(...$props)
    {}
    
    public function withExists(...$props)
    {}
}
