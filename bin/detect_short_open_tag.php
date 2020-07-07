#!/usr/bin/env php
<?php declare(strict_types=1);

// If short_open_tag is not enabled, re-execute self with them
if ('1' !== ini_get('short_open_tag')) {
    $cmd = sprintf('%s -d short_open_tag=On %s',
        PHP_BINARY,
        implode(' ', $argv)
    );
    system($cmd, $exitCode);
    exit($exitCode);
}

$fileExtensions = [];

if ($argc === 1) {
    usage_and_exit($argv[0]);
}

$options = getopt('e:', ['extension:'], $optind);
$dirs = array_slice($argv, $optind);

if (!$dirs) {
    usage_and_exit($argv[0]);
}

$fileExtensions = get_file_extensions($options);
$files = collect_files($dirs, $fileExtensions);
sort($files);

$found = false;

foreach ($files as $file) {
    $tokens = find_short_open_tags(file_get_contents($file));
    foreach ($tokens as $token) {
        $found = true;
        echo format_error($file, $token);
    }
}

if (!$found) {
    echo "No errors detected\n";
}

exit($found ? 1 : 0);

function usage_and_exit($self)
{
    echo <<<USAGE
$self [-e|--extension php] <dir1>... <dirn>
Scans the given paths for PHP files containing the use of short open tags and reports them.
  -e|--extension    File extensions to scan, defaults to 'php'
  <dir>             One or more directories to scan fo
  The script will   exit with 0 if no short open tags where found and 1 otherwise.

USAGE;

    exit(1);
}

function collect_files(array $dirs, array $fileExtensions): array
{
    $files = [];

    $regex = '/\.(?:' . implode('|', $fileExtensions) . ')$/';

    foreach ($dirs as $dir) {
        $iterator = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator(
            $iterator,
            RecursiveIteratorIterator::LEAVES_ONLY,
            RecursiveIteratorIterator::CATCH_GET_CHILD
        );
        $iterator = new RegexIterator($iterator, $regex);

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            $files[] = $file->__toString();
        }

    }

    return $files;
}

function get_file_extensions(array $options): array
{
    $fileExtensions = [];

    if (isset($options['e'])) {
        $fileExtensions = array_merge($fileExtensions, (array)$options['e']);
    }
    if (isset($options['extension'])) {
        $fileExtensions = array_merge($fileExtensions, (array)$options['extension']);
    }
    if (!$fileExtensions) {
        $fileExtensions = ['php'];
    }

    return $fileExtensions;
}

function find_short_open_tags(string $code): Generator
{
    $tokens = token_get_all($code);

    foreach ($tokens as $token) {
        if (!is_array($token)) {
            continue;
        }

        if (T_OPEN_TAG !== $token[0]) {
            continue;
        }

        if ('<?php' === trim($token[1])) {
            continue;
        }

        yield $token;
    }
}

function format_error(string $file, array $token): string
{
    return "::error file=$file,line=$token[2]::short open tag not allowed\n";
}
