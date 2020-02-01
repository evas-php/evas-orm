<?php
/**
 * @package evas-php/evas-orm
 */
namespace Evas\Orm\Base;

/**
 * Класс ошибки запроса в базу данных.
 * @author Egor Vasyakin <egor@evas-php.com>
 * @since 1.0
 */
class QueryError
{
    /**
     * @var array PDO::errorInfo();
     */
    public $errorInfo;

    /**
     * @var string query sql
     */
    public $query;

    /**
     * @var array|null params for PDO::execute()
     */
    public $params;

    /**
     * Конструктор
     * @param array PDO::errorInfo().
     * @param string query sql
     * @param array|null params for PDO::execute()
     */
    public function __construct(array $errorInfo, string $query, array $params = null)
    {
        $this->errorInfo = $errorInfo;
        $this->query = $query;
        $this->params = $params ?? [];
    }

    /**
     * Получить PDO::errorInfo().
     * @return array PDO::errorInfo()
     */
    public function errorInfo(): array
    {
        return $this->errorInfo;
    }

    /**
     * Получить SQLSTATE часть из PDO::errorInfo().
     * @return string sqlstate
     */
    public function sqlState(): string
    {
        return $this->errorInfo[0];
    }

    /**
     * Получить код ошибки, заданный драйвером.
     * @return string code
     */
    public function getCode(): string
    {
        return $this->errorInfo[1];
    }

    /**
     * Получить сообщение об ошибке.
     * @return string message
     */
    public function getMessage(): string
    {
        return $this->errorInfo[2];
    }

    /**
     * Получить query.
     * @return string sql
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * Получить параметры запроса для PDO::execute().
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Преобразование ошибки в строку.
     * @return string
     */
    public function __toString(): string
    {
        $data = [
            'error' => [
                'code' => $this->getCode(),
                'message' => $this->getMessage(),
            ],
            'query' => $this->getQuery(),
            'params' => $this->getParams(),
        ];
        return json_encode($data);
    }
}
