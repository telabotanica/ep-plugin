<?php

/**
 * Classe intermédiaire entre  BP_Group_Extension et chaque classe outil
 */
class TB_Outil extends BP_Group_Extension {

	/** configuration de l'outil pour l'instance (projet) en cours */
	protected $config;

	protected $urlPlugin;
	protected $urlOutil;

	/**
	 * Initialisation post-constructeur : définit les chemins, charge les scripts,
	 * styles etc.
	 */
	public function initialisation()
	{
		// recherche d'une config générale dans la base
		$this->chargerConfig();

		$this->definirChemins();
		// préparation des scripts / styles
		add_action('bp_enqueue_scripts', array($this, 'scriptsEtStylesConditionnels'));
	}

	/**
	 * Ne chargera les scripts et styles définis dans scriptsEtStyles() que si
	 * l'onglet de l'outil courant est actif
	 */
	public function scriptsEtStylesConditionnels()
	{
		if ($this->outilCourant()) {
			$this->scriptsEtStyles();
		}
	}

	/**
	 * Placer ici les wp_enqueue_(script|style)() pour l'outil courant
	 */
	protected function scriptsEtStyles()
	{
		// rien par défaut
	}

	protected function definirChemins()
	{
        // url to your plugin dir : site.url/wp-content/plugins/buddyplug/
        $this->urlPlugin = plugin_dir_url(__FILE__);
		$this->urlOutil = trailingslashit($this->urlPlugin . $this->slug);
    }

	/**
	 * Charge la configuration générale de l'outil plus la configuration pour le
	 * projet en cours; concernant la colonne "config" (JSON libre), mélange les
	 * deux en donnant la priorité à la config du projet en cours et place le
	 * tout dans $this->config
	 */
	protected function chargerConfig()
	{
		global $wpdb;

		// 0) Config par défaut si rien n'est trouvé dans la base
		$id_projet = bp_get_current_group_id();
		$this->prive = 0;
		$this->create_step_position = 100;
		$this->nav_item_position = 100;
		$this->enable_nav_item = 1;

		/* 1) Lecture de la table "wp_tb_outils" (config pour tous les projets) */
		$requete = "
			SELECT * 
			FROM {$wpdb->prefix}tb_outils
			WHERE id_outil='" . $this->slug . "'
		";
		$res1 = $wpdb->get_results($requete) ;

		// @TODO supprimer cette astuce de boucle alors qu'il n'est censé y avoir qu'un tuple ?
		foreach ($res1 as $meta) {	
			// @TODO gérer l'activation / désactivation générale
			$this->config = json_decode($meta->config, true);
		}

		/* 2) Lecture de la table "wp_tb_outils_reglages" (config pour le projet en cours) */
		$requete = "
			SELECT * 
			FROM {$wpdb->prefix}tb_outils_reglages
			WHERE id_projet='" . $id_projet . "'
			AND id_outil='" . $this->slug . "'
		";
		$res2 = $wpdb->get_results($requete) ;

		// @TODO supprimer cette astuce de boucle alors qu'il n'est censé y avoir qu'un tuple ?
		foreach ($res2 as $meta) {	
			$this->name = $meta->name;
			$this->prive = $meta->prive;
			$this->create_step_position = $meta->create_step_position;
			$this->nav_item_position = $meta->nav_item_position;
			$this->enable_nav_item = $meta->enable_nav_item;
			$this->config = array_merge($this->config, json_decode($meta->config, true)); // priorité à la config locale
		}
		
		// @TODO si aucune config locale n'a été trouvée, écrire les paramètres
		// par défaut dedans ? Serait plus simple pour la suite
	}

	/**
	 * Retourne true si le flux d'exécution est dans l'outil en cours - permet
	 * de n'effectuer certaines actions que pour un outil donné
	 */
	protected function outilCourant() {
		$slug = $this->slug;
		//echo "SLUG EN COURS: [$slug]<br/>";
		$ok = bp_is_current_action($slug);
		//echo "COURANT: "; var_dump($ok);
		return $ok;
	}

	/**
	 * Exécuté lors de l'installation du plugin TelaBotanica
	 */
	public function installation() {
		
	}

	/**
	 * Exécuté lors de la désinstallation du plugin TelaBotanica
	 */
	public function desinstallation() {
		
	}
}