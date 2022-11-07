<?php
namespace Evas\Orm;

use Evas\Orm\Identity\ModelIdentity;
use Evas\Orm\ActiveRecord;

class IdentityMap
{
    protected static $instance;
    protected $models = [];


    public static function instance()
    {
        if (!static::$instance) static::$instance = new static;
        return static::$instance;
    }

    protected static function getIdentity($identity, $model = null)
    {
        if (func_num_args() > 1 && $model instanceof ActiveRecord) {
            return [$identity, $model];
        }
        if ($identity instanceof ActiveRecord) {
            return [$identity->identity(), $identity];
        } else {
            return [$identity];
        }
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
        @[$identity] = static::getIdentity($identity);
        return isset(static::instance()->models[(string) $identity]);
    }

    public static function set($identity, $model = null)
    {
        @[$identity, $model] = static::getIdentity($identity, $model);
        static::instance()->models[(string) $identity] = $model;
        return static::instance();
    }

    public static function get($identity)
    {
        @[$identity] = static::getIdentity($identity);
        return static::instance()->models[(string) $identity] ?? null;
    }

    public static function getOrSet($identity, $model = null)
    {
        @[$identity, $model] = static::getIdentity($identity, $model);
        if (!static::has($identity)) {
            static::set($identity, $model);
            return $model;
        } else {
            $old = static::get($identity);
            // sync state
            $props = $old->getUpdatedProps();
            $old->fill($model->getData());
            $old->saveState();
            $old->fill($props);
            return $old;
        }
    }

    public static function unset($identity)
    {
        @[$identity] = static::getIdentity($identity);
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
