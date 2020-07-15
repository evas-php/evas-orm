<?php
/**
 * @package evas-php\evas-orm
 */
namespace Evas\Orm\Scheme;

use Evas\Orm\Base\Database;
use Evas\Orm\OrmException;
use Evas\Orm\Scheme\ColumnScheme;
use Evas\Orm\Scheme\Exception\NotFoundColumnException;

/**
 * Класс схемы таблицы.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 1.0
 */
class TableScheme
{
    /**
     * @var Database
     */
    public $database;

    /**
     * @var string мя таблицы
     */
    public $table;

    /**
     * @var array массив схем столбцов
     */
    protected $scheme;

    /**
     * @var array массив столбцов таблицы
     */
    protected $columns;

    /**
     * @var string первичный ключ
     */
    protected $primaryKey;

    /**
     * Конструктор.
     * @param Database
     * @param string имя таблицы
     */
    public function __construct(Database &$database, string $table)
    {
        $this->database = &$database;
        $this->table = $table;
    }

    /**
     * Получение первичного ключа таблицы.
     * @throws OrmException
     * @return string
     */
    public function primaryKey(): string
    {
        if (empty($this->primaryKey)) {
            $row = $this->database->query("SHOW KEYS FROM `$this->table` WHERE Key_name = 'PRIMARY'")->assocArray();
            $this->primaryKey = $row['Column_name'];
        }
        if (empty($this->primaryKey)) {
            throw new OrmException("Primary key does not exist in table \"$this->table\"");
        }
        return $this->primaryKey;
    }

    /**
     * Получение схемы столбцов.
     * @return array of ColumnScheme
     */
    public function columnSchemes(): array
    {
        if (null === $this->scheme) {
            $rows = $this->database->query("SHOW COLUMNS FROM `{$this->database->dbname}`.`$this->table`")->assocArrayAll();
            $scheme = [];
            if ($rows) foreach ($rows as &$row) {
                $scheme[$row['Field']] = new ColumnScheme($row);
            }
            $this->scheme = $scheme;
        }
        return $this->scheme;
    }

    /**
     * Получение схемы колонки.
     * @param string имя колонки
     * @throws NotFoundColumnException
     * @return ColumnScheme
     */
    public function columnScheme(string $column): ColumnScheme
    {
        $scheme = $this->columnScheme();
        if (empty($scheme[$column])) {
            throw new NotFoundColumnException("Not found column `$column` in table `{$this->database->dbname}`.`$this->table`");
        }
        return $scheme[$column];
    }

    /**
     * Получение столбцов таблицы.
     * @return array
     */
    public function columns(): array
    {
        if (null === $this->columns) {
            $scheme = $this->columnSchemes();
            $this->columns = array_keys($scheme);
        }
        return $this->columns;
    }
}
