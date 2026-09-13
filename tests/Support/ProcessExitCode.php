<?php

declare(strict_types=1);

namespace SGITests\Support;

final class ProcessExitCode
{
    /** @param array{running: bool, exitcode: int} $status */
    public static function observe(?int $previous, array $status): ?int
    {
        if ($previous !== null && $previous >= 0) {
            return $previous;
        }

        if (!$status['running'] && $status['exitcode'] >= 0) {
            return $status['exitcode'];
        }

        return $previous;
    }

    public static function resolve(int $closeExitCode, ?int $observedExitCode): int
    {
        if ($closeExitCode !== -1) {
            return $closeExitCode;
        }

        return $observedExitCode !== null && $observedExitCode >= 0
            ? $observedExitCode
            : -1;
    }
}
