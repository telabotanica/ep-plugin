<?php

/**
 * @link              https://github.com/telabotanica/ep-plugin
 * @since             1.0.0
 * @package           Tela_Botanica_Plugin
 *
 * @wordpress-plugin
 * Plugin Name:       Tela Botanica Plugin
 * Plugin URI:        https://github.com/telabotanica/ep-plugin
 * GitHub Plugin URI: https://github.com/telabotanica/ep-plugin
 * Description:       All Tela Botanica stuffs
 * Version:           1.0.0 dev
 * Author:            Tela Botanica
 * Author URI:        https://github.com/telabotanica
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       telabotanica
 * Domain Path:       /languages
 */

/*
 * Chargement de la configuration depuis config.json
 */
function tbChargerConfigPlugin()
{
	$fichierConfig = dirname( __FILE__ ) . "/config.json";
	if (! file_exists($fichierConfig)) {
		throw new Exception("Veuillez placer un fichier de configuration valide dans 'config.json'");
	}
	$config = file_get_contents($fichierConfig);
	$config = json_decode($config, true);

	return $config;
}

/* Chargement du code nécessitant BuddyPress */
function initialisation_bp()
{
	require( dirname( __FILE__ ) . '/admin/admin.php' );
	require( dirname( __FILE__ ) . '/newsletter/newsletter.php' );
	require( dirname( __FILE__ ) . '/outils/TB_Outil.php' );
	require( dirname( __FILE__ ) . '/formulaires/description/description-complete.php' );

	$config = tbChargerConfigPlugin();
	// chargement des outils depuis la configuration
	if (array_key_exists('outils', $config)) {
		foreach ($config['outils'] as $outil) {
			// include plutôt que require pour éviter une erreur fatale en cas
			// de mauvaise config
			include_once( dirname( __FILE__ ) . '/outils/' . $outil . '.php' );
		}
	}

	//require( dirname( __FILE__ ) . '/formulaires/etiquettes/etiquettes.php' );	
}

// amorçage du plugin lors de l'amorçage de BuddyPress
add_action( 'bp_include', 'initialisation_bp' );
add_action( 'bp_include', 'description_complete' );
//add_action( 'bp_include', 'categorie' );


// Charge la gestion des catégories de projets
require( dirname( __FILE__ ) . '/categories/categories.php' );

// Charge les hooks de synchronisation des données de wordpress vers les autres outils
require( dirname( __FILE__ ) . '/hooks/hooks.php' );
new Hooks();

// Charge la gestion de la modification du profil étendu (inscription lettre d'actu)
require( dirname( __FILE__ ) . '/profil-etendu/profil-etendu.php' );
new TB_GestionProfilEtendu();

// Charge les rôles propres au SSO (nécessite le plugin "Multiple Roles")
require( dirname( __FILE__ ) . '/roles/roles_sso.php' );
// le hook d'activation doit obligatoirement se trouver dans ce fichier...
function ajout_roles_sso() {
	RolesSSO::ajout_roles();
}
// idem pour le hook de désactivation
function suppression_roles_sso() {
	RolesSSO::ajout_roles(true);
}
// @TODO déplacer dans TelaBotanica::installation et TelaBotanica::desactivation
register_activation_hook(__FILE__, 'ajout_roles_sso');
register_deactivation_hook(__FILE__, 'suppression_roles_sso');


class TelaBotanica
{

	/**
	 * Constructeur de la classe TelaBotanica
	 * 
	 * Déclare les "hooks" d'activation / désactivation / désintallation  des
	 * fonctionnalités apportées par le plugin
	 */
	public function __construct()
	{
		// requiert Buddypress @TODO faire ça mieux avec admin_notice, mais
		// comment annuler l'activation sans jeter d'exception ?
		$plugins = get_option('active_plugins');
		$buddypressActif = in_array("buddypress/bp-loader.php", $plugins);
		if (! $buddypressActif) {
			trigger_error("Vous devez installer et activer Buddypress pour utiliser ce plugin", E_USER_WARNING);
		}

		// MODE PROD
		// il n'existe pas de "install_hook", c'est "activation_hook" qui doit
		// se charger de créer les tables et insérer les données par défaut
		register_activation_hook(__FILE__,array('TelaBotanica','installation'));
		register_uninstall_hook(__FILE__,array('TelaBotanica','desinstallation'));
		register_deactivation_hook(__FILE__,array('TelaBotanica','desactivation'));
	}

	/**
	 * Méthode d'installation / activation du plugin; tout ce qui est déclenché
	 * ici doit être idempotent car la désactivation ne supprime pas ce qui a
	 * été installé (seule la suppression le fait) mais la réactivation
	 * déclenche à nouveau cette méthode
	 */
	static function installation()
	{
		self::installation_outils();
		self::installation_champs_profil();
	}

	/**
	 * Méthode de désactivation - ne fait rien
	 */
	static function desactivation()
	{
	}

	/**
	 * Méthode de désinstallation du plugin
	 */
	static function desinstallation()
	{
		self::desinstallation_outils();
	}

	/**
	 * Insère dans les tables bp_xprofile_fields et bp_xprofile_groups les
	 * données des champs de profil étendu, après avoir **vidé** ces tables;
	 * fonctionne car il n'y a pas de ON DELETE CASCADE dans les tables
	 * bp_xprofile_*
	 * @TODO valider cette stratégie
	 * 
	 * Lit les fichiers SQL du dossier /profil-etendu, penser à les mettre à
	 * jour lorsque les champs du profil étendu changent : exporter les tables
	 * bp_xprofile_fields et bp_xprofile_groups, et dans chaque fichier
	 * supprimer le préfixe des tables. Ex:
	 *    "INSERT INTO `test_bp_xprofile_fields`"
	 * => "INSERT INTO `bp_xprofile_fields`"
	 */
	static function installation_champs_profil()
	{
		global $wpdb;
		$tableXPFields = $wpdb->prefix . 'bp_xprofile_fields';
		$tableXPGroups = $wpdb->prefix . 'bp_xprofile_groups';
		// vidage
		$wpdb->query("TRUNCATE TABLE $tableXPFields");
		$wpdb->query("TRUNCATE TABLE $tableXPGroups");

		$donneesXPFields = file_get_contents(__DIR__ . '/profil-etendu/bp_xprofile_fields.sql');
		$donneesXPFields = str_replace('bp_xprofile_fields', $tableXPFields, $donneesXPFields);
		$donneesXPGroups = file_get_contents(__DIR__ . '/profil-etendu/bp_xprofile_groups.sql');
		$donneesXPGroups = str_replace('bp_xprofile_groups', $tableXPGroups, $donneesXPGroups);
		// remplissage
		$wpdb->query($donneesXPFields);
		$wpdb->query($donneesXPGroups);
	}

	/*
	 * Crée la table "{$wpdb->prefix}tb_outils_reglages" et appelle la méthode
	 * installation() de chaque outil recensé dans la config
	 * 
	 * "{$wpdb->prefix}tb_outils_reglages" concerne la configuration d'un outil
	 * pour un projet donné (liée au sous-onglet de réglages de l'outil, dans
	 * l'onglet d'administration d'un projet)
	 * 
	 * Dans chaque table, la colonne "config" contient la configuration propre à
	 * chaque outil, en JSON (ou autre, à la discrétion de l'outil)
	 */
	static function installation_outils()
	{
		global $wpdb;
		$config = tbChargerConfigPlugin();		

		// Réglages d'un outil pour un projet
		$create_outils_reglages = "
			CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}tb_outils_reglages` (
				`id_projet` bigint(11) NOT NULL,
				`id_outil` varchar(50) NOT NULL,
				`name` varchar(50) NOT NULL,
				`prive` tinyint(1) NOT NULL,
				`create_step_position` tinyint(3) NOT NULL,
				`nav_item_position` tinyint(3) NOT NULL,
				`enable_nav_item` tinyint(1) NOT NULL,
				`config` text NOT NULL,
				PRIMARY KEY (`id_projet`,`id_outil`),
				KEY (`id_projet`),
				CONSTRAINT `fk_id-projet_id-group` FOREIGN KEY (`id_projet`) REFERENCES `{$wpdb->prefix}bp_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
			)
			ENGINE=InnoDB DEFAULT CHARSET=utf8;"
		;

		$wpdb->query($create_outils_reglages);

		/* déclenchement des routines d'installation des outils depuis la config
		 * @WARNING Astuce pourrite : les fichiers des classes outils ne sont
		 * normalement pas inclus si l'extension n'est pas activée, donc lors de
		 * l'activation de l'extention, eh ben ils n'y sont pas encore... donc on
		 * les inclut à la main ici, afin d'accéder à leur méthode "install"
		 * (un peu nul - revoir cette stratégie)
		 */
		if (array_key_exists('outils', $config)) {
			require( dirname( __FILE__ ) . '/outils/TB_Outil.php' );
			foreach ($config['outils'] as $outil) {
				// include plutôt que require pour éviter une erreur fatale en cas
				// de mauvaise config
				include_once( dirname( __FILE__ ) . '/outils/' . $outil . '.php' );
				$classeOutil = TelaBotanica::nomFichierVersClasseOutil($outil);
				call_user_func(array($classeOutil, 'installation'));
			}
		}
	}

	/*
	 * Appelle la méthode desinstallation() de chaque outil recensé dans la
	 * config puis supprime la table "{$wpdb->prefix}tb_outils_reglages"
	 */
	static function desinstallation_outils()
	{
		global $wpdb;
		$config = tbChargerConfigPlugin();

		// déclenchement des routines de désinstallation des outils depuis la config
		if (array_key_exists('outils', $config)) {
			foreach ($config['outils'] as $outil) {
				$classeOutil = TelaBotanica::nomFichierVersClasseOutil($outil);
				//echo "CO: [$classeOutil] ";
				call_user_func(array($classeOutil, 'desinstallation'));
			}
		}

		// On vérifie que les tables existent puis on les supprime
		$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}tb_outils_reglages;");
	}

	/*
	 * Convertit un nom d'outil correspondant au nom de fichier dans l'extension
	 * (ex: porte-documents) en nom de classe (ex: Porte_Documents); les - sont
	 * convertis en _, et la première lettre de  chaque mot passe en majuscule
	 */
	static function nomFichierVersClasseOutil($nomFichier)
	{
		$classeOutil = $nomFichier;
		$morceaux = explode('-', $nomFichier);
		foreach ($morceaux as $m) {
			$m = ucfirst(strtolower($m));
		}
		$classeOutil = implode('_', $morceaux);
		return $classeOutil;
	}	
}

new TelaBotanica();
