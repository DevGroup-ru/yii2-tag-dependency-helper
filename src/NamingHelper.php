<?php

namespace DevGroup\TagDependencyHelper;

use Yii;
use yii\db\ActiveRecord;
use yii\base\InvalidParamException;

class NamingHelper
{
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
        return $class . '[ObjectTag:' . self::getCacheKeyById($id) . ']';
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
     * Return string for cache-key from varios primary key
     * @param mixed $id
     * @return string
     */
    public static function getCacheKeyById($id)
    {
        if (is_array($id)) {
            ksort($id);
            return implode('|', $id);
        }

        return (string)$id;
    }


}