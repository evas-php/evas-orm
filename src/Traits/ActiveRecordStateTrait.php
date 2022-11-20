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
     * Получение данных записи для базы данных.
     * @return array
     */
    public function getProps(): array
    {
        $props = [];
        foreach (static::columns() as &$column) {
            if (isset($this->$column)) $props[$column] = $this->$column;
        }
        return $props;
    }

    /**
     * Получение маппинга измененных свойств записи.
     * @return array
     */
    public function getUpdatedProps(): array
    {
        $props = $this->getProps();
        if (empty($this->primaryValue())) {
            return $props;
        } else {
            $state = $this->getState();
            // // return array_diff($props, $state ?? []);
            // // var_dump($state);
            // // echo '<br>';
            // // var_dump($props);
            // // echo '<br>';
            $updated = [];
            foreach ($props as $name => $value) {
                if (!isset($state[$name]) || $value !== $state[$name]) {
                    $updated[$name] = $value;
                }
            }
            // var_dump($updated); echo '<hr>';
            return $updated;
            // return array_merge(
            //     array_fill_keys(array_keys(array_diff($state ?? [], $props)), null),
            //     array_diff_assoc($props, $state ?? [])
            // );
        }
    }
}
