<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

$root = dirname(__DIR__, 2);
$temporary = sys_get_temp_dir() . '/low-options-nav-' . bin2hex(random_bytes(8));
mkdir($temporary . '/low_options/Conduit', 0700, true);
register_shutdown_function(function () use ($temporary) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temporary, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        if ($file->isLink() || !$file->isDir()) {
            unlink($file->getPathname());
        } else {
            rmdir($file->getPathname());
        }
    }
    rmdir($temporary);
});
foreach (array('low_options.phar', 'addon.setup.php', 'addon.json', 'autoload.php') as $file) {
    symlink($root . '/low_options/' . $file, $temporary . '/low_options/' . $file);
}
copy($root . '/tests/Fixtures/custom-nav.php', $temporary . '/low_options/Conduit/McpNav.php');

define('LOW_OPTIONS_ROOT', $root);
define('BASEPATH', $root . '/tests/');
define('PATH_THIRD', $temporary . '/');
define('APP_VER', '7.5.0');
define('REQ', 'CP');
define('PATH_CACHE', $temporary . '/cache/');
require $root . '/tests/Support/Environment.php';
require $root . '/tests/Support/functions.php';
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

if (getenv('LOW_OPTIONS_NAV_COVERAGE')) {
    if (!function_exists('xdebug_start_code_coverage')) {
        throw new RuntimeException('Xdebug branch instrumentation is required.');
    }
    xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE | XDEBUG_CC_BRANCH_CHECK);
    register_shutdown_function(function () {
        $coverage = array_filter(xdebug_get_code_coverage(), function ($file) {
            return strpos($file, 'low_options.phar/FluxCapacitor/Conduit/McpNav.php') !== false;
        }, ARRAY_FILTER_USE_KEY);
        file_put_contents(getenv('LOW_OPTIONS_NAV_COVERAGE'), json_encode($coverage, JSON_PRETTY_PRINT));
    });
}
require $root . '/low_options/autoload.php';
require $temporary . '/low_options/Conduit/McpNav.php';

/**
 * Exercise a valid installed-addon navigation context using original PHAR code.
 *
 * @param string $context
 * @return array
 * @throws InvalidArgumentException
 */
function navContext($context)
{
    $GLOBALS['low_options_ee'] = new \LowOptionsTests\Environment();
    ee()->services['CP/Sidebar'] = new \LowOptionsTests\Sidebar();
    ee()->uri->path = 'cp/addons/settings/low_options/detail';
    \Low\Options\FluxCapacitor\Conduit\StaticCache::clear();
    $GLOBALS['nav_fixture'] = array(
        'items' => array('/' => 'Home', 'history' => 'History', 'HEADINGgroup' => 'Group', 'https://example.test' => 'External'),
        'buttons' => array('history' => array('new' => 'Add')),
        'map' => array('detail' => 'history'),
        'defer' => false,
    );
    if ($context === 'empty-alias') {
        $GLOBALS['nav_fixture']['items'] = array('' => 'Home', 'history' => 'History');
        $GLOBALS['nav_fixture']['buttons'] = array();
        $GLOBALS['nav_fixture']['map'] = array('detail' => '/');
    }
    if ($context === 'existing-map') {
        $GLOBALS['nav_fixture']['items'] = array('' => 'Home', 'history' => 'History');
        $GLOBALS['nav_fixture']['buttons'] = array();
        $GLOBALS['nav_fixture']['map'] = array('detail' => '', 'low_options' => 'history');
        ee()->uri->path = 'cp/addons/settings/low_options';
    }
    if (in_array($context, array('direct', 'defer'), true)) {
        $GLOBALS['nav_fixture']['items'] = array('history' => 'History', 'license' => 'License');
        $GLOBALS['nav_fixture']['buttons'] = array();
        $GLOBALS['nav_fixture']['map'] = array();
        $GLOBALS['nav_fixture']['defer'] = $context === 'defer';
        ee()->uri->path = 'cp/addons/settings/low_options/history';
    }
    if ($context === 'inactive') {
        $GLOBALS['nav_fixture']['map'] = array();
        ee()->uri->path = 'cp/addons/settings/low_options/unknown';
    }
    if (!in_array($context, array('full', 'empty-alias', 'existing-map', 'direct', 'defer', 'inactive', 'empty'), true)) {
        throw new InvalidArgumentException('Unknown navigation context: ' . $context);
    }
    $nav = $context === 'empty' ? new \Low\Options\Conduit\EmptyNav() : new \Low\Options\Conduit\McpNav();
    $deferred = $nav->sidebar === null;
    if ($deferred) {
        $nav->generateNav();
        $nav->postGenerateNav();
    }
    $items = array();
    foreach (ee()->services['CP/Sidebar']->items as $item) {
        $buttons = array_map(function ($button) {
            return array($button[0], $button[1]->path);
        }, $item->buttons);
        $items[] = array(
            'title' => $item->title,
            'active' => $item->active,
            'url' => $item->url instanceof \LowOptionsTests\Url ? $item->url->path : $item->url,
            'buttons' => $buttons,
        );
    }
    $active = $nav->getActiveItem();
    $result = array('items' => $items, 'active_title' => $active ? $active->title : null, 'deferred' => $deferred, 'breadcrumbs' => count(ee()->cp->breadcrumbs));
    if ($context === 'full') {
        $nav->setToolbarIcon();
        $result['default_toolbar'] = ee()->view->header['title'];
        $nav->setToolbarIcon('Custom', 'history', 'gear');
        $result['custom_toolbar'] = array(ee()->view->header['title'], ee()->view->header['toolbar_items']['gear']['href']->path);
        $result['slash_url'] = $nav->getUrl('/')->path;
        $result['external_url'] = $nav->getUrl('https://example.test');
    }
    $result['active_reference'] = $active === null ? null : in_array($active, ee()->services['CP/Sidebar']->items, true);
    return $result;
}

try {
    $context = isset($argv[1]) ? $argv[1] : 'full';
    if ($context === 'all') {
        $result = array();
        foreach (array('full', 'empty-alias', 'existing-map', 'direct', 'defer', 'inactive', 'empty') as $case) {
            $result[$case] = navContext($case);
        }
    } else {
        $result = navContext($context);
    }
    echo json_encode($result);
} catch (Throwable $error) {
    fwrite(STDERR, get_class($error) . ': ' . $error->getMessage() . ' at ' . $error->getFile() . ':' . $error->getLine() . "\n");
    exit(1);
}
