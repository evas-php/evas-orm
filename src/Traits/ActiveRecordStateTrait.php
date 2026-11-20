<?php
/**
 * Трейт состояния для Active Record.
 * @package evas-php\evas-orm
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Orm\Traits;

trait ActiveRecordStateTrait
{
    /** @var array состояние полей модели */
    protected $state = [];

    /**
     * Сохранение состояния.
     * @return self
     */
    public function saveState()
    {
        $this->state = $this->getProps();
        return $this;
    }

    /**
     * Получение состояния сохраняемых полей.
     * @return array
     */
    public function getState(): array
    {
        return $this->state;
    }

    /**
     * Проверка наличия значения первичного ключа записи в состоянии.
     * @return bool
     */
    public function isStateHasPrimaryValue(): bool
    {
        return isset($this->state[static::primaryKey()]);
    }

    /**
     * Возвращает свойства модели, приведенные к типам БД согласно схеме таблицы.
     * @return array<string, mixed>
     */
    public function getPreparedProps(): array
    {
        $table = static::table();
        $prepared = [];
        foreach ($this->getProps() as $name => $value) {
            $column = $table->columnSchema($name);
            if (!$column) continue;
            $prepared[$name] = $this->convertValueToDb($value, $column);;
        }
        return $prepared;
    }

    /**
     * Получение данных записи для базы данных.
     * @return array<string, mixed>
     */
    public function getProps(): array
    {
        $table = static::table();
        $registry = static::converterRegistry();
        $props = [];
        foreach ($table->columnSchemas() as $name => $column) {
            if (isset($this->$name)) {
                $props[$name] = $registry->normalize($column->type, $this->$name);
            }
        }
        return $props;
    }


    /**
     * Получение маппинга измененных свойств записи (для INSERT и UPDATE).
     * @return array<string, mixed>
     */
    public function getUpdatedProps(): array
    {
        $props = $this->getProps();

        // Для новой записи (INSERT) возвращаем все подготовленные свойства
        if (empty($this->primaryValue())) return $props;

        // Для существующей записи (UPDATE) возвращаем только изменившиеся свойства
        $state = $this->getState();
        $updated = [];
        foreach ($props as $name => $value) {
            if (!array_key_exists($name, $state) || $value !== $state[$name]) {
                $updated[$name] = $value;
            }
        }
        return $updated;
    }
}
