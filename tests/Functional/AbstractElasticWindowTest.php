<?php

namespace Sunspikes\Tests\Functional;

use Sunspikes\Ratelimit\RateLimiter;
use Sunspikes\Ratelimit\Throttle\Factory\ThrottlerFactory;
use Sunspikes\Ratelimit\Throttle\Hydrator\HydratorFactory;
use Sunspikes\Ratelimit\Throttle\Settings\ElasticWindowSettings;

abstract class AbstractElasticWindowTest extends AbstractThrottlerTestCase
{
    /**
     * @inheritdoc
     */
    protected function createRatelimiter()
    {
        return new RateLimiter(
            new ThrottlerFactory($this->createCacheAdapter()),
            new HydratorFactory(),
            new ElasticWindowSettings($this->getMaxAttempts(), 600)
        );
    }
}
