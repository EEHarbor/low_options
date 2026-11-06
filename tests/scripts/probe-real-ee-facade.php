<?php

error_reporting(-1);
ini_set('display_errors', 'stderr');
require_once dirname(__DIR__) . '/Support/Diagnostics.php';
set_error_handler(\LowOptionsTests\Diagnostics::handler());

$root = dirname(__DIR__, 2);
$eeRoot = isset($argv[1]) ? rtrim($argv[1], '/') : '';
$facadeFile = $eeRoot . '/ExpressionEngine/Legacy/Facade.php';
if (!is_file($facadeFile)) {
    fwrite(STDERR, "Usage: php tests/scripts/probe-real-ee-facade.php /path/to/system/ee\n");
    exit(2);
}
define('PATH_THIRD', $root . '/');
define('APP_VER', '7.5.0');
require $facadeFile;

/**
 * Resolve the actual EE Facade for this isolated native bridge probe.
 *
 * @return ExpressionEngine\Legacy\Facade
 */
function ee()
{
    return $GLOBALS['real_ee_facade'];
}

$GLOBALS['real_ee_facade'] = new ExpressionEngine\Legacy\Facade();
require $root . '/low_options/autoload.php';
$bridge = new Low\Options\FluxCapacitor\FluxCapacitor();
$original = (object) array('name' => 'original');
$replacement = (object) array('name' => 'replacement');
ee()->set('custom', $original);
$result = array(
    'php' => PHP_VERSION,
    'declared_ee_context' => APP_VER,
    'facade_sha256' => hash_file('sha256', $facadeFile),
    'archive_sha256' => hash_file('sha256', $root . '/low_options/low_options.phar'),
);
try {
    $bridge->overwriteEEClass('custom', $replacement);
    $result['replacement_succeeded'] = ee()->get('custom') === $replacement;
} catch (Throwable $error) {
    $result['replacement_succeeded'] = false;
    $result['error'] = get_class($error) . ': ' . $error->getMessage();
    $result['error_file'] = $error->getFile();
    $result['error_line'] = $error->getLine();
}
$result['original_retained'] = ee()->get('custom') === $original;
$bridge->removeEEClass('custom');
$result['removed'] = !ee()->has('custom');
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
exit($result['replacement_succeeded'] && $result['removed'] ? 0 : 1);
