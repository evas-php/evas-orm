<?php
/**
 * @package evas-php/evas-orm
 */
namespace Evas\Orm\Integrate;

use Evas\Orm\Integrate\Exception\DatabaseConfigNotFoundException;


if (!defined('EVAS_DATABASE_CONFIG_PATH')) define('EVAS_DATABASE_CONFIG_PATH', 'config/db.php');
if (!defined('EVAS_DATABASE_CONFIG')) define('EVAS_DATABASE_CONFIG', null);

/**
 * Расширение поддержки установки, подгрузки и автоподгрузки конфига базы данных.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 1.1
 */
trait AppDbConfigTrait
{
    /**
     * @var string путь к конфигу базы данных
     */
    protected $dbConfigPath = EVAS_DATABASE_CONFIG_PATH;

    /**
     * @var array конфиг базы данных
     */
    protected $dbConfig = EVAS_DATABASE_CONFIG;

    /**
     * Установка пути к конфигу базы данных.
     * @param string путь
     * @return self
     */
    public static function setDbConfigPath(string $path)
    {
        return static::instanceSet('dbConfigPath', $path);
    }

    /**
     * Получение пути к конфигу базы данных.
     * @param string путь
     */
    public static function getDbConfigPath(): string
    {
        return static::instanceGet('dbConfigPath');
    }

    /**
     * Установка конфига базы данных.
     * @param array
     * @return self
     */
    public static function setDbConfig(array $config)
    {
        return static::instanceSet('dbConfig', $config);
    }

    /**
     * Подгрузка конфига базы данных.
     * @throws DatabaseConfigNotFoundException
     * @return mixed конфиг базы данных
     */
    public static function loadDbConfig()
    {
        $config = static::loadByApp(static::getDbConfigPath());
        if (! $config) throw new DatabaseConfigNotFoundException;
        // устанавливаем и возвращаем конфиг базы 
        return static::setDbConfig($config)->instanceGet('dbConfig');
    }

    /**
     * Получение конфига базы данных.
     * @throws DatabaseConfigNotFoundException
     * @return array содержимое конфига
     */
    public static function getDbConfig()
    {
        if (!static::instanceHas('dbConfig')) {
            static::loadDbConfig();
        }
        return static::instanceGet('dbConfig');
    }
}
