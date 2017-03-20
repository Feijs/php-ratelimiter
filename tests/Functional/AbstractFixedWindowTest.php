<?php

namespace Sunspikes\Tests\Ratelimit\Functional;

use Mockery as M;
use Sunspikes\Ratelimit\RateLimiter;
use Sunspikes\Ratelimit\Throttle\Factory\TimeAwareThrottlerFactory;
use Sunspikes\Ratelimit\Throttle\Hydrator\HydratorFactory;
use Sunspikes\Ratelimit\Throttle\Settings\FixedWindowSettings;
use Sunspikes\Ratelimit\Time\TimeAdapterInterface;

abstract class AbstractFixedWindowTest extends AbstractThrottlerTestCase
{
    const TIME_LIMIT = 4;

    /**
     * @var int
     */
    private $startTime;

    /**
     * @var TimeAdapterInterface|M\MockInterface
     */
    private $timeAdapter;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->timeAdapter = M::mock(TimeAdapterInterface::class);
        $this->timeAdapter->shouldReceive('now')->andReturn($this->startTime = time())->byDefault();

        parent::setUp();
    }

    public function testWindowIsFixed()
    {
        $throttle = $this->ratelimiter->get('window-is-fixed');

        for ($i = -1; $i < $this->getMaxAttempts(); $i++) {
            $throttle->hit();
        }

        //override time
        $this->timeAdapter->shouldReceive('now')->andReturn($this->startTime + self::TIME_LIMIT + 1);

        self::assertEquals(0, $throttle->count());
    }

    /**
     * @inheritdoc
     */
    protected function createRatelimiter()
    {
        return new RateLimiter(
            new TimeAwareThrottlerFactory($this->createCacheAdapter(), $this->timeAdapter),
            new HydratorFactory(),
            new FixedWindowSettings($this->getMaxAttempts(), self::TIME_LIMIT)
        );
    }
}
