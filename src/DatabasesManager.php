<?php
/**
 * @package evas-php\evas-orm
 */
namespace Evas\Orm;

use Evas\Base\Helpers\PhpHelper;
use Evas\Orm\Base\Database as BaseDatabase;
use Evas\Orm\Database;
use Evas\Orm\Integrate\Exception\DatabaseNotInitializedException;

/**
 * Константы для параметров соединения по умолчанию.
 */
if (!defined('EVAS_DATABASE_CLASS')) define('EVAS_DATABASE_CLASS', Database::class);

/**
 * Менеджер соединений.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 1.1
 */
class DatabasesManager
{
    /**
     * @var string класс для соединения с базой данных.
     */
    public $databaseClass = EVAS_DATABASE_CLASS;
    
    /**
     * @var array массив соединений с базами данных
     */
    public $connections = [];

    /**
     * @var BaseDatabase последнее выбранное соединение
     */
    private $lastDatabase;

    /**
     * Установка соединения.
     * @param BaseDatabase
     * @param string|null имя соединения, если не задано подставляется dbname
     * @return self
     */
    public function set(BaseDatabase $connection, string $name = null)
    {
        if (empty($name)) $name = $connection->name ?? $connection->dbname;
        $this->connections[$name] = &$connection;
        $this->lastDatabase = $connection;
        return $this;
    }

    /**
     * Инициализация соединения.
     * @param array параметры соединения
     * @param string|null имя соединения, если не задано подставляется dbname
     * @return self
     */
    public function init(array $params, string $name = null)
    {
        if (PhpHelper::isAssoc($params)) {
            $this->set(new $this->databaseClass($params), $name);
        } else foreach ($params as &$subparams) {
            $this->init($subparams);
        }
        return $this;
    }

    /**
     * Получение соединения.
     * @param string имя соединения
     * @throws DatabaseNotInitializedException
     * @return BaseDatabase
     */
    public function get(string $name = null): BaseDatabase
    {
        if (null === $name) {
            if (empty($this->lastDatabase)) {
                throw new DatabaseNotInitializedException;
            }
            return $this->lastDatabase;
        } else {
            $connection = $this->_findDatabase($name);
            $this->lastDatabase = &$connection;
            return $connection;
        }
    }

    /**
     * Поиск соединения по имени.
     * @param string имя
     * @throws DatabaseNotInitializedException
     * @return BaseDatabase
     */
    private function _findDatabase(string $name): BaseDatabase
    {
        $connection = $this->connections[$name] ?? null;
        if (null === $connection) {
            throw new DatabaseNotInitializedException($name);
        }
        return $connection;
    }

    /**
     * Установка последнего соединения.
     * @param string имя
     * @throws DatabaseNotInitializedException
     * @return self
     */
    public function setLast(string $name)
    {
        $connection = $this->_findDatabase($name);
        $this->lastDatabase = &$connection;
        return $this;
    }
}
