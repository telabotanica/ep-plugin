<?php

/**
 * Classe intermédiaire entre  BP_Group_Extension et chaque classe outil
 */
class TB_Outil extends BP_Group_Extension {

	/** configuration de l'outil pour l'instance (projet) en cours */
	protected $config;

	protected $urlPlugin;
	protected $urlOutil;

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