<?php

class Forum extends TB_Outil {

	function forum()
	{
		// identifiant de l'outil et nom par défaut
		$this->slug = 'forum';
		$this->name = 'Forum';

		// init du parent
		$this->initialisation();
	}

	// @TODO maintenir en cohésion avec le fichier config.defaut.json d'ezmlm-php
	protected function getConfigDefautOutil()
	{
		$configDefaut = array(
			//"domainRoot" => "http://localhost", // ne pas mentionner, autodétecté
			//"baseUri" => "/ezmlm-forum", // ne pas mentionner, autodétecté
			"title" => "", // laisser vide pour que WP/BP gèrent le titre
			"hrefBuildMode" => "REST",
			"defaultPage" => "view-list",
			"ezmlm-php" => array(
				"rootUri" => "http://localhost/ezmlm-php",
				"list" => "" // si vide, cherchera une liste ayant le nom du projet
			)
		);
		return $configDefaut;
	}

	/**
	 * Exécuté lors de l'installation du plugin TelaBotanica; ATTENTION, à ce
	 * moment elle est appelée en contexte non-objet
	 */
	public function installation()
	{
		global $wpdb;
		$configDefaut = Forum::getConfigDefautOutil();

		// l'id outil "forum" n'est pas tiré de $this->slug car la méthode d'install
		// est appelée en contexte non-objet => mettre le slug dans un attribut statique ?
		$insert_config_defaut = "
			INSERT INTO `{$wpdb->prefix}tb_outils` VALUES (
				'forum', 1, '" . json_encode($configDefaut) . "'
			);
		";
		$wpdb->query($insert_config_defaut);
	}

	/**
	 * Exécuté lors de la désinstallation du plugin TelaBotanica; ATTENTION, à
	 * ce moment elle est appelée en contexte non-objet
	 */
	public function desinstallation()
	{
		global $wpdb;

		// l'id outil "forum" n'est pas tiré de $this->slug car la méthode d'install
		// est appelée en contexte non-objet => mettre le slug dans un attribut statique ?
		$delete_config_defaut = "
			DELETE FROM `{$wpdb->prefix}tb_outils` WHERE `id_outil` = 'forum';
		";
		// $wpdb->query($delete_config_defaut); // @DEBUG désactivé
	}

	public function scriptsEtStylesAvant() {
		wp_enqueue_script('jquery', $this->urlOutil . 'bower_components/jquery/dist/jquery.min.js');
		wp_enqueue_script('bootstrap-js', $this->urlOutil . 'bower_components/bootstrap/dist/js/bootstrap.min.js');
		// @WTF le style n'est pas écrasé par le BS du thème, malgré son ID
		// identique et sa priorité faible, c'est lui qui écrase l'autre :-/
		// @TODO trouver une solution, car si on utilise le plugin sans le thème,
		// y aura pas de BS et ça marchera pas :'(
		//wp_enqueue_style('bootstrap-css', $this->urlOutil . 'bower_components/bootstrap/dist/css/bootstrap.min.css');
	}

	public function scriptsEtStylesApres() {
		wp_enqueue_script('moment', $this->urlOutil . 'bower_components/moment/min/moment.min.js');
		wp_enqueue_script('moment-fr', $this->urlOutil . 'bower_components/moment/locale/fr.js');
		wp_enqueue_script('mustache', $this->urlOutil . 'bower_components/mustache.js/mustache.min.js');
		wp_enqueue_script('binette', $this->urlOutil . 'bower_components/binette.js/binette.js');

		wp_enqueue_style('EzmlmForum-CSS', $this->urlOutil . 'css/ezmlm-forum-internal.css');

		// code de l'appli Forum
		wp_enqueue_script('EzmlmForum', $this->urlOutil . 'js/EzmlmForum.js');
		wp_enqueue_script('ViewThread', $this->urlOutil . 'js/ViewThread.js');
		wp_enqueue_script('ViewList', $this->urlOutil . 'js/ViewList.js');
	}

	/* Vue onglet principal */
	function display($group_id = null) {
		if ($this->prive) {
			// on ne devrait passer là que si les contrôles de sécurités précédents
			// ont réussi, càd si on est dans un groupe auquel on a droit (soit
			// le groupe est public, soit l'utilisateur en est membre)
			echo "<h4>L'outil <?php echo $this->name ?> est réservé aux membres du groupe</h4>";
			return;
		}

		// paramètres automatiques :
		// - domaine racine
		$this->config['domainRoot'] = $this->getServerRoot();
		// - URI de base
		$this->config['baseUri'] = $this->getBaseUri();
		// - URI de base pour les données (/wp-content/*)
		$this->config['dataBaseUri'] = $this->getDataBaseUri();
		//echo "DBU: [" . $this->config['dataBaseUri'] . "]";
		// - nom de la liste
		if (empty($this->config['ezmlm-php']['list'])) {
			$this->config['ezmlm-php']['list'] = "botadrome";
			//$this->config['ezmlm-php']['list'] = bp_get_current_group_slug();
		}

		//var_dump($this->config);

		// portée des styles
		echo '<div class="wp-bootstrap">';
		echo '<div id="ezmlm-forum-main">';

		// amorcer l'outil
		chdir(dirname(__FILE__) . "/forum/");
		require "ezmlm-forum.php";
		$fc = new EzmlmForum($this->config); // front controller

		// - définir le titre

		// - inclure le corps de page
		$fc->renderPage();
		echo "</div>";
		echo "</div>";
	}

	/* Vue onglet admin */
	function edit_screen($group_id = null) {
		if ( !bp_is_group_admin_screen( $this->slug ) )
		return false;
		
		?>
		<h4>Paramètres de l'outil <?php echo $this->name ?></h4>
		
		<p class="editfield">
			<?php
				if ( $this->enable_nav_item ) { $activation = "actif"; }
				else { $activation = "inactif"; }
			?>
			<label for="activation-outil">Activation de l'outil <br/>(<?php echo $activation ?>)</label>
			<input type="range" min="0" max="1" id="activation-outil" class="pointer on-off" name="activation-outil" value="<?php echo $this->enable_nav_item ?>"/>
		</p>
		
		<p class="editfield">
			<label for="nom-outil">Nom de l'outil</label>
			<input type="text" id="nom-outil" name="nom-outil" value="<?php echo $this->name ?>" />
		</p>
		
		<p class="editfield">
			<label for="position-outil">Position de l'outil <br/>(<?php echo $this->nav_item_position ?>)</label>
			<input type="range" min="0" max="100" step="5" id="position-outil" class="pointer" name="position-outil" value="<?php echo $this->nav_item_position ?>"/>
		</p>
		
		<p class="editfield">
			<label for="confidentialite-outil">Outil privé <br/>(<?php echo $this->prive ?>)</label>
			<input type="range" min="0" max="1" id="confidentialite-outil" class="pointer on-off" name="confidentialite-outil" value="<?php echo $this->prive ?>"/>
		</p>
		
		<?php
		
		wp_nonce_field( 'groups_edit_save_' . $this->slug );
		do_action( 'bp_after_group_settings_admin' );
	}

	function edit_screen_save($group_id = null) {
		global $wpdb, $bp;
		$id_projet = bp_get_current_group_id();
		if ( !isset( $_POST ) )	return false;
		check_admin_referer( 'groups_edit_save_' . $this->slug );
		
		/* Mise à jour de la ligne dans la base de données */
		$table = "{$wpdb->prefix}tb_outils_reglages";
		$data = array( 												
			'enable_nav_item' => $_POST['activation-outil'],
			'name' => $_POST['nom-outil'],
			'nav_item_position' => $_POST['position-outil'],
			'prive' => $_POST['confidentialite-outil'],
		);
		$where = array( 												
			'id_projet' => $id_projet,
			'id_outil' => $this->slug
		);
		$format = null;
		$where_format = null;
		$wpdb->update($table, $data, $where, $format, $where_format);
		
		$success = 1;
		if ( !$success )
		bp_core_add_message( __( 'There was an error saving, please try again', 'buddypress' ), 'error' );
		else
		bp_core_add_message( __( 'Settings saved successfully', 'buddypress' ) );
		bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . '/admin/' . $this->slug );
	}
}

bp_register_group_extension( 'Forum' );
