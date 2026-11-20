<?php

namespace Evas\Orm\Converters;

class TypeConverterRegistry
{
    /**
     * Карта соответствия: тип => [класс или объект конвертера, контекст по умолчанию]
     * @var array<string, array{converter: TypeConverterInterface|class-string<TypeConverterInterface>, context: array}>
     */
    private array $map = [];

    /**
     * Кэш созданных инстансов конвертеров (чтобы не плодить объекты).
     * @var array<class-string<TypeConverterInterface>, TypeConverterInterface>
     */
    private array $instances = [];

    public function __construct()
    {
        $this->registerDefaults();
    }

    protected function registerDefaults(): void
    {
        $this->register('date', DateTimeConverter::class, ['format' => 'Y-m-d']);
        $this->register('time', DateTimeConverter::class, ['format' => 'H:i:s']);
        $this->register('year', DateTimeConverter::class, ['format' => 'Y']);
        $this->register('datetime', DateTimeConverter::class, ['format' => 'Y-m-d H:i:s']);
        $this->register('timestamp', DateTimeConverter::class, ['format' => 'Y-m-d H:i:s']);
    }

    /**
     * Регистрирует правило конвертации для типа.
     * @param string  $type Имя типа (например, 'date', 'json').
     * @param TypeConverterInterface|class-string<TypeConverterInterface> $converter Класс или готовый экземпляр конвертера.
     * @param array<string, mixed> $context Контекст по умолчанию для этого типа.
     */
    public function register(string $type, TypeConverterInterface|string $converter, array $context = []): void
    {
        $key = strtolower($type);

        if ($converter instanceof TypeConverterInterface) {
            $this->instances[$converter::class] = $converter;
        }

        $this->map[$key] = [
            'converter' => $converter,
            'context'   => $context,
        ];
    }

    public function has(string $type): bool
    {
        return isset($this->map[strtolower($type)]);
    }

    /**
     * Нормализует значение по имени типа.
     */
    public function normalize(string $type, mixed $value, array $extraContext = []): mixed
    {
        $entry = $this->resolve($type);
        if ($entry === null) return $value;

        [$converter, $defaultContext] = $entry;

        return $converter->normalize($value, array_merge($defaultContext, $extraContext));
    }

    /**
     * Денормализует значение по имени типа.
     */
    public function denormalize(string $type, mixed $value, array $extraContext = []): mixed
    {
        $entry = $this->resolve($type);
        if ($entry === null) return $value;

        [$converter, $defaultContext] = $entry;

        return $converter->denormalize($value, array_merge($defaultContext, $extraContext));
    }

    /**
     * @return array{0: TypeConverterInterface, 1: array<string, mixed>}|null
     */
    private function resolve(string $type): ?array
    {
        $key = strtolower($type);
        if (!isset($this->map[$key])) return null;

        $converter = $this->map[$key]['converter'];
        if (is_string($converter)) {
            $converter = $this->instances[$converter] ??= new $converter();
            $this->map[$key]['converter'] = $converter;
        }

        return [$converter, $this->map[$key]['context']];
    }
}
