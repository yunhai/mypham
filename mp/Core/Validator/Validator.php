<?php

namespace Mp\Core\Validator;

use Mp\Lib\Utility\Hash;

class Validator {

    protected $rule = [];

    public function def() {
        return [];
    }

    public function rule($rule = null) {
        if (is_null($rule)) {
            return $this->rule;
        }
        $this->rule = $this->$rule();
        return $this;
    }

    public function validate($target = [], &$error = []) {
        if (empty($this->rule)) {
            return true;
        }

        foreach ($this->rule as $field => $list) {
            if (isset($target[$field]) === false) {
                continue;
            }

            foreach ($list as $name => $item) {
                extract($item);

                $method = $rule[0];


                $rule[0] = $target[$field];

                $arguments = array_values($rule);
                $arguments[] = $target;


                $flag = call_user_func_array([$this, $method], $arguments);

                if ($flag === false) {
                    $error[$field][$name] = $message;
                    if (empty($next)) {
                        break;
                    }
                }
            }
        }

        return empty($error);
    }

    public function alterRule($key = '', $rule = []) {
        $this->rule = Hash::insert($this->rule, $key, $rule);
        return $this;
    }

    public function deleteRule($key = '') {
        $this->rule = Hash::remove($this->rule, $key);
        return $this;
    }

    public function appendRule($key = '', $rule = []) {
        $default = Hash::get($this->rule, $key);
        $this->rule = Hash::merge($default, $rule);
        return $this;
    }

    /**
     * Checks that a string contains something other than whitespace
     *
     * Returns true if string contains something other than whitespace
     *
     * $check can be passed as an array:
     * array('check' => 'valueToCheck');
     *
     * @param string|array $check Value to check
     * @return bool Success
     */
    public function notEmpty($check) {
        if (empty($check) && $check != '0') {
            return false;
        }

        return self::_check($check, '/[^\s]+/m');
    }

    /**
     * Checks that a string contains only integer or letters
     *
     * Returns true if string contains only integer or letters
     *
     * $check can be passed as an array:
     * array('check' => 'valueToCheck');
     *
     * @param string|array $check Value to check
     * @return bool Success
     */
    public function alphaNumeric($check) {
        if (empty($check) && $check != '0') {
            return false;
        }
        return self::_check($check, '/^[\p{Ll}\p{Lm}\p{Lo}\p{Lt}\p{Lu}\p{Nd}]+$/Du');
    }

    /**
     * Checks that a string length is within s specified range.
     * Spaces are included in the character count.
     * Returns true is string matches value min, max, or between min and max,
     *
     * @param string $check Value to check for length
     * @param int $min Minimum value in range (inclusive)
     * @param int $max Maximum value in range (inclusive)
     * @return bool Success
     */
    public function lengthBetween($check, $min, $max) {
        $length = mb_strlen($check);
        return ($length >= $min && $length <= $max);
    }

    /**
     * Returns true if field is left blank -OR- only whitespace characters are present in its value
     * Whitespace characters include Space, Tab, Carriage Return, Newline
     *
     * $check can be passed as an array:
     * array('check' => 'valueToCheck');
     *
     * @param string|array $check Value to check
     * @return bool Success
     */
    public function blank($check) {
        return !self::_check($check, '/[^\\s]/');
    }

    /**
     * Used when a custom regular expression is needed.
     *
     * @param string|array $check When used as a string, $regex must also be a valid regular expression.
     *    As and array: array('check' => value, 'regex' => 'valid regular expression')
     * @param string $regex If $check is passed as a string, $regex must also be set to valid regular expression
     * @return bool Success
     */
    public function custom($check, $regex = null) {
        if ($regex === null) {
            throw new InternalErrorException('invalid regular expression');
            return false;
        }
        return self::_check($check, $regex);
    }

    /**
     * Validates for an email address.
     *
     * Only uses getmxrr() checking for deep validation if PHP 5.3.0+ is used, or
     * any PHP version on a non-windows distribution
     *
     * @param string $check Value to check
     * @param bool $deep Perform a deeper validation (if true), by also checking availability of host
     * @param string $regex Regex to use (if none it will use built in regex)
     * @return bool Success
     */
    public function email($check, $deep = false, $regex = null) {
        return filter_var($check, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Check that value has a valid file extension.
     *
     * @param string|array $check Value to check
     * @param array $extensions file extensions to allow. By default extensions are 'gif', 'jpeg', 'png', 'jpg'
     * @return bool Success
     */
    public function extension($check, $extensions = ['gif', 'jpeg', 'png', 'jpg']) {
        if (is_array($check)) {
            return self::extension(array_shift($check), $extensions);
        }
        $extension = strtolower(pathinfo($check, PATHINFO_EXTENSION));
        foreach ($extensions as $value) {
            if ($extension === strtolower($value)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks whether the length of a string is greater or equal to a minimal length.
     *
     * @param string $check The string to test
     * @param int $min The minimal string length
     * @return bool Success
     */
    public function minLength($check, $min) {
        return mb_strlen($check) >= $min;
    }

    /**
     * Checks whether the length of a string is smaller or equal to a maximal length..
     *
     * @param string $check The string to test
     * @param int $max The maximal string length
     * @return bool Success
     */
    public function maxLength($check, $max) {
        return mb_strlen($check) <= $max;
    }

    /**
     * Checks if a value is numeric.
     *
     * @param string $check Value to check
     * @return bool Success
     */
    public function numeric($check) {
        return is_numeric($check);
    }

    /**
     * Validate that a number is in specified range.
     * if $lower and $upper are not set, will return true if
     * $check is a legal finite on this platform
     *
     * @param string $check Value to check
     * @param int|float $lower Lower limit
     * @param int|float $upper Upper limit
     * @return bool Success
     */
    public function range($check, $lower = null, $upper = null) {
        if (!is_numeric($check)) {
            return false;
        }
        if (isset($lower) && isset($upper)) {
            return ($check > $lower && $check < $upper);
        }
        return is_finite($check);
    }

    /**
     * Runs a regular expression match.
     *
     * @param string $check Value to check against the $regex expression
     * @param string $regex Regular expression
     * @return bool Success of match
     */
    protected function _check($check, $regex) {
        if (is_string($regex) && preg_match($regex, $check)) {
            return true;
        }
        return false;
    }
}
