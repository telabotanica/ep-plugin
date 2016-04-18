<?php

require_once "config.php";

$actions = array("documents", "projets", "inscrits", "listes");

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
$bdWordpress = connexionWordpress();
$bdWordpress->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


// action en fonction du 1er argument de la ligne de commande
switch($action) {
	case "documents":
		migration_documents($argc, $argv);
		break;
	case "projets":
		migration_projets($argc, $argv);
		break;
	case "inscrits":
		migration_inscrits($argc, $argv);
		break;
	case "listes":
		migration_listes($argc, $argv);
		break;
	default:
		throw new Exception('une action déclarée dans $actions devrait avoir un "case" correspondant dans le "switch"');
}

/**
 * Copie tous les documents (fichiers) des anciens projets vers Cumulus
 * @ATTENTION penser ensuite à régénérer les mimetypes/tailles (script Cumulus)
 * et changer le propriétaire des fichiers
 */
function migration_documents($argc, $argv) {
	// config à l'arrache
	global $bdProjet;
	global $bdCumulus;
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
		$description = $f['pd_description'];
		$nomFichier = $f['pd_lien'];
		$nomFichierUtf = $nomFichier;

		// déHTMLentitiesization comme un goret
		$titre = html_entity_decode($titre, ENT_COMPAT | ENT_HTML401, 'UTF-8');
		$description = html_entity_decode($description, ENT_COMPAT | ENT_HTML401, 'UTF-8');

		$titreUtf = $titre;

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
			'storage_path' => $_storage_path,
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

/**
 * Copie les caractéristiques des projets existants dans les
 * nouveaux projets (Wordpress / Buddypress)
 */
function migration_projets($argc, $argv) {
	global $bdProjet;
	global $bdWordpress;
	global $prefixe_tables_wp;

	$reqProjets = "SELECT p_id, p_titre, p_resume, p_description, p_espace_internet, p_wikini, p_date_creation, p_type, p_modere, GROUP_CONCAT(pt_label_theme) as themes FROM projet LEFT JOIN projet_avoir_theme ON pat_id_projet = p_id LEFT JOIN projet_theme ON pat_id_theme = pt_id_theme GROUP BY p_id";
	$resProjets = $bdProjet->query($reqProjets);
	$projets = array();
	while ($ligne = $resProjets->fetch()) {
		$projets[$ligne['p_id']] = array(
			"p_titre" => $ligne['p_titre'],
			"p_resume" => $ligne['p_resume'],
			"p_description" => $ligne['p_description'],
			"p_espace_internet" => $ligne['p_espace_internet'],
			"p_wikini" => $ligne['p_wikini'],
			"p_date_creation" => $ligne['p_date_creation'],
			"p_type" => $ligne['p_type'],
			"p_modere" => $ligne['p_modere'],
			"themes" => $ligne['themes']
		);
	}
	var_dump($projets);
	echo count($projets) . " projets trouvés\n";

	// insertion dans {$prefixe_tables_wp}_bp_groups
	$tableGroupes = $prefixe_tables_wp . 'bp_groups';
	$cpt = 0;
	foreach ($projets as $id => $projet) {
		$nom = $projet['p_titre'];
		$slug = $nom;
		$description = $projet['p_description'];
		/*
		 * ATTENTION, p_description va dans bp_groups mais c'est la description
		 * courte; la description longue va dans les triplets de bp_groups_meta
		 */
		$status = "public"; // "public", "hidden" ou "private";
		if ($projet['p_modere'] == 1) { // @TODO vérifier si ça correspond (plutôt un réglage de liste ?)
			$status = "private";
		}
		$dateCreation = $projet['p_date_creation'];
		// Le creator_id sera mis à jour dans migration_inscrits
		$req = "INSERT INTO $tableGroupes (id, creator_id, name, slug, description, status, enable_forum, date_created) "
			. "VALUES($id, NULL, '$nom', '$slug', '$description', '$status', 0, '$dateCreation');";
		echo $req . "\n";
		/*try {
			$bdWordpress->exec($req);
			$cpt++;
		} catch(Exception $e) {
			echo "-- ECHEC REQUÊTE: [$req]\n";
		}*/
	}
	echo "$cpt projets migrés\n";
}

/**
 * Copie les utilisateurs inscrits aux projets existants dans les
 * nouveaux projets (Wordpress / Buddypress)
 */
function migration_inscrits($argc, $argv) {
	global $bdProjet;
	global $bdWordpress;
	global $prefixe_tables_wp;

	$reqInscrits = "SELECT psu_id_utilisateur, psu_id_projet, ps_statut_nom FROM projet_statut_utilisateurs LEFT JOIN projet_statut ON psu_id_statut = ps_id_statut";
	$resInscrits = $bdProjet->query($reqInscrits);
	$inscrits = $resInscrits->fetchAll();
	var_dump($inscrits);
	echo count($inscrits) . " inscrits trouvés\n";

	// insertion dans {$prefixe_tables_wp}_tb_outils_reglages
	/*$tableReglages = $prefixe_tables_wp . 'tb_outils_reglages';
	$cpt = 0;
	foreach ($listes as $idProjet => $liste) {
		$prive = ($liste['pl_visibilite'] == 1 ? 0 : 1); // inverseur de flux quantique
		$nomListe = $liste['pl_nom_liste'];
		$posAt = strpos($nomListe, '@'); // au cas où le nom de liste soit l'adresse entière
		if ($posAt !== false) {
			$nomListe = substr($nomListe, 0, $posAt);
		}
		$jsonConfig = '{"ezmlm-php": {"list": "' . $nomListe . '"}}';
		$req = "INSERT INTO $tableReglages (id_projet, id_outil, name, prive, config) VALUES($idProjet, 'forum', 'forum', $prive, '$jsonConfig');";
		//echo $req . "\n";
		try {
			$bdWordpress->exec($req);
			$cpt++;
		} catch(Exception $e) {
			echo "-- ECHEC REQUÊTE: [$req]\n";
		}
	}
	echo "$cpt listes migrées\n";*/
}

/**
 * Migre les listes de discussion (nom, visibilité)
 * @ATTENTION penser à migrer les projets d'abord, sans quoi la contrainte de
 * clef étrangère va empêcher d'insérer les données de réglages d'outils
 */
function migration_listes($argc, $argv) {
	global $bdProjet;
	global $bdWordpress;
	global $prefixe_tables_wp;

	$reqListes = "SELECT pl.pl_nom_liste, pl.pl_visibilite, pll.pl_id_projet FROM projet_liste pl LEFT JOIN projet_lien_liste pll ON pll.pl_id_liste = pl.pl_id_liste";
	$resListes = $bdProjet->query($reqListes);
	$listes = array();
	while ($ligne = $resListes->fetch()) {
		$listes[$ligne['pl_id_projet']] = array(
			"pl_nom_liste" => $ligne['pl_nom_liste'],
			"pl_visibilite" => $ligne['pl_visibilite']
		);
	}
	//var_dump($listes);
	echo count($listes) . " listes trouvées\n";

	// insertion dans {$prefixe_tables_wp}_tb_outils_reglages
	$tableReglages = $prefixe_tables_wp . 'tb_outils_reglages';
	$cpt = 0;
	foreach ($listes as $idProjet => $liste) {
		$prive = ($liste['pl_visibilite'] == 1 ? 0 : 1); // inverseur de flux quantique
		$nomListe = $liste['pl_nom_liste'];
		$posAt = strpos($nomListe, '@'); // au cas où le nom de liste soit l'adresse entière
		if ($posAt !== false) {
			$nomListe = substr($nomListe, 0, $posAt);
		}
		$jsonConfig = '{"ezmlm-php": {"list": "' . $nomListe . '"}}';
		$req = "INSERT INTO $tableReglages (id_projet, id_outil, name, prive, config) VALUES($idProjet, 'forum', 'forum', $prive, '$jsonConfig');";
		//echo $req . "\n";
		try {
			$bdWordpress->exec($req);
			$cpt++;
		} catch(Exception $e) {
			echo "-- ECHEC REQUÊTE: [$req]\n";
		}
	}
	echo "$cpt listes migrées\n";
}