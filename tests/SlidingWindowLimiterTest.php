<?php

namespace BeyondCode\SlidingWindowLimiter\Tests;

use BeyondCode\SlidingWindowLimiter\SlidingWindowLimiter;
use BeyondCode\SlidingWindowLimiter\SlidingWindowLimiterServiceProvider;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Orchestra\Testbench\TestCase;
use Redis;

class SlidingWindowLimiterTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();

        Redis::del('sliding-window-limiter-resource');
    }

    protected function getPackageProviders($app)
    {
        return [
            SlidingWindowLimiterServiceProvider::class
        ];
    }

    /** @test */
    public function it_limits_the_number_of_attempts()
    {
        Carbon::setTestNow('2019-01-01 00:00:00');

        $limiter = SlidingWindowLimiter::create(CarbonInterval::minute(1), 2);

        $this->assertTrue($limiter->attempt('resource'));
        $this->assertTrue($limiter->attempt('resource'));

        $this->assertFalse($limiter->attempt('resource'));
        $this->assertFalse($limiter->attempt('resource'));

        Carbon::setTestNow('2019-01-01 01:00:00');

        $this->assertTrue($limiter->attempt('resource'));
    }

    /** @test */
    public function it_limits_the_number_of_attempts_in_a_sliding_window()
    {
        Carbon::setTestNow('2019-01-01 00:00:00');

        // Limiter allows 100 attempts within a 1 hour window
        $limiter = SlidingWindowLimiter::create(CarbonInterval::hour(1), 100);

        // Make 50 attempts at 00:30:00
        Carbon::setTestNow('2019-01-01 00:30:00');
        $this->makeAttempts($limiter, 'resource', 50);

        // Make 50 attempts at 00:45:00
        Carbon::setTestNow('2019-01-01 00:45:00');
        $this->makeAttempts($limiter, 'resource', 50);

        // Attempt at 00:46 should fail
        Carbon::setTestNow('2019-01-01 00:46:00');
        $this->assertFalse($limiter->attempt('resource'));

        // Usage should be 100, since all attempts have been made
        $this->assertSame(100, $limiter->getUsage('resource'));

        // Attempt at 01:31 should go through
        Carbon::setTestNow('2019-01-01 01:31:00');
        $this->assertTrue($limiter->attempt('resource'));

        // Usage should have "released" the 50 requests from 00:30
        $this->assertSame(51, $limiter->getUsage('resource'));
    }

    /** @test */
    public function it_returns_the_usage_count()
    {
        $limiter = SlidingWindowLimiter::create(CarbonInterval::minute(), 100);

        $limiter->attempt('resource');
        $limiter->attempt('resource');

        $this->assertSame(2, $limiter->getUsage('resource'));
    }

    /** @test */
    public function it_returns_the_remaining_attempts_count()
    {
        $limiter = SlidingWindowLimiter::create(CarbonInterval::minute(), 100);

        $limiter->attempt('resource');
        $limiter->attempt('resource');

        $this->assertSame(98, $limiter->getRemaining('resource'));
    }

    /** @test */
    public function it_returns_the_remaining_attempts_count_when_the_limit_is_exceeded()
    {
        $limiter = SlidingWindowLimiter::create(CarbonInterval::minute(), 5);

        foreach(range(1,10) as $i) {
            $limiter->attempt('resource');
        }

        $this->assertSame(0, $limiter->getRemaining('resource'));
    }

    /** @test */
    public function it_returns_the_maximum_usage_when_its_over_the_limit()
    {
        $limiter = SlidingWindowLimiter::create(CarbonInterval::minute(), 5);

        foreach(range(1,10) as $i) {
            $limiter->attempt('resource');
        }

        $this->assertSame(5, $limiter->getUsage('resource'));
    }

    /** @test */
    public function it_can_reset_attempts()
    {
        $limiter = SlidingWindowLimiter::create(CarbonInterval::second(2), 2);

        $this->assertTrue($limiter->attempt('resource'));
        $this->assertTrue($limiter->attempt('resource'));
        $this->assertFalse($limiter->attempt('resource'));
        $this->assertFalse($limiter->attempt('resource'));

        $limiter->reset('resource');

        $this->assertTrue($limiter->attempt('resource'));
    }

    protected function makeAttempts(SlidingWindowLimiter $limiter, string $string, int $int)
    {
        foreach(range(1,50) as $i) {
            $limiter->attempt('resource');
        }
    }
}