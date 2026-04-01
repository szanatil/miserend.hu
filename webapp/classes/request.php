<?php

class Request {

    static function Integer($name) {
        $value = self::get($name);    
        if ($value <> '' AND !is_numeric($value)) {
            throw new Exception("Required '$name' is not an Integer.");
        }
        return $value;
    }

    static function IntegerRequired($name) {
        $value = self::getRequired($name);
        if (!is_numeric($value)) {
            throw new Exception("Required '$name' is not an Integer.");
        }
        return $value;
    }

    static function IntegerwDefault($name, $default = false) {
        $value = self::getwDefault($name, $default);
        if (!is_numeric($value)) {
            throw new Exception("Required '$name' is not an Integer.");
        }
        return $value;
    }

    static function Text($name) {
        $value = self::get($name);
        $value = sanitize($value);
        return $value;
    }

    static function TextwDefault($name, $default = false) {
        $value = self::getwDefault($name, $default);
        $value = sanitize($value);
        return $value;
    }

    static function TextRequired($name) {
        $value = self::getRequired($name);
        $value = sanitize($value);
        return $value;
    }
    
    static function InArray($name, $array) {
        $value = self::get($name);
        if(!$value) return false;
        
        if(!in_array($value, $array)) {
            throw new Exception("Array '$name' is not in Array.");
        }
        return $value;
    }
    
    static function InArrayRequired($name, $array) {
        $value = self::get($name);
        if(!in_array($value, $array)) {
            throw new Exception("Required '$name' is not in Array.");
        }
        return $value;
    }

    static function Simpletext($name) {
        $value = self::get($name);
        if ($value != '' AND ! preg_match('/^[0-9a-zA-Z_-]+$/i', $value)) {
            throw new Exception("Variable '$name' is not a SimpleText.");
        }
        return $value;
    }

    static function SimpletextwDefault($name, $default = false) {
        $value = self::getwDefault($name, $default);
        if ($value != '' AND ! preg_match('/^[0-9a-zA-Z_-]+$/i', $value)) {
            throw new Exception("Variable '$name' is not a SimpleText.");
        }
        return $value;
    }

    static function SimpletextRequired($name) {
        $value = self::getRequired($name);
        if (!preg_match('/^[0-9a-zA-Z_-]+$/i', $value)) {
            throw new Exception("Required '$name' is not a SimpleText.");
        }
        return $value;
    }

    static function IntegerArray($name) {
        $value = self::get($name);
        if (!$value) return false;
        
        if (!is_array($value)) {
            throw new Exception("'$name' is not an Array.");
        }
        
        foreach ($value as $item) {
            if (!is_numeric($item)) {
                throw new Exception("Array '$name' contains non-integer values.");
            }
        }
        
        return $value;
    }

    static function IntegerArrayRequired($name) {
        $value = self::get($name);
        
        if (!$value) {
            throw new Exception("Required '$name' is missing.");
        }
        
        if (!is_array($value)) {
            throw new Exception("Required '$name' is not an Array.");
        }
        
        foreach ($value as $item) {
            if (!is_numeric($item)) {
                printr($item);
                throw new Exception("Required Array '$name' contains non-integer values.");
            }
        }
        
        return $value;
    }

    static function StringArray($name) {
        $value = self::get($name);
        if (!$value) return false;
        
        if (!is_array($value)) {
            throw new Exception("'$name' is not an Array.");
        }
        
        foreach ($value as $item) {
            if (!is_string($item)) {
                throw new Exception("Array '$name' contains non-string values.");
            }
        }
        
        return $value;
    }

    static function StringArrayRequired($name) {
         $value = self::get($name);
         
         if (!$value) {
             throw new Exception("Required '$name' is missing.");
         }
         
         if (!is_array($value)) {
             throw new Exception("Required '$name' is not an Array.");
         }
         
         foreach ($value as $item) {
             if (!is_string($item)) {
                 throw new Exception("Required Array '$name' contains non-string values.");
             }
         }
         
         return $value;
     }

    static function ArrayArray($name) {
        $value = self::get($name);
        if ($value === false) return false;
        
        if (!is_array($value)) {
            throw new Exception("'$name' is not an Array.");
        }

        foreach ($value as $item) {
            if (!is_array($item)) {
                throw new Exception("Array '$name' contains non-array values.");
            }
        }
        
        return $value;
    }

    static function ArrayArraywDefault($name, $default = []) {
        $value = self::getwDefault($name, $default);
        
        if (!is_array($value)) {
            throw new Exception("'$name' is not an Array.");
        }
        
        foreach ($value as $item) {
            if (!is_array($item)) {
                throw new Exception("Array '$name' contains non-array values.");
            }
        }
        
        return $value;
    }

    static function Boolean($name) {
        $value = self::get($name);
        if (!$value) return false;
        
        if (!is_bool($value) && $value !== '1' && $value !== '0' && $value !== 1 && $value !== 0 && $value !== 'true' && $value !== 'false' && $value !== '') {
            throw new Exception("'$name' is not a Boolean.");
        }
        
        return (bool)$value;
    }

     static function validateDateFormat($value) {
        // Strict YYYY-mm-dd format validation
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }
        
        // Use DateTime::createFromFormat to strictly validate the date
        // This will reject invalid dates like 2023-02-29
        $date = DateTime::createFromFormat('Y-m-d', $value);
        
        // Check if the date is valid and matches the format exactly
        return $date && $date->format('Y-m-d') === $value;
    }

    static function Date($name) {
        $value = self::get($name);
        if ($value != '' && !self::validateDateFormat($value)) {
            throw new Exception("Required '$name' is not a Date in YYYY-mm-dd format.");
        }
        return $value;
    }

    static function DateRequired($name) {
        $value = self::getRequired($name);
        if (!self::validateDateFormat($value)) {
            throw new Exception("Required '$name' is not a Date in YYYY-mm-dd format.");
        }
        return $value;
    }

    static function DatewDefault($name, $default = false) {
        $value = self::getwDefault($name, $default);
        if ($value !== false && !self::validateDateFormat($value)) {
            throw new Exception("Required '$name' is not a Date in YYYY-mm-dd format.");
        }
        return $value;
    }

    static function getwDefault($name, $default = false) {
        if ($value = self::get($name)) {
            return $value;
        } else {
            return $default;
        }
    }

    static function getRequired($name) {
        if (!$value = self::get($name)) {
            throw new Exception("Required '$name' is required.");
        } else {
            return $value;
        }
    }

    static function get($name) {
         // Ellenőrizzük, hogy a kulcs tömbszerű-e (pl. church[lat])
        if (strpos($name, '[') !== false && strpos($name, ']') !== false) {
            // A kulcs feldarabolása tömbszerű kulcsokra
            $keys = explode('[', str_replace(']', '', $name));
            $value = $_REQUEST;

            // Bejárjuk a kulcsokat, hogy elérjük a megfelelő értéket
            foreach ($keys as $key) {
                if (isset($value[$key])) {
                    $value = $value[$key];
                } else {
                    return false; // Ha bármelyik kulcs nem létezik, false-t adunk vissza
                }
            }
            return $value;
        } else {
            // Egyszerű kulcsok kezelése
            return isset($_REQUEST[$name]) ? $_REQUEST[$name] : false;
        }     
    }

}
