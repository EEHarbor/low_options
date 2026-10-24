<?php

// The shared foundation gulp task's substitutions and PHAR packaging stay local here.
$root = dirname(__DIR__);
$source = __DIR__ . '/foundation';
$target = $root . '/low_options/low_options.phar';
$temporary = sys_get_temp_dir() . '/low-options-build-' . bin2hex(random_bytes(8));
mkdir($temporary, 0700);

try {
    $contents = $temporary . '/contents';
    mkdir($contents, 0700);
    $mapping = array(
        '@@@ADDON_NAMESPACE@@@' => 'Low\\Options',
        '@@@LICENSE_CONSTANT@@@' => 'false',
        '@@@MODULE@@@' => 'low_options',
        '@@@MODULE_NAME@@@' => 'Low Options',
        '@@@FOUNDATION_VERSION@@@' => '4.11.0',
        '@@@VALIDATION_JS@@@' => file_get_contents($source . '/validation.js'),
    );
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source . '/contents', FilesystemIterator::SKIP_DOTS));
    $expected = array();
    $provenance = json_decode(file_get_contents($source . '/provenance.json'), true, 512, JSON_THROW_ON_ERROR);
    foreach ($files as $file) {
        $name = substr($file->getPathname(), strlen($source . '/contents/'));
        $bytes = str_replace(array_keys($mapping), array_values($mapping), file_get_contents($file->getPathname()));
        if (!is_dir(dirname($contents . '/' . $name))) {
            mkdir(dirname($contents . '/' . $name), 0700, true);
        }
        file_put_contents($contents . '/' . $name, $bytes);
        chmod($contents . '/' . $name, $provenance['members'][$name]['permissions']);
        $expected[$name] = hash('sha256', $bytes);
    }

    $archive = new Phar($temporary . '/low_options.phar', FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_FILENAME, 'low_options.phar');
    $archive->buildFromDirectory($contents);
    $stub = rtrim(file_get_contents($source . '/stub.php'), "\r\n") . "\r\n";
    do {
        $previous = $stub;
        $stub = preg_replace('/const LEN = [0-9]+;/', 'const LEN = ' . strlen($stub) . ';', $stub);
    } while ($previous !== $stub);
    $archive->setStub($stub);
    $archive->setSignatureAlgorithm(Phar::SHA1);

    if ($archive->getStub() !== $stub || count($archive) !== count($expected)) {
        throw new RuntimeException('Foundation archive layout verification failed.');
    }
    foreach ($expected as $name => $digest) {
        if (hash('sha256', $archive[$name]->getContent()) !== $digest) {
            throw new RuntimeException('Foundation archive member verification failed: ' . $name);
        }
    }
    unset($archive);
    rename($temporary . '/low_options.phar', $target);
    echo hash_file('sha256', $target) . "  low_options/low_options.phar\n";
} finally {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temporary, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        if ($file->isDir()) {
            rmdir($file->getPathname());
            continue;
        }
        unlink($file->getPathname());
    }
    rmdir($temporary);
}
