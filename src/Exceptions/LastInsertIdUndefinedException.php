<?php
/**
 * Исключение не найденного id последней вставленной записи.
 * @package evas-php\evas-orm
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Orm\Exceptions;

use Evas\Orm\Exceptions\OrmException;

class LastInsertIdUndefinedException extends OrmException
{}
