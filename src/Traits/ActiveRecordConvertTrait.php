<?php
/**
 * Трейт конвертации ActiveRecord.
 * @package evas-php\evas-orm
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Orm\Traits;

trait ActiveRecordConvertTrait
{
    /**
     * Конвертация данных и связей модели в массив.
     * @return array
     */
    public function toArray(): array
    {
        return $this->getData();
        // return array_merge($this->getData(), $this->getRelatedCollections());
    }
    
    /**
     * Конвертация модели в JSON сериализацию.
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Конвертация данных и связей модели в JSON.
     * @param int|null опции для json_encode
     * @return string
     */
    public function toJson(int $options = JSON_UNESCAPED_UNICODE): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Конвертация модели в строку.
     * @return string
     */
    public function __toString(): string
    {
        return $this->toJson();
    }
}
