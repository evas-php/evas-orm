<?php
/**
 * @package evas-php\evas-orm
 */
namespace Evas\Orm\Builders;

/**
 * Трейт сборки значений запроса.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 1.0
 */
trait QueryValuesTrait
{
    /**
     * @var array значения запроса для экранирования
     */
    public $values = [];

    /**
     * Добавление значения.
     * @param string|numeric алиас или значение
     * @param string|numeric|null значение или null
     * @return self
     */
    public function bindValue($name, $value = null)
    {
        assert(is_string($name) || is_numeric($name));
        if (!empty($value)) {
            assert(is_string($value) || is_numeric($value));
            $this->values[$name] = $value;
        } else {
            $this->values[] = $name;
        }
        return $this;
    }

    /**
     * Добавление значений.
     * @param array массив или маппинг значений
     * @return self
     */
    public function bindValues(array $values)
    {
        $this->values = array_merge($this->values, $values);
        return $this;
    }

    /**
     * Получение значений.
     * @return array
     */
    public function values(): array
    {
        return $this->values;
    }
}
