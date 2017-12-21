<?php

namespace Migration\App\Config;

//require_once(dirname(__FILE__) . "/../App/DbNamesEnum.php");
//use \Migration\App\DbNamesEnum;

abstract class DbNamesEnum {
    const Tela = "tela";
    const Wp   = "wp";
    const Spip = "spip";
}

const DATASOURCES = [
  DbNamesEnum::Tela => [
    'host'        => 'localhost',
    'port'        => '3306',
    'user'        => 'dbuser',
    'password'    => '123',
    'dbname'      => 'dbname',
    'tb_prefix'   => ''
  ],
  DbNamesEnum::Spip => [
    'host'        => 'localhost',
    'port'        => '3306',
    'user'        => 'dbuser',
    'password'    => '123',
    'dbname'      => 'dbname',
    'tb_prefix'   => ''
  ],
  DbNamesEnum::Wp => [
    'host'        => 'localhost',
    'port'        => '3306',
    'user'        => 'dbuser',
    'password'    => '123',
    'dbname'      => 'tb_wordpress_migration',
    'tb_prefix'   => ''
  ]
];
