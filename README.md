yii2-tag-dependency-helper
==========================
[![Code Climate](https://codeclimate.com/github/DevGroup-ru/yii2-tag-dependency-helper/badges/gpa.svg)](https://codeclimate.com/github/DevGroup-ru/yii2-tag-dependency-helper)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/DevGroup-ru/yii2-tag-dependency-helper/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/DevGroup-ru/yii2-tag-dependency-helper/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/DevGroup-ru/yii2-tag-dependency-helper/badges/build.png?b=master)](https://scrutinizer-ci.com/g/DevGroup-ru/yii2-tag-dependency-helper/build-status/master)
[![Bitdeli Badge](https://d2weczhvl823v0.cloudfront.net/DevGroup-ru/yii2-tag-dependency-helper/trend.png)](https://bitdeli.com/free "Bitdeli Badge")

Helper for unifying cache tag names with invalidation support for Yii2.

Usage
-----

In your model add behavior:


``` php

/**
 * @inheritdoc
 */
public function behaviors()
{
    return [
        [
            'class' => \devgroup\TagDependencyHelper\ActiveRecordHelper::className(),
            'cache' => 'cache', // optional option - application id of cache component
        ],
    ];
}

```

This behavior automatically invalidates tags by model name and pair model-id.

If your cache entry should be flushed every time any row of model is edited - use `getCommonTag` helper function:

``` php
$models = Configurable::getDb()->cache(
    function ($db) {
        return Configurable::find()->all($db);
    },
    86400,
    new TagDependency([
        'tags' => ActiveRecordHelper::getCommonTag(Configurable::className()),
    ])
);
```

If your cache entry should be flushed only when exact row of model is edited - use `getObjectTag` helper function:

``` php
$cacheKey = 'Product:' . $model_id;
if (false === $product = Yii::$app->cache->get($cacheKey)) {
    if (null === $product = Product::findById($model_id)) {
        throw new NotFoundHttpException;
    }
    Yii::$app->cache->set(
        $cacheKey,
        $product,
        86400,
        new TagDependency(
            [
                'tags' => [
                    ActiveRecordHelper::getObjectTag(Product::className(), $model_id),
                ]
            ]
        )
    );
}

```
