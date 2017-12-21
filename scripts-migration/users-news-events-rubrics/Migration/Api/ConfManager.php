<?php

namespace Migration\Api;

require_once(dirname(__FILE__) . "/../App/Config/conf.php");
use const \Migration\App\Config\CONF;

/**
 * Singleton managing the app configuration.
 */
class ConfManager {

  private $_conf = array();
  private static $_instance = null;

  public function __construct($conf) {
    $this->_conf = $conf;
  }

  /**
   * Returns the ConfManager instance.
   */
  public static function getInstance() {

    if(is_null(self::$_instance)) {
      self::$_instance = new ConfManager(CONF);
    }

    return self::$_instance;
  }

  public function getConfEntryValue($conf_key) {
    return $this->_conf[$conf_key];
  }

}
