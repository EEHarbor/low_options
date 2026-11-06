<?php

error_reporting(-1);
ini_set('display_errors', 'stderr');
require_once dirname(__DIR__) . '/Support/Diagnostics.php';
set_error_handler(\LowOptionsTests\Diagnostics::handler());
define('LOW_OPTIONS_ROOT', dirname(__DIR__, 2));
$context = $argv[1];
$options = json_decode($argv[2], true, 512, JSON_THROW_ON_ERROR);
require LOW_OPTIONS_ROOT . '/tests/Support/NativeCrypto.php';

if ($context === 'queue') {
    putenv('LOW_OPTIONS_CRYPTO_COVERAGE');
    $expected = array();
    foreach (array('payload', 'updater', 'version', 'public-key') as $mode) {
        foreach (array(false, true) as $seeded) {
            if ($seeded) {
                openssl_pkey_get_public('not a public key');
                openssl_pkey_get_public('not a public key');
            }
            $errors = array();
            while (($error = openssl_error_string()) !== false) {
                $errors[] = $error;
            }
            if ($seeded && !$errors) {
                throw new RuntimeException('The queue sentinel did not seed a native error.');
            }
            if (!$seeded && $errors) {
                throw new RuntimeException('The empty queue sentinel inherited a native error.');
            }
            if ($seeded) {
                openssl_pkey_get_public('not a public key');
                openssl_pkey_get_public('not a public key');
            }
            $result = \LowOptionsTests\NativeCrypto::run($mode, array('record' => array()));
            if ($result[0] !== 0 || $result[2] !== '') {
                throw new RuntimeException('Queue sentinel child failed: ' . $result[1] . $result[2]);
            }
            $after = array();
            while (($error = openssl_error_string()) !== false) {
                $after[] = $error;
            }
            $expected[$mode . '/' . ($seeded ? 'seeded' : 'empty')] = $errors === $after;
        }
    }
    echo json_encode($expected, JSON_THROW_ON_ERROR);
    exit(0);
}

define('BASEPATH', LOW_OPTIONS_ROOT . '/tests/');
define('PATH_THIRD', LOW_OPTIONS_ROOT . '/');
define('APP_VER', '7.5.0');
define('REQ', 'PAGE');
define('PATH_CACHE', sys_get_temp_dir() . '/cache/');
require LOW_OPTIONS_ROOT . '/tests/Support/Environment.php';
require LOW_OPTIONS_ROOT . '/tests/Support/functions.php';
$ee = new \LowOptionsTests\Environment();
$GLOBALS['low_options_ee'] = $ee;
$ee->db->queryResult->num_rows = 1;
$ee->db->queryResult->record = array('Column_name' => 'license_key', 'Non_unique' => '0');
$ee->config->items['base_url'] = 'https://example.test/';

if (getenv('LOW_OPTIONS_CRYPTO_COVERAGE')) {
    if (!function_exists('xdebug_start_code_coverage')) {
        throw new RuntimeException('Isolated crypto coverage requires Xdebug.');
    }
    $label = $context;
    if ($context === 'payload') {
        $label .= empty($options['record']) ? '-absent' : '-installed';
    }
    if ($context === 'version' && !empty($options['development'])) {
        $label .= '-development';
    }
    $report = getenv('LOW_OPTIONS_CRYPTO_COVERAGE') . '.' . $label . '.' . getmypid() . '.json';
    $handle = fopen($report, 'x');
    xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE | XDEBUG_CC_BRANCH_CHECK);
    register_shutdown_function(function () use ($handle) {
        $coverage = array_filter(xdebug_get_code_coverage(), function ($file) {
            return strpos($file, LOW_OPTIONS_ROOT . '/low_options/') !== false;
        }, ARRAY_FILTER_USE_KEY);
        fwrite($handle, json_encode($coverage, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        fclose($handle);
    });
}
require LOW_OPTIONS_ROOT . '/low_options/autoload.php';

if ($context === 'updater') {
    require LOW_OPTIONS_ROOT . '/tests/Fixtures/Updater.php';
    $updater = new Low_options_upd();
    $data = array('version' => $updater->version, 'module_name' => $updater->module_name, 'footer_count' => count($ee->cp->footer_item));
    $ee->db->calls = array();
    $ee->functions->cleared = array();
    $data['installed'] = $updater->install();
    $data['cleared'] = $ee->functions->cleared;
    $data['calls'] = $ee->db->calls;
    echo json_encode($data, JSON_THROW_ON_ERROR);
    exit(0);
}

$version = new \Low\Options\FluxCapacitor\Conduit\Version();
if ($context === 'public-key') {
    $method = new ReflectionMethod($version, 'getOpenSSLPublicKey');
    if (PHP_VERSION_ID < 80100) {
        $method->setAccessible(true);
    }
    echo json_encode(array('loaded' => $method->invoke($version) !== false), JSON_THROW_ON_ERROR);
    exit(0);
}

if ($context === 'version') {
    if (!empty($options['development'])) {
        $_GET['ldev'] = '1';
    }
    $version->check();
    $first = $ee->cp->footer_item;
    $second = $version->check();
    echo json_encode(array('first' => $first, 'second' => $second, 'footer' => $ee->cp->footer_item), JSON_THROW_ON_ERROR);
    exit(0);
}

if ($context !== 'payload') {
    throw new InvalidArgumentException('Unknown crypto context: ' . $context);
}
$module = new \LowOptionsTests\Query();
$module->record = $options['record'];
$ee->db->results['modules'] = $module;
$ee->config->items['site_license_key'] = 'test-site-key';
$key = openssl_pkey_get_private(file_get_contents(LOW_OPTIONS_ROOT . '/tests/Fixtures/version-payload.pem'));
if ($key === false) {
    throw new RuntimeException('Could not load the generated test private key.');
}
$details = openssl_pkey_get_details($key);
$property = new ReflectionProperty($version, 'public_key');
if (PHP_VERSION_ID < 80100) {
    $property->setAccessible(true);
}
$property->setValue($version, base64_encode($details['key']));
$version->check();
$matched = preg_match('/\?([a-f0-9]+)/i', $ee->cp->footer_item[0], $matches);
$decrypted = $matched === 1 && openssl_private_decrypt(hex2bin($matches[1]), $payload, $key);
echo json_encode(array(
    'pro' => (array) $version->pro, 'calls' => $ee->db->calls,
    'footer_count' => count($ee->cp->footer_item), 'matched' => $matched,
    'decrypted' => $decrypted, 'payload' => $decrypted ? json_decode($payload, true, 512, JSON_THROW_ON_ERROR) : null,
), JSON_THROW_ON_ERROR);
