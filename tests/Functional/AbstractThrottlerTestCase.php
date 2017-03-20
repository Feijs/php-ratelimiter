<?php

namespace Sunspikes\Tests\Functional;

use Mockery as M;
use Sunspikes\Ratelimit\Cache\Adapter\CacheAdapterInterface;
use Sunspikes\Ratelimit\RateLimiter;

abstract class AbstractThrottlerTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Ratelimiter
     */
    protected $ratelimiter;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->ratelimiter = $this->createRatelimiter();
    }

    public function testThrottlePreLimit()
    {
        $throttle = $this->ratelimiter->get('pre-limit-test');

        for ($i = 0; ++$i < $this->getMaxAttempts();) {
            $throttle->hit();
        }

        $this->assertTrue($throttle->check());
    }

    public function testThrottlePostLimit()
    {
        $throttle = $this->ratelimiter->get('post-limit-test');

        for ($i = 0; $i < $this->getMaxAttempts(); $i++) {
            $throttle->hit();
        }

        $this->assertFalse($throttle->check());
    }

    public function testThrottleAccess()
    {
        $throttle = $this->ratelimiter->get('access-test');

        for ($i = 0; $i < $this->getMaxAttempts(); $i++) {
            $throttle->access();
        }

        $this->assertFalse($throttle->access());
    }

    public function testThrottleCount()
    {
        $throttle = $this->ratelimiter->get('count-test');

        for ($i = 0; $i < $this->getMaxAttempts(); $i++) {
            $throttle->access();
        }

        $this->assertEquals(3, $throttle->count());
    }

    public function testClear()
    {
        $throttle = $this->ratelimiter->get('clear-test');
        $throttle->hit();
        $throttle->clear();

        self::assertEquals(0, $throttle->count());
    }

    /**
     * @return int
     */
    protected function getMaxAttempts()
    {
        return 3;
    }

    /**
     * @return RateLimiter
     */
    abstract protected function createRatelimiter();

    /**
     * @return CacheAdapterInterface
     */
    abstract protected function createCacheAdapter();
}
