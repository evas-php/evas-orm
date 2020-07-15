<?php
/**
 * @package evas-php\evas-orm
 */
namespace Evas\Orm\Models\Exception;

use Evas\Orm\Models\Exception\IdentityMapException;

/**
 * Класс исключения отсутсвия значения первичного ключа сущности IdentityMap.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 1.0
 */
class IdentityMapNotFoundEntityPrimaryValueException extends IdentityMapException
{}
