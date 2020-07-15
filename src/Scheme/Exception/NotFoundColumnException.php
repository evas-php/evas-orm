<?php
/**
 * @package evas-php\evas-orm
 */
namespace Evas\Orm\Scheme\Exception;

use Evas\Orm\OrmException;

/**
 * Класс исключения обращения к несуществующей колонке таблицы.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 1.0
 */
class NotFoundColumnException extends OrmException
{}
