<?php
/**
* @package evas-php/evas-orm
*/
namespace Evas\Orm;

use \Exception;

/**
* Обработчик ошибок базы данных.
* @author Egor Vasyakin <e.vasyakin@itevas.ru>
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
    public function __construct($errorInfo, $query, $params = null)
    {
        $this->errorInfo = $errorInfo;
        $this->query = $query;
        $this->params = $params;
    }

    /**
    * Получить PDO::errorInfo().
    * @return array PDO::errorInfo()
    */
    public function errorInfo()
    {
        return $this->errorInfo;
    }

    /**
    * Получить SQLSTATE часть из PDO::errorInfo().
    * @return string sqlstate
    */
    public function sqlState()
    {
        return $this->errorInfo[0];
    }

    /**
    * Получить код ошибки, заданный драйвером.
    * @return string code
    */
    public function getCode()
    {
        return $this->errorInfo[1];
    }

    /**
    * Получить сообщение об ошибке.
    * @return string message
    */
    public function getMessage()
    {
        return $this->errorInfo[2];
    }

    /**
    * Получить query.
    * @return string sql
    */
    public function getQuery()
    {
        return $this->query;
    }

    /**
    * Получить параметры запроса для PDO::execute().
    * @return array|null
    */
    public function getParams()
    {
        return $this->params;
    }

    /**
    * Получение полной информации об ошибке в виде текста.
    * @return string
    */
    public function getFullInfo(): string
    {
        return 'Не удалось выполнить запрос. ERROR[code: ' . $this->getCode() . ', message: ' . $this->getMessage() . '] QUERY[' . $this->getQuery() . '] PARAMS [' . print_r($this->getParams(), true) . ']';
    }

    /**
    * Обработка ошибки.
    * @throws \Exception
    */
    public function handle()
    {
        die ('QueryError catch exception: ' . $this->getFullInfo());
    }
}
