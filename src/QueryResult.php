<?php
/**
* @package evas-php/evas-orm
*/
namespace Evas\Orm;

use \Exception;
use \pdo;
use \PDOStatement;

/**
* Класс для получения ответов базы данных в разном виде.
* @author Egor Vasyakin <e.vasyakin@itevas.ru>
* @since 1.0
*/
class QueryResult
{
    /**
    * @var PDOStatement
    */
    protected $_stmt;

    /**
    * Конструктор.
    * @param PDOStatement
    */
    public function __construct(PDOStatement &$stmt)
    {
        $this->_stmt = &$stmt;
    }

    /**
    * Получить statement ответа базы.
    * @throws \Exception if statement undefined
    * @return PDOStatement
    */
    public function getStmt()
    {
        if ($this->_stmt === false) {
            throw new Exception('PDOStatement undefined');
        }
        return $this->_stmt;
    }

    /**
    * Получить количество возвращённых строк.
    * @throws \Exception if statement undefined
    * @return int
    */
    public function rowCount(): int
    {
        return $this->getStmt()->rowCount();
    }

    /**
    * Получить запись в виде массива.
    * @throws \Exception if statement undefined
    * @return numericArray
    */
    public function numericArray(): array
    {
        return $this->getStmt()->fetch(PDO::FETCH_NUM); 
    }

    /**
    * Получить все записи в виде массива массивов.
    * @throws \Exception if statement undefined
    * @return array of numericArray
    */
    public function numericArrayAll(): array
    {
        return $this->getStmt()->fetchAll(PDO::FETCH_NUM); 
    }

    /**
    * Получить запись в виде ассоциативного массива.
    * @throws \Exception if statement undefined
    * @return assocArray
    */
    public function assocArray()
    {
        return $this->getStmt()->fetch(PDO::FETCH_ASSOC); 
    }

    /**
    * Получить все записи в виде массива ассоциативных массивов.
    * @throws \Exception if statement undefined
    * @return array of assocArray
    */
    public function assocArrayAll(): array
    {
        return $this->getStmt()->fetchAll(PDO::FETCH_ASSOC); 
    }

    /**
    * Получить запись в виде анонимного объекта.
    * @throws \Exception if statement undefined
    * @return stdClass
    */
    public function anonymObject()
    {
        return $this->getStmt()->fetch(PDO::FETCH_OBJ); 
    }

    /**
    * Получить все записи в виде массива анонимных объектов.
    * @throws \Exception if statement undefined
    * @return array of stdClass
    */
    public function anonymObjectAll(): array
    {
        return $this->getStmt()->fetchAll(PDO::FETCH_OBJ); 
    }

    /**
    * Получить запись в виде объекта класса.
    * @param string имя класса
    * @throws \Exception if statement undefined
    * @return object
    */
    public function classObject(string $className)
    {
        $this->getStmt()->setFetchMode(PDO::FETCH_CLASS, $className);
        return $this->getStmt()->fetch();
    }

    /**
    * Получить все записи в виде объектов класса.
    * @param string имя класса
    * @throws \Exception if statement undefined
    * @return array of object
    */
    public function classObjectAll(string $className): array
    {
        $this->getStmt()->setFetchMode(PDO::FETCH_CLASS, $className);
        return $this->getStmt()->fetchAll();
    }
}
