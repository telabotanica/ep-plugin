<?php

namespace Migration\Config;

$srcDatasources = [
  DbNamesEnum::Tela => [
    'host'     => 'localhost',
    'port'     => '3306',
    'user'     => 'dbuser',
    'password' => '123',
    'dbname'   => 'dbname',
    'tb_prefix'   => ''
  ],
  DbNamesEnum::Spip => [
    'host'     => 'localhost',
    'port'     => '3306',
    'user'     => 'dbuser',
    'password' => '123',
    'dbname'   => 'dbname',
    'tb_prefix'   => ''
  ]
]


$targetDatasources = [
  DbNamesEnum::Wp => [
    'host'     => 'localhost',
    'port'     => '3306',
    'user'     => 'dbuser',
    'password' => '123',
    'dbname'   => 'tb_wordpress_migration',
    'tb_prefix'   => ''    
  ]
]
