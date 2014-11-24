<?php

namespace devgroup\TagDependencyHelper;

use Yii;
use yii\base\Behavior;
use yii\base\InvalidParamException;
use yii\db\ActiveRecord;

/**
 * Helper for yii\db\ActiveRecord models
 * Features:
 * - automatically invalidate cache based on unified tag names
 * - unified tag naming helper-functions
 * @package app\behaviors
 */
class ActiveRecordHelper extends Behavior
{
    /**
     * Get events list.
     * @return array
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_DELETE => 'invalidateTags',
            ActiveRecord::EVENT_AFTER_INSERT => 'invalidateTags',
            ActiveRecord::EVENT_AFTER_UPDATE => 'invalidateTags',
        ];
    }

    /**
     * Invalidate model tags.
     * @return bool
     */
    public function invalidateTags()
    {
        \yii\caching\TagDependency::invalidate(
            Yii::$app->cache,
            [
                self::getCommonTag($this->owner->className()),
                self::getObjectTag($this->owner->className(), $this->owner->id),
            ]
        );
        return true;
    }

    /**
     * Get common tag name.
     * @param string|ActiveRecord $class
     * @return string
     * @throws \yii\base\InvalidParamException
     */
    public static function getCommonTag($class)
    {
        if (is_object($class) && $class instanceof ActiveRecord) {
            $class = $class->className();
        }
        if (!is_string($class)) {
            throw new InvalidParamException('Param $class must be a string or an object.');
        }
        return $class . '[CommonTag]';
    }

    /**
     * Get object tag name.
     * @param string|ActiveRecord $class
     * @param integer $id
     * @return string
     * @throws \yii\base\InvalidParamException
     */
    public static function getObjectTag($class, $id)
    {
        if (is_object($class) && $class instanceof ActiveRecord) {
            $class = $class->className();
        }
        if (!is_string($class)) {
            throw new InvalidParamException('Param $class must be a string or an object.');
        }
        return $class . '[ObjectTag:' . $id . ']';
    }

    /**
     * Returns common tag name for model instance
     * @return string tag name
     */
    public function commonTag()
    {
        return $this->owner->className() . '[CommonTag]';
    }

    /**
     * Returns object tag name including it's id
     * @return string tag name
     */
    public function objectTag()
    {
        return $this->owner->className() . '[ObjectTag:' . $this->owner->id . ']';
    }
}
