<?php

/**
 * Inventory function scopes using PHP tokens, excluding strings and JavaScript.
 *
 * @param string $source
 * @return array
 */
function inventoryMethods($source)
{
    $tokens = token_get_all($source);
    $methods = array();
    $line = 1;
    $count = count($tokens);
    for ($index = 0; $index < $count; $index++) {
        $token = $tokens[$index];
        $text = is_array($token) ? $token[1] : $token;
        if (is_array($token) && $token[0] === T_FUNCTION) {
            $name = '{closure}';
            $start = $token[2];
            $cursor = $index + 1;
            while ($cursor < $count) {
                $next = $tokens[$cursor];
                if (is_array($next) && $next[0] === T_STRING) {
                    $name = $next[1];
                    break;
                }
                if ($next === '(') {
                    break;
                }
                $cursor++;
            }
            while ($cursor < $count && $tokens[$cursor] !== '{' && $tokens[$cursor] !== ';') {
                $cursor++;
            }
            if ($cursor >= $count || $tokens[$cursor] === ';') {
                $index++;
                continue;
            }
            $end = $start;
            for ($scan = $index; $scan < $cursor; $scan++) {
                $part = $tokens[$scan];
                $end += substr_count(is_array($part) ? $part[1] : $part, "\n");
            }
            $depth = 0;
            for (; $cursor < $count; $cursor++) {
                $part = $tokens[$cursor];
                if ($part === '{') {
                    $depth++;
                }
                if (is_array($part) && in_array($part[0], array(T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES), true)) {
                    $depth++;
                }
                if ($part === '}') {
                    $depth--;
                }
                $end += substr_count(is_array($part) ? $part[1] : $part, "\n");
                if ($depth === 0) {
                    break;
                }
            }
            $methods[] = array('name' => $name, 'start' => $start, 'end' => $end);
        }
        $line += substr_count($text, "\n");
    }
    return $methods;
}

$root = dirname(__DIR__, 2);
$output = isset($argv[1]) ? $argv[1] : $root . '/tests/reports/inventory.json';
$measurement = isset($argv[2]) ? $argv[2] : null;
$files = array();
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/low_options', FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if ($file->isLink() || !$file->isFile() || substr($file->getPathname(), -4) !== '.php') {
        continue;
    }
    $files[substr($file->getPathname(), strlen($root) + 1)] = file_get_contents($file->getPathname());
}
$pharPath = $root . '/low_options/low_options.phar';
$phar = new Phar($pharPath);
$files['low_options/low_options.phar/<stub>.php'] = $phar->getStub();
foreach (new RecursiveIteratorIterator($phar) as $file) {
    if (substr($file->getPathname(), -4) === '.php') {
        $member = str_replace('phar://' . $pharPath . '/', '', $file->getPathname());
        $files['low_options/low_options.phar/' . $member] = $file->getContent();
    }
}
ksort($files);
$manifest = array('baseline' => 'ffa06e5eed4ea35657d3175a00fc82a297f8b912', 'archive_sha256' => hash_file('sha256', $pharPath), 'files' => array());
foreach ($files as $name => $source) {
    $manifest['files'][$name] = array('sha256' => hash('sha256', $source), 'bytes' => strlen($source), 'methods' => inventoryMethods($source));
    if ($measurement) {
        $copy = $measurement . '/' . $name;
        if (!is_dir(dirname($copy))) {
            mkdir(dirname($copy), 0700, true);
        }
        file_put_contents($copy, $source);
        if (hash_file('sha256', $copy) !== $manifest['files'][$name]['sha256']) {
            throw new RuntimeException('Measurement copy hash mismatch: ' . $name);
        }
    }
}
file_put_contents($output, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
echo count($files) . " PHP source units inventoried.\n";
