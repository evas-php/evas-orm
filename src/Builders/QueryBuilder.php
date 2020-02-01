<?php
/**
 * @package evas-php/evas-orm
 */
namespace Evas\Orm\Builders;

use Evas\Orm\Base\Database;
use Evas\Orm\Base\QueryResult;
use Evas\Orm\Builders\JoinBuilder;
use Evas\Orm\Builders\QueryValuesTrait;

/**
 * Сборщик запроса SELECT/UPDATE/DELETE.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 1.0
 */
class QueryBuilder
{
    /**
     * Подключаем поддержку работы со значениями запроса.
     */
    use QueryValuesTrait;

    /**
     * @var Database соединение с базой данных
     */
    public $database;

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
     * @var int сдвиг поиска
     */
    public $offset;

    /**
     * @var int лимит выдачи
     */
    public $limit;

    /**
     * Конструктор.
     * @param Database
     */
    public function __construct(Database &$database)
    {
        $this->database = $database;
    }

    /**
     * Начало SELECT запроса.
     * @param string имя таблицы
     * @param string поля
     * @return self
     */
    public function select(string $tbl, string $columns = null)
    {
        if (empty($columns)) $columns = '*';
        return $this->from("SELECT $columns FROM $tbl");
    }

    /**
     * Начало DELETE запроса.
     * @param string имя таблицы
     * @return self
     */
    public function delete(string $tbl)
    {
        return $this->from("DELETE FROM $tbl");
    }

    /**
     * Начало UPDATE запроса.
     * @param string имя таблицы
     * @param string|array|object значения записи или sql-код
     * @param array значения для экранирования
     * @return self
     */
    public function update(string $tbl, $row, array $vals = [])
    {
        assert(is_array($row) || is_object($row) || is_string($row));
        if (is_array($row) || is_object($row)) { 
            $upd = [];
            foreach ($row as $key => $val) {
                $upd[] = "$key = ?";
                $vals[] = $val;
            }
            $upd = implode(', ', $upd);
        } else {
            $upd = $row;
        }
        $this->values = array_merge($this->values, $vals);
        return $this->from("UPDATE $tbl SET $upd");
    }


    /**
     * Установка части FROM.
     * @param string часть from
     * @param array параметры для экранирования
     * @return self
     */
    public function from(string $from, array $values = [])
    {
        $this->from = $from;
        $this->values = array_merge($this->values, $values);
        return $this;
    }

    /**
     * Запуск сборщика INNER JOIN.
     * @param string|null имя таблицы
     * @return JoinBuilder
     */
    public function innerJoin(string $tbl = null): JoinBuilder
    {
        return new JoinBuilder($this, 'INNER', $tbl);
    }

    /**
     * Запуск сборщика LEFT JOIN.
     * @param string|null имя таблицы
     * @return JoinBuilder
     */
    public function leftJoin(string $tbl = null): JoinBuilder
    {
        return new JoinBuilder($this, 'LEFT', $tbl);
    }

    /**
     * Запуск сборщика RIGHT JOIN.
     * @param string|null имя таблицы
     * @return JoinBuilder
     */
    public function rightJoin(string $tbl = null): JoinBuilder
    {
        return new JoinBuilder($this, 'RIGHT', $tbl);
    }

    /**
     * Запуск сборщика OUTER JOIN.
     * @param string|null имя таблицы
     * @return JoinBuilder
     */
    public function outerJoin(string $tbl = null): JoinBuilder
    {
        return new JoinBuilder($this, 'OUTER', $tbl);
    }

    /**
     * Запуск сборщика INNER JOIN (алиас для innerJoin).
     * @param string|null имя таблицы
     * @return JoinBuilder
     */
    public function join(string $tbl = null): JoinBuilder
    {
        return $this->innerJoin($tbl);
    }

    /**
     * Добавление JOIN.
     * @param string join
     * @param array параметры для экранирования
     * @return self
     */
    public function setJoin(string $join, array $values = [])
    {
        $this->join[] = $join;
        return $this->bindValues($values);
    }


    /**
     * Установка WHERE.
     * @param string where часть
     * @param array параметры для экранирования
     * @return self
     */
    public function where(string $where, array $values = [])
    {
        if (!empty($this->where)) $this->where .= ' ';
        $this->where .= $where;
        return $this->bindValues($values);
    }

    /**
     * Установка WHERE IN.
     * @param string имя поля
     * @param array массив значений сопоставления
     * @return self
     */
    public function whereIn(string $key, array $values)
    {
        if (!empty($this->where)) $this->where .= ' ';
        $this->where .= "$key IN (" . implode(',', array_fill(0, count($values), '?')) . ')';
        return $this->bindValues($values);
    }


    /**
     * Установка GROUP BY.
     * @param string столбцы группировки
     * @return self
     */
    public function groupBy(string $columns)
    {
        $this->groupBy = $columns;
        return $this;
    }

    /**
     * Установка ORDER BY.
     * @param string столбцы сортировки
     * @return self
     */
    public function orderBy(string $columns)
    {
        $this->orderBy = $columns;
        return $this;
    }

    /**
     * Установка OFFSET.
     * @param int сдвиг
     * @return self
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
     * @return self
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
        if (!empty($this->groupBy)) {
            $sql .= ' GROUP BY ' . $this->groupBy;
        }
        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ' . $this->orderBy;
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
    public function one(): QueryResult
    {
        return $this->query(1);
    }

    /**
     * Получение записей.
     * @param int|null limit
     * @return QueryResult
     */
    public function query(int $limit = null): QueryResult
    {
        if ($limit > 0) $this->limit($limit);
        return $this->database->query($this->getSql(), $this->values());
    }
}
