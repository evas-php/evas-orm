<?php
/**
 * @package evas-php\evas-orm
 */
namespace Evas\Orm\Scheme;

/**
 * Класс схемы колонки.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 1.0
 */
class ColumnScheme
{
    /**
     * @var string имя
     */
    public $name;

    /**
     * @var string тип
     */
    public $type;

    /**
     * @var bool разрешен NULL
     */
    public $null;

    /**
     * @var string|null ключ
     */
    public $key;

    /**
     * @var string|null значение по умолчанию
     */
    public $default;

    /**
     * @var string|null extra
     */
    public $extra;

    /**
     * Конструктор.
     * @param array параметры колонки
     */
    public function __construct(array $params = null)
    {
        if ($params) foreach ($params as $name => $value) {
            $this->__set($name, $value);
        }
    }

    public function __set(string $name, $value)
    {
        if ('Field' === $name) $name = 'name';
        else if ('Null' === $name) $value = 'YES' == $value ? true : false;
        $name = strtolower($name);
        $this->$name = $value;
    }

    /**
     * Проверка на индекс.
     * @return bool
     */
    public function isIndex(): bool
    {
        return !empty($this->key) ? true : false;
    }
}
