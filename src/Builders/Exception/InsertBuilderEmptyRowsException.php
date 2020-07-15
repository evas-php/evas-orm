<?php
/**
 * @package evas-php\evas-orm
 */
namespace Evas\Orm\Builders\Exception;

use Evas\Orm\OrmException;

/**
 * Класс исключения отсутствия записей в сборщике INSERT-запроса.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 1.0
 */
class InsertBuilderEmptyRowsException extends OrmException
{}
