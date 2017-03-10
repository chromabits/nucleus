<?php

/**
 * Copyright 2015, Eduardo Trujillo
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * This file is part of the Nucleus package
 */

use Chromabits\Nucleus\Data\ArrayList;
use Chromabits\Nucleus\Support\Std;

if (!function_exists('mb_lcfirst')) {
    /**
     * Multi-byte version of lcfirst().
     *
     * @param string $str
     * @param null|string $encoding
     *
     * @return string
     */
    function mb_lcfirst($str, $encoding = null)
    {
        $encoding = Std::coalesce($encoding, mb_internal_encoding());

        $first = mb_strtolower(
            mb_substr($str, 0, 1, $encoding),
            $encoding
        );

        return $first . mb_substr($str, 1, null, $encoding);
    }
}

if (!function_exists('mb_ucfirst')) {
    /**
     * Multi-byte version of ucfirst().
     *
     * @param string $str
     * @param null|string $encoding
     *
     * @return string
     */
    function mb_ucfirst($str, $encoding = null)
    {
        $encoding = Std::coalesce($encoding, mb_internal_encoding());

        $first = mb_strtoupper(
            mb_substr($str, 0, 1, $encoding),
            $encoding
        );

        return $first . mb_substr($str, 1, null, $encoding);
    }
}

if (!function_exists('mb_ctype_lower')) {
    /**
     * Multi-byte version of ctype_lower().
     *
     * @param string $text
     * @param null|string $encoding
     *
     * @return bool
     */
    function mb_ctype_lower($text, $encoding = null)
    {
        $encoding = Std::coalesce($encoding, mb_internal_encoding());
        $characters = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($characters as $char) {
            if (mb_strtolower($char, $encoding) != $char) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('mb_ucwords')) {
    /**
     * Multibyte version of ucwords().
     *
     * @param string $str
     * @param string $delimiters
     * @param null|string $encoding
     *
     * @return string
     */
    function mb_ucwords($str, $delimiters = " \t\r\n\f\v", $encoding = null)
    {
        $encoding = Std::coalesce($encoding, mb_internal_encoding());

        $delimitersArray = mb_str_split($delimiters, 1, $encoding);
        $upper = true;
        $result = '';

        for ($ii = 0; $ii < mb_strlen($str, $encoding); $ii++) {
            $char = mb_substr($str, $ii, 1, $encoding);

            if ($upper) {
                $char = mb_convert_case($char, MB_CASE_UPPER, $encoding);
                $upper = false;
            } elseif (ArrayList::of($delimitersArray)->includes($char)) {
                $upper = true;
            }

            $result .= $char;
        }

        return $result;
    }
}

if (!function_exists('mb_str_split')) {
    /**
     * @param string $string
     * @param int $splitLength
     * @param string $encoding
     *
     * @return array
     * @throws Exception
     */
    function mb_str_split($string, $splitLength = 1, $encoding = null)
    {
        if ($splitLength == 0) {
            throw new Exception(
                'The length of each segment must be greater than zero'
            );
        }

        $result = [];
        $len = mb_strlen($string, $encoding);

        for ($ii = 0; $ii < $len; $ii += $splitLength) {
            $result[] = mb_substr($string, $ii, $splitLength, $encoding);
        }

        if (empty($result)) {
            return [''];
        }

        return $result;
    }
}
