<?php
/**
 * Migre les différentes données existantes dans l' "ancien site Tela Botanica"
 * vers la base de données de Wordpress, et les documents des projets vers
 * Cumulus
 */

require_once "config.php";

$actions = array("nettoyage", "tout_sauf_docs", "documents", "documents-proprietaires", "projets", "inscrits", "listes", "listes-permissions", "config-porte-docs", "utilisateurs", "wikis");

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
	case "documents-proprietaires":
		migration_proprietaires_documents($argc, $argv);
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
	case "listes-permissions":
		migration_listes_permissions($argc, $argv);
		break;
	case "config-porte-docs":
		configuration_porte_documents($argc, $argv);
		break;
	case "utilisateurs":
		migration_utilisateurs($argc, $argv);
		break;
	case "wikis":
		migration_wikis($argc, $argv);
		break;
	case "nettoyage":
		nettoyage($argc, $argv);
		break;
	case "tout_sauf_docs": // et sauf "liste-permissions", aussi
		migration_projets($argc, $argv);
		//migration_utilisateurs($argc, $argv);
		migration_inscrits($argc, $argv);
		migration_listes($argc, $argv);
		configuration_porte_documents($argc, $argv);
		migration_wikis($argc, $argv);
		break;
	default:
		throw new Exception('une action déclarée dans $actions devrait avoir un "case" correspondant dans le "switch"');
}

/**
 * Remet tout comme c'était avant, sauf les documents : vide la table des
 * projets, la table des inscrits, les métadonnées afférentes, et la config des
 * outils Tela Botanica
 * @WARNING détruit tout violemment !!
 * @TODO ajouter une confirmation avant de le lancer
 */
function nettoyage($argc, $argv) {
	global $bdWordpress;
	global $prefixe_tables_wp;

	$tableGroupes = $prefixe_tables_wp . 'bp_groups';
	$tableGroupesMeta = $prefixe_tables_wp . 'bp_groups_groupmeta';
	$tableMetadonneesUtilisateurs = $prefixe_tables_wp . 'usermeta';
	$tableMembres = $prefixe_tables_wp . 'bp_groups_members';
	$tableReglages = $prefixe_tables_wp . 'tb_outils_reglages';
	$tableBPActivite = $prefixe_tables_wp . "bp_activity";
	$tableUtilisateurs = $prefixe_tables_wp . "users";
	$tableUtilisateursNouveauNom = $prefixe_tables_wp . "users_original";

	$req = "DELETE FROM $tableGroupes;";
	try {
		$bdWordpress->exec($req);
		echo "Groupes supprimés" . PHP_EOL;
	} catch(Exception $e) {
		echo "-- ECHEC REQUÊTE: [$req]" . PHP_EOL;
	}
	$req = "DELETE FROM $tableGroupesMeta;";
	try {
		$bdWordpress->exec($req);
		echo "Métadonnées des groupes supprimées" . PHP_EOL;
	} catch(Exception $e) {
		echo "-- ECHEC REQUÊTE: [$req]" . PHP_EOL;
	}
	$req = "DELETE FROM $tableMetadonneesUtilisateurs WHERE user_id > 1 AND meta_key = 'total_group_count';";
	try {
		$bdWordpress->exec($req);
		echo "Métadonnées des utilisateurs supprimées" . PHP_EOL;
	} catch(Exception $e) {
		echo "-- ECHEC REQUÊTE: [$req]" . PHP_EOL;
	}
	$req = "DELETE FROM $tableMembres WHERE user_id > 1;";
	try {
		$bdWordpress->exec($req);
		echo "Membres supprimés" . PHP_EOL;
	} catch(Exception $e) {
		echo "-- ECHEC REQUÊTE: [$req]" . PHP_EOL;
	}
	$req = "DELETE FROM $tableReglages;";
	try {
		$bdWordpress->exec($req);
		echo "Réglages des outils supprimés" . PHP_EOL;
	} catch(Exception $e) {
		echo "-- ECHEC REQUÊTE: [$req]" . PHP_EOL;
	}
	$req = "DELETE FROM $tableBPActivite WHERE user_id > 1;";
	try {
		$bdWordpress->exec($req);
		echo "Activité des membres supprimée" . PHP_EOL;
	} catch(Exception $e) {
		echo "-- ECHEC REQUÊTE: [$req]" . PHP_EOL;
	}
	// la vue utilisateurs n'est plus utilisée depuis que les utilisateurs ont
	// été migrés pour de vrai
	/*$req = "DROP VIEW $tableUtilisateurs;";
	try {
		$bdWordpress->exec($req);
		echo "Vue utilisateurs supprimée" . PHP_EOL;
	} catch(Exception $e) {
		echo "-- ECHEC REQUÊTE: [$req]" . PHP_EOL;
	}
	$req = "RENAME TABLE $tableUtilisateursNouveauNom TO $tableUtilisateurs;";
	try {
		$bdWordpress->exec($req);
		echo "Table utilisateurs d'origine restaurée" . PHP_EOL;
	} catch(Exception $e) {
		echo "-- ECHEC REQUÊTE: [$req]" . PHP_EOL;
	}*/
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
			"pd_nom" => trim(nettoyer_nom_ressource($ligne['pd_nom'])),
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
		$titre = trim(nettoyer_nom_ressource($f['pd_nom']));
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
		$_path = rtrim($_path, '/');
		$_storage_path = $prefixe_bd . rtrim($cheminCumulus, '/') . '/' . $nouveauNomFichier;

		// calcul de la clef
		$clef = sha1($_path . $nouveauNomFichier);
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
			'keywords' => array(
				// permet de retrouver les documents par leur ancien ID, avec une URL de la forme :
				// https://api.tela-botanica.org/service:cumulus:doc/api/by-keywords/legacyid:21397/dl
				'legacyid:' . $f['pd_id']
			),
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

/**
 * Convertit les adresses email des propriétaires ("owner") des documents
 * Cumulus importés en numéros d'utilisateurs dans l'annuaire TB
 * @TODO devrait se trouver dans le script de maintenance de Cumulus et non ici
 */
function migration_proprietaires_documents($argc, $argv) {
	global $bdCumulus;

	$req = "UPDATE cumulus_files f LEFT JOIN tela_prod_v4.annuaire_tela a ON a.U_MAIL = f.owner SET f.owner = a.U_ID;";
	try {
		$bdCumulus->exec($req);
	} catch (Exception $e) {
		echo "-- ECHEC REQUÊTE: [$req]" . PHP_EOL;
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
			// on va traiter le père au prochain tour
			$entite = $dossiers[$entite['pd_pere']];
			// on remonte le chemin depuis la fin
			$chemin = '/' . $entite['pd_nom'] . $chemin;
		} else {
			// chemin cassé
			return false;
		}
	}
	return $chemin;
}

/**
 * Supprime les '/' (sauf le '/' final sinon ça casse tout)
 * Les noms de fichiers peuvent contenir des '/' et ça casse tout, donc on les vire
 * Idem pour les noms de dossiers, même si il faut garder le '/' final
 */
function nettoyer_nom_ressource($nom) {
	$propre = preg_replace_callback(
		"@/.+@", // y-a-t'il un slash non-final dans le nom ?
		function ($input) {
			$name = $input[0];
			$isFolder = false;
			if ('/' === mb_substr($name, -1)) { // slash final ?
				$isFolder = true;
				$name = mb_substr($name, 0, strlen($name)-1); // on enlève le slash pour le remettre + tard
			}

			$name = str_replace('/', '-', $name);

			return $name . ($isFolder ? '/' : '');
		},
		$nom
	);

	if (!$propre) {
		throw new Exception("Propre isn't propre.", 1);
	}

	return $propre;
}

/**
 * Copie les caractéristiques des projets existants dans les
 * nouveaux projets (Wordpress / Buddypress)
 */
function migration_projets($argc, $argv) {
	global $bdProjet;
	global $bdWordpress;
	global $prefixe_tables_wp;
	global $projets_a_migrer;

	$reqProjets = "SELECT p_id, p_titre, p_resume, p_description, p_espace_internet, p_wikini, p_date_creation, p_type, p_modere, GROUP_CONCAT(pt_label_theme) as themes FROM projet LEFT JOIN projet_avoir_theme ON pat_id_projet = p_id LEFT JOIN projet_theme ON pat_id_theme = pt_id_theme WHERE p_id IN ($projets_a_migrer) GROUP BY p_id";
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
	//var_dump($projets);
	echo count($projets) . " projets trouvés\n";

	// insertion dans {$prefixe_tables_wp}_bp_groups
	$tableGroupes = $prefixe_tables_wp . 'bp_groups';
	$tableGroupesMeta = $prefixe_tables_wp . 'bp_groups_groupmeta';
	$cpt = 0;
	foreach ($projets as $id => $projet) {
		$nom = html_entity_decode($projet['p_titre']);
		if (! preg_match('//u', $nom)) {
			$nom = iconv("ISO-8859-1", "UTF-8//TRANSLIT", $nom);
		}
		$nom = dqq($nom);

		$slug = html_entity_decode($projet['p_titre']);
		if (! preg_match('//u', $slug)) {
			$slug = iconv("ISO-8859-1", "UTF-8//TRANSLIT", $slug);
		}
		$slug = limacifier($slug);

		$description = html_entity_decode($projet['p_description']);
		if (! preg_match('//u', $description)) {
			$description = iconv("ISO-8859-1", "UTF-8//TRANSLIT", $description);
		}
		$description = dqq($description);

		$resume = html_entity_decode($projet['p_resume']);
		if (! preg_match('//u', $resume)) {
			$resume = iconv("ISO-8859-1", "UTF-8//TRANSLIT", $resume);
		}
		$resume = dqq($resume);

		$espaceInternet = $projet['p_espace_internet'];
		$wikiExterne = $projet['p_wikini']; // en général Wikini
		/*
		 * ATTENTION, p_resume va dans bp_groups mais c'est la description
		 * courte; la description longue va dans les triplets de bp_groups_meta
		 */
		$status = "public"; // "public", "hidden" ou "private";
		if ($projet['p_modere'] == 1) { // @TODO vérifier si ça correspond (plutôt un réglage de liste ?)
			$status = "private";
		}
		$dateCreation = $projet['p_date_creation'];
		// Le creator_id est NOT NULL; il est mis à 1 (utilisateur admin) et
		// sera mis à jour dans migration_inscrits
		//var_dump($projet);
		$req = "INSERT INTO $tableGroupes (id, creator_id, name, slug, description, status, enable_forum, date_created) "
			. "VALUES($id, 1, '$nom', '$slug', '$resume', '$status', 1, '$dateCreation');";
		//echo $req . "\n";
		// Tout ce qui ne rentre pas dans "groups" part en métadonnées :
		// - description complète
		// - adresse wiki externe ("espace Internet")
		// - mots-clés (dans "gtags_group_tags"), non utilisé ici
		// - last_activity doit exister, sans quoi le groupe ne ressortira pas
		//		dans la liste des groupes !
		$reqMeta = "INSERT INTO $tableGroupesMeta (group_id, meta_key, meta_value) VALUES "
			. "($id, 'description-complete', '$description'), "
			. "($id, 'published', '1'), "
			. "($id, 'total_member_count', '0'), "
			. "($id, 'invite_status', 'members'), "
			. "($id, 'last_activity', NOW()), "
			. "($id, 'wiki-externe', '$wikiExterne'), "
			. "($id, 'url-site', '$espaceInternet')";

		//echo $reqMeta . "\n";
		try {
			$bdWordpress->exec($req);
			$bdWordpress->exec($reqMeta);
			$cpt++;
		} catch(Exception $e) {
			echo "-- ECHEC REQUÊTE: [$req | $reqMeta]\n";
		}
	}
	echo "$cpt projets migrés\n";
}

/**
 * Transforme un titre de projet en "slug" (limace) : un nom sans espace, en
 * minuscules, sans accents ni caractères spéciaux
 * http://stackoverflow.com/questions/2955251/php-function-to-make-slug-url-string
 */
function limacifier($text) {
	$originalText = $text;
	// replace non letter or digits by -
	$text = preg_replace('~[^\pL\d]+~u', '-', $text);
	// remove accents
	$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
	// remove unwanted characters
	$text = preg_replace('~[^-\w]+~', '', $text);
	// trim
	$text = trim($text, '-');
	// remove duplicate -
	$text = preg_replace('~-+~', '-', $text);
	// lowercase
	$text = strtolower($text);
	if (empty($text)) {
		echo "La limacification a trop bien marché pour [$originalText] :-/" . PHP_EOL;
	}
return $text;
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
	//var_dump($inscrits);
	echo count($inscrits) . " inscrits trouvés\n";

	// groupement par personne, pour avoir le nombre de projets par utilisateur
	$projetsParPersonne = array();
	foreach ($inscrits as $i) {
		$idUtilisateur = $i['psu_id_utilisateur'];
		if (array_key_exists($idUtilisateur, $projetsParPersonne)) {
			$projetsParPersonne[$idUtilisateur]++;
		} else {
			$projetsParPersonne[$idUtilisateur] = 1;
		}
	}
	//var_dump($projetsParPersonne);

	// groupement par projet, pour trouver le nombre total de membres et qui est
	// le patron
	$inscritsParProjet = array();
	foreach ($inscrits as $id => $inscrit) {
		$idProjet = $inscrit['psu_id_projet'];
		$idUtilisateur = $inscrit['psu_id_utilisateur'];
		$statutUtilisateur = $inscrit['ps_statut_nom'];
		if (! array_key_exists($idProjet, $inscritsParProjet)) {
			$inscritsParProjet[$idProjet] = array(
				'inscrits' => array(),
				'admin' => null
			);
		}
		// on compte les inscrits
		$inscritsParProjet[$idProjet]['inscrits'][] = $inscrit;
		// qui est le patron ?
		// le premier des "coordonnateurs" est considéré comme le créateur du
		// projet @TODO c'est certainement pas vrai !
		if (($statutUtilisateur == 'Coordonnateur' || $statutUtilisateur == 'Administrateur') && ($inscritsParProjet[$idProjet]['admin'] === null)) {
			$inscritsParProjet[$idProjet]['admin'] = (int)$idUtilisateur;
		}
	}
	//var_dump($inscritsParProjet);

	// mise à jour du nombre total de groupes dans {$prefixewp}_usermeta
	$tableMetadonneesUtilisateurs = $prefixe_tables_wp . 'usermeta';
	$cptM = 0;
	foreach ($projetsParPersonne as $idUtilisateur => $nombre) {
		$req = "INSERT INTO $tableMetadonneesUtilisateurs (user_id, meta_key, meta_value) VALUES ($idUtilisateur, 'total_group_count', $nombre)";
		//echo $req . "\n";
		try {
			$bdWordpress->exec($req);
			$cptM++;
		} catch(Exception $e) {
			echo "-- ECHEC REQUÊTE: [$req]\n";
		}
	}
	echo "$cptM métadonnées utilisateurs mises à jour (total_group_count)\n";

	// insertion dans {$prefixe_tables_wp}_bp_groups
	$tableGroupes = $prefixe_tables_wp . 'bp_groups';
	$tableMembres = $prefixe_tables_wp . 'bp_groups_members';
	$tableGroupesMeta = $prefixe_tables_wp . 'bp_groups_groupmeta';
	$cptP = 0;
	$cptI = 0;
	foreach ($inscritsParProjet as $idProjet => $ipp) {
		// le pire n'est jamais décevant !
		if ($idProjet == 0) continue;

		$nbInscrits = count($ipp['inscrits']);
		$createur = $ipp['admin'];
		if ($createur === null) {
			$createur = $ipp['inscrits'][0]['psu_id_utilisateur'];
		}
		//var_dump($ipp);
		$reqMajP = "UPDATE $tableGroupes SET creator_id = $createur WHERE id = $idProjet";
		//echo $reqMajP . "\n";
		// Stockage de "total_member_count" en métadonnées
		// $reqMeta = "INSERT INTO $tableGroupesMeta (group_id, meta_key, meta_value) VALUES "
		// 	. "($idProjet, 'total_member_count', $nbInscrits)";
		$reqMeta = "UPDATE $tableGroupesMeta SET meta_value = $nbInscrits "
			. "WHERE group_id = $idProjet AND meta_key = 'total_member_count'";
		//echo $reqMeta . "\n";
		try {
			$bdWordpress->exec($reqMajP);
			$bdWordpress->exec($reqMeta);
			$cptP++;
			// inscriptions des messieurs-dames
			foreach ($ipp['inscrits'] as $inscrit) {
				$idUtilisateur = $inscrit['psu_id_utilisateur'];
				$statut = $inscrit['ps_statut_nom'];
				// s'il n'y a pas de créateur de groupe, ou si c'est lui qu'on
				// est en train de traiter, on considère que le membre a été
				// invité par l'admin du site (ça ou autre chose...)
				$inviteur = 1;
				if ($ipp['admin'] !== null && $ipp['admin'] != $idUtilisateur) {
					$inviteur = $ipp['admin'];
				}
				// @TODO dans un premier temps on confond les rôles "admin" et "mod"
				$estAdmin = ($statut == 'Coordonnateur' || $statut == 'Administrateur') ? 1 : 0;
				$estConfirme = ($statut == 'En attente') ? 0 : 1;
				// go Jeannine !
				$req = "INSERT INTO $tableMembres (group_id, user_id, inviter_id, is_admin, is_mod, user_title, date_modified, comments, is_confirmed, is_banned, invite_sent) "
					. "VALUES ($idProjet, $idUtilisateur, $inviteur, $estAdmin, $estAdmin, '$statut', NOW(), '', $estConfirme, 0, 0);";
				//echo $req . "\n";
				try {
					$bdWordpress->exec($req);
					$cptI++;
				} catch(Exception $e) {
					echo "-- ECHEC REQUÊTE: [$req]\n";
				}
			}
		} catch(Exception $e) {
			echo "-- ECHEC REQUÊTE: [$reqMajP | $reqMeta]\n";
		}
	}
	echo "$cptI inscrits migrés\n";
	echo "$cptP projets mis à jour\n";
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
	global $projets_a_migrer;

	$reqListes = "SELECT pl.pl_nom_liste, pl.pl_visibilite, pll.pl_id_projet FROM projet_liste pl LEFT JOIN projet_lien_liste pll ON pll.pl_id_liste = pl.pl_id_liste WHERE pll.pl_id_projet IN ($projets_a_migrer)";
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
		$req = "INSERT INTO $tableReglages (id_projet, id_outil, name, prive, create_step_position, nav_item_position, enable_nav_item, config) "
			. "VALUES($idProjet, 'forum', 'Forum', $prive, 70, 70, 1, {$bdWordpress->quote($jsonConfig)});";
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


/**
 * Consulte les permissions des listes dans la nouvelle base de données, et
 * génère une liste de commandes à exécuter sur sequoia/vpopmail pour mettre à
 * jour les permissions dans ezmlm. @ATTENTION, lire la page de manuel
 * d'ezmlm-make pour ne pas faire de bêtises (sticky bit, etc.)
 * Conseil : rediriger la sortie vers un fichier
 */
function migration_listes_permissions($argc, $argv) {
	global $bdWordpress;
	global $prefixe_tables_wp;

	$tableReglages = $prefixe_tables_wp . 'tb_outils_reglages';
	$req = "SELECT prive, config FROM $tableReglages WHERE id_outil = 'forum'";
	$res = $bdWordpress->query($req);
	$listes = array(
		'a_rendre_publiques' => array(),
		'a_rendre_privees' => array()
	);
	while ($ligne = $res->fetch()) {
		$config = json_decode($ligne['config'], true);
		$nomListe = $config['ezmlm-php']['list'];
		if ($ligne['prive'] == 1) {
			$listes['a_rendre_privees'][] = $nomListe;
		} else {
			$listes['a_rendre_publiques'][] = $nomListe;
		}
	}
	//var_dump($listes);
	sort($listes['a_rendre_privees']);
	sort($listes['a_rendre_publiques']);

	$pat = "/usr/local/bin/ezmlm/ezmlm-make -e+%s /home/vpopmail/domains/tela-botanica.org/%s/\n";
	echo "# Généré par le script de migration du plugin Wordpress [telabotanica] le " . date("Y-m-d H:i:s") . " :\n\n";
	// commandes à exécuter
	echo "# listes à rendre PRIVÉES (" . count($listes['a_rendre_privees']) . ") :\n";
	foreach ($listes['a_rendre_privees'] as $l) {
		printf($pat, "P", $l);
	}
	echo "\n";
	echo "# listes à rendre PUBLIQUES (" . count($listes['a_rendre_publiques']) . ") :\n";
	foreach ($listes['a_rendre_publiques'] as $l) {
		printf($pat, "p", $l);
	}
}

/**
 * Insère les réglages du porte-documents par défaut, pour chaque projet
 * @ATTENTION, ne touche pas aux documents, voir l'action "documents" pour ça
 */
function configuration_porte_documents($argc, $argv) {
	global $bdWordpress;
	global $prefixe_tables_wp;

	$tableReglages = $prefixe_tables_wp . 'tb_outils_reglages';
	$tableProjets = $prefixe_tables_wp . 'bp_groups';
	$req = "INSERT INTO $tableReglages "
		. "(id_projet, id_outil, name, prive, create_step_position, nav_item_position, enable_nav_item, config) "
		. "SELECT ID, 'porte-documents', 'Porte-documents', IF(status = 'private', 1, 0), 71, 71, 1, '{}' FROM $tableProjets";
	try {
		$bdWordpress->exec($req);
		echo "Configuration par défaut du porte-documents insérée\n";
	} catch(Exception $e) {
		echo "-- ECHEC REQUÊTE: [$req]\n";
	}
}

/**
 * Renomme {prefixewp}_users en  {prefixewp}_users_original et crée une vue de
 * tela_prod_v4.annuaire_tela sur {prefixewp}_users; ajoute une métadonnée
 * "last_activity" aux utilisateurs, afin qu'ils apparaîssent dans les listes
 * de membres des groupes
 */
function migration_utilisateurs($argc, $argv) {
	throw new Exception("Cette méthode est obsolète ! Les utilisateurs sont censés avoir été migrés pour de vrai.");

	global $bdWordpress;
	global $prefixe_tables_wp;

	$tableUtilisateurs = $prefixe_tables_wp . "users";
	$tableUtilisateursNouveauNom = $prefixe_tables_wp . "users_original";
	$tableMetadonneesUtilisateurs = $prefixe_tables_wp . "usermeta";
	$tableBPActivite = $prefixe_tables_wp . "bp_activity";
	$tableAnnuaire = "tela_prod_v4.annuaire_tela";

	$vueExisteDeja = false;
	$req0 = "SHOW TABLES LIKE '$tableUtilisateursNouveauNom'";
	try {
		$res = $bdWordpress->query($req0);
		$res = $res->fetchAll();
		$vueExisteDeja = (count($res) > 0);
	} catch(Exception $e) {
		echo "-- ECHEC REQUÊTE: [$req0]\n";
		exit;
	}

	if ($vueExisteDeja) {
		echo "La vue $tableUtilisateurs semble déjà exister (présence d'une table $tableUtilisateursNouveauNom)" . PHP_EOL;
	} else {
		// renomme la table des utilisateurs Wordpress pour la remplacer par la vue
		$req1 = "RENAME TABLE $tableUtilisateurs TO $tableUtilisateursNouveauNom;";
		try {
			$bdWordpress->exec($req1);
			echo "Table $tableUtilisateurs renommée en $tableUtilisateursNouveauNom" . PHP_EOL;
		} catch(Exception $e) {
			echo "-- ECHEC REQUÊTE: [$req1]\n";
		}

		// création d'une vue qui unit les utilisateurs Wordpress et les
		// utilisateurs de Tela Botanica
		$req2 = "CREATE VIEW $tableUtilisateurs AS "
			. "SELECT *, "
			// ajout de 2 colonnes pour coller à l'astuce plus bas
			. "'' as first_name, '' as last_name "
			. "FROM $tableUtilisateursNouveauNom "
			. "UNION "
			. "SELECT U_ID as ID, LOWER(U_MAIL) as user_login, U_PASSWD as user_pass, "
			// astuce pour avoir un user_nicename valide, unique, et pas trop explicite
			. "REPLACE(LCASE(CONCAT(SUBSTRING(U_MAIL, 1, LOCATE('@', U_MAIL) - 1), '-',REVERSE(SUBSTRING(U_MAIL, LOCATE('@', U_MAIL) + 1)))),'.','-') as user_nicename, "
			. "U_MAIL as user_email, '' as user_url, U_DATE as user_registered, "
			. "'' as user_activation_key, 0 as user_status, CONCAT(U_SURNAME, ' ', U_NAME) as display_name, "
			// ajout de 2 colonnes pour pouvoir générer les métadonnées ensuite
			. "U_SURNAME as first_name, U_NAME as last_name "
			. "FROM $tableAnnuaire WHERE U_PASSWD != '';";
		try {
			$bdWordpress->exec($req2);
			echo "Vue $tableUtilisateurs créée" . PHP_EOL;
		} catch(Exception $e) {
			echo "-- ECHEC REQUÊTE: [$req2]\n";
			// on remet tout comme avant
			$req3 = "RENAME TABLE $tableUtilisateursNouveauNom TO $tableUtilisateurs;";
			try {
				$bdWordpress->exec($req3);
				echo "Table $tableUtilisateursNouveauNom renommée en $tableUtilisateurs" . PHP_EOL;
			} catch(Exception $e) {
				echo "-- ECHEC REQUÊTE: [$req3]\n";
			}
		}
	}

	// insertion d'une métadonnée "last_activity" à tous les utilisateurs hors
	// table d'origine de Wordpress dans la table {prefixewp}_usermeta
	$req4 = "INSERT INTO $tableMetadonneesUtilisateurs (user_id, meta_key, meta_value) "
		. "SELECT ID, 'last_activity', NOW() "
		. "FROM $tableUtilisateurs "
		. "WHERE ID NOT IN(SELECT DISTINCT user_id FROM $tableMetadonneesUtilisateurs WHERE meta_key = 'last_activity') "
		. "AND ID NOT IN (SELECT ID FROM $tableUtilisateursNouveauNom);";
	try {
		$bdWordpress->exec($req4);
		echo "Activité WP insérée" . PHP_EOL;
	} catch(Exception $e) {
		echo "-- ECHEC REQUÊTE: [$req4]\n";
	}

	// insertion des métadonnées "first_name", "last_name" et "nickname" pour
	// tous les utilisateurs
	$req4a = "INSERT INTO $tableMetadonneesUtilisateurs (user_id, meta_key, meta_value) "
		. "SELECT ID, 'first_name', first_name "
		. "FROM $tableUtilisateurs "
		. "WHERE ID NOT IN(SELECT DISTINCT user_id FROM $tableMetadonneesUtilisateurs WHERE meta_key = 'first_name') "
		. "AND ID NOT IN (SELECT ID FROM $tableUtilisateursNouveauNom);";
	try {
		$bdWordpress->exec($req4a);
		echo "Prénoms WP insérés (meta)" . PHP_EOL;
	} catch(Exception $e) {
		echo "-- ECHEC REQUÊTE: [$req4a]\n";
	}
	$req4b = "INSERT INTO $tableMetadonneesUtilisateurs (user_id, meta_key, meta_value) "
		. "SELECT ID, 'last_name', last_name "
		. "FROM $tableUtilisateurs "
		. "WHERE ID NOT IN(SELECT DISTINCT user_id FROM $tableMetadonneesUtilisateurs WHERE meta_key = 'last_name') "
		. "AND ID NOT IN (SELECT ID FROM $tableUtilisateursNouveauNom);";
	try {
		$bdWordpress->exec($req4b);
		echo "Noms WP insérés (meta)" . PHP_EOL;
	} catch(Exception $e) {
		echo "-- ECHEC REQUÊTE: [$req4b]\n";
	}
	$req4c = "INSERT INTO $tableMetadonneesUtilisateurs (user_id, meta_key, meta_value) "
		. "SELECT ID, 'nickname', display_name "
		. "FROM $tableUtilisateurs "
		. "WHERE ID NOT IN(SELECT DISTINCT user_id FROM $tableMetadonneesUtilisateurs WHERE meta_key = 'nickname') "
		. "AND ID NOT IN (SELECT ID FROM $tableUtilisateursNouveauNom);";
	try {
		$bdWordpress->exec($req4c);
		echo "Surnoms WP insérés (meta)" . PHP_EOL;
	} catch(Exception $e) {
		echo "-- ECHEC REQUÊTE: [$req4c]\n";
	}

	// insertion d'une métadonnée "last_activity" dans la table
	// {prefixewp}_bp_activity
	$req5 = "INSERT INTO $tableBPActivite (user_id, component, type, action, content, primary_link, item_id, secondary_item_id, date_recorded, hide_sitewide, mptt_left, mptt_right, is_spam)"
		. "SELECT ID, 'members', 'last_activity', '', '','', 0, NULL, NOW(), 0, 0, 0, 0 "
		. "FROM $tableUtilisateurs "
		. "WHERE ID NOT IN(SELECT DISTINCT user_id FROM $tableBPActivite WHERE component = 'members' AND type = 'last_activity') "
		. "AND ID NOT IN (SELECT ID FROM $tableUtilisateursNouveauNom);";
	try {
		$bdWordpress->exec($req5);
		echo "Activité BP insérée" . PHP_EOL;
	} catch(Exception $e) {
		echo "-- ECHEC REQUÊTE: [$req5]\n";
	}

	// insertion des valeurs "{wp_prefix}_capabilities" et  "{wp_prefix}_user_level"
	$cleCapabilities = $prefixe_tables_wp . "capabilities";
	$req6a = "INSERT INTO $tableMetadonneesUtilisateurs (user_id, meta_key, meta_value) "
		. "SELECT ID, '$cleCapabilities', '{a:1:{s:11:\"contributor\";b:1;}' " // "contributeur"
		. "FROM $tableUtilisateurs "
		. "WHERE ID NOT IN(SELECT DISTINCT user_id FROM $tableMetadonneesUtilisateurs WHERE meta_key = '$cleCapabilities') "
		. "AND ID NOT IN (SELECT ID FROM $tableUtilisateursNouveauNom);";
	try {
		$bdWordpress->exec($req6a);
		echo "Capabilities WP insérées (meta)" . PHP_EOL;
	} catch(Exception $e) {
		echo "-- ECHEC REQUÊTE: [$req6a]\n";
	}
	$cleUserLevel = $prefixe_tables_wp . "user_level";
	$req6b = "INSERT INTO $tableMetadonneesUtilisateurs (user_id, meta_key, meta_value) "
		. "SELECT ID, '$cleUserLevel', '1' " // user level 1 = "contributeur"
		. "FROM $tableUtilisateurs "
		. "WHERE ID NOT IN(SELECT DISTINCT user_id FROM $tableMetadonneesUtilisateurs WHERE meta_key = '$cleUserLevel') "
		. "AND ID NOT IN (SELECT ID FROM $tableUtilisateursNouveauNom);";
	try {
		$bdWordpress->exec($req6b);
		echo "User levels WP insérés (meta)" . PHP_EOL;
	} catch(Exception $e) {
		echo "-- ECHEC REQUÊTE: [$req6b]\n";
	}
}

/**
 * Active ou non le pseudo-outil "wiki" dans les groupes, selon la présence d'un
 * "wiki-externe" dans leurs métadonnées
 */
function migration_wikis($argc, $argv) {
	global $bdWordpress;
	global $prefixe_tables_wp;

	$tableOutilsReglages = $prefixe_tables_wp . "tb_outils_reglages";
	$tableGroupesMeta = $prefixe_tables_wp . "bp_groups_groupmeta";

	// suppression des réglages précédents
	$req = "DELETE FROM $tableOutilsReglages WHERE id_outil = 'wiki';";
	try {
		$bdWordpress->exec($req);
		echo "Réglages des wikis supprimés" . PHP_EOL;
	} catch(Exception $e) {
		echo "-- ECHEC REQUÊTE: [$req]" . PHP_EOL;
	}

	// insertion des nouveaux réglages
	// @TODO le faire en une fois avec un JOIN, la honte
	$req2 = "INSERT INTO $tableOutilsReglages "
		. "SELECT DISTINCT group_id, 'wiki', 'Wiki', 0, 75, 75, 0, '' "
		. "FROM $tableGroupesMeta WHERE meta_key = 'wiki-externe' AND meta_value = '';";
	try {
		$bdWordpress->exec($req2);
		echo "Réglages insérés pour les projets sans wikis" . PHP_EOL;
	} catch(Exception $e) {
		echo "-- ECHEC REQUÊTE: [$req2]" . PHP_EOL;
	}
	$req3 = "INSERT INTO $tableOutilsReglages "
		. "SELECT DISTINCT group_id, 'wiki', 'Wiki', 0, 75, 75, 1, "
		. "CONCAT('{\"wikiName\":\"',"
			. "IF(meta_value LIKE '%wakka.php%',"
				. "SUBSTRING("
					. "SUBSTRING_INDEX(meta_value, '/', -2),"
					. "1,"
					. "LOCATE("
						. "'/',"
						. "SUBSTRING_INDEX(meta_value, '/', -2)"
					. ")-1"
				. "),"
				. "IF(meta_value LIKE '%/',"
					. "SUBSTRING_INDEX(SUBSTRING(meta_value, 1, LENGTH(meta_value)-1), '/', -1),"
					. "SUBSTRING_INDEX(meta_value, '/', -1)"
			. ")),"
		. "'\"}') "
		. "FROM $tableGroupesMeta WHERE meta_key = 'wiki-externe' AND meta_value != '';";
	try {
		$bdWordpress->exec($req3);
		echo "Réglages insérés pour les projets avec wikis" . PHP_EOL;
	} catch(Exception $e) {
		echo "-- ECHEC REQUÊTE: [$req3]" . PHP_EOL;
	}
}
