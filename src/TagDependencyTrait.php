<?php

namespace DevGroup\TagDependencyHelper;

use Yii;
use yii\caching\TagDependency;

/**
 * TagDependencyTrait features:
 * - retrieving common and object tags
 * - configuring cache component(through overriding getTagDependencyCacheComponent)
 */
trait TagDependencyTrait
{
    /**
     * @return \yii\caching\Cache
     */
    public function getTagDependencyCacheComponent()
    {
        return Yii::$app->cache;
    }

    /**
     * Returns common tag name for model instance
     * @return string tag name
     */
    public function commonTag()
    {
        /** @var \yii\db\ActiveRecord $this */
        return NamingHelper::getCommonTag($this->className());
    }

    /**
     * Returns object tag name including it's id
     * @return string tag name
     */
    public function objectTag()
    {
        /** @var \yii\db\ActiveRecord $this */
        return NamingHelper::getObjectTag($this->className(), $this->getPrimaryKey());
    }

    /**
     * Finds or creates new model using or not using cache(objectTag is applied)
     * @param string|int $id ID of model to find
     * @param bool $createIfEmptyId Create new model instance(record) if id is empty
     * @param bool $useCache Use cache
     * @param int $cacheLifetime Cache lifetime in seconds
     * @param bool|\Exception $throwException False or exception instance to throw if model not found or (empty id AND createIfEmptyId==false)
     * @return \yii\db\ActiveRecord|null|self|TagDependencyTrait
     * @throws \Exception
     */
    public static function loadModel(
        $id,
        $createIfEmptyId = false,
        $useCache = true,
        $cacheLifetime = 86400,
        $throwException = false
    ) {
        /** @var \yii\db\ActiveRecord|TagDependencyTrait $model */
        $model = null;
        if (empty($id)) {
            if ($createIfEmptyId === true) {
                $model = new self;
            } else {
                if ($throwException !== false) {
                    throw $throwException;
                } else {
                    return null;
                }
            }
        }
        if ($useCache === true && $model===null) {
            $model = Yii::$app->cache->get(self::className() . ":" . $id);
        }
        if (!is_object($model)) {
            $model = self::findOne($id);

            if (is_object($model) && $useCache === true) {
                Yii::$app->cache->set(
                    self::className() . ":" . $id,
                    $model,
                    $cacheLifetime,
                    new TagDependency([
                        'tags' => $model->objectTag()
                    ])
                );
            }
        }
        if (!is_object($model)) {
            if ($throwException) {
                throw $throwException;
            } else {
                return null;
            }
        }
        return $model;
    }

    /**
     * Invalidate model tags.
     * @return bool
     */
    public function invalidateTags()
    {

        /** @var TagDependencyTrait $this */
        \yii\caching\TagDependency::invalidate(
            $this->getTagDependencyCacheComponent(),
            [
                $this->commonTag(),
                $this->objectTag(),
            ]
        );
        return true;
    }

}