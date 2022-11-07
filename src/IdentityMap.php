<?php
namespace Evas\Orm;

use Evas\Orm\Identity\ModelIdentity;

class IdentityMap
{
    protected static $instance;
    protected $models = [];


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
        $this->models = [];
        return $this;
    }

    public static function count(): int
    {
        return count(static::instance()->models);
    }

    public static function has($identity)
    {
        return isset(static::instance()->models[(string) $identity]);
    }

    public static function set($identity, $model = null)
    {
        if (func_num_args() < 2) {
            [$model, $identity] = [$identity, $identity->identity()];
        }
        static::instance()->models[(string) $identity] = $model;
        return static::instance();
    }

    public static function get($identity)
    {
        return static::instance()->models[(string) $identity] ?? null;
    }

    public static function getOrSet($identity, $model = null)
    {
        if (func_num_args() < 2) {
            [$model, $identity] = [$identity, $identity->identity()];
        }
        if (!static::has($identity)) {
            static::set($identity, $model);
            return $model;
        } else {
            $old = static::get($identity);
            /** @todo Sync state */
            $props = $old->getUpdatedProps();
            $old->fill($model->getData());
            $old->saveState();
            var_dump($props);
            foreach ($props as $name => $value) {
                $old->$name = $value;
            }
            $old->saveState();
            return $old;
        }
        // return static::get($identity);
    }

    public static function unset($identity)
    {
        unset(static::instance()->models[(string) $identity]);
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
