<?php

namespace DevGroup\TagDependencyHelper;

use Yii;
use yii\base\Behavior;

/**
 * LazyCache behavior.
 * Add it to your cache component, for example in your config:
 *
 * ```php
 * 'components' => [
 *      'cache' => [
 *          'class' => 'yii\caching\Memcache',
 *          'as lazy' => [
 *              'class' => 'DevGroup\TagDependencyHelper\LazyCache',
 *          ],
 *      ],
 * ],
 *
 * ```
 *
 *
 * @package DevGroup\TagDependencyHelper
 */
class LazyCache extends Behavior
{
    use LazyCacheTrait;
}