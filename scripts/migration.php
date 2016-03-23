<?php

require_once "config.php";

$actions = array("documents_bdd", "documents_fichiers", "listes");

function usage() {
	global $argv;
	global $actions;
	echo "Utilisation: " . $argv[0] . " action\n";
	echo "\t" . "action: " . implode(" | ", $actions) . "\n";
	exit;
}

if ($argc < 2 || !in_array($argv[1], $actions)) {
	usage();
}

$action = $argv[1];
// arguments de l'action : tout moins le nom du script et le nom de l'action
array_shift($argv);
array_shift($argv);
$argc -= 2;

// connexion aux BDs
$bdProjet = connexionProjet();
$bdProjet->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$bdCumulus = connexionCumulus();
$bdCumulus->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// action en fonction du 1er argument de la ligne de commande
switch($action) {
	case "documents_bdd":
		migration_documents_bdd($argc, $argv);
		break;
	case "documents_fichiers":
		migration_documents_fichiers($argc, $argv);
		break;
	default:
		throw new Exception('une action déclarée dans $actions devrait avoir un "case" correspondant dans le "switch"');
}

// actions qui font vraiment des trucs
function migration_documents_bdd($argc, $argv) {
	global $bdProjet;
	global $bdCumulus;
	// ++++++++++++++++ CONFIG À L'ARRACHE ++++++++++++++++++++++++++
	$prefixe_stockage = '/grosdur/cumulus/docs/_projets';
	
	// tous les dossiers (pour reconstruire les chemins);
	$reqDos = "SELECT pd_id, pd_nom, pd_pere FROM projet_documents WHERE pd_ce_type = 0";
	$resDos = $bdProjet->query($reqDos);
	$dossiers = array();
	while ($ligne = $resDos->fetch()) {
		$dossiers[$ligne['pd_id']] = array(
			"pd_nom" => $ligne['pd_nom'],
			"pd_pere" => $ligne['pd_pere']
		);
	}
	//var_dump($dossiers);
	echo count($dossiers) . " dossiers trouvés\n";

	// tous les fichiers, dans les dossiers
	$reqFic = "SELECT p_id, p_titre, U_MAIL, pd_id, pd_ce_type, pd_nom, pd_lien, "
		. "pd_pere, pd_permissions, pd_date_de_mise_a_jour, pd_description, pd_visibilite "
		. "FROM projet "
		. "LEFT JOIN projet_documents ON p_id = pd_ce_projet "
		. "LEFT JOIN annuaire_tela ON pd_ce_utilisateur = U_ID "
		. "WHERE pd_ce_type != 0 "
		//. "LIMIT 10"
		. "";
	$resFic = $bdProjet->query($reqFic);
	$fichiers = array();
	while ($ligne = $resFic->fetch()) {
		$fichiers[] = $ligne;
	}
	//var_dump($fichiers);
	echo count($fichiers) . " fichiers trouvés\n";

	$fichiersOrphelins = array();
	// formatage pour cumulus
	$fichiersCumulus = array();
	foreach ($fichiers as $f) {
		// rassemblement de tout qu'es-ce qu'y faut
		$chemin = reconstruire_chemin($dossiers, $f);
		if ($chemin === false) {
			// quarantainisation du fichier
			$fichiersOrphelins[] = $f;
			continue;
		}
		//$chemin = utf8_encode($chemin);
		$chemin = iconv("ISO-8859-1", "UTF-8//TRANSLIT", $chemin);
		//echo "Chemin: [$chemin], Nom: [" . $f['pd_lien'] . "]\n";
		$nomFichier = iconv("ISO-8859-1", "UTF-8//TRANSLIT", $f['pd_lien']);
		$clef = sha1($chemin . $f['pd_lien']);
		//echo "=> clef: [$clef]\n";
		// au kazoo que /i
		if ($clef == "") {
			throw new Exception("clef vide pour fichier n°" . $f['pd_id'] . " : " . print_r($f, true));
		}
		$prefixe = $prefixe_stockage . '/' . $f['p_id'];
		$perms = 'wr';
		if ($f['pd_visibilite'] == 'prive') {
			$perms = 'r-';
		}
		// construction de l'entrée de fichier
		$fc = array(
			'fkey' => $clef,
			'name' => $nomFichier,
			'path' => $chemin,
			'storage_path' => $prefixe . rtrim($chemin, '/') . '/' . $nomFichier,
			'mimetype' => null,
			'size' => null,
			'owner' => $f['U_MAIL'], // @PB : si on change d'email ? Mettre l'id numérique !
			'groups' => array(
				'projet:' . $f['p_id']
			),
			'permissions' => $perms,
			'keywords' => null,
			'license' => 'CC BY SA',
			'meta' => array(
				'description' => iconv("ISO-8859-1", "UTF-8//TRANSLIT", $f['pd_description']),
				'titre' => iconv("ISO-8859-1", "UTF-8//TRANSLIT", $f['pd_nom'])
			),
			'creation_date' => $f['pd_date_de_mise_a_jour'],
			'last_modification_date' => $f['pd_date_de_mise_a_jour']
		);
		$fichiersCumulus[] = $fc;
	}
	//var_dump($fichiersCumulus);
	echo count($fichiersCumulus) . " entrées de fichiers Cumulus générées\n";
	//var_dump($fichiersOrphelins);
	echo count($fichiersOrphelins) . " fichiers orphelins\n";

	// insertion !!
	foreach ($fichiersCumulus as $fc) {
		$req = "INSERT INTO cumulus_files VALUES(";
		$req .= "'" . $fc['fkey'] . "',";
		$req .= "'" . dqq($fc['name']) . "',";
		$req .= "'" . dqq($fc['path']) . "',";
		$req .= "'" . dqq($fc['storage_path']) . "',";
		$req .= "NULL,";
		$req .= "NULL,";
		$req .= "'" . $fc['owner'] . "',";
		$req .= "'" . implode(',', $fc['groups']) . "',";
		$req .= "'" . $fc['permissions'] . "',";
		$req .= "NULL,";
		$req .= "'" . $fc['license'] . "',";
		$req .= "'" .  dqq(json_encode($fc['meta'], JSON_UNESCAPED_UNICODE)) . "',";
		$req .= "'" . $fc['creation_date'] . "',";
		$req .= "'" . $fc['last_modification_date'] . "'";
		$req .= ");";
		//echo "R/ [$req]\n";
		//exit;
		try {
			$bdCumulus->exec($req);
		} catch(Exception $e) {
			echo "-- FOIRAX: [$req]\n";
		}
	}
}

// double quote les quotes : transforme ' en '' pour MySQL
function dqq($str) {
	return str_replace("'", "''", $str);
}

function reconstruire_chemin(&$dossiers, $f) {
	$chemin = '';
	$entite = $f;
	$parentsParcourus = array(); // detecteur de boucles
	while(($entite['pd_pere'] != 0) && !in_array($entite['pd_pere'], $parentsParcourus)) {
		if (isset($dossiers[$entite['pd_pere']])) {
			$parentsParcourus[] = $entite['pd_pere'];
			$entite = $dossiers[$entite['pd_pere']];
			$chemin .= '/' . $entite['pd_nom'];
		} else {
			// chemin cassé
			return false;
		}
	}
	if ($chemin == "") {
		$chemin = "/";
	}
	return $chemin;
}

