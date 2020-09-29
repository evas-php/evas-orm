<?php
/**
 * @package evas-php\evas-orm
 */
namespace Evas\Orm\Integrate;

use Evas\Orm\Database;
use Evas\Orm\Base\Database as BaseDatabase;
use Evas\Orm\Integrate\AppDbClassTrait;
use Evas\Orm\Integrate\Exception\DatabaseNotInitializedException;

/**
 * Расширение поддержки базы данных приложения.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 1.1
 */
trait AppDbTrait
{
    /**
     * Подключаем трейт поддержки класса базы данных.
     */
    use AppDbClassTrait;

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
