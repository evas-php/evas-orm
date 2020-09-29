<?php
/**
 * @package evas-php\evas-orm
 */
namespace Evas\Orm\Integrate;

use Evas\Base\Helpers\PhpHelper;
use Evas\Orm\Base\Database as BaseDatabase;
use Evas\Orm\Database;
use Evas\Orm\DatabasesManager;
use Evas\Orm\Integrate\AppDbClassTrait;
use Evas\Orm\Integrate\Exception\DatabaseConfigNotFoundException;
use Evas\Orm\Integrate\Exception\DatabaseNotInitializedException;

/**
 * Константы для параметров соединения по умолчанию.
 */
if (!defined('EVAS_DATABASES_MANAGER_CLASS')) {
    define('EVAS_DATABASES_MANAGER_CLASS', DatabasesManager::class);
}

/**
 * Расширение поддержки множества баз данных приложения.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 1.1
 */
trait AppDbsTrait
{
    /**
     * Подключаем трейт поддержки класса базы данных.
     */
    use AppDbClassTrait;

    /**
     * Установка класса менеджера баз данных.
     * @param string
     * @return self
     */
    public static function setDbsManagerClass(string $dbsManagerClass): object
    {
        return static::set('dbsManagerClass', $dbsManagerClass);
    }

    /**
     * Получение класса менеджера баз данных.
     * @param string
     */
    public static function getDbsManagerClass(): string
    {
        if (!static::has('dbsManagerClass')) {
            static::set('dbsManagerClass', EVAS_DATABASES_MANAGER_CLASS);
        }
        return static::get('dbsManagerClass');
    }

    /**
     * Установка менеджера соединений.
     * @param DatabasesManager
     * @return self
     */
    public static function setDbsManager(DatabasesManager $dbs): object
    {
        return static::set('dbs', $dbs);
    }

    /**
     * Получение менеджера соединений.
     * @return DatabasesManager
     */
    public static function getDbsManager(): object
    {
        if (!static::has('dbs')) {
            $dbs = new static::getDbsManagerClass();
            $dbs->databaseClass = static::getDbClass();
            static::setDbsManager($dbs);
        }
        return static::get('dbs');
    }

    /**
     * Установка соединения.
     * @param BaseDatabase
     * @param string|null имя соединения, если не задано подставляется dbname
     * @throws DatabaseNotInitializedException
     * @return self
     */
    public static function setDb(BaseDatabase $connection, string $name = null): object
    {
        static::getDbsManager()->set($connection, $name);
        return static::instance();
    }

    /**
     * Инициализация соединения.
     * @param array|null параметры соединения
     * @param string|null имя соединения, если не задано подставляется dbname
     * @throws DatabaseNotInitializedException
     * @return self
     */
    public static function initDb(array $params = null, string $name = null): object
    {
        static::getDbsManager()->init($params, $name);
        return static::instance();
    }

    /**
     * Получение соединения.
     * @param string имя соединения
     * @throws DatabaseNotInitializedException
     * @return Database
     */
    public static function getDb(string $name = null): object
    {
        return static::getDbsManager()->get($name);
    }

    /**
     * Получение соединения с автоинициализацией по конфигу.
     * @param string имя соединения
     * @throws DatabaseConfigNotFoundException
     * @throws DatabaseNotInitializedException
     * @return BaseDatabase
     */
    public static function db(string $name = null): BaseDatabase
    {
        try {
            return static::getDbsManager()->get($name);
        } catch (DatabaseNotInitializedException $e) {
            try {
                $config = static::getDbConfig();
            } catch (DatabaseConfigNotFoundException $e) {
                throw new DatabaseConfigNotFoundException($name);
            }
            static::initDb($config);
            if (empty($name)) {
                $name = PhpHelper::isAssoc($config)
                    ? @$config['name'] ?? $config['dbname']
                    : @$config[0]['name'] ?? $config[0]['dbname'];
            }
            return static::getDbsManager()->get($name);
        }
    }

    /**
     * Установка последнего соединения.
     * @param string имя
     * @throws DatabaseNotInitializedException
     * @return self
     */
    public static function setLastDb(string $name)
    {
        static::getDbsManager()->setLast($name);
        return static::instance();
    }
}
