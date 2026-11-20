<?php

namespace Evas\Orm\Converters;

use DateTime;
use DateTimeInterface;
use DateTimeZone;

if (!defined('EVAS_ORM_DATETIME_FORMAT')) {
    define('EVAS_ORM_DATETIME_FORMAT', DateTimeInterface::ATOM);
}

class DateTimeConverter implements TypeConverterInterface
{
    public function __construct(
        private string $defaultFormat = EVAS_ORM_DATETIME_FORMAT,
        private ?DateTimeZone $timezone = null,
    ) {}

    public function denormalize(mixed $value, array $context = []): ?DateTime
    {
        if ($value === null || $value === '') return null;
        if ($value instanceof DateTime) return $value;
        
        if ($value instanceof DateTimeInterface) return DateTime::createFromInterface($value);

        $raw = (string) $value;
        if (str_starts_with($raw, '0000-00-00')) return null;

        $tz = $context['timezone'] ?? $this->timezone;
        return new DateTime($raw, $tz);
    }

    public function normalize(mixed $value, array $context = []): ?string
    {
        if ($value === null || is_string($value)) return $value;

        if ($value instanceof DateTimeInterface) {
            $format = $context['format'] ?? $this->defaultFormat;
            $tz = $context['timezone'] ?? $this->timezone;

            if ($tz !== null) {
                $value = DateTime::createFromInterface($value)->setTimezone($tz);
            }

            return $value->format($format);
        }

        return (string) $value;
    }
}
