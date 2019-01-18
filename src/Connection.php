<?php
/**
* @package evas-php/evas-orm
*/
namespace Evas\Orm;

use \Exception;
use \pdo;
use \PDOException;
use Evas\Di\ContainerTrait;
use Evas\Orm\QueryError;
use Evas\Orm\QueryBuilder;
use Evas\Orm\QueryResult;

/**
* Класс для соединения с базой данных.
* @author Egor Vasyakin <e.vasyakin@itevas.ru>
* @since 1.0
*/
class Connection
{
    /**
    * @var string драйвер базы данных
    */
    public $driver;

    /**
    * @var string хост базы данных
    */
    public $host = 'localhost';

    /**
    * @var string пользователь базы данных
    */
    public $username;

    /**
    * @var string пароль пользователя базы данных
    */
    public $password;

    /**
    * @var string имя базы данных
    */
    public $dbname;

    /**
    * @var array опции соединения
    */
    public $options;

    /**
    * @var string кодировка
    */
    public $charset = 'utf8';

    /**
    * @var \pdo
    */
    protected $_pdo;

    /**
    * @var string имя класса обработчика ошибок запросов базы данных
    */
    public $errorHandler = QueryError::class;

    /**
    * Конструктор.
    * @param array параметры
    */
    public function __construct(array $params = null)
    {
        if ($params) foreach ($params as $name => $value) {
            $this->$name = $value;
        }
    }

    /**
    * Открыть соединение с базой.
    * @throws \PDOException
    * @return Connection
    */
    public function open()
    {
        $dsn = $this->driver . ':host=' . $this->host . ';charset=' . $this->charset;
        if ($this->dbname) {
            $dsn .= ';dbname=' . $this->dbname;
        }
        try {
            $this->_pdo = new pdo($dsn, $this->username, $this->password, $this->options);
        } catch (PDOException $e) {
            throw new Exception('Database connection error: ' . $e->getMessage());
        }
        return $this;
    }

    /**
    * Закрыть соединение с базой.
    */
    public function close()
    {
        $this->_pdo = null;
    }

    /**
    * Открыто ли соединение с базой.
    * @return bool
    */
    public function isOpen(): bool
    {
        return $this->_pdo !== null ? true : false;
    }

    /**
    * Получить PDO.
    * @throws \Exception
    * @return \pdo
    */
    public function getPdo(): pdo
    {
        if (! $this->isOpen()) {
            throw new Exception('Database is not open');
        }
        return $this->_pdo;
    }



    /**
    * Создание транзакции.
    */
    public function beginTransaction()
    {
        $this->getPdo()->beginTransaction();
    }

    /**
    * Проверка транзакции.
    * @return bool
    */
    public function isTransaction()
    {
        return $this->getPdo()->isTransaction();
    }

    /**
    * Отмена транзакции.
    */
    public function rollBack()
    {
        $this->getPdo()->rollBack();
    }

    /**
    * Коммит транзакции.
    */
    public function commit()
    {
        $this->getPdo()->commit();
    }


    /**
    * Запрос в базу.
    * @param string sql
    * @param array|null параметры
    * @throws QueryError
    * @return QueryResult
    */
    public function query(string $sql, array $params = null)
    {
        try {
            $sth = $this->getPdo()->prepare($sql);
        } catch (PDOException $e) {
            throw new Exception('Database query error: ' . $e->getMessage());
        }
        if (!$sth->execute($params)){
            $error = new $this->errorHandler($sth->errorInfo(), $sql, $params);
            $error->handle();
        }
        return new QueryResult($sth);
    }

    /**
    * Экранирование пользовательских данных запроса.
    * @param mixed value
    * @return string|numeric quoted value
    */
    public function quote($value)
    {
        if ($value === null) {
            return 'NULL';
        } else if (is_numeric($value)) {
            return $value;
        } else if (is_string($value)) {
            return "'" . str_replace('\\\'', "'", $value) . "'";
        }
    }



    /**
    * Получить PDO::errorInfo().
    * @return array PDO::errorInfo()
    */
    public function errorInfo(){
        return $this->getPdo()->errorInfo();
    }

    /**
    * Получить id последней вставленной записи.
    * @param string table name
    * @return int lastInsertId
    */
    public function lastInsertId(string $tbl = null)
    {
        return $this->getPdo()->lastInsertId($tbl);
    }


    /**
    * Добавление записи в таблицу.
    * @param string table name
    * @param array row
    * @return QueryResult
    */
    public function insert(string $tbl, array $row)
    {
        $keys = array_keys($row);
        $vals = array_values($row);
        return $this->query("INSERT INTO $tbl (" . implode(', ', $keys) . ') VALUES (' . implode(', ', array_map(function () {
            return '?';
        }, $vals)) . ')', $vals);
    }

    /**
    * Добавление нескольких записей в таблицу.
    * @param string table name
    * @param array rows
    * @return QueryResult
    */
    public function batchInsert(string $tbl, array $rows)
    {
        $vals = [];
        $quotes = [];
        foreach ($rows as &$row) {
            $values = array_values($row);
            $vals = array_merge($vals, $values);
            $quotes[] = '(' . implode(', ', array_map(function () {
                return '?';
            }, $values)) . ')';
        }
        $keys = array_keys($row);
        return $this->query("INSERT INTO $tbl (" . implode(', ', $keys) . ') VALUES ' . implode(', ', $quotes), $vals);
    }

    /**
    * Сборка запроса через Orm\QueryBuilder.
    * @return QueryBuilder
    */
    public function buildQuery(): QueryBuilder
    {
        return new QueryBuilder($this);
    }

    /**
    * Запрос SELECT.
    * @param string таблица
    * @param string олучаемые ключи
    * @return QueryBuilder
    */
    public function select(string $tbl, string $select = '*'): QueryBuilder
    {
        return $this->buildQuery()->select($tbl, $select);
    }

    /**
    * Запрос UPDATE.
    * @param string таблица
    * @param array|object row
    * @return QueryBuilder
    */
    public function update(string $tbl, $row): QueryBuilder
    {
        return $this->buildQuery()->update($tbl, $row);
    }

    /**
    * Запрос DELETE.
    * @param string таблица
    * @return QueryBuilder
    */
    public function delete(string $tbl): QueryBuilder
    {
        return $this->buildQuery()->delete($tbl);
    }
}
