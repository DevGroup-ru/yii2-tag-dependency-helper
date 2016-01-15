<?php

namespace DevGroup\TagDependencyHelper;

use Yii;
use yii\base\InvalidParamException;
use yii\db\ActiveRecord;

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
        } elseif (!is_string($class)) {
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
        } elseif (!is_string($class)) {
            throw new InvalidParamException('Param $class must be a string or an object.');
        }
        return $class . '[CommonTag]';
    }

    /**
     * Get composite tag name from model fields.
     * @param string|ActiveRecord $class
     * @param array $fields - ['field_model' => 'value']
     * @return string
     * @throws \yii\base\InvalidParamException
     */
    public static function getCompositeTag($class, $fields)
    {
        if (is_object($class) && $class instanceof ActiveRecord) {
            $class = $class->className();
        } elseif (!is_string($class)) {
            throw new InvalidParamException('Param $class must be a string or an object.');
        }

        return $class .
            '[CompositeTag(' .
            self::getCacheKeyById($fields, true) .
            '):(' .
            self::getCacheKeyById($fields) .
            ')]';
    }

    /**
     * Return string for cache-key from varios primary key
     * @param mixed $id
     * @param bool $keys if true, will used keys array for build the cache key
     * @return string
     */
    public static function getCacheKeyById($id, $keys = false)
    {
        if (is_array($id)) {
            ksort($id);
            $id = ($keys) ? array_keys($id) : array_values($id);

            return implode('|', $id);
        }

        return (string)$id;
    }


}
