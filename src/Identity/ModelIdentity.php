<?php
namespace Evas\Orm\Identity;

class ModelIdentity
{
    protected ?string $dbname;
    protected string $class;
    protected $id;

    public function __construct(string $class, $id, ?string $dbname = null) {
        $this->dbname = $dbname;
        $this->class = $class;
        $this->id = $id;
    }

    public function __toString()
    {
        return implode(':', [$this->dbname, $this->class, $this->id]);
    }

    public static function createFromModel(object $model)
    {
        return ($id = $model->primaryValue()) 
        ? new static(get_class($model), $id, $model::getDbName())
        : null;
    }
}
