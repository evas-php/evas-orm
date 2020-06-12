<?php
/**
 * @package evas-php/evas-orm
 */
namespace Evas\Orm\Models;

use Evas\Orm\Base\QueryResult;
use Evas\Orm\Builders\QueryBuilder;
use Evas\Orm\Scheme\TableScheme;

/**
 * Класс таблицы базы данных.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 1.0
 */
class Table extends TableScheme
{
    // Table CRUD

    /**
     * Начало сборки INSERT-запроса.
     * @param array|object|null значения записи для сохранения с автосборкой
     * @return InsertBuilder|QueryResult
     */
    public function insert($row = null)
    {
        $this->database->insert($this->table, $row);
    }

    /**
     * Вставка нескольких записей.
     * @param array значения записей
     * @param array|null ключи записи
     * @return QueryResult
     */
    public function batchInsert(array $rows, array $keys = null): QueryResult
    {
        return $this->database->batchInsert($this->table, $rows, $keys);
    }

    /**
     * Начало сборки SELECT-запроса.
     * @param string|null столбцы
     * @return QueryBuilder
     */
    public function select(string $columns = null): QueryBuilder
    {
        return $this->database->select($this->table, $columns);
    }

    /**
     * Начало сборки UPDATE-запроса.
     * @param array|object значения записи
     * @return QueryBuilder
     */
    public function update($row): QueryBuilder
    {
        return $this->database->update($this->table, $row);
    }

    /**
     * Начало сборки DELETE-запроса.
     * @return QueryBuilder
     */
    public function delete(): QueryBuilder
    {
        return $this->database->delete($this->table);
    }

    /**
     * Получение id последней вставленной записи.
     * @return int|null
     */
    public function lastInsertId(): ?int
    {
        return $this->database->lastInsertId($this->table);
    }
}
