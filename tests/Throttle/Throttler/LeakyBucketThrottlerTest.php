<?php

namespace Sunspikes\Tests\Ratelimit\Throttle\Throttler;

use Mockery as M;
use Sunspikes\Ratelimit\Cache\Adapter\CacheAdapterInterface;
use Sunspikes\Ratelimit\Cache\Exception\ItemNotFoundException;
use Sunspikes\Ratelimit\Throttle\Throttler\LeakyBucketThrottler;
use Sunspikes\Ratelimit\Throttle\Throttler\ThrottlerInterface;
use Sunspikes\Ratelimit\Time\TimeAdapterInterface;

class LeakyBucketThrottlerTest extends \PHPUnit_Framework_TestCase
{
    const CACHE_TTL = 3600;
    const INITIAL_TIME = 0;
    const TOKEN_LIMIT = 270;
    const TIME_LIMIT = 24000;
    const THRESHOLD = 30;

    /**
     * @var CacheAdapterInterface|\Mockery\MockInterface
     */
    private $cacheAdapter;

    /**
     * @var TimeAdapterInterface|\Mockery\MockInterface
     */
    private $timeAdapter;

    /**
     * @var ThrottlerInterface
     */
    private $throttler;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->timeAdapter = M::mock(TimeAdapterInterface::class);
        $this->cacheAdapter = M::mock(CacheAdapterInterface::class);

        $this->throttler = new LeakyBucketThrottler(
            $this->cacheAdapter,
            $this->timeAdapter,
            'key',
            self::TOKEN_LIMIT,
            self::TIME_LIMIT,
            self::THRESHOLD,
            self::CACHE_TTL
        );
    }

    public function testAccess()
    {
        //More time has passed than the given window
        $this->mockTimePassed(self::TIME_LIMIT + 1, 2);
        $this->mockSetUsedCapacity(1, self::INITIAL_TIME + self::TIME_LIMIT + 1);

        $this->assertEquals(true, $this->throttler->access());
    }

    public function testHitBelowThreshold()
    {
        // No time has passed
        $this->mockTimePassed(0, 2);

        // Used tokens one below threshold
        $this->cacheAdapter
            ->shouldReceive('get')
            ->with('key'.LeakyBucketThrottler::TOKEN_CACHE_KEY)
            ->andReturn(self::THRESHOLD - 1);

        $this->mockSetUsedCapacity(self::THRESHOLD, self::INITIAL_TIME);

        $this->assertEquals(0, $this->throttler->hit());
    }

    public function testHitOnThreshold()
    {
        // No time has passed
        $this->mockTimePassed(0, 2);

        // Used tokens on threshold
        $this->cacheAdapter
            ->shouldReceive('get')
            ->with('key'.LeakyBucketThrottler::TOKEN_CACHE_KEY)
            ->andReturn(self::THRESHOLD);

        $this->mockSetUsedCapacity(self::THRESHOLD + 1, self::INITIAL_TIME);

        $expectedWaitTime = self::TIME_LIMIT / (self::TOKEN_LIMIT - self::THRESHOLD);
        $this->timeAdapter->shouldReceive('usleep')->with(1e3 * $expectedWaitTime)->once();

        $this->assertEquals($expectedWaitTime, $this->throttler->hit());
    }

    public function testClear()
    {
        $this->timeAdapter->shouldReceive('now')->once()->andReturn(self::INITIAL_TIME + 1);
        $this->mockSetUsedCapacity(0, self::INITIAL_TIME + 1);

        $this->throttler->clear();
    }

    public function testCountWithMissingCacheItem()
    {
        $this->timeAdapter->shouldReceive('now')->twice()->andReturn(self::INITIAL_TIME + 1);
        $this->cacheAdapter->shouldReceive('get')->andThrow(ItemNotFoundException::class);

        $this->mockSetUsedCapacity(0, self::INITIAL_TIME + 1);

        self::assertEquals(0, $this->throttler->count());
    }

    public function testCountWithMoreTimePassedThanLimit()
    {
        //More time has passed than the given window
        $this->mockTimePassed(self::TIME_LIMIT + 1, 1);

        $this->assertEquals(0, $this->throttler->count());
    }

    public function testCountWithLessTimePassedThanLimit()
    {
        // Time passed to refill 1/6 of tokens
        $this->mockTimePassed(self::TIME_LIMIT / 6, 1);

        // Previously 1/2 of tokens used
        $this->cacheAdapter
            ->shouldReceive('get')
            ->with('key'.LeakyBucketThrottler::TOKEN_CACHE_KEY)
            ->andReturn(self::TOKEN_LIMIT / 2);

        // So bucket should be filled for 1/3
        $this->assertEquals(self::TOKEN_LIMIT / 3, $this->throttler->count());
    }

    public function testCheck()
    {
        //More time has passed than the given window
        $this->mockTimePassed(self::TIME_LIMIT + 1, 1);

        $this->assertTrue(true, $this->throttler->check());
    }

    /**
     * @param int $tokens
     * @param int $time
     */
    private function mockSetUsedCapacity($tokens, $time)
    {
        $this->cacheAdapter
            ->shouldReceive('set')
            ->with('key'.LeakyBucketThrottler::TOKEN_CACHE_KEY, $tokens, self::CACHE_TTL)
            ->once();

        $this->cacheAdapter
            ->shouldReceive('set')
            ->with('key'.LeakyBucketThrottler::TIME_CACHE_KEY, $time, self::CACHE_TTL)
            ->once();
    }

    /**
     * @param int $timeDiff
     * @param int $numCalls
     */
    private function mockTimePassed($timeDiff, $numCalls)
    {
        $this->timeAdapter->shouldReceive('now')->times($numCalls)->andReturn(self::INITIAL_TIME + $timeDiff);

        $this->cacheAdapter
            ->shouldReceive('get')
            ->with('key'.LeakyBucketThrottler::TIME_CACHE_KEY)
            ->andReturn(self::INITIAL_TIME);
    }
}
