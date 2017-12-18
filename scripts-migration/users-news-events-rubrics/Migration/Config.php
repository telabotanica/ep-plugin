<?php

namespace Migration;

use \PDO;

class Config {


public static function getWpTablePrefix() {
  return '';
}

public static function getWpDir() {
  return '/var/www/wordpress';
}

public static function connexionSpip() {
        // touche à ça
        $hote = 'localhost';
        $port = '3306';
        $utilisateur = 'dbuser';
        $mdp = '123';
        // $base = 'tela_prod_spip_actu';
        $base = 'dbname';

        // touche pas à ça
        $dsn = 'mysql:host=' . $hote . ';port=' . $port . ';dbname=' . $base;

        return new \PDO($dsn, $utilisateur, $mdp, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
}

public static function connexionTelaProd() {
        // touche à ça
        $hote = 'localhost';
        $port = '3306';
        $utilisateur = 'dbuser';
        $mdp = '123';
        // $base = 'tela_prod_v4';
        $base = 'dbname';

        // touche pas à ça
        $dsn = 'mysql:host=' . $hote . ';port=' . $port . ';dbname=' . $base;

        return new \PDO($dsn, $utilisateur, $mdp, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
}

public static function connexionWordpress() {
        // touche à ça
        $hote = 'localhost';
        $port = '3306';
        $utilisateur = 'dbuser';
        $mdp = '123';
        // $base = 'wordpress';
        $base = 'tb_wordpress_migration';

        // touche pas à ça
        $dsn = 'mysql:host=' . $hote . ';port=' . $port . ';dbname=' . $base;


        return new \PDO($dsn, $utilisateur, $mdp, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
}


}
