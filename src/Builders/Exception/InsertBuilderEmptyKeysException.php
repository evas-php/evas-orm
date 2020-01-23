<?php
/**
 * @package evas-php/evas-orm
 */
namespace Evas\Orm\Builders\Exception;

use Evas\Orm\OrmException;

/**
 * Класс исключения отсутствия ключей в сборщике INSERT-запроса.
 * @author Egor Vasyakin <e.vasyakin@itevas.ru>
 * @since 1.0
 */
class InsertBuilderEmptyKeysException extends OrmException
{}
