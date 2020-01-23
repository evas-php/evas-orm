<?php
/**
 * @package evas-php/evas-orm
 */
namespace Evas\Orm\Models;

use Evas\Orm\Models\Table;

/**
 * Трейт поддержки модели таблиц в базе данных.
 * @author Egor Vasyakin <e.vasyakin@itevas.ru>
 * @since 1.0
 */
trait DatabaseTableTrait
{
    /**
     * @var array объекты таблиц
     */
    protected $tables = [];

    /**
     * Получение объекта таблицы.
     * @param string имя таблицы
     * @return Table
     */
    public function table(string $table): Table
    {
        if (empty($this->tables[$table])) {
            $this->tables[$table] = new Table($this, $table);
        }
        return $this->tables[$table];
    }
}
