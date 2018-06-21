<?php

namespace Migration\Api;

require_once(dirname(__FILE__) . "/../App/Config/datasources.php");
use const \Migration\App\Config\DATASOURCES;

use \PDO;
use \Exception;

/**
 * Singleton managing data sources (PDO objects).
 */
class DatasourceManager {

  private $_pdos = array();
  private $_tb_prefix = array();
  private static $_instance = null;

  /**
   * Returns a new <code>DatasourceManager</code> instance.
   */
  public function __construct($datasourcesConf) {

    foreach($datasourcesConf as $ds_name => $ds_conf) {
      $pdo = $this->buildPdo($ds_conf);
      $this->_pdos = array_merge($this->_pdos, array($ds_name => $pdo));
      $this->_tb_prefix = array_merge($this->_tb_prefix, array($ds_name => $ds_conf['tb_prefix']));
    }

  }

  /**
   * Closes all connections.
   */
  public function closeAll() {
    $this->_pdos = null;
  }

  /**
   * Returns the <code>DatasourceManager</code> instance.
   */
  public static function getInstance() {

    if(is_null(self::$_instance)) {
      self::$_instance = new DatasourceManager(DATASOURCES);
    }

    return self::$_instance;
  }

  /**
   * Returns the connection corresponding to $ds_name data source.
   *
   * @parameter  $ds_name the name of the data source.
   */
  public function getConnection($ds_name) {
    return $this->_pdos[$ds_name];
  }

  /**
   * Returns the table prefix corresponding to $ds_name data
   * source.
   *
   * @parameter  $ds_name the name of the data source.
   */
  public function getTablePrefix($ds_name) {
    return $this->_tb_prefix[$ds_name];
  }

  private function buildDsn($host, $port, $db) {
    return 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $db;
  }

  private function buildPdo($datasource) {
    $dsn = $this->buildDsn($datasource['host'], $datasource['port'], $datasource['dbname']);
    $conn = new PDObis(
      $dsn,
      $datasource['user'],
      $datasource['password'],
      array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8')
    );
    //$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    return $conn;
  }

}

class PDObis extends PDO {
  public function __construct($dsn, $username, $password, $options) {
    parent::__construct($dsn, $username, $password, $options);
  }

  public function exec($query, $params = array()) {
    $sth = $this->prepare($query);
    if (false === $sth->execute($params)) {
      throw new Exception($sth->errorInfo()[2]);
    }

    return $sth;
  }
}
