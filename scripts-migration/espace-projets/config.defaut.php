<?php

$prefixe_stockage_cumulus = '/grosdur/cumulus/docs';
$prefixe_stockage_cumulus_bd = '/grosdur/cumulus/docs';
$prefixe_stockage_projets_cumulus = '/_projets';
$prefixe_stockage_anciens_projets = '/home/telabotap/www/reseau/projet/fichiers';

$prefixe_tables_wp = 'wp_';

// définir ici les paramètres de connexion MySQL (pas de fichier de config, la flemme)
function connexionProjet() {
	// touche à ça
	$hote = "127.0.0.1";
	$port = "3306";
	$utilisateur = "";
	$mdp = "";
	$base = "tela_prod_v4";
	// touche pas à ça
	$dsn = "mysql:host=" . $hote . ";port=" . $port . ";dbname=" . $base;
	$bd = new PDO($dsn, $utilisateur, $mdp, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
	return $bd;
}
function connexionCumulus() {
	// touche à ça
	$hote = "";
	$port = "3306";
	$utilisateur = "";
	$mdp = "";
	$base = "cumulus";
	// touche pas à ça
	$dsn = "mysql:host=" . $hote . ";port=" . $port . ";dbname=" . $base;
	$bd = new PDO($dsn, $utilisateur, $mdp, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
	return $bd;
}
function connexionWordpress() {
	// touche à ça
	$hote = "localhost";
	$port = "3306";
	$utilisateur = "";
	$mdp = "";
	$base = "wordpress";
	// touche pas à ça
	$dsn = "mysql:host=" . $hote . ";port=" . $port . ";dbname=" . $base;
	$bd = new PDO($dsn, $utilisateur, $mdp, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
	return $bd;
}

$projets_a_migrer = '1, 2, 3, 4, 5, 7, 8, 9, 10, 11, 12, 15, 18, 21, 26, 28, 37, 40, 42, 48, 50, 55, 57, 59, 62, 65, 66, 70, 71, 72, 74, 77, 78, 79, 80, 81, 84, 85, 86, 87, 90, 92, 93, 95, 97, 99, 100, 101, 102, 104, 105, 106, 108, 109, 112, 113, 119, 121, 124, 126, 127, 128, 129, 132, 133, 134, 135, 136, 138, 140, 141, 142, 143, 144, 145, 146, 147, 148, 150, 152, 153, 154, 156, 157, 158, 159, 160, 161, 162, 163';
