<?php

namespace LowOptionsTests;

final class NativeCrypto
{
    /**
     * Keep OpenSSL's irreversible error queue inside a disposable interpreter.
     *
     * @param string $context
     * @param array $options
     * @return array
     * @throws \RuntimeException
     */
    public static function run($context, array $options = array())
    {
        require_once __DIR__ . '/NativeProcess.php';
        $directives = array();
        $extension = getenv('LOW_OPTIONS_CRYPTO_XDEBUG');
        if ($extension) {
            $directives = array('zend_extension=' . $extension, 'xdebug.mode=coverage');
        }
        return NativeProcess::run(LOW_OPTIONS_ROOT . '/tests/scripts/crypto-probe.php', array($context, json_encode($options, JSON_THROW_ON_ERROR)), $directives);
    }
}
