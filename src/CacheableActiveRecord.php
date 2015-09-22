<?php

namespace DevGroup\TagDependencyHelper;

use Yii;
use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;

/**
 * Helper for yii\db\ActiveRecord models
 * Features:
 * - automatically invalidate cache based on unified tag names
 *
 * This behavior needs TagDependencyTrait to be added to model!
 */
class CacheableActiveRecord extends Behavior
{
    /**
     * Get events list.
     * @return array
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_DELETE => [$this->owner, 'invalidateTags'],
            ActiveRecord::EVENT_AFTER_INSERT => [$this->owner, 'invalidateTags'],
            ActiveRecord::EVENT_AFTER_UPDATE => [$this->owner, 'invalidateTags'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attach($owner)
    {
        if ($owner->hasMethod('commonTag', false) === false) {
            throw new InvalidConfigException(
                Yii::t('app', 'You should add TagDependencyTrait to your model class {class}', ['class'=>$owner->className()])
            );
        }
        return parent::attach($owner);
    }

}
