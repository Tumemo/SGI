<?php

declare(strict_types=1);

namespace SGITests\Unit\Support;

use PHPUnit\Framework\TestCase;
use SGITests\Support\ProcessExitCode;

final class ProcessExitCodeTest extends TestCase
{
    public function testObservedExitCodeIsUsedWhenClosingReturnsUnknown(): void
    {
        self::assertSame(0, ProcessExitCode::resolve(-1, 0));
        self::assertSame(7, ProcessExitCode::resolve(-1, 7));
    }

    public function testCloseExitCodeTakesPrecedenceAndUnknownDoesNotBecomeSuccess(): void
    {
        self::assertSame(0, ProcessExitCode::resolve(0, 7));
        self::assertSame(7, ProcessExitCode::resolve(7, 0));
        self::assertSame(-1, ProcessExitCode::resolve(-1, null));
        self::assertSame(-1, ProcessExitCode::resolve(-1, -1));
    }

    public function testSubprocessFailureRemainsVisibleAfterStatusPolling(): void
    {
        $pipes = [];
        $process = proc_open(
            [PHP_BINARY, '-r', 'exit(7);'],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );
        self::assertIsResource($process);
        fclose($pipes[0]);

        $deadline = microtime(true) + 5.0;
        do {
            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }
            usleep(1000);
        } while (microtime(true) < $deadline);

        if ($status['running']) {
            proc_terminate($process);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
            self::fail('The subprocess did not exit within the test deadline.');
        }

        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(7, ProcessExitCode::resolve(proc_close($process), $status['exitcode']));
    }
}
