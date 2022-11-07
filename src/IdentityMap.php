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
        $this->resetModels();
    }

    public function resetModels()
    {
        $this->models = new \WeakMap;
        return $this;
    }

    public static function count(): int
    {
        return static::models()->count();
    }

    public static function models()
    {
        return static::instance()->models;
    }

    public static function has($identity)
    {
        return static::models()->offsetExists($identity);
    }

    public static function set($identity, $model = null)
    {
        if (func_num_args() < 2) {
            [$model, $identity] = [$identity, $identity->identity()];
        }
        // $state = $model->toArray();
        static::models()->offsetSet($identity, $model);
        return static::instance();
    }

    public static function get($identity)
    {
        return static::models()->offsetGet($identity);
    }

    public static function getOrSet($identity, $model = null)
    {
        if (func_num_args() < 2) {
            [$model, $identity] = [$identity, $identity->identity()];
        }
        if (!static::has($identity)) {
            static::set($identity, $model);
        }
        return static::get($identity);
    }

    public static function unset($identity)
    {
        static::models()->offsetUnset($identity);
        return static::instance();
    }

    public static function unsetAll()
    {
        static::instance()->resetModels();
    }

    public function __toString()
    {
        return json_encode(["models_count" => static::count()]);
    }
}
