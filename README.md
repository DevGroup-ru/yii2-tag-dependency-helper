yii2-tag-dependency-helper
==========================
[![Code Climate](https://codeclimate.com/github/DevGroup-ru/yii2-tag-dependency-helper/badges/gpa.svg)](https://codeclimate.com/github/DevGroup-ru/yii2-tag-dependency-helper)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/DevGroup-ru/yii2-tag-dependency-helper/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/DevGroup-ru/yii2-tag-dependency-helper/?branch=master)
[![Build Status](https://travis-ci.org/DevGroup-ru/yii2-tag-dependency-helper.svg?branch=master)](https://travis-ci.org/DevGroup-ru/yii2-tag-dependency-helper)


Helper for unifying cache tag names with invalidation support for Yii2 ActiveRecord models.

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist devgroup/yii2-tag-dependency-helper "*"
```

or add

```
"devgroup/yii2-tag-dependency-helper": "*"
```

to the require section of your `composer.json` file.

## Core concept

This extension introduces 2 standard cache tags types for ActiveRecord:
- common tag - Tag is invalidated if any model of this type is updated/inserted
- object tag - Tag is invalidated if exact model record is updated
- composite tag - Tag is invalidated if model with specified fields record is updated

## Usage

In your active record model add behavior and trait:


``` php

use \DevGroup\TagDependencyHelper\TagDependencyTrait;

/**
 * @inheritdoc
 */
public function behaviors()
{
    return [
        'CacheableActiveRecord' => [
            'class' => \DevGroup\TagDependencyHelper\CacheableActiveRecord::className(),
        ],
    ];
}

```

This behavior automatically invalidates tags by model name and pair model-id.

### Finding model

There's a special method in TagDependencyTrait for finding models by ID with using tag cache:

```php
/**
 * Finds or creates new model using or not using cache(objectTag is applied, not commonTag!)
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
)
{
}
```

Example call: `$post = Post::loadModel('', false, false, 0, new \Exception("test2"));`

For Post model instance(`$post`) cache will be automatically invalidated by object and common tags on update,insert,delete.

Direct invalidation can be done by calling `$post->invalidateTags()`.

 
### Adding cache tags in other scenarios

If your cache entry should be flushed every time any row of model is edited - use `getCommonTag` helper function:

``` php
$models = Configurable::getDb()->cache(
    function ($db) {
        return Configurable::find()->all($db);
    },
    86400,
    new TagDependency([
        'tags' => NamingHelper::getCommonTag(Configurable::className()),
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
                    NamingHelper::getObjectTag(Product::className(), $model_id),
                ]
            ]
        )
    );
}

```

If your cache entry should be flushed only when row of model with specified fields is edited - use `getCompositeTag` helper function and override function `cacheCompositeTagFields` in model:

``` php
//in model for cache, in this case Comments model
protected function cacheCompositeTagFields()
{
    return ['id_app', 'object_table', 'id_object'];
}

//Data for caching
$comments = Comments::getDb()->cache(
    function ($db) use ($id_app, $id_object, $object_table) {
        return Comments::find()->where(['id_app' => $id_app, 'object_table' => $object_table, 'id_object' => $id_object])->all($db);
    },
    0,
    new TagDependency([
        'tags' => [
            NamingHelper::getCompositeTag(Comments::className(), ['id_app' => $id_app, 'object_table' => $object_table, 'id_object' => $id_object])
        ]
    ])
);

//PROFIT!
```

## Lazy cache

Lazy cache is a technique inspired by [iiifx-production/yii2-lazy-cache](https://github.com/iiifx-production/yii2-lazy-cache) composer package.

After configuring(see below) you can use it like this:

```php
$pages = Yii::$app->cache->lazy(function() {
    return Page::find()->where(['active'=>1])->all();
}, 'AllActivePages', 3600, $dependency);
```

In this example Pages find query will be performed only if cache entry with key `AllActivePages` will not be found.
After successful retrieving of models array the result will be automatically stored in cache 
with `AllActivePages` as cache key for 3600 seconds and with `$dependency` as Cache dependency.  

### Configuring - Performance-way

For performance reasons(yii2 behaviors are slower then traits) - create your own `\yii\caching\Cache` class
and add `LazyCacheTrait` to it, for example:

```php
namespace app\components;

class MyCache extends \yii\caching\FileCache {
    use \DevGroup\TagDependencyHelper\LazyCacheTrait;
}
```

And modify your application configuration to use your cache component:

```php
return [
    'components' => [
        'class' => '\app\components\MyCache',
    ],
];
```

Now you can use lazy cache:


### Configuring - Behavior-way

Just modify your configuration like this:

```php
return [
    'components' => [
        'cache' => [
            'class' => '\yii\caching\FileCache',
            'as lazy' => [
                'class' => '\DevGroup\TagDependencyHelper\LazyCache',
            ],
        ],
    ],
];

```

## Migrating from 0.0.x to 1.x

1. We have changed namespace from `devgroup` to `DevGroup`
2. We've splitted behavior into 3 components:

- CacheableActiveRecord - behavior that adds invalidation on update/insert/delete of ActiveRecord model
- TagDependencyTrait - trait that must be also added to ActiveRecord class, handles invalidation and adds new static method `loadModel`
- NamingHelper - the only one class that handles naming policy for cache tags
