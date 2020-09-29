<?php
/**
 * @package evas-php\evas-orm
 */
namespace Evas\Orm\Integrate;

use Evas\Orm\Database;
use Evas\Orm\Base\Database as BaseDatabase;
use Evas\Orm\Integrate\AppDbConfigTrait;
use Evas\Orm\Integrate\Exception\DatabaseNotInitializedException;

/**
 * Константы для параметров соединения по умолчанию.
 */
if (!defined('EVAS_DATABASE_CLASS')) define('EVAS_DATABASE_CLASS', Database::class);

/**
 * Расширение поддержки базы данных приложения.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 1.1
 */
trait AppDbTrait
{
    /**
     * Подключаем трейт поддержки конфига базы данных.
     */
    use AppDbConfigTrait;

    /**
     * Установка класса базы данных.
     * @param string
     * @return self
     */
    public static function setDbClass(string $dbClass): object
    {
        return static::set('dbClass', $dbClass);
    }

    /**
     * Получение класса базы данных.
     * @param string
     */
    public static function getDbClass(): string
    {
        if (!static::has('dbClass')) {
            static::set('dbClass', EVAS_DATABASE_CLASS);
        }
        return static::get('dbClass');
    }

    /**
     * Установка соединения.
     * @param BaseDatabase
     * @return self
     */
    public static function setDb(BaseDatabase $db): object
    {
        return static::set('db', $db);
    }

    /**
     * Инициализация соединения.
     * @param array|null параметры соединения
     * @return self
     */
    public static function initDb(array $params = null): object
    {
        $dbClass = static::getDbClass();
        $params = array_merge(static::getDbConfig(), $params ?? []);
        return static::setDb(new $dbClass($params));
    }

    /**
     * Получение соединения.
     * @throws DatabaseNotInitializedException
     * @return BaseDatabase
     */
    public static function getDb(): BaseDatabase
    {
        if (!static::has('db')) {
            throw new DatabaseNotInitializedException;
        }
        return static::get('db');
    }

    /**
     * Получение соединения с автоинициализацией по конфигу.
     * @throws DatabaseConfigNotFoundException
     * @return BaseDatabase
     */
    public static function db(): BaseDatabase
    {
        if (!static::has('db')) {
            static::initDb();
        }
        return static::getDb();
    }
}
