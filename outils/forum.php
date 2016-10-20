<?php

class Forum extends TB_Outil {

	public function __construct()
	{
		// identifiant de l'outil et nom par défaut
		$this->slug = 'forum';
		$this->name = 'Forum';

		// init du parent
		$this->initialisation();
	}

	public function getConfigDefautOutil()
	{
		$cheminConfig = __DIR__ . "/forum_config-defaut.json";
		$configDefaut = json_decode(file_get_contents($cheminConfig), true);
		return $configDefaut;
	}

	/**
	 * Exécuté lors de l'installation du plugin TelaBotanica; ATTENTION, à ce
	 * moment elle est appelée en contexte non-objet
	 */
	public function installation()
	{
		$configDefaut = Forum::getConfigDefautOutil();
		// l'id outil "forum" n'est pas tiré de $this->slug car la méthode d'install
		// est appelée en contexte non-objet => mettre le slug dans un attribut statique ?
		add_option('tb_forum_config',json_encode($configDefaut));
	}

	/**
	 * Exécuté lors de la désinstallation du plugin TelaBotanica; ATTENTION, à
	 * ce moment elle est appelée en contexte non-objet
	 */
	public function desinstallation()
	{
		// l'id outil "forum" n'est pas tiré de $this->slug car la méthode d'install
		// est appelée en contexte non-objet => mettre le slug dans un attribut statique ?
		delete_option('tb_forum_config');
	}

	public function scriptsEtStylesAvant()
	{
		wp_enqueue_script('bootstrap-js', $this->urlOutil . 'bower_components/bootstrap/dist/js/bootstrap.min.js');
		// @WTF le style n'est pas écrasé par le BS du thème, malgré son ID
		// identique et sa priorité faible, c'est lui qui écrase l'autre :-/
		// @TODO trouver une solution, car si on utilise le plugin sans le thème,
		// y aura pas de BS et ça marchera pas :'(
		// wp_enqueue_style('bootstrap-css', $this->urlOutil . 'bower_components/bootstrap/dist/css/bootstrap.min.css');
	}

	public function scriptsEtStylesApres()
	{
		wp_enqueue_script('jquery-noconflict-compat', $this->urlOutil . 'js/jquery-noconflict-compat.js');
		wp_enqueue_script('moment', $this->urlOutil . 'bower_components/moment/min/moment.min.js');
		wp_enqueue_script('moment-fr', $this->urlOutil . 'bower_components/moment/locale/fr.js');
		wp_enqueue_script('mustache', $this->urlOutil . 'bower_components/mustache.js/mustache.min.js');
		wp_enqueue_script('binette', $this->urlOutil . 'bower_components/binette.js/binette.js');

		wp_enqueue_style('EzmlmForum-CSS', $this->urlOutil . 'css/ezmlm-forum-internal.css');

		// code de l'appli Forum
		wp_enqueue_script('AuthAdapter', $this->urlOutil . 'js/AuthAdapter.js');
		wp_enqueue_script('AuthAdapterTB', $this->urlOutil . 'js/auth/AuthAdapterTB.js');
		wp_enqueue_script('EzmlmForum', $this->urlOutil . 'js/EzmlmForum.js');
		wp_enqueue_script('ViewThread', $this->urlOutil . 'js/ViewThread.js');
		wp_enqueue_script('ViewList', $this->urlOutil . 'js/ViewList.js');
	}

	/*
	 * Vue onglet principal - affichage du forum dans la page
	 */
	function display($group_id = null)
	{
		$this->appliquerCaracterePrive();

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
			//$this->config['ezmlm-php']['list'] = "botadrome";
			$this->config['ezmlm-php']['list'] = bp_get_current_group_slug();
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
		$this->controleAccesReglages();
		?>
		<h4>Paramètres de l'outil <?php echo $this->name ?></h4>

		<p class="editfield">
			<label for="activation-outil">Activation de l'outil</label>
			<select name="activation-outil">
				<option value="true" <?php echo ($this->enable_nav_item ? 'selected' : '') ?>>Activé</option>
				<option value="false" <?php echo ($this->enable_nav_item ? '' : 'selected') ?>>Désactivé</option>
			</select>
		</p>

		<p class="editfield">
			<label for="nom-outil">Nom de l'outil</label>
			<input type="text" id="nom-outil" name="nom-outil" value="<?php echo $this->name ?>" />
		</p>

		<p class="editfield">
			<label for="liste-outil">Nom de la liste</label>
			<input type="text" disabled="disabled" id="liste-outil" placeholder="automatique (nom du projet)" value="<?php echo $this->config['ezmlm-php']['list'] ?>" />
			<span class="description">Vous ne pouvez pas modifier ce paramètre.</span>
		</p>

		<p class="editfield">
			<label for="confidentialite-outil">Visibilité</label>
			<select name="confidentialite-outil">
				<option value="false" <?php echo ($this->prive ? '' : 'selected') ?>>Public</option>
				<option value="true" <?php echo ($this->prive ? 'selected' : '') ?>>Privé</option>
			</select>
			<br/>
			<span class="description">Si "privé", seuls les membres pourront y accéder (ne s'applique qu'aux groupes publics)</span>
		</p>

		<!--<p class="editfield">
			<label for="position-outil">Position de l'outil <br/>(<?php echo $this->nav_item_position ?>)</label>
			<input type="range" min="0" max="100" step="5" id="position-outil" class="pointer" name="position-outil" value="<?php echo $this->nav_item_position ?>"/>
		</p>-->

		<?php
		wp_nonce_field( 'groups_edit_save_' . $this->slug );
		do_action( 'bp_after_group_settings_admin' );
	}

	/** traitement de la page de réglages */
	function edit_screen_save($group_id = null) {
		global $wpdb, $bp;
		$id_projet = bp_get_current_group_id();
		if ( !isset( $_POST ) )	return false; // gni?
		check_admin_referer( 'groups_edit_save_' . $this->slug );

		/* Mise à jour de la ligne dans la base de données */
		$table = "{$wpdb->prefix}tb_outils_reglages";
		//var_dump($_POST); exit;
		$data = array( 												
			'enable_nav_item' => ($_POST['activation-outil'] == 'true'),
			'name' => $_POST['nom-outil'],
			//'nav_item_position' => $_POST['position-outil'],
			'prive' => ($_POST['confidentialite-outil'] == 'true')
		);
		$where = array( 												
			'id_projet' => $id_projet,
			'id_outil' => $this->slug
		);
		$success = $wpdb->update($table, $data, $where);

		if ($success === false) {
			bp_core_add_message( __( 'There was an error saving, please try again', 'buddypress' ), 'error' );
		} else {
			bp_core_add_message( __( 'Settings saved successfully', 'buddypress' ) );
			bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . '/admin/' . $this->slug );
		}
	}
}

bp_register_group_extension( 'Forum' );
