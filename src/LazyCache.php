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
    /**
     * Performs lazy caching. If $cacheKey is not found in cache - run callable and cache result.
     * Callable is called only if no cache entry
     *
     * @param callable                         $callable Callable to run for actual retrieving your info
     * @param string                           $cacheKey Cache key string
     * @param int                              $duration Duration of cache entry in seconds
     * @param null|\yii\caching\Dependency     $dependency Cache dependency
     *
     * @return mixed
     */
    public function lazy(callable $callable, $cacheKey, $duration = 86400, $dependency = null)
    {
        /** @var \yii\caching\Cache $owner */
        $owner = $this->owner;
        $result = $owner->get($cacheKey);
        if ($result === false) {
            $result = call_user_func($callable);
            $owner->set($cacheKey, $result, $duration, $dependency);
        }
        return $result;
    }
}