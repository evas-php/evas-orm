<?php
/**
 * @package evas-php/evas-orm
 */
namespace Evas\Orm\Integrate;

use Evas\Base\PhpHelper;
use Evas\Orm\Base\Database;
use Evas\Orm\Integrate\AppDbConfigTrait;
use Evas\Orm\Integrate\DatabasesManager;
use Evas\Orm\Integrate\Exception\DatabaseConfigNotFoundException;
use Evas\Orm\Integrate\Exception\DatabaseNotInitializedException;

/**
 * Расширение поддержки множества баз данных приложения.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 1.1
 */
trait AppDbsTrait
{
    /**
     * Подключаем трейт поддержки конфига базы данных.
     */
    use AppDbConfigTrait;

    /**
     * @var DatabasesManager мененджер соединений
     */
    protected $dbs;

    /**
     * Получение менеджера соединений.
     * @return DatabasesManager
     */
    public static function getDatabasesManager()
    {
        if (!static::instanceHas('dbs')) {
            static::instanceSet('dbs', new DatabasesManager);
        }
        return static::instanceGet('dbs');
    }

    /**
     * Установка соединения.
     * @param Database
     * @param string|null имя соединения, если не задано подставляется dbname
     * @throws DatabaseNotInitializedException
     * @return self
     */
    public static function setDb(Database $connection, string $name = null)
    {
        static::getDatabasesManager()->set($connection, $name);
        return static::instance();
    }

    /**
     * Инициализация соединения.
     * @param array|null параметры соединения
     * @param string|null имя соединения, если не задано подставляется dbname
     * @throws DatabaseNotInitializedException
     * @return self
     */
    public static function initDb(array $params = null, string $name = null)
    {
        static::getDatabasesManager()->init($params, $name);
        return static::instance();
    }

    /**
     * Получение соединения.
     * @param string имя соединения
     * @throws DatabaseNotInitializedException
     * @return Database
     */
    public static function getDb(string $name = null)
    {
        return static::getDatabasesManager()->get($name);
    }

    /**
     * Получение соединения с автоинициализацией по конфигу.
     * @param string имя соединения
     * @throws DatabaseConfigNotFoundException
     * @throws DatabaseNotInitializedException
     * @return Database
     */
    public static function db(string $name = null)
    {
        try {
            return static::getDatabasesManager()->get($name);
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
            return static::getDatabasesManager()->get($name);
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
        static::getDatabasesManager()->setLast($name);
        return static::instance();
    }
}
