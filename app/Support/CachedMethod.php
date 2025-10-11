<?php

declare(strict_types=1);

namespace FireflyIII\Support;

/**
 * Trait CachedMethod
 * 
 * Provides a simplified interface for caching expensive method results
 * using the existing CacheProperties system.
 */
trait CachedMethod
{
    /**
     * Execute a method with caching support
     * 
     * @param string $methodName The name of the method being cached
     * @param array $params Parameters to include in the cache key
     * @param callable $compute The expensive computation to cache
     * @return mixed The cached or computed result
     */
    protected function cachedMethod(string $methodName, array $params, callable $compute)
    {
        $cache = new CacheProperties();
        $cache->addProperty($methodName);
        
        foreach ($params as $param) {
            $cache->addProperty($param);
        }
        
        if ($cache->has()) {
            return $cache->get();
        }
        
        $result = $compute();
        $cache->store($result);
        
        return $result;
    }
}
