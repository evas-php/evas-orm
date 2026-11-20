<?php
namespace Evas\Orm\Converters;

interface TypeConverterInterface
{
    /**
     * Преобразует внешнее (сырое) значение во внутренний тип.
     *
     * @param mixed $value   Входное значение (строка, число, null и т.д.).
     * @param array $context Дополнительные параметры (формат, точность, таймзона).
     *
     * @return mixed
     */
    public function denormalize(mixed $value, array $context = []): mixed;

    /**
     * Преобразует внутренний тип во внешнее (плоское) представление.
     *
     * @param mixed $value   Рабочее значение (например, DateTimeInterface).
     * @param array $context Параметры вывода (например, ['format' => 'Y-m-d']).
     *
     * @return mixed
     */
    public function normalize(mixed $value, array $context = []): mixed;
}
