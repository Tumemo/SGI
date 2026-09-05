<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$directories = [
    $root . DIRECTORY_SEPARATOR . 'src',
    $root . DIRECTORY_SEPARATOR . 'config',
    $root . DIRECTORY_SEPARATOR . 'api',
    $root . DIRECTORY_SEPARATOR . 'views',
    $root . DIRECTORY_SEPARATOR . 'public',
    $root . DIRECTORY_SEPARATOR . 'index.php',
];

$iterator = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        static function (SplFileInfo $fileInfo): bool {
            return $fileInfo->getFilename() !== 'vendor';
        }
    )
);

$failed = false;
foreach ($iterator as $file) {
    if (!$file instanceof SplFileInfo || $file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $isSource = false;
    foreach ($directories as $directory) {
        $isExactFile = str_ends_with($directory, '.php') && $path === $directory;
        if ($isExactFile || str_starts_with($path, $directory . DIRECTORY_SEPARATOR)) {
            $isSource = true;
            break;
        }
    }

    if (!$isSource) {
        continue;
    }

    passthru(PHP_BINARY . ' -l ' . escapeshellarg($path), $exitCode);
    $failed = $failed || $exitCode !== 0;
}

exit($failed ? 1 : 0);
