<?php
/**
 * Трейт конвертации значений столбцов для Active Record.
 * @package evas-php\evas-orm
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Orm\Traits;

use Evas\Orm\Converters\TypeConverterRegistry;

trait ActiveRecordConverterTrait
{
    /** @var TypeConverterRegistry|null Реестр конвертеров типов */
    protected static ?TypeConverterRegistry $converterRegistry = null;

    /**
     * Получение реестра конвертеров типов.
     * @return TypeConverterRegistry
     */
    public static function converterRegistry(): TypeConverterRegistry
    {
        return static::$converterRegistry ??= static::createConverterRegistry();
    }

    /**
     * Установка реестра конвертеров типов.
     * @param TypeConverterRegistry|null $registry
     * @return TypeConverterRegistry
     */
    public static function setConverterRegistry(?TypeConverterRegistry $registry = null): TypeConverterRegistry
    {
        return static::$converterRegistry = $registry ?? static::createConverterRegistry();
    }

    /**
     * Создание реестра конвертеров типов по умолчанию.
     * Можно переопределить в дочерней или абстрактной модели (в паре с $converterRegistry = null).
     * @return TypeConverterRegistry
     */
    protected static function createConverterRegistry(): TypeConverterRegistry
    {
        return new TypeConverterRegistry();
    }
}
