<?php
/**
 * @package evas-php/evas-orm
 */
namespace Evas\Orm\Builders;

use Evas\Base\Helpers\PhpHelper;
use Evas\Orm\Base\Database;
use Evas\Orm\Base\QueryResult;
use Evas\Orm\Builders\QueryValuesTrait;
use Evas\Orm\Builders\Exception\InsertBuilderEmptyKeysException;
use Evas\Orm\Builders\Exception\InsertBuilderEmptyRowsException;
use Evas\Orm\Builders\Exception\InsertBuilderNotSetRowValueException;

/**
 * Сборщик INSERT-запроса.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 1.0
 */
class InsertBuilder
{
    /**
     * Подключаем поддержку работы со значениями запроса.
     */
    use QueryValuesTrait;

    /**
     * @var string имя таблицы
     */
    public $tbl;

    /**
     * @var array ключи вставляемых значений записи
     */
    public $keys;

    /**
     * @var int счетчик количества вставляемых записей
     */
    protected $rowCount = 0;

    /**
     * Конструктор.
     * @param Database соединение с базой данных
     * @param string имя таблицы
     */
    public function __construct(Database &$connection, string $tbl)
    {
        $this->connection = &$connection;
        $this->tbl = &$tbl;
    }

    /**
     * Установка ключей вставляемых значений записи.
     * @param array
     * @return self
     */
    public function keys(array $keys)
    {
        $this->keys = &$keys;
        return $this;
    }

    /**
     * Установка значений записи.
     * @param array|object
     * @throws InsertBuilderNotSetRowValueException
     * @return self
     */
    public function row($row)
    {
        assert(is_array($row) || is_object($row));
        if (is_object($row)) $row = array_values($row);
        if (PhpHelper::isAssoc($row)) {
            if (empty($this->keys)) $this->keys(array_keys($row));
            foreach ($this->keys as &$key) {
                $this->bindValue($row[$key] ?? 'NULL');
            }
        } else {
            $this->bindValues($row);
        }
        $this->rowCount++;
        return $this;
    }

    /**
     * Установка значений нескольких записей.
     * @param array
     * @return self
     */
    public function rows(array $rows)
    {
        foreach ($rows as &$row) { $this->row($row); }
        return $this;
    }

    /**
     * Получение собранного sql-запроса.
     * @throws InsertBuilderEmptyRowsException
     * @throws InsertBuilderEmptyKeysException
     * @return string
     */
    public function getSql(): string
    {
        if ($this->rowCount == 0) throw new InsertBuilderEmptyRowsException();
        if (empty($this->keys)) throw new InsertBuilderEmptyKeysException();
        $keys = '('. implode(', ', $this->keys) .')';
        $quote = '('. implode(', ', array_fill(0, count($this->keys), '?')) .')';
        if ($this->rowCount > 1) {
            $quote = implode(', ', array_fill(0, $this->rowCount, $quote));
        }
        $sql = "INSERT INTO $this->tbl $keys VALUES $quote";
        return $sql;
    }

    /**
     * Выполнение sql-запроса.
     * @return QueryResult
     */
    public function query(): QueryResult
    {
        return $this->connection->query($this->getSql(), $this->values());
    }
}
