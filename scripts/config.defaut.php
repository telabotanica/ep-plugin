<?php

$ICONV_UTF8 = true;

$prefixe_stockage_cumulus = '/grosdur/cumulus/docs';
$prefixe_stockage_projets_cumulus = '/_projets';
$prefixe_stockage_anciens_projets = '/home/telabotap/www/reseau/projet/fichiers';

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
	$bd = new PDO($dsn, $utilisateur, $mdp);
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
	$bd = new PDO($dsn, $utilisateur, $mdp);
	return $bd;
}