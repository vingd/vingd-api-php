<?php

class ConversionException extends Exception {}

class SafeFormatter {
    function __construct($pattern) {
        $this->pattern = $pattern;
        $this->next = 0;
    }

    public function format(/* values */) {
        $this->args = func_get_args();
        return preg_replace_callback(
            '/\\{(?:(?P<idx>\d+))?:(?P<typ>\w+)\\}/',
            array($this, "replace"),
            $this->pattern
        );
    }
    
    private function replace($match) {
        $idx = $match['idx'];
        $typ = $match['typ'];
        if (strlen($idx) < 1) {
            $idx = $this->next++;
        } else {
            $idx = intval($idx);
        }
        
        if (array_key_exists($idx, $this->args)) {
            $arg = $this->args[$idx];
        } else {
            throw new ConversionException("Index out of bounds: $idx.");
        }
        
        if (array_key_exists($typ, self::$converters)) {
            $convname = self::$converters[$typ];
        } else {
            throw new ConversionException("Invalid converter/type: '$typ'.");
        }
        
        try {
            $val = call_user_func(array($this, $convname), $arg);
        } catch (Exception $e) {
            throw new ConversionException("Argument '$arg' not of type '$typ'.");
        }
        
        return (string)$val;
    }
    
    static $converters = array(
        'int' => 'to_int',
        'hex' => 'to_hex',
        'str' => 'to_str',
        'ident' => 'to_ident'
    );
    
    /* Cast to string. */
    static function to_str($val) {
        return (string)$val;
    }
    
    /* Allow decimal digits. */
    static function to_int($val) {
        if (preg_match('/^\\d+$/', (string)$val)) {
            return $val;
        }
        throw new ConversionException("Non-digits in number.");
    }
    
    /* Allow hexadecimal digits. */
    static function to_hex($val) {
        if (preg_match('/^[a-fA-F\\d]+$/', (string)$val)) {
            return $val;
        }
        throw new ConversionException("Non-hex digits in hex number.");
    }
    
    /* Allow letters, digits, underscore and minus/dash. */
    static function to_ident($val) {
        if (preg_match('/^[-\w]*$/', (string)$val)) {
            return $val;
        }
        throw new ConversionException("Non-identifier characters in string.");
    }
    
}

function safeformat(/* $pattern, [$value1, $value2, ...] */) {
    $n = func_num_args();
    if ($n < 1) {
        throw new ConversionException("Pattern argument missing.");
    }
    $args = func_get_args();
    $pattern = array_shift($args);
    
    return call_user_func_array(array(new SafeFormatter($pattern), "format"), $args);
}

?>