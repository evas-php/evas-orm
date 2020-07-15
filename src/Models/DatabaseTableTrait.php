<?php
/**
 * @package evas-php\evas-orm
 */
namespace Evas\Orm\Models;

use Evas\Orm\Models\Table;

/**
 * Трейт поддержки модели таблиц в базе данных.
 * @author Egor Vasyakin <egor@evas-php.com>
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
    
    /**
     * Получение списка таблицы базы данных.
     * @param bool перезапросить список таблиц
     * @return array
     */
    public function tables(bool $reload = false): array
    {
        static $tables = null;
        if (null === $tables || true === $reload) {
            $tables = [];
            $rows = $this->query('SHOW TABLES')->numericArrayAll();
            foreach ($rows as &$row) {
                $tables[] = $row[0];
            }
        }
        return $tables;
    }
}
