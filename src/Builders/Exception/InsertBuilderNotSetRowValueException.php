<?php
/**
 * @package evas-php/evas-orm
 */
namespace Evas\Orm\Builders\Exception;

use Evas\Orm\OrmException;

/**
 * Класс исключения пустого обязательного значения записи в сбощике INSERT-запроса.
 * @author Egor Vasyakin <e.vasyakin@itevas.ru>
 * @since 1.0
 */
class InsertBuilderNotSetRowValueException extends OrmException
{}