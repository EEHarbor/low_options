<?php

namespace LowOptionsTests;

class ArchiveStubHelperTest extends TestCase
{
    /**
     * Load the original executable stub through the normal archive loader.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        class_exists('Low\\Options\\FluxCapacitor\\Base\\Pi');
    }

    /**
     * Compare the stub manifest and every extracted member with native Phar data.
     *
     * @return void
     */
    public function testOriginalArchiveManifestAndContents(): void
    {
        $path = LOW_OPTIONS_ROOT . '/low_options/low_options.phar';
        $archive = new \Phar($path);
        $stream = fopen($path, 'rb');
        try {
            fseek($stream, \Extract_low_options::LEN);
            $length = unpack('V', fread($stream, 4))[1];
            $manifest = \Extract_low_options::_unpack(fread($stream, $length));
            $dataStart = ftell($stream);
            self::assertCount(count($archive), $manifest['m']);
            self::assertSame(0, $manifest['c']);
            $offset = 0;
            foreach ($manifest['m'] as $member => $entry) {
                $file = $archive[$member];
                self::assertSame($file->getSize(), $entry[0], $member);
                self::assertSame($file->getMTime(), $entry[1], $member);
                self::assertSame($file->getCompressedSize(), $entry[2], $member);
                self::assertSame(sprintf('%u', $file->getCRC32()), $entry[3], $member);
                self::assertSame($offset, $entry[7], $member);
                fseek($stream, $dataStart + $entry[7]);
                self::assertSame($file->getContent(), \Extract_low_options::extractFile($member, $entry, $stream), $member);
                $offset += $entry[2];
            }
        } finally {
            fclose($stream);
        }
    }

    /**
     * Preserve aliases, serialized metadata, entry flags and compressed offsets.
     *
     * @return void
     */
    public function testManifestMetadataAndCompressionFlags(): void
    {
        $alias = 'fixture.phar';
        $metadata = serialize(array('zero' => 0));
        $fileMetadata = serialize(array('nullable' => null));
        $entries = array(
            'empty.bin' => array(0, 1700000000, 0, '0', 0, 0),
            'gzip.bin' => array(9, 1700000001, 7, '4294967295', \Extract_low_options::GZ, strlen($fileMetadata)),
            'bzip.bin' => array(15, 1700000002, 11, '2147483648', \Extract_low_options::BZ2, 0),
        );
        $manifest = pack('VvVV', count($entries), 0x11, 0, strlen($alias)) . $alias . pack('V', strlen($metadata)) . $metadata;
        foreach ($entries as $name => $entry) {
            $manifest .= pack('V', strlen($name)) . $name . pack('V6', ...$entry);
            if ($entry[5]) {
                $manifest .= $fileMetadata;
            }
        }
        $parsed = \Extract_low_options::_unpack($manifest);
        self::assertSame(\Extract_low_options::GZ | \Extract_low_options::BZ2, $parsed['c']);
        $offset = 0;
        foreach ($entries as $name => $entry) {
            $entry[7] = $offset;
            self::assertSame($entry, $parsed['m'][$name]);
            $offset += $entry[2];
        }
        self::assertSame(array('c' => 0), \Extract_low_options::_unpack(pack('VvVVV', 0, 0x11, 0, 0, 0)));
    }

    /**
     * Extract exact binary bytes across the read chunk and compression boundaries.
     *
     * @dataProvider extractionProvider
     * @param int $size
     * @param string $compression
     * @return void
     */
    public function testBinaryExtractionBoundaries($size, $compression): void
    {
        $body = '';
        for ($block = 0; strlen($body) < $size; $block++) {
            $body .= hash('sha256', (string) $block, true);
        }
        $body = substr($body, 0, $size);
        $data = $body;
        $flags = 0;
        if ($compression === 'gzip') {
            $data = gzdeflate($body);
            $flags = \Extract_low_options::GZ;
        }
        if ($compression === 'bzip') {
            $data = bzcompress($body);
            $flags = \Extract_low_options::BZ2;
        }
        if ($size >= 8192) {
            self::assertGreaterThanOrEqual(8192, strlen($data), 'The encoded fixture must exercise a full source read chunk.');
        }
        $entry = array(strlen($body), 0, strlen($data), sprintf('%u', crc32($body) & 0xffffffff), $flags);
        $stream = fopen('php://memory', 'w+b');
        try {
            fwrite($stream, $data);
            rewind($stream);
            self::assertSame($body, \Extract_low_options::extractFile('fixture.bin', $entry, $stream));
            self::assertSame(strlen($data), ftell($stream));
        } finally {
            fclose($stream);
        }
    }

    /**
     * Cover empty, partial, exact and repeated read chunks for each native codec.
     *
     * @return array
     */
    public static function extractionProvider(): array
    {
        $cases = array();
        foreach (array(0, 1, 8191, 8192, 8193, 20000) as $size) {
            foreach (array('plain', 'gzip', 'bzip') as $compression) {
                $cases[$size . '/' . $compression] = array($size, $compression);
            }
        }
        return $cases;
    }

    /**
     * Resolve the Unix temporary override and fallback without leaking environment.
     *
     * @return void
     */
    public function testUnixTemporaryDirectorySelection(): void
    {
        $before = getenv('TMPDIR');
        try {
            putenv('TMPDIR=/private/tmp/low-options-uncreated-fixture');
            self::assertSame('/private/tmp/low-options-uncreated-fixture', \Extract_low_options::tmpdir());
            putenv('TMPDIR');
            self::assertSame(realpath('/tmp'), \Extract_low_options::tmpdir());
        } finally {
            putenv($before === false ? 'TMPDIR' : 'TMPDIR=' . $before);
        }
    }

    /**
     * Remove owned extraction trees and return to the original working directory.
     *
     * @dataProvider cleanupProvider
     * @param array $files
     * @param array $directories
     * @param array $links
     * @return void
     */
    public function testTemporaryExtractionTreeCleanup(array $files, array $directories, array $links = array()): void
    {
        $temporary = sys_get_temp_dir() . '/low-options-stub-' . bin2hex(random_bytes(8));
        $original = getcwd();
        mkdir($temporary, 0700);
        try {
            foreach ($directories as $directory) {
                mkdir($temporary . '/' . $directory, 0700, true);
            }
            foreach ($links as $link => $target) {
                symlink($temporary . '/' . $target, $temporary . '/' . $link);
            }
            foreach ($files as $file) {
                file_put_contents($temporary . '/' . $file, 'fixture');
            }
            \Extract_low_options::_removeTmpFiles($temporary, $original);
            self::assertDirectoryDoesNotExist($temporary);
            self::assertSame($original, getcwd());
        } finally {
            chdir($original);
            if (is_dir($temporary)) {
                $remaining = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($temporary, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
                foreach ($remaining as $file) {
                    if ($file->isLink() || !$file->isDir()) {
                        unlink($file->getPathname());
                        continue;
                    }
                    rmdir($file->getPathname());
                }
                rmdir($temporary);
            }
        }
    }

    /**
     * Include the recursive nonempty directory path promised by the cleanup code.
     *
     * @return array
     */
    public static function cleanupProvider(): array
    {
        return array(
            'empty' => array(array(), array()),
            'files' => array(array('first.bin', 'second.bin'), array()),
            'empty subdirectory' => array(array('first.bin'), array('nested')),
            'nonempty nested directory' => array(array('nested/child.bin'), array('nested')),
            'dangling symlink robustness' => array(array(), array(), array('dangling' => 'absent')),
        );
    }
}
