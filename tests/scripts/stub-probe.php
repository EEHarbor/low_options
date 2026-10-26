<?php

error_reporting(-1);
ini_set('display_errors', 'stderr');
require_once dirname(__DIR__) . '/Support/Diagnostics.php';
set_error_handler(\LowOptionsTests\Diagnostics::handler());
$context = $argv[1];
if ($context === 'output') {
    fwrite(STDOUT, str_repeat('A', 1048576));
    fwrite(STDERR, str_repeat('B', 1048576));
    exit(0);
}
if ($context === 'wait') {
    file_put_contents($argv[2], sys_get_temp_dir());
    usleep(5000000);
    exit(0);
}
if ($context === 'diagnostic') {
    @trigger_error('native strict sentinel', E_USER_DEPRECATED);
    exit(0);
}
$root = dirname(__DIR__, 2);
if (getenv('LOW_OPTIONS_STUB_COVERAGE')) {
    if (!function_exists('xdebug_start_code_coverage')) {
        throw new RuntimeException('Native stub coverage requires Xdebug.');
    }
    $report = getenv('LOW_OPTIONS_STUB_COVERAGE') . '.' . $context . '.' . getmypid() . '.json';
    $handle = fopen($report, 'x');
    xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE | XDEBUG_CC_BRANCH_CHECK);
    register_shutdown_function(function () use ($handle, $root) {
        $coverage = array_filter(xdebug_get_code_coverage(), function ($file) use ($root) {
            return strpos($file, $root . '/low_options/') !== false;
        }, ARRAY_FILTER_USE_KEY);
        fwrite($handle, json_encode($coverage, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        fclose($handle);
    });
}
$argv = array('probe', 'load', '7.5.0', 'Base\Pi');
ob_start();
require __DIR__ . '/native-probe.php';
$loaded = json_decode(ob_get_clean(), true, 512, JSON_THROW_ON_ERROR);
if ($loaded !== array('loaded' => true)) {
    throw new RuntimeException('The original archive did not load.');
}
$body = 'body';
$entry = array($context === 'size' ? 5 : 4, 0, 4, $context === 'checksum' ? '123' : sprintf('%u', crc32($body)), 0);
$stream = fopen('php://memory', 'w+b');
fwrite($stream, $body);
rewind($stream);
echo 'RETURNED:' . Extract_low_options::extractFile('fixture.bin', $entry, $stream);
