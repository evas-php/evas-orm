<?php
/**
 * @package evas-php/evas-orm
 */
namespace Evas\Orm\Base;

use \pdo;
use \PDOException;
use \PDOStatement;
use Evas\Orm\Base\Exception\DatabaseConnectionException;
use Evas\Orm\Base\Exception\DatabaseQueryException;
use Evas\Orm\Base\QueryError;
use Evas\Orm\Base\QueryResult;

/**
 * Константы для класса по умолчанию.
 */
if (!defined('EVAS_DATABASE_OPTIONS')) {
    define('EVAS_DATABASE_OPTIONS', [
        PDO::ATTR_EMULATE_PREPARES => false, // помогает с приведением типов из базы в php
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        // PDO::ATTR_CASE => PDO::CASE_LOWER,
        // PDO::ATTR_AUTOCOMMIT => false,
        // PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
        // PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
}
if (!defined('EVAS_DATABASE_DRIVER')) define('EVAS_DATABASE_DRIVER', 'mysql');
if (!defined('EVAS_DATABASE_HOST')) define('EVAS_DATABASE_HOST', 'localhost');
if (!defined('EVAS_DATABASE_CHARSET')) define('EVAS_DATABASE_CHARSET', 'utf8');


if (!defined('EVAS_DATABASE_QUERY_RESULT_CLASS')) {
    define('EVAS_DATABASE_QUERY_RESULT_CLASS', QueryResult::class);
}

/**
 * Базовый класс соединения с базой данных.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 1.0
 */ 
class Database
{
    /**
     * @var string драйвер
     */
    public $driver = EVAS_DATABASE_DRIVER;

    /**
     * @var string хост
     */
    public $host = EVAS_DATABASE_HOST;

    /**
     * @var string имя базы данных
     */
    public $dbname;

    /**
     * @var string имя пользователя
     */
    public $username;

    /**
     * @var string пароль пользователя
     */
    public $password;

    /**
     * @var array опции соединения
     */
    public $options = EVAS_DATABASE_OPTIONS;

    /**
     * @var string кодировка
     */
    public $charset = EVAS_DATABASE_CHARSET;

    /**
     * @var string класс ответов ORM
     */
    public $queryResultClass = EVAS_DATABASE_QUERY_RESULT_CLASS;

    /**
     * @var string класс исключения sql-запроса
     */
    public $databaseQueryExceptionClass = DatabaseQueryException::class;

    /**
     * @var string класс исключения соединения с базой
     */
    public $databaseConnectionExceptionClass = DatabaseConnectionException::class;

    /**
     * @var pdo
     */
    protected $pdo;


    /**
     * Конструктор.
     * @param array|null параметры
     */
    public function __construct(array $params = null)
    {
        if ($params) foreach ($params as $name => $value) {
            $this->$name = $value;
        }
    }

    // Работа с соединением

    /**
     * Открытие соединения.
     * @throws DatabaseConnectionException
     * @return self
     */
    public function open()
    {
        $dsn = "$this->driver:host=$this->host";
        if (!empty($this->dbname)) $dsn .= ";dbname=$this->dbname";
        if (!empty($this->charset)) $dsn .= ";charset=$this->charset";
        try {
            $this->pdo = new pdo($dsn, $this->username, $this->password, $this->options);
        } catch (PDOException $e) {
            $databaseConnectionExceptionClass = $this->databaseConnectionExceptionClass;
            throw new $databaseConnectionExceptionClass($e->getMessage());
        }
        return $this;
    }

    /**
     * Закрытие соединения.
     * @return self
     */
    public function close()
    {
        $this->pdo = null;
        return $this;
    }

    /**
     * Проверка открытости соединения.
     * @return bool
     */
    public function isOpen(): bool
    {
        return null !== $this->pdo ? true : false;
    }

    /**
     * Получить PDO.
     * @throws DatabaseConnectionException
     * @return pdo
     */
    public function getPdo(): pdo
    {
        if (! $this->isOpen()) $this->open();
        return $this->pdo;
    }


    // Работа с транзакциями

    /**
     * Проверка открытости транзакции.
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->getPdo()->inTransaction() ? true : false;
    }

    /**
     * Создание транзакции.
     * @return self
     */
    public function beginTransaction()
    {
        if (false === $this->inTransaction()) $this->getPdo()->beginTransaction();
        return $this;
    }

    /**
     * Отмена транзакции.
     * @return self
     */
    public function rollBack()
    {
        if (true === $this->inTransaction()) $this->getPdo()->rollBack();
        return $this;
    }

    /**
     * Коммит транзакции.
     * @return self
     */
    public function commit()
    {
        if (true === $this->inTransaction()) $this->getPdo()->commit();
        return $this;
    }


    // Работа с запросами


    /**
     * Получение подготовленного запроса.
     * @param string sql-запрос
     * @return PDOStatement
     */
    public function prepare(string $sql): PDOStatement
    {
        try {
            $sth = $this->getPdo()->prepare($sql);
        } catch (PDOException $e) {
            $databaseQueryExceptionClass = $this->databaseQueryExceptionClass;
            throw new $databaseQueryExceptionClass('Database prepare query error: ' . $e->getMessage());
        }
        return $sth;
    }

    /**
     * Выполнение подготовленного запроса.
     * @param array|null экранируемые параметры запроса
     * @throws DatabaseQueryException
     * @return QueryResult
     */
    public function execute(PDOStatement $sth, array $values = null): QueryResult
    {
        if (!$sth->execute($values)){
            $error = new QueryError($sth->errorInfo(), $sql, $values);
            $databaseQueryExceptionClass = $this->databaseQueryExceptionClass;
            throw new $databaseQueryExceptionClass($error, $error->getCode());
        }
        $queryResultClass = $this->queryResultClass;
        return new $queryResultClass($sth, $this);
    }

    /**
     * Запрос в базу.
     * @param string sql-запрос
     * @param array|null экранируемые параметры запроса
     * @throws DatabaseQueryException
     * @return QueryResult
     */
    public function query(string $sql, array $values = null): QueryResult
    {
        $sth = $this->prepare($sql);
        return $this->execute($sth, $values);
    }


    /**
     * Экранирование пользовательских данных для запроса.
     * @param mixed значение
     * @return string|numeric экранированное значение
     */
    public function quote($value)
    {
        if (null === $value) return 'NULL';
        if (is_numeric($value)) return $value;
        if (is_string($value)) return "'" . str_replace("'", '\\\'', $value) . "'";
        return serialize($value);
    }

    /**
     * Получить расширенную информацию об ошибке последнего запроса.
     * @return array|null
     */
    public function errorInfo(): ?array
    {
        return $this->getPdo()->errorInfo();
    }

    /**
     * Получить id последней вставленной записи.
     * @param string|null имя таблицы
     * @return int|null
     */
    public function lastInsertId(string $tbl = null): ?int
    {
        return $this->getPdo()->lastInsertId($tbl);
    }
}
