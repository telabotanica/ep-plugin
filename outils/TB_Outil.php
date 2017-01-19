<?php

/**
 * Classe intermédiaire entre  BP_Group_Extension et chaque classe outil
 */
class TB_Outil extends BP_Group_Extension {

	/** configuration de l'outil pour l'instance (projet) en cours */
	protected $config;

	/** URL de la racine du plugin
	 ex: "http://localhost/wordpress/wp-content/plugins/tela-botanica/outils/" */
	protected $urlPlugin;
	/** URL de la racine de l'outil, pour charger les ressources en HTTP 
	    ex: "http://localhost/wordpress/wp-content/plugins/tela-botanica/outils/forum/" */
	protected $urlOutil;

	/** si true, l'outil ne sera disponible que pour les membres du projet 
	 * (s'applique uniquement aux groupes publics) */
	protected $prive;

	/** mis à true lors du chargement de la config, si l'outil est désactivé
	 * globalement dans le TdB WP */
	protected $desactive_globalement = false;

	/** id du groupe en cours */
	protected $groupId;

	/** id de l'utilisateur en cours */
	protected $userId;

	/**
	 * Initialisation post-constructeur : définit les chemins, charge les scripts,
	 * styles etc.
	 */
	public function initialisation()
	{
		// accès à l'objet magique BuddyPress
		$bp = buddypress();
		// copie de ce qui nous intéresse pour éviter d'y accédér cracratement
		// et hétérogènement par la suite
		$this->groupId = bp_get_current_group_id();
		$this->userId = $bp->loggedin_user->id;

		// Utile ?
		// Lu sur https://codex.buddypress.org/developer/group-extension-api/#examples
		$args = array(
			'slug' => $this->slug,
			'name' => $this->name
        );
		if (! empty($this->template_file)) {
			$args['template_file'] = $this->template_file;
		}
		parent::init($args);

		// chargement de la config : défaut < générale < locale
		$this->chargerConfig();

		$this->definirChemins();

		// préparation des scripts / styles, pour l'outil courant seulement
		// @WARNING le système de priorités n'a pas l'air de marcher...
		if ($this->outilCourant()) {
			add_action('wp_enqueue_scripts', array($this, 'scriptsEtStylesAvant'), 1);
			add_action('wp_enqueue_scripts', array($this, 'scriptsEtStylesApres'), 100);
		}
	}

	/**
	 * Placer ici les wp_enqueue_(script|style)() pour l'outil courant; ils
	 * seront déclarés AVANT les ressources globales de WP/BP, donc ils
	 * seront écrasés par les ressources ayant le même identifiant
	 */
	public function scriptsEtStylesAvant()
	{
		// rien par défaut
	}

	/**
	 * Placer ici les wp_enqueue_(script|style)() pour l'outil courant; ils
	 * seront déclarés APRES les ressources globales de WP/BP, donc ils
	 * écraseront les ressources ayant le même identifiant
	 */
	public function scriptsEtStylesApres()
	{
		// rien par défaut
	}

	public function definirChemins()
	{
        $this->urlPlugin = plugin_dir_url(__FILE__);
		$this->urlOutil = trailingslashit($this->urlPlugin . $this->slug);
    }

	/**
	 * Retourne la "configuration" par défaut d'un outil : pas les réglages de
	 * l'onglet BP (position, nom, etc.) mais les réglages de l'outil, par exemple
	 * pour le forum : URL racine de la lib ezmlm-php, etc.
	 */
	protected static function getConfigDefautOutil()
	{
		return array();
	}

	/**
	 * Charge la configuration générale de l'outil plus la configuration pour le
	 * projet en cours; concernant la colonne "config" (JSON libre), mélange les
	 * deux en donnant la priorité à la config du projet en cours et place le
	 * tout dans $this->config;
	 *
	 * Si aucune configuration locale pour le projet en cours n'existe au moment
	 * du chargement, un tuple sera écrit dans la table _tb_outils_reglages
	 */
	protected function chargerConfig()
	{
		global $wpdb;

		// 0) Config par défaut si rien n'est trouvé dans la base (ne devrait pas
		// se produire car la table_tb_outils devrait toujours avoir un tuple de
		// config par outil
		$id_projet = bp_get_current_group_id();
		$this->prive = 0;
		$this->create_step_position = 100;
		$this->nav_item_position = 100;
		$this->enable_nav_item = 1;
		$this->config = static::getConfigDefautOutil();

		/* 1) Lecture de la table "options" (config pour tous les projets) */
		$options_name = 'tb_' . $this->slug . '_config';
		$config_options_json = get_option($options_name);
		$config_options = json_decode($config_options_json, true);

		if (is_array($config_options)) {
			$this->config = array_merge($this->config, $config_options); // priorité à la config générale
		}

		/* 2) Lecture de la table "tb_outils_reglages" (config pour le projet en cours) */
		$requete = "
			SELECT *
			FROM {$wpdb->prefix}tb_outils_reglages
			WHERE id_projet='" . $id_projet . "'
			AND id_outil='" . $this->slug . "'
		";
		$res2 = $wpdb->get_results($requete) ;

		if (count($res2) > 0) {
			$config_locale = array_pop($res2);
			$this->name = $config_locale->name;
			$this->prive = $config_locale->prive;
			$this->create_step_position = $config_locale->create_step_position;
			$this->nav_item_position = $config_locale->nav_item_position;
			$this->enable_nav_item = $config_locale->enable_nav_item;
			$configLocale = json_decode($config_locale->config, true);

			if (is_array($configLocale)) {
				$this->config = array_replace_recursive($this->config, $configLocale); // priorité à la config locale
			}
		} else {
			// écriture de la config locale (projet en cours) s'il n'y en avait pas
			$this->ecrireConfigLocale();
		}

		// gestion de la désactivation globale d'un outil - ne pas reporter dans
		// la config locale
		if (isset($config_options['active']) && ($config_options['active'] == false)) {
			$this->desactive_globalement = true;
			$this->enable_nav_item = 0;
		}
	}

	/**
	 * Écrit dans la configuration locale (pour le projet en cours) les valeurs
	 * par défaut des options qui peuvent être changées par le menu "réglages du
	 * projet"; attention à ne pas écrire les options qui sont manipulées par le
	 * réglage général du plugin dans le TdB WP, sans quoi ce réglage général ne
	 * fonctionnera plus ! Voir preparer_config_locale()
	 */
	protected function ecrireConfigLocale()
	{
		global $wpdb;

		// différence entre la config totale et les options générales du TdB
		$config_locale = $this->preparer_config_locale();

		$bpGroupId = bp_get_current_group_id();
		// 0 signifie qu'on n'est pas dans une page de groupe
		if ($bpGroupId > 0) {
			$table = "{$wpdb->prefix}tb_outils_reglages";
			$data = array(
				"id_projet" => $bpGroupId,
				"id_outil" => $this->slug,
				"name" => $this->name,
				"prive" => $this->prive,
				"create_step_position" => $this->create_step_position,
				"nav_item_position" => $this->nav_item_position,
				"enable_nav_item" => $this->enable_nav_item,
				"config" => json_encode($config_locale)
			);
			$wpdb->insert($table, $data);
		}
	}

	/**
	 * Retourne une sous-partie de $this->config ne contenant que les options de
	 * l'outil en cours pouvant être modifiées dans l'onglet Réglages du projet
	 * @TODO améliorer cette stratégie (définition positive plutôt que négative
	 * => généricisation ?)
	 */
	protected function preparer_config_locale()
	{
		// par défaut tous les outils ont au moins un paramètre général "active"
		$config_locale = $this->config;
		unset($config_locale['active']);
		return $config_locale;
	}

	/**
	 * Retourne true si le flux d'exécution est dans l'outil en cours - permet
	 * de n'effectuer certaines actions que pour un outil donné
	 */
	protected function outilCourant() {
		$ok = bp_is_current_action($this->slug);
		return $ok;
	}

	/**
	 * Retourne le protocole et le domaine, donnés par PHP
	 */
	protected function getServerRoot()
	{
		return (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
	}

	/**
	 * Retourne l'URI de base de l'outil pour le groupe en cours (sans le domaine)
	 * ex: "/wordpress/groups/flore-d-afrique-du-nord/forum"
	 */
	protected function getBaseUri()
	{
		$pageGroupes = $this->getBPPageSlug("groups");
		$dossierRacine = $this->getDossierRacine();

        $baseUri = '/';
        if (! empty($dossierRacine)) {
            $baseUri .= $dossierRacine . '/';
        }
        $baseUri .= $pageGroupes . '/';
        $baseUri .= bp_get_current_group_slug() . '/';
        $baseUri .= $this->slug;

        return $baseUri;
	}

	/**
	 * Retourne l'URI de base des données l'outil (sans le domaine)
	 * ex: "/wordpress/wp-content/plugins/tela-botanica/outils/forum"
	 */
	protected function getDataBaseUri()
	{
		$dossierRacine = $this->getDossierRacine();
		return '/' . (! empty($dossierRacine) ? $dossierRacine . '/' : '') . 'wp-content/plugins/tela-botanica/outils/' . $this->slug;
	}

	/**
	 * Retourne le dossier dans lequel est installé Wordpress
	 * ex: "wordpress"
	 */
	protected function getDossierRacine()
	{
		$siteUrl = get_option("siteurl");
		$racineServeur = $this->getServerRoot();
		$dossierRacine = substr($siteUrl, strlen($racineServeur) + 1);

		return $dossierRacine;
	}

	/**
	 * Retourne le slug de la page Wordpress associée à unee "page" BuddyPress
	 * ("members", "groups", "activity"...)
	 *		=> ("inscrits", "projets", "activité"...)
	 */
	protected function getBPPageSlug($bpPage)
	{
		$wpToBpPages = get_option("bp-pages");
		if (! array_key_exists($bpPage, $wpToBpPages)) {
			throw new Exception('La page BuddyPress "' . $bpPage . '" n\'existe pas');
		}
		$wpPageSlug = get_post($wpToBpPages[$bpPage]);
		return $wpPageSlug->post_name;
	}

	/**
	 * Exécuté lors de l'installation du plugin TelaBotanica
	 */
	public function installation() {
		// rien par défaut
	}

	/**
	 * Exécuté lors de la désinstallation du plugin TelaBotanica
	 */
	public function desinstallation() {
		// rien par défaut
	}

	/**
	 * Si l'outil est désactivé dans la config générale, vide le panneau de
	 * réglages de l'outil et affiche un message à la place des réglages
	 */
	protected function controleAccesReglages() {
		if (
			(! bp_is_group_admin_screen($this->slug)) ||
			($this->desactive_globalement)
		) {
			echo "<p>L'outil " . $this->name . " a été désactivé par l'administrateur du site.</p>";
			exit;
		}
	}

	/**
	 * Si l'outil est privé, vérifie que l'utilisateur en cours est membre du
	 * projet : si oui, ne fait rien; si non, affiche un message et interrompt
	 * le chargement
	 */
	protected function appliquerCaracterePrive() {
		if ($this->prive) {
			$estMembre = groups_is_user_member($this->userId, $this->groupId);
			if (! $estMembre && ! is_super_admin()) {
				echo "<p>L'outil " . $this->name . " est réservé aux membres du projet</p>";
				exit;
			}
		}
	}
}
