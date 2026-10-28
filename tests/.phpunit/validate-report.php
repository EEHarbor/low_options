<?php

$path = isset($argv[1]) ? $argv[1] : '';
if (!is_file($path)) {
    fwrite(STDERR, "Rejected PHPUnit execution without a fresh JUnit report.\n");
    exit(3);
}
$report = simplexml_load_file($path, 'SimpleXMLElement', LIBXML_NONET);
$cases = $report === false ? array() : $report->xpath('//testcase');
if (!$cases) {
    fwrite(STDERR, "Rejected PHPUnit selection: zero test cases executed.\n");
    exit(3);
}
