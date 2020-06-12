<?php
/**
 * @package evas-php/evas-orm
 */
namespace Evas\Orm\Builders;

use Evas\Orm\Base\QueryResult;
use Evas\Orm\Builders\InsertBuilder;
use Evas\Orm\Builders\QueryBuilder;

/**
 * Константы для свойств трейта по умолчанию.
 */
if (!defined('EVAS_DATABASE_INSERT_BUILDER_CLASS')) {
    define('EVAS_DATABASE_INSERT_BUILDER_CLASS', InsertBuilder::class);
}
if (!defined('EVAS_DATABASE_QUERY_BUILDER_CLASS')) {
    define('EVAS_DATABASE_QUERY_BUILDER_CLASS', QueryBuilder::class);
}

/**
 * Трейт расширения класса соединения с базой данных подддержкой базовых запросов и сборщиков.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 1.0
 */
trait DatabaseBuildersTrait
{
    /**
     * @var string класс сборки INSERT-запросов
     */
    public $insertBuilderClass = EVAS_DATABASE_INSERT_BUILDER_CLASS;

    /**
     * @var string класс сборки SELECT/DELETE/UPDATE-запросов
     */
    public $queryBuilderClass = EVAS_DATABASE_QUERY_BUILDER_CLASS;


    // Вызов сборщиков

    /**
     * Вызов сборщика запросов QueryBuilder для SELECT/UPDATE/DELETE-запросов.
     * @return QueryBulder
     */
    public function buildQuery(): QueryBuilder
    {
        $queryBuilderClass = $this->queryBuilderClass;
        return new $queryBuilderClass($this);
    }


    // Работа с более практичными запросами.

    /**
     * Начало сборки INSERT-запроса.
     * @param string имя таблицы
     * @param array|object|null значения записи для сохранения с автосборкой
     * @return InsertBuilder|QueryResult
     */
    public function insert(string $tbl, $row = null)
    {
        $insertBuilderClass = $this->insertBuilderClass;
        $ib = new $insertBuilderClass($this, $tbl);
        return empty($row) ? $ib : $ib->row($row)->query();
    }

    /**
     * Вставка нескольких записей.
     * @param string имя таблицы
     * @param array значения записей
     * @param array|null столбцы записи
     * @return QueryResult
     */
    public function batchInsert(string $tbl, array $rows, array $columns): QueryResult
    {
        $ib = $this->insert($tbl);
        if (!empty($columns)) $ib->key($columns);
        return $ib->rows($rows)->query();
    }

    /**
     * Начало сборки SELECT-запроса.
     * @param string имя таблицы
     * @param string|null столбцы
     * @return QueryBuilder
     */
    public function select(string $tbl, string $columns = null): QueryBuilder
    {
        return $this->buildQuery()->select($tbl, $columns);
    }

    /**
     * Начало сборки UPDATE-запроса.
     * @param string имя таблицы
     * @param array|object значения записи
     * @return QueryBuilder
     */
    public function update(string $tbl, $row): QueryBuilder
    {
        return $this->buildQuery()->update($tbl, $row);
    }

    /**
     * Начало сборки DELETE-запроса.
     * @param string имя таблицы
     * @return QueryBuilder
     */
    public function delete(string $tbl): QueryBuilder
    {
        return $this->buildQuery()->delete($tbl);
    }

}
