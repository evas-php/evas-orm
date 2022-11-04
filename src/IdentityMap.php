<?php
namespace Evas\Orm;

use Evas\Orm\Identity\ModelIdentity;

class IdentityMap
{
    protected static $instance;
    protected $models;


    public static function instance()
    {
        if (!static::$instance) static::$instance = new static;
        return static::$instance;
    }

    protected function __construct()
    {
        $this->unsetAll();
    }

    public function has($model)
    {
        return $this->models->offsetExists($model);
    }

    public function set($model)
    {
        $state = $model->toArray();
        $this->models->offsetSet($model, $state);
        return $this;
    }

    public function get($model)
    {
        return $this->models->offsetGet($model);
    }

    public function unset($model)
    {
        $this->models->offsetUnset($model);
        return $this;
    }

    public function unsetAll()
    {
        $this->models = new \WeakMap;
        return $this;
    }

    public function __toString()
    {
        return json_encode(["models" => $this->models]);
    }
}
