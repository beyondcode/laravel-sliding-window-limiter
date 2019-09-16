<?php

namespace BeyondCode\SlidingWindowLimiter;

use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Redis;
use Illuminate\Redis\Connections\Connection;

class SlidingWindowLimiter
{
    /** @var int */
    private $timeWindow;

    /** @var int */
    private $limit;

    /** @var int */
    private $minimumInterval;

    public function __construct(CarbonInterval $timeWindow, int $limit, CarbonInterval $minimumInterval = null)
    {
        $this->timeWindow = $timeWindow->totalSeconds;

        $this->limit = $limit;

        $this->minimumInterval = $minimumInterval ?? CarbonInterval::minute();
    }

    public static function create(CarbonInterval $timeWindow, int $limit, CarbonInterval $minimumInterval = null): self
    {
        return new static($timeWindow, $limit, $minimumInterval);
    }
    
    public function attempt(string $resource): bool
    {
        $redis = $this->getConnection();

        $key = $this->buildKey($resource);

        $currentTimestamp = now()->round($this->minimumInterval)->timestamp;

        $allowsAttempt = $this->getUsage($resource) < $this->limit;

        if ($allowsAttempt) {
            $redis->hincrby($key, $currentTimestamp, 1);

            $redis->expire($key, $this->timeWindow);
        }

        return $allowsAttempt;
    }

    public function getRemaining(string $resource): int
    {
        $remaining = $this->limit - $this->getUsage($resource);

        return max(0, $remaining);
    }

    private function getConnection(): Connection
    {
        return Redis::connection(config('limiter.connection'));
    }

    private function buildKey(string $resource): string
    {
        return config('sliding-limiter.prefix') . $resource;
    }

    public function getUsage(string $resource): int
    {
        $usage = $this->getConnection()->hgetall($this->buildKey($resource));

        $minimumTimestamp = now()->subSeconds($this->timeWindow)->floor($this->minimumInterval)->timestamp;

        $totalAttempts = 0;

        foreach ($usage as $timestamp => $attempts) {
            if ($timestamp < $minimumTimestamp) {
                $this->getConnection()->hdel($resource, [$timestamp]);
                continue;
            }

            $totalAttempts += $attempts;
        }

        return $totalAttempts;
    }

    public function reset(string $resource)
    {
        $key = $this->buildKey($resource);

        $this->getConnection()->del([$key]);
    }
}
