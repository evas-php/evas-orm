<?php
/**
 * @package evas-php/evas-orm
 */
namespace Evas\Orm\Integrate\Exception;

use \Throwable;
use Evas\Orm\OrmException;

/**
 * Класс исключения ненайденного конфига базы данных.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 1.0
 */
class DatabaseConfigNotFoundException extends OrmException
{
    /**
     * Переопределяем базовый конструтор исключения.
     * @param string имя базы данных
     * @param int код
     * @param Throwable предыдущие исключения
     */
    public function __construct(string $name = null, int $code = 0, Throwable $previous = null)
    {
        parent::__construct("Database config \"$name\" not found", $code, $previous);
    }
}
