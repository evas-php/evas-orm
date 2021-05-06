<?php
/**
 * @package evas-php\evas-orm
 */
namespace Evas\Orm\Integrate;

use Evas\Orm\Database;
use Evas\Orm\Integrate\AppDbConfigTrait;

/**
 * Константы для параметров соединения по умолчанию.
 */
if (!defined('EVAS_DATABASE_CLASS')) define('EVAS_DATABASE_CLASS', Database::class);

/**
 * Расширение поддержки класса базы данных.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 29 Sep 2020
 */
trait AppDbClassTrait
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
}
