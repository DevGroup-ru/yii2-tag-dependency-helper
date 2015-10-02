<?php
/**
 * Created by PhpStorm.
 * User: bethrezen
 * Date: 28.09.15
 * Time: 16:05
 */

namespace DevGroup\TagDependencyHelper;

use yii\caching\TagDependency;

/**
 * LazyCacheTrait is used when you have your own implementation of \yii\caching\Cache and don't want to use as behavior.
 * Trait is a bit faster behavior.
 *
 * @package DevGroup\TagDependencyHelper
 */
trait LazyCacheTrait
{
    /**
     * Performs lazy caching. If $cacheKey is not found in cache - run callable and cache result.
     * Callable is called only if no cache entry
     *
     * @param callable                                $callable Callable to run for actual retrieving your info
     * @param string                                  $cacheKey Cache key string
     * @param int                                     $duration Duration of cache entry in seconds
     * @param null|\yii\caching\Dependency|string     $dependency Cache dependency
     *
     * @return mixed
     */
    public function lazy(callable $callable, $cacheKey, $duration = 86400, $dependency = null)
    {
        /** @var \yii\caching\Cache $owner */
        if (get_called_class() === LazyCache::className()) {
            $owner = $this->owner;
        } else {
            $owner = $this;
        }
        $result = $owner->get($cacheKey);
        if ($result === false) {
            $result = call_user_func($callable);

            if (is_string($dependency)) {
                $dependency = [$dependency];
            }
            if (is_array($dependency)) {
                $dependency = new TagDependency([
                    'tags' => $dependency,
                ]);
            }

            $owner->set($cacheKey, $result, $duration, $dependency);
        }
        return $result;
    }
}