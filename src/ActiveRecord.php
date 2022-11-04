<?php
/**
 * Модель данных ActiveRecord.
 * @package evas-php\evas-orm
 * @author Egor Vasyakin <egor@evas-php.com>
 */
namespace Evas\Orm;

use Evas\Base\Help\HooksTrait;
use Evas\Orm\Exceptions\LastInsertIdUndefinedException;
use Evas\Orm\Traits\ActiveRecordConvertTrait;
use Evas\Orm\Traits\ActiveRecordDatabaseTrait;
use Evas\Orm\Traits\ActiveRecordDataTrait;
use Evas\Orm\Traits\ActiveRecordStateTrait;
use Evas\Orm\Traits\ActiveRecordTableTrait;
use Evas\Orm\Traits\ActiveRecordQueryTrait;

class ActiveRecord implements \JsonSerializable
{
    // подключаем конвертацию
    use ActiveRecordConvertTrait;

    use ActiveRecordDatabaseTrait;

    use ActiveRecordDataTrait;

    use ActiveRecordStateTrait;

    use ActiveRecordTableTrait;

    use ActiveRecordQueryTrait;

    // подключаем поддержку произвольных хуков в наследуемых классах
    use HooksTrait;

    /**
     * Конструктор.
     * @param array|null свойства модели
     */
    public function __construct(array $props = null)
    {
        $creating = empty($this->primaryValue());
        if (!$creating) $this->saveState();
        $this->hook('beforeConstruct', $props);

        $this->hook($creating ? 'beforeCreate' : 'beforeGet', $props);
        if (!empty($props)) $this->fill($props);
        
        $this->hook($creating ? 'afterCreate' : 'afterGet');
        $this->hook('afterConstruct');
    }

    /**
     * Сохранение записи.
     * @return self
     * @throws LastInsertIdUndefinedException
     */
    public function save()
    {
        $props = $this->getUpdatedProps();
        if (empty($props)) {
            $this->hook('nothingSave');
            return $this;
        }

        $this->hook('beforeSave', $props);
        $pk = static::primaryKey();
        if ($this->isStateHasPrimaryValue()) {
            $this->hook('beforeUpdate', $props);
            static::table(true)->where($pk, $this->$pk)->update($props);
            $this->hook('afterUpdate', $props);
        } else {
            $this->hook('beforeInsert', $props);
            static::table(true)->insert($props);
            $this->$pk = static::lastInsertId();
            if (empty($this->$pk)) {
                throw new LastInsertIdUndefinedException();
            }
            $this->hook('afterInsert', $props);
        }
        $this->hook('afterSave', $props);
        // return static::getDb()->identityMapUpdate($this, $pk);
        return $this->saveState();
    }

    /**
     * Сохранение модели и её связей.
     */
    public function push()
    {
        $this->save();
        foreach ($this->relatedCollections as $models) {
            if (!is_array($models)) $models = [$models];
            foreach ($models as $model) $model->push();
        }
    }

    /**
     * Удаление записи.
     */
    public function delete()
    {
        $pk = static::primaryKey();
        if (empty($this->$pk)) {
            $this->hook('nothingDelete');
            return $this;
        }
        $this->hook('beforeDelete');
        $qr = static::table(true)->where($pk, $this->$pk)->limit(1)->delete();
        $this->hook('afterDelete', $qr->rowCount());
        if (0 < $qr->rowCount()) {
            // static::getDb()->identityMapUnset($this, $pk);
            $this->$pk = null;
        }
        return $this;
    }

    /**
     * Обновление данных модели из базы.
     */
    public function reload()
    {
        $pv = $this->primaryValue();
        if (!$pv) return;
        $this->fill((array) static::find($pv));
    }
}
