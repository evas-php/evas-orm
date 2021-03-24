<?php
/**
 * @package evas-php\evas-orm
 */
namespace Evas\Orm\Base;

use \PDO;
use \PDOStatement;

/**
 * Класс-обертка над PDOStatement для получения ответа запроса в удобном виде.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 1.0
 */
class QueryResult
{
    /**
     * @var PDOStatement
     */
    protected $stmt;

    /**
     * Конструктор.
     * @param PDOStatement
     */
    public function __construct(PDOStatement &$stmt)
    {
        $this->stmt = &$stmt;
    }

    /**
     * Получить statement ответа базы.
     * @return PDOStatement
     */
    public function stmt(): PDOStatement
    {
        return $this->stmt;
    }

    /**
     * Получить имя таблицы для select-запроса.
     * @return string|null
     */
    public function tableName(): ?string
    {
        if (0 > $this->stmt->columnCount()) return null;
        $columnMeta = $this->stmt->getColumnMeta(0);
        return $columnMeta['table'] ?? null;
    }

    /**
     * Получить количество возвращённых строк.
     * @return int
     */
    public function rowCount(): int
    {
        return $this->stmt->rowCount();
    }


    // Получение записи в разном виде

    /**
     * Получить запись в виде нумерованного массива.
     * @return numericArray|null
     */
    public function numericArray(): ?array
    {
        $row = $this->stmt->fetch(PDO::FETCH_NUM);
        return $row ? $row : null;
    }

    /**
     * Получить все записи в виде массива нумерованных массивов.
     * @return array
     */
    public function numericArrayAll(): array
    {
        return $this->stmt->fetchAll(PDO::FETCH_NUM);
    }

    /**
     * Получить запись в виде ассоциативного массива.
     * @return assocArray|null
     */
    public function assocArray(): ?array
    {
        $row = $this->stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row : null;
    }

    /**
     * Получить все записи в виде массива ассоциативных массивов.
     * @return array
     */
    public function assocArrayAll(): array
    {
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Получить запись в виде анонимного объекта.
     * @return stdClass|null
     */
    public function anonymObject(): ?object
    {
        $row = $this->stmt->fetch(PDO::FETCH_OBJ);
        return $this->objectHook($row ? $row : null);
    }

    /**
     * Получить все записи в виде массива анонимных объектов.
     * @return array
     */
    public function anonymObjectAll(): array
    {
        return $this->objectsHook($this->stmt->fetchAll(PDO::FETCH_OBJ));
    }

    /**
     * Получить запись в виде объекта класса.
     * @param string имя класса
     * @return object|null
     */
    public function classObject(string $className): ?object
    {
        $this->stmt->setFetchMode(PDO::FETCH_CLASS, $className);
        $row = $this->stmt->fetch();
        return $this->objectHook($row ? $row : null);
    }

    /**
     * Получить все записи в виде массива объектов класса.
     * @param string имя класса
     * @return array
     */
    public function classObjectAll(string $className): array
    {
        $this->stmt->setFetchMode(PDO::FETCH_CLASS, $className);
        $rows = $this->stmt->fetchAll();
        return $this->objectsHook($rows);
    }

    /**
     * Добавить параметры записи в объект.
     * @param object
     * @return object
     */
    public function intoObject(object &$object): object
    {
        $this->stmt->setFetchMode(PDO::FETCH_INTO, $object);
        return $this->stmt->fetch();
    }


    // Хуки

    /**
     * Проверка наличия хука.
     * @param object объект
     * @param string метод
     * @return bool
     */
    public static function hasHook(object &$object, string $method): bool
    {
        return method_exists($object, $method) ? true : false;
    }

    /**
     * Запуск хука.
     * @param object объект
     * @param string метод
     */
    public static function runHook(object &$object, string $method)
    {
        if (method_exists($object, $method)) {
            $object->$method();
        }
    }


    /**
     * Хук для постобработки полученного объекта записи.
     * @param object|null запись
     * @return object|null постобработанная запись
     */
    public function objectHook(object &$row = null): ?object
    {
        if (!empty($row) && is_object($row)) {
            static::runHook($row, 'afterFind');
        }
        return $row;
    }

    /**
     * Хук для постобработки полученных объектов записей.
     * @param array|null записи
     * @return array|null постобработанные записи
     */
    public function objectsHook(array &$rows = null): ?array
    {
        if (static::hasHook('afterFind')) foreach ($rows as &$row) {
            $row = $this->objectHook($row);
        }
        return $rows;
    }
}
