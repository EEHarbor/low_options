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
        if (strpos($serialized, 'S:') !== false) {
            $serialized = self::normalizeEscapedStrings($serialized);
        }
        $parse_line = 0;
        $invalid = false;
        $previous = null;
        $previous = set_error_handler(static function ($severity, $message, $file, $line) use (&$parse_line, &$invalid, &$previous) {
            if ($file === __FILE__ && $line === $parse_line && in_array($severity, array(E_NOTICE, E_WARNING), true) && preg_match('/^(?:unserialize\(\): (?:Error at offset [0-9]+ of [0-9]+ bytes|Unexpected end of serialized data|Maximum depth of [0-9]+ exceeded.*)|Insufficient data for unserializing - -?[0-9]+ required, [0-9]+ present)$/D', $message)) {
                $invalid = true;
                return true;
            }
            // Native parsing retains clamped integers and complete prefixes with unused bytes.
            if ($file === __FILE__ && $line === $parse_line && in_array($severity, array(E_NOTICE, E_WARNING), true) && preg_match('/^unserialize\(\): (?:Numerical result out of range|Extra data starting at offset [0-9]+ of [0-9]+ bytes)$/D', $message)) {
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

    /**
     * Rewrite known string tokens and leave the remaining bytes to native parsing.
     *
     * @param string $value
     * @return string
     */
    private static function normalizeEscapedStrings($value)
    {
        $length = strlen($value);
        $position = 0;
        $result = '';
        $depth = 0;

        while ($position < $length) {
            $start = $position;
            $type = $value[$position];

            if ($type === '}') {
                $result .= '}';
                $position++;
                $depth--;
                if ($depth <= 0) {
                    return $result . substr($value, $position);
                }
                continue;
            }

            if (preg_match('/\G(?:N;|[bidrR]:[^;]*;|a:[0-9]+:\{)/', $value, $match, 0, $position)) {
                $result .= $match[0];
                $position += strlen($match[0]);
                if (substr($match[0], -1) === '{') {
                    $depth++;
                }
                if ($depth === 0) {
                    return $result . substr($value, $position);
                }
                continue;
            }

            if (!preg_match('/\G([sSEOC]):([0-9]+):"/', $value, $match, 0, $position)) {
                return $result . substr($value, $start);
            }

            $size = (int) $match[2];
            $position += strlen($match[0]);
            $contents = '';

            if ($type === 'S') {
                for ($i = 0; $i < $size; $i++) {
                    if ($position >= $length) {
                        return $result . substr($value, $start);
                    }
                    $byte = $value[$position++];
                    if ($byte === '\\') {
                        $hex = substr($value, $position, 2);
                        if (strlen($hex) !== 2 || strspn($hex, '0123456789abcdefABCDEF') !== 2) {
                            return $result . substr($value, $start);
                        }
                        $byte = chr(hexdec($hex));
                        $position += 2;
                    }
                    $contents .= $byte;
                }
            } else {
                if ($size > $length - $position) {
                    return $result . substr($value, $start);
                }
                $position += $size;
            }

            if ($type === 'O' || $type === 'C') {
                if (!preg_match('/\G":([+-]?[0-9]+):\{/', $value, $tail, 0, $position)) {
                    return $result . substr($value, $start);
                }
                $position += strlen($tail[0]);
                if ($type === 'C') {
                    $payload_length = (int) $tail[1];
                    if ($payload_length < 0 || $payload_length >= $length - $position || $value[$position + $payload_length] !== '}') {
                        return $result . substr($value, $start);
                    }
                    $position += $payload_length + 1;
                }
                $result .= substr($value, $start, $position - $start);
                if ($type === 'O') {
                    $depth++;
                }
                if ($depth === 0) {
                    return $result . substr($value, $position);
                }
                continue;
            }

            if (substr($value, $position, 2) !== '";') {
                return $result . substr($value, $start);
            }
            $position += 2;
            $result .= $type === 'S' ? 's:' . strlen($contents) . ':"' . $contents . '";' : substr($value, $start, $position - $start);
            if ($depth === 0) {
                return $result . substr($value, $position);
            }
        }

        return $result;
    }
}
