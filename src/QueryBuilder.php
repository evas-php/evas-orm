<?php
/**
* @package evas-php/evas-orm
*/
namespace Evas\Orm;

use Evas\Orm\Connection;
use Evas\Orm\JoinBuilder;

/**
* Сборщик запроса.
* @author Egor Vasyakin <e.vasyakin@itevas.ru>
* @since 1.0
*/
class QueryBuilder
{
    /**
    * @var Connection соединение с базой данных
    */
    public $connection;

    /**
    * @var string начало запроса и from
    */
    public $from;

    /**
    * @var array джоины
    */
    public $join = [];

    /**
    * @var string where часть
    */
    public $where;

    /**
    * @var string поля группировки
    */
    public $groupBy;

    /**
    * @var string поля сортировки
    */
    public $orderBy;

    /**
    * @var bool сортировать по убыванию
    */
    public $orderDesc = false;

    /**
    * @var int сдвиг поиска
    */
    public $offset;

    /**
    * @var int лимит выдачи
    */
    public $limit;

    /**
    * @var array параметры для экранирования
    */
    public $params = [];

    /**
    * Конструктор.
    * @param Connection
    */
    public function __construct(Connection &$connection)
    {
        $this->connection = $connection;
    }

    /**
    * Начало SELECT запроса.
    * @param string таблица
    * @param string поля
    * @return $this
    */
    public function select(string $tbl, string $fields = '*')
    {
        return $this->from("SELECT $fields FROM $tbl");
    }

    /**
    * Начало DELETE запроса.
    * @param string таблица
    * @return $this
    */
    public function delete(string $tbl)
    {
        return $this->from("DELETE FROM $tbl");
    }

    /**
    * Начало UPDATE запроса.
    * @param string таблица
    * @param string поля
    * @return $this
    */
    public function update(string $tbl, $row)
    {
        assert(is_array($row) || is_object($row));
        $i = 0;
        $upd = '';
        $vals = [];
        foreach ($row as $key => $val) {
            // $val = $this->connection->quote($val);
            if ($i > 0) {
                $upd .= ', ';
            }
            $upd .= "$key = ?";
            $vals[] = $val;
            $i++;
        }
        $this->params = array_merge($this->params, $vals);
        return $this->from("UPDATE $tbl SET $upd");
    }


    /**
    * Установка части FROM.
    * @param string часть from
    * @param array параметры для экранирования
    * @return $this
    */
    public function from(string $from, array $params = [])
    {
        $this->from = $from;
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    /**
    * Запуск сборщика JOIN.
    * @param string|null таблица
    * @return JoinBuilder
    */
    public function join(string $tbl = null)
    {
        return new JoinBuilder($this, 'INNER', $tbl);
    }

    /**
    * Запуск сборщика LEFT JOIN.
    * @param string|null таблица
    * @return JoinBuilder
    */
    public function leftJoin(string $tbl = null)
    {
        return new JoinBuilder($this, 'LEFT', $tbl);
    }

    /**
    * Запуск сборщика RIGHT JOIN.
    * @param string|null таблица
    * @return JoinBuilder
    */
    public function rightJoin(string $tbl = null)
    {
        return new JoinBuilder($this, 'RIGHT', $tbl);
    }

    /**
    * Запуск сборщика OUTER JOIN.
    * @param string|null таблица
    * @return JoinBuilder
    */
    public function outerJoin(string $tbl = null)
    {
        return new JoinBuilder($this, 'OUTER', $tbl);
    }

    /**
    * Добавление JOIN.
    * @param string join
    * @param array параметры для экранирования
    * @return $this
    */
    public function setJoin(string $join, array $params = [])
    {
        $this->join[] = $join;
        $this->params = array_merge($this->params, $params);
        return $this;
    }


    /**
    * Установка WHERE.
    * @param string where часть
    * @param array параметры для экранирования
    * @return $this
    */
    public function where(string $where, array $params = [])
    {
        $this->where = $where;
        $this->params = array_merge($this->params, $params);
        return $this;
    }


    /**
    * Установка GROUP BY.
    * @param string поля группировки
    * @return $this
    */
    public function groupBy(string $fields)
    {
        $this->groupBy = $fields;
        return $this;
    }

    /**
    * Установка ORDER BY.
    * @param string поля сортировки
    * @param bool сортировать по убыванию
    * @return $this
    */
    public function orderBy(string $fields, bool $desc = false)
    {
        $this->orderBy = $fields;
        $this->orderDesc = $desc;
        return $this;
    }

    /**
    * Установка OFFSET.
    * @param int сдвиг
    * @return $this
    */
    public function offset(int $offset)
    {
        $this->offset = $offset;
        return $this;
    }

    /**
    * Установка LIMIT.
    * @param int лимит
    * @param int|null сдвиг
    * @return $this
    */
    public function limit(int $limit, int $offset = null)
    {
        $this->limit = $limit;
        return $offset !== null ? $this->offset($offset) : $this;
    }


    /**
    * Получение sql.
    * @return string
    */
    public function getSql(): string
    {
        $sql = $this->from;
        if (!empty($this->join)) {
            $sql .= ' ' . implode(' ', $this->join);
        }
        if (!empty($this->where)) {
            $sql .= ' WHERE ' . $this->where;
        }
        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ' . $this->orderBy;
            if ($this->orderDesc === true) {
                $sql .= ' DESC';
            }
        }
        if (!empty($this->limit)) {
            $sql .= ' LIMIT ' . $this->limit; 
        }
        if (!empty($this->offset)) {
            $sql .= ' OFFSET ' . $this->offset;
        }
        return $sql;
    }

    /**
    * Получение одной записи.
    * @return QueryResult
    */
    public function one()
    {
        $this->limit(1);
        return $this->query();
    }

    /**
    * Получение записей.
    * @return QueryResult
    */
    public function query()
    {
        return $this->connection->query($this->getSql(), $this->params);
    }
}
