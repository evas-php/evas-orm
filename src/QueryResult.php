<?php
/**
 * @package evas-php\evas-orm
 */
namespace Evas\Orm;

use \PDOStatement;
use Evas\Orm\Base\Database;
use Evas\Orm\Base\QueryResult as BaseQueryResult;

/**
 * Класс-обертка базового QueryResult с добавление IdentityMap.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 1.0
 */
class QueryResult extends BaseQueryResult
{
    /**
     * @var Database
     */
    protected $database;

    /**
     * Конструктор.
     * @param PDOStatement
     */
    public function __construct(PDOStatement &$stmt, Database &$database)
    {
        $this->stmt = &$stmt;
        $this->database = $database;
    }

    /**
     * Переопределяем хук для постобработки полученного объекта записи.
     * @param object|null запись
     * @return object|null постобработанная запись
     */
    public function objectHook(object $row = null): ?object
    {
        if (!empty($row) && is_object($row)) {
            $row = $this->identityMapHookUpdate($row);
        }
        return $row;
    }

    /**
     * Переопределяем хук для постобработки полученных объектов записей.
     * @param array|null записи
     * @return array|null постобработанные записи
     */
    public function objectsHook(array $rows = null): ?array
    {
        if (!empty($rows)) foreach ($rows as &$row) {
            $row = $this->objectHook($row);
        }
        return $rows;
    }


    // Добавленные хуки

    /**
     * Хук для обновления сущности в IdentityMap.
     * @param object
     */
    public function identityMapHookUpdate(object &$object)
    {
        $table = $this->tableName();
        $primaryKey = $this->database->table($table)->primaryKey();
        return $this->database->identityMapUpdate($object, $primaryKey);
    }
}
