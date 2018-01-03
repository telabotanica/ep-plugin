<?php

namespace Migration;
/**
 * Autoload classes based on namespaces.
 */
class Autoloader {

    /**
     * Registers autoloader
     */
    static function register(){
        spl_autoload_register(array(__CLASS__, 'autoload'));
    }

    /**
     * Requires the .php files corresponding to a class.
     *
     * @param $class string The (namespace prefixed) name of the class. 
     */
    static function autoload($class){
        if (strpos($class, __NAMESPACE__ . '\\') === 0){
            $class = str_replace(__NAMESPACE__ . '\\', '', $class);
            $class = str_replace('\\', '/', $class);
            require $class . '.php';
        }
    }

}
