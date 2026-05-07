<?php

declare(strict_types=1);

namespace App\Modules\Prinect\ValueObjects;

use InvalidArgumentException;
use Stringable;

/**
 * Identificativo composito di un workstep Prinect.
 *
 * Un workstep è univocamente identificato dalla coppia (jobId, workstepId):
 * lo stesso $workstepId può ripetersi tra job diversi.
 *
 * Encapsula la validazione (entrambi non vuoti) ed espone l'unico path che
 * conta lato API: "/rest/job/{job}/workstep/{ws}".
 */
final class WorkstepId implements Stringable
{
    public function __construct(
        public readonly string $jobId,
        public readonly string $workstepId,
    ) {
        if (trim($jobId) === '') {
            throw new InvalidArgumentException('WorkstepId: jobId non può essere vuoto.');
        }
        if (trim($workstepId) === '') {
            throw new InvalidArgumentException('WorkstepId: workstepId non può essere vuoto.');
        }
    }

    /**
     * Path REST relativo (senza base URL), utile per logging e cache key.
     */
    public function restPath(): string
    {
        return "/rest/job/{$this->jobId}/workstep/{$this->workstepId}";
    }

    /**
     * Cache key deterministica per Redis/file cache.
     */
    public function cacheKey(string $prefix = 'prinect:ws'): string
    {
        return "{$prefix}:{$this->jobId}:{$this->workstepId}";
    }

    public function __toString(): string
    {
        return "{$this->jobId}#{$this->workstepId}";
    }

    public function equals(self $other): bool
    {
        return $this->jobId === $other->jobId && $this->workstepId === $other->workstepId;
    }
}
