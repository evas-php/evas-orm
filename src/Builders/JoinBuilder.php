<?php
/**
 * @package evas-php\evas-orm
 */
namespace Evas\Orm\Builders;

use Evas\Orm\Builders\QueryBuilder;
use Evas\Orm\Builders\QueryValuesTrait;

/**
 * Сборщик JOIN.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 1.0
 */
class JoinBuilder
{
    /**
     * Подключаем поддержку работы со значениями запроса.
     */
    use QueryValuesTrait;

    /**
     * @var QueryBuilder
     */
    public $queryBuilder;

    /**
     * @var string тип склейки
     */
    public $type;

    /**
     * @var string склеиваемая таблица или запрос записей склеиваемой таблицы
     */
    public $from;

    /**
     * @var string псевдоним склеиваемой таблицы
     */
    public $as;

    /**
     * @var string условие склеивания
     */
    public $on;

    /**
     * @var string столбец склеивания
     */
    public $using;

    /**
     * Конструктор.
     * @param QueryBuilder
     * @param string|null тип склейки INNER | LEFT | RIGHT | OUTER
     * @param string|null таблица склейки
     */
    public function __construct(QueryBuilder $queryBuilder, string $type = null, string $tbl = null)
    {
        $this->queryBuilder = $queryBuilder;
        $this->type = $type;
        $this->from = $tbl;
    }

    /**
     * Установка склеиваемой таблицы.
     * @param string склеиваемая таблица или запрос записей склеиваемой таблицы
     * @param array|null значения для экранирования\
     * @return self
     */
    public function from(string $from, array $values = []): JoinBuilder
    {
        $this->from = $from;
        return $this->bindValues($values);
    }

    /**
     * Установка псевдонима для склеиваемой таблицы.
     * @param string псевдоним
     * @return self
     */
    public function as(string $as): JoinBuilder
    {
        $this->as = $as;
        return $this;
    }

    /**
     * Установка условия склеивания.
     * @param string условие
     * @param string значения для экранирования
     * @return QueryBuilder
     */
    public function on(string $on, array $values = []): QueryBuilder
    {
        $this->on = $on;
        return $this->bindValues($values)->endJoin();
    }

    /**
     * Установка столбца связывания.
     * @param string столбец
     * @return QueryBuilder
     */
    public function using(string $column): QueryBuilder
    {
        $this->using = $column;
        return $this->endJoin();
    }

    /**
     * Получение sql.
     * @return string
     */
    public function getSql(): string
    {
        $sql = "$this->type JOIN";
        if (!empty($this->as)) {
            $sql .= " ($this->from) AS $this->as";
        } else {
            $sql .= " $this->from";
        }
        if (!empty($this->on)) {
            $sql .= " ON $this->on";
        }
        if (!empty($this->using)) {
            $sql .= " USING ($this->using)";
        }
        return $sql;
    }

    /**
     * Сборка join-части запроса и его установка в сборщик запроса.
     * @return QueryBuilder
     */
    public function endJoin(): QueryBuilder
    {
        return $this->queryBuilder->setJoin($this->getSql(), $this->values());
    }
}
