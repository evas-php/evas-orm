<?php
/**
 * Трейт поддержки IdentityMap в ActiveRecord.
 * @package evas-php\evas-orm
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Orm\Traits;

use Evas\Orm\Identity\ModelIdentity;
use Evas\Orm\IdentityMap;

trait ActiveRecordIdentityTrait
{
    protected $identity;

    public function identity()
    {
        if (!$this->identity) {
            // $this->identity = ModelIdentity::createFromModel($this);
            $this->identity = implode(':', [static::class, $this->primaryValue(), static::getDbName()]);
        }
        return $this->identity;
    }

    public function identityMapSave()
    {
        return IdentityMap::getOrSet($this);
    }

    public function identityMapRemove()
    {
        IdentityMap::unset($this);
    }
}
