<?php

declare(strict_types=1);

namespace App\Shared\Http;

/** Isolates remaining procedural handlers from routing and page rendering. */
final class LegacyEndpoint
{
    public function execute(string $file): void
    {
        $directory = getcwd();
        try {
            chdir(dirname($file));
            require $file;
        } finally {
            if (is_string($directory)) {
                chdir($directory);
            }
        }
    }
}
