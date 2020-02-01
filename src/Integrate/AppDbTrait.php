<?php
/**
 * @package evas-php/evas-orm
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
     * @var string класс для соединения с базой данных.
     */
    public static $databaseClass = EVAS_DATABASE_CLASS;

    /**
     * @var Database соединение
     */
    protected $db;

    /**
     * Установка соединения.
     * @param Database
     * @return self
     */
    public static function setDb(Database $db)
    {
        return static::instanceSet('db', $db);
    }

    /**
     * Инициализация соединения.
     * @param array параметры соединения
     * @return self
     */
    public static function initDb(array $params)
    {
        return static::setDb(new static::$databaseClass($params));
    }

    /**
     * Получение соединения.
     * @throws DatabaseNotInitializedException
     * @return Database
     */
    public static function getDb()
    {
        if (!static::instanceHas('db')) {
            throw new DatabaseNotInitializedException;
        }
        return static::instanceGet('db');
    }

    /**
     * Получение соединения с автоинициализацией по конфигу.
     * @throws DatabaseConfigNotFoundException
     * @return Database
     */
    public static function db()
    {
        if (!static::instanceHas('db')) {
            static::initDb(static::getDbConfig());
        }
        return static::getDb('db');
    }
}
