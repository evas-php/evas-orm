<?php
/**
 * Трейт работы с данными ActiveRecord.
 * @package evas-php\evas-orm
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Orm\Traits;

trait ActiveRecordDataTrait
{
    /** @var array данные модели */
    protected $modelData = [];

    /**
     * Дефолтные значения новой записи.
     * @return array
     */
    public static function default(): array
    {
        return [];
    }

    /**
     * Получение значения первичного ключа модели.
     * @return int|string|null
     */
    public function primaryValue()
    {
        return $this->{static::primaryKey()};
    }

    /**
     * Заполнение модели свойствами.
     * @param array свойства модели
     * @return self
     */
    public function fill(array $props)
    {
        foreach ($props as $name => $value) {
            $this->$name = $value;
        }
        return $this;
    }

    /**
     * Получение данных модели.
     * @return array
     */
    public function getData(): array
    {
        return $this->modelData;
    }

    /**
     * Установка свойства.
     * @param string имя
     * @param mixed значение
     */
    public function __set(string $name, $value)
    {
        $this->modelData[$name] = $value;
    }

    /**
     * Получение свойства или связанных моделей.
     * @param string имя
     * @return mixed значение
     */
    public function __get(string $name)
    {
        // if (static::hasRelation($name)) {
        //     return $this->getRelatedCollection($name);
        // } else {
            return $this->modelData[$name] ?? null;
        // }
    }

    /**
     * Проверка наличия свойства.
     * @param string имя свойства
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return isset($this->modelData[$name]);
    }

    /**
     * Очистка свойства.
     * @param string имя свойства
     */
    public function __unset(string $name)
    {
        unset($this->modelData[$name]);
    }
}
