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
            $this->identity = ModelIdentity::createFromModel($this);
        }
        return $this->identity;
    }

    public function identityMapSave()
    {
        // echo '<pre>';
        echo dumpOrm($this->identity());
        echo dumpOrm(spl_object_id($this->identity()));
        echo dumpOrm(IdentityMap::has($this->identity()));
        // echo '</pre>';
        return IdentityMap::getOrSet($this);
    }

    public function identityMapRemove()
    {
        IdentityMap::unset($this->identity());
    }
}
