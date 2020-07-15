<?php
/**
 * @package evas-php\evas-orm
 */
namespace Evas\Orm\Integrate;

use Evas\Orm\Database;
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
     * @param Database
     * @return self
     */
    public static function setDb(Database $db): object
    {
        return static::set('db', $db);
    }

    /**
     * Инициализация соединения.
     * @param array параметры соединения
     * @return self
     */
    public static function initDb(array $params): object
    {
        $dbClass = static::getDbClass();
        $params = array_merge(static::getDbConfig(), $params);
        return static::setDb(new $dbClass($params));
    }

    /**
     * Получение соединения.
     * @throws DatabaseNotInitializedException
     * @return Database
     */
    public static function getDb(): object
    {
        if (!static::has('db')) {
            throw new DatabaseNotInitializedException;
        }
        return static::get('db');
    }

    /**
     * Получение соединения с автоинициализацией по конфигу.
     * @throws DatabaseConfigNotFoundException
     * @return Database
     */
    public static function db(): object
    {
        if (!static::has('db')) {
            static::initDb();
        }
        return static::getDb();
    }
}
