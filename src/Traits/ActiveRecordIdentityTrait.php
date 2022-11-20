<?php
/**
 * Трейт поддержки IdentityMap в ActiveRecord.
 * @package evas-php\evas-orm
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Orm\Traits;

use Evas\Orm\ActiveRecord;
use Evas\Orm\IdentityMap;

trait ActiveRecordIdentityTrait
{
    /** @var string идентификатор модели для IdentityMap */
    protected $identity;

    /** 
     * Получение идентификатора модели для IdentityMap 
     * @return string идентификатор модели для IdentityMap 
     * */
    public function identity(): string
    {
        if (!$this->identity) {
            $this->identity = implode(':', [static::class, $this->primaryValue(), static::dbName()]);
        }
        return $this->identity;
    }

    /**
     * Сохранение модели в IdentityMap.
     * @return static
     */
    public function identityMapSave(): ActiveRecord
    {
        return IdentityMap::getWithSave($this);
    }

    /**
     * Удаление модели из IdentityMap.
     */
    public function identityMapRemove()
    {
        IdentityMap::unset($this);
    }
}
