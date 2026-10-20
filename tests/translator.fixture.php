<?php
// Loaded before every PHP check (tests/run.sh): Laravel's __() and trans_choice() without a translation,
// so services can be tested outside the framework. Replaces :placeholders like Laravel does.
if (!function_exists('__')) {
    function __($key = null, $replace = [], $locale = null) {
        foreach ($replace as $name => $value) $key = str_replace(':'.$name, (string) $value, (string) $key);
        return $key;
    }
}
if (!function_exists('trans_choice')) {
    function trans_choice($key, $number, array $replace = [], $locale = null) {
        $forms = explode('|', (string) $key);
        return __($forms[count($forms) > 1 && (is_countable($number) ? count($number) : $number) != 1 ? 1 : 0], $replace);
    }
}
