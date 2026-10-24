<?php

namespace @@@ADDON_NAMESPACE@@@\FluxCapacitor\Conduit;

/** Decode stored PHP values while preserving native types and references. */
final class SerializedValue
{
    /**
     * Return false for malformed storage and retain unrelated diagnostics.
     *
     * @param string $serialized
     * @return mixed
     * @throws \Throwable
     */
    public static function decode($serialized)
    {
        $parse_line = 0;
        $invalid = false;
        $previous = null;
        $previous = set_error_handler(static function ($severity, $message, $file, $line) use (&$parse_line, &$invalid, &$previous) {
            if ($file === __FILE__ && $line === $parse_line && in_array($severity, array(E_NOTICE, E_WARNING), true) && preg_match('/^unserialize\(\): (?:Error at offset [0-9]+ of [0-9]+ bytes|Unexpected end of serialized data|Maximum depth of [0-9]+ exceeded.*)$/D', $message)) {
                $invalid = true;
                return true;
            }
            return $previous ? call_user_func_array($previous, func_get_args()) : false;
        });

        try {
            $parse_line = __LINE__ + 1;
            $value = unserialize($serialized);
            return $invalid ? false : $value;
        } finally {
            restore_error_handler();
        }
    }
}
