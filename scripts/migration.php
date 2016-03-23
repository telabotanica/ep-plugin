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
	// config à l'arrache
	global $bdProjet;
	global $bdCumulus;
	global $ICONV_UTF8;
	global $prefixe_stockage_cumulus;
	global $prefixe_stockage_cumulus_bd;
	global $prefixe_stockage_projets_cumulus;
	global $prefixe_stockage_anciens_projets;

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
	$reqFic = "SELECT p_id, p_nom_repertoire, p_titre, U_MAIL, pd_id, pd_ce_type, pd_nom, pd_lien, "
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
		$cheminProjet = reconstruire_chemin_projet($dossiers, $f);
		$cheminCumulus = reconstruire_chemin_cumulus($dossiers, $f);
		if ($cheminCumulus === false) {
			// quarantainisation du fichier
			$fichiersOrphelins[] = $f;
			continue;
		}
		$prefixe_bd = $prefixe_stockage_cumulus_bd . $prefixe_stockage_projets_cumulus . '/' . $f['p_id'];
		$prefixe_disque = $prefixe_stockage_cumulus . $prefixe_stockage_projets_cumulus . '/' . $f['p_id'];

		// merdier de partout avec les encodages pas cohérents etc.
		$titre = $f['pd_nom'];
		$titreUtf = $titre;
		$description = $f['pd_description'];
		$nomFichier = $f['pd_lien'];
		$nomFichierUtf = $nomFichier;

		// déHTMLentitiesization comme un goret
		$titre = html_entity_decode($titre, ENT_COMPAT | ENT_HTML401, 'UTF-8');
		$description = html_entity_decode($description, ENT_COMPAT | ENT_HTML401, 'UTF-8');

		$nouveauNomFichier = $titre . substr($nomFichier, strrpos($nomFichier, '.'));
		// trucs à encoder en utf-8
		if (! preg_match('//u', $cheminCumulus)) {
			$cheminCumulus = iconv("ISO-8859-1", "UTF-8//TRANSLIT", $cheminCumulus);
		}
		if (! preg_match('//u', $cheminProjet)) {
			$cheminProjet = iconv("ISO-8859-1", "UTF-8//TRANSLIT", $cheminProjet);
		}
		if (! preg_match('//u', $nomFichierUtf)) {
			$nomFichierUtf = iconv("ISO-8859-1", "UTF-8//TRANSLIT", $nomFichierUtf);
		}
		if (! preg_match('//u', $titreUtf)) {
			$titreUtf = iconv("ISO-8859-1", "UTF-8//TRANSLIT", $titreUtf);
		}
		/*if (! preg_match('//u', $description)) {
			$description = iconv("ISO-8859-1", "UTF-8//TRANSLIT", $description);
		}*/
		$nouveauNomFichierUtf = $titreUtf . substr($nomFichier, strrpos($nomFichier, '.'));

		$_path = $prefixe_stockage_projets_cumulus . '/' . $f['p_id'] . $cheminCumulus;
		$_storage_path = $prefixe_bd . rtrim($cheminCumulus, '/') . '/' . $nouveauNomFichier;
		/*if (strpos($f['pd_nom'], 'en nomenclature normal') !== false || strpos($f['pd_nom'], 'groupe Trac') !== false) {
			echo "\n";
			echo "Titre: $titre\n";
			echo "Titre UTF: $titreUtf\n";
			echo "Description: $description\n";
			echo "Nom fichier: $nomFichier\n";
			echo "Nom fichier UTF: $nomFichierUtf\n";
			echo "Nouveau nom fichier: $nouveauNomFichier\n";
			echo "Nouveau nom fichier UTF: $nouveauNomFichierUtf\n";
			echo "Chemin projet: $cheminProjet\n";
			echo "Chemin cumulus: $cheminCumulus\n";
			echo "path: $_path\n";
			echo "storage_path: $_storage_path\n";
			echo "\n";
		}*/

		// calcul de la clef
		$clef = sha1($cheminCumulus . $f['pd_lien']);
		//echo "=> clef: [$clef]\n";
		// au kazoo que /i
		if ($clef == "") {
			throw new Exception("clef vide pour fichier n°" . $f['pd_id'] . " : " . print_r($f, true));
		}
		$perms = 'wr';
		if ($f['pd_visibilite'] == 'prive') {
			$perms = 'r-';
		}
		// construction de l'entrée de fichier
		$fc = array(
			'id' => $f['pd_id'],
			'fkey' => $clef,
			'name' => $nouveauNomFichier,
			'path' => $_path,
			'storage_path' => $prefixe_bd . rtrim($cheminCumulus, '/') . '/' . $nouveauNomFichier,
			'mimetype' => null,
			'size' => null,
			'owner' => $f['U_MAIL'], // @PB : si on change d'email ? Mettre l'id numérique !
			'groups' => array(
				'projet:' . $f['p_id']
			),
			'permissions' => $perms,
			'keywords' => null,
			'license' => 'CC BY SA',
			// JSON à la main car pb d'unicode avec PHP < 5.3
			'meta' => '{"description":"' . $description
				. '","titre":"' . $titre . '"}',
			'creation_date' => $f['pd_date_de_mise_a_jour'],
			'last_modification_date' => $f['pd_date_de_mise_a_jour'],
			// pour la copie de fichiers
			'ancien_chemin' => $prefixe_stockage_anciens_projets . "/" . rtrim($cheminProjet, '/') . '/' . $nomFichierUtf,
			'nouveau_chemin' => $prefixe_disque . rtrim($cheminCumulus, '/') . '/' . $nouveauNomFichierUtf
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
		$req .= "'" .  dqq($fc['meta']) . "',";
		$req .= "'" . $fc['creation_date'] . "',";
		$req .= "'" . $fc['last_modification_date'] . "'";
		$req .= ");";
		//echo "R/ [$req]\n";
		//echo "Ancien chemin: [" . $fc['ancien_chemin'] . "]\n";
		//echo "Nouveau chemin: [" . $fc['storage_path'] . "]\n";
		//exit;
		if (file_exists($fc['ancien_chemin'])) {
			$rep = dirname($fc['nouveau_chemin']);
			// création du dossier si besoin
			//echo "$rep\n";
			if (! file_exists($rep)) {
				mkdir($rep, 0777, true);
			}
			// copie du fichier
			$ok = copy($fc['ancien_chemin'], $fc['nouveau_chemin']);
			if ($ok) {
				try {
					$bdCumulus->exec($req);
				} catch(Exception $e) {
					echo "-- ECHEC REQUÊTE: [$req]\n";
				}
			} else {
				echo "-- ECHEC COPIE FICHIER [" . $fc['ancien_chemin'] . "] vers [" . $fc['nouveau_chemin'] . "]\n";
			}
		} else {
			echo "-- FICHIER SOURCE INEXISTANT: [" . $fc['id'] . "] [" . $fc['ancien_chemin'] . "]\n";
		}
	}
}

// double quote les quotes : transforme ' en '' pour MySQL
function dqq($str) {
	return str_replace("'", "''", $str);
}

// retrouve le chemin où aller chercher le fichier existant sur le disque
function reconstruire_chemin_projet(&$dossiers, $f) {
	$chemin = '';
	$entite = $f;
	$parentsParcourus = array(); // detecteur de boucles
	while(($entite['pd_pere'] != 0) && !in_array($entite['pd_pere'], $parentsParcourus)) {
		// si le dossier parent existe dans la BD
		if (isset($dossiers[$entite['pd_pere']])) {
			// on marque qu'on est déjà passé par là
			$parentsParcourus[] = $entite['pd_pere'];
			// on remonte le chemin depuis la fin
			$chemin = $entite['pd_pere'] . '/' . $chemin;
			// on continue notre route
			$entite = $dossiers[$entite['pd_pere']];
		} else {
			// chemin cassé
			return false;
		}
	}
	$chemin = $f['p_nom_repertoire'] . '/' . $chemin;
	return $chemin;
}

// construit le chemin où placer le nouveau fichier sur le disque
function reconstruire_chemin_cumulus(&$dossiers, $f) {
	$chemin = '/';
	$entite = $f;
	$parentsParcourus = array(); // detecteur de boucles
	while(($entite['pd_pere'] != 0) && !in_array($entite['pd_pere'], $parentsParcourus)) {
		// si le dossier parent existe dans la BD
		if (isset($dossiers[$entite['pd_pere']])) {
			// on marque qu'on est déjà passé par là
			$parentsParcourus[] = $entite['pd_pere'];
			$entite = $dossiers[$entite['pd_pere']];
			// on remonte le chemin depuis la fin
			$chemin .= $entite['pd_nom'] . '/';
		} else {
			// chemin cassé
			return false;
		}
	}
	return $chemin;
}

