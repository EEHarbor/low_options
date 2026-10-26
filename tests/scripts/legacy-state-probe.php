<?php

error_reporting(-1);
ini_set('display_errors', 'stderr');
require_once dirname(__DIR__) . '/Support/Diagnostics.php';
set_error_handler(\LowOptionsTests\Diagnostics::handler());
define('LOW_OPTIONS_ROOT', dirname(__DIR__, 2));
$options = json_decode($argv[1], true, 512, JSON_THROW_ON_ERROR);
define('BASEPATH', LOW_OPTIONS_ROOT . '/tests/');
define('PATH_THIRD', sys_get_temp_dir() . '/');
define('APPPATH', sys_get_temp_dir() . '/system/');
define('APP_VER', $options['version']);
define('REQ', 'CP');
define('BASE', isset($options['base']) ? $options['base'] : '/admin.php?S=0');
define('AMP', '&amp;');
require LOW_OPTIONS_ROOT . '/tests/Support/Environment.php';
require LOW_OPTIONS_ROOT . '/tests/Support/LegacyState.php';
require LOW_OPTIONS_ROOT . '/tests/Support/functions.php';
$ee = new \LowOptionsTests\Environment();
$ee->session = new \LowOptionsTests\LegacySession();
$ee->load = new \LowOptionsTests\LegacyLoader();
$ee->pagination = new \LowOptionsTests\LegacyPagination();
$GLOBALS['low_options_ee'] = $ee;
if (getenv('LOW_OPTIONS_LEGACY_COVERAGE')) {
    if (!function_exists('xdebug_start_code_coverage')) {
        throw new RuntimeException('Native legacy coverage requires Xdebug.');
    }
    $label = $options['mode'] . '-' . substr(hash('sha256', $argv[1]), 0, 12);
    $report = getenv('LOW_OPTIONS_LEGACY_COVERAGE') . '.' . $label . '.' . getmypid() . '.json';
    $handle = fopen($report, 'x');
    xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE | XDEBUG_CC_BRANCH_CHECK);
    register_shutdown_function(function () use ($handle, $report, $options) {
        $coverage = array_filter(xdebug_get_code_coverage(), function ($file) {
            return strpos($file, LOW_OPTIONS_ROOT . '/low_options/') !== false;
        }, ARRAY_FILTER_USE_KEY);
        fwrite($handle, json_encode($coverage, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        fclose($handle);
        $metadata = array(
            'php' => PHP_VERSION, 'xdebug' => phpversion('xdebug'), 'source_root' => LOW_OPTIONS_ROOT,
            'baseline' => 'ffa06e5eed4ea35657d3175a00fc82a297f8b912',
            'archive_sha256' => hash_file('sha256', LOW_OPTIONS_ROOT . '/low_options/low_options.phar'),
            'raw_sha256' => hash_file('sha256', $report), 'ee_version' => APP_VER, 'request_type' => REQ,
            'options' => $options, 'locale_ctype' => setlocale(LC_CTYPE, 0),
            'opcache_enable_cli' => ini_get('opcache.enable_cli'),
            'scope' => 'Direct unchanged AbstractBridge/StaticCache/EE2 PHAR imports; no historical EE boot.',
        );
        file_put_contents($report . '.context', json_encode($metadata, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    });
}
foreach (array('Bridge/AbstractBridge', 'Conduit/StaticCache', 'Bridge/EE2') as $entry) {
    require 'phar://' . LOW_OPTIONS_ROOT . '/low_options/low_options.phar/FluxCapacitor/' . $entry . '.php';
}
$bridge = new \Low\Options\FluxCapacitor\Bridge\EE2(array('module' => 'low_options', 'module_name' => 'Low Options'));
if ($options['mode'] === 'url') {
    $ee->session->userdata = $options['userdata'];
    $ee->config->items = array_merge($ee->config->items, $options['config']);
    echo json_encode(array('base' => $bridge->getBaseURL('history'), 'module' => $bridge->moduleURL('edit', array('page' => 0))), JSON_THROW_ON_ERROR);
    exit(0);
}
if ($options['mode'] === 'view') {
    if ($options['writable']) {
        mkdir(PATH_THIRD . 'low_options/views', 0700, true);
    }
    $result = $bridge->view('fixture', array('zero' => 0, 'ee_ver' => 'caller'), $options['return'], $options['string']);
    $path = PATH_THIRD . 'low_options/views/dynamic.php';
    echo json_encode(array('result' => $result, 'calls' => $ee->load->calls, 'dynamic' => file_exists($path) ? file_get_contents($path) : false), JSON_THROW_ON_ERROR);
    exit(0);
}
if ($options['mode'] === 'pagination') {
    $ee->input->get = $options['get'];
    $data = array('page' => $bridge->getCurrentPage(array('per_page' => 10)), 'start' => $bridge->getStartNum(array('current_page' => 3, 'per_page' => 10)));
    $pagination = array('total_rows' => 42, 'per_page' => 10, 'base_url' => '/items');
    if ($options['current']) {
        $pagination['current_page'] = 3;
    }
    $data['pagination'] = $bridge->pagination($pagination);
    $data['calls'] = $ee->pagination->calls;
    $data['loader'] = $ee->load->calls;
    echo json_encode($data, JSON_THROW_ON_ERROR);
    exit(0);
}
throw new InvalidArgumentException('Unknown legacy state mode.');
