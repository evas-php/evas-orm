<?php
/**
 * Трейт извлечения и сериализации данных ActiveRecord.
 * @package evas-php\evas-orm
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Orm\Traits;

use DateTimeInterface;
use JsonSerializable;

trait ActiveRecordExtractTrait
{
    /**
     * Конвертация данных и связей модели в массив (с сохранением PHP-типов).
     * @param string[]|null $fields Список нужных полей (null — все загруженные поля)
     * @return array<string, mixed>
     */
    public function toArray(?array $fields = null): array
    {
        $data = $this->getData();
        // $data = array_merge($this->getData(), $this->getRelatedCollections());
        return $fields === null ? $data : array_intersect_key($data, array_flip($fields));
    }

    /**
     * Конвертация модели для JSON-сериализации и HTTP-ответов.
     * @param string[]|null $fields Список нужных полей (null — все загруженные поля)
     * @return array<string, mixed>
     */
    public function jsonSerialize(?array $fields = null): array
    {
        return $this->prepareForSerialize($this->toArray($fields));
    }

    /**
     * Конвертация данных и связей модели в JSON-строку.
     * @param int           $options Опции для json_encode
     * @param string[]|null $fields  Список нужных полей
     * @return string
     */
    public function toJson(int $options = JSON_UNESCAPED_UNICODE, ?array $fields = null): string
    {
        return json_encode($this->jsonSerialize($fields), $options);
    }

    /**
     * Конвертация модели в строку.
     * @return string
     */
    public function __toString(): string
    {
        return $this->toJson();
    }

    /**
     * Рекурсивная нормализация значений для безопасной сериализации (даты, вложенные модели и коллекции).
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function prepareForSerialize(array $data): array
    {
        foreach ($data as $key => $value) {
            if ($value instanceof DateTimeInterface) $data[$key] = $value->format(DateTimeInterface::ATOM);
            else if ($value instanceof JsonSerializable) $data[$key] = $value->jsonSerialize();
            else if (is_array($value)) $data[$key] = $this->prepareForSerialize($value);
        }
        return $data;
    }
}
