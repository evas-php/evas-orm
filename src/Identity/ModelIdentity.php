<?php
namespace Evas\Orm\Identity;

class ModelIdentity
{
    public readonly ?string $dbname = null;
    public readonly string $class;
    public readonly $id;

    public function __construct(string $class, $id, ?string $dbname = null)
    {
        $this->dbname = $dbname;
        $this->class = $class;
        $this->id = $id;
    }

    public function __toString()
    {
        return implode(':', [$this->dbname, $this->class, $this->id]);
    }
}
