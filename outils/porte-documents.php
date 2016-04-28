<?php

class Porte_Documents extends TB_Outil {

	function porte_documents() {
		// identifiant de l'outil et nom par défaut
		$this->slug = 'porte-documents';
		$this->name = 'Porte-documents';

		// init du parent
		$this->initialisation();
	}

	protected function getConfigDefautOutil()
	{
		return array(
			"title" => "", // laisser vide pour que WP/BP gèrent le titre
			"hrefBuildMode" => "REST",
			"cumulus-api" => array(
				"rootUri" => "http://localhost/cumulus",
				"list" => "" // si vide, cherchera une liste ayant le nom du projet
			)
		);
	}

	/**
	 * Exécuté lors de l'installation du plugin TelaBotanica
	 */
	public function installation() {
		global $wpdb;
		$configDefaut = Porte_Documents::getConfigDefautOutil();

		// l'id outil "forum" n'est pas tiré de $this->slug car la méthode d'install
		// est appelée en contexte non-objet => mettre le slug dans un attribut statique ?

		add_option('tb_porte-documents_config', json_encode($configDefaut));
	}

	/**
	 * Exécuté lors de la désinstallation du plugin TelaBotanica; ATTENTION, à
	 * ce moment elle est appelée en contexte non-objet
	 */
	public function desinstallation()
	{
		global $wpdb;

		// l'id outil "porte-documents" n'est pas tiré de $this->slug car la méthode d'install
		// est appelée en contexte non-objet => mettre le slug dans un attribut statique ?
		$delete_config_defaut = "
			DELETE FROM `{$wpdb->prefix}tb_outils` WHERE `id_outil` = 'porte-documents';
		";
		// $wpdb->query($delete_config_defaut); // @DEBUG désactivé
	}

	public function scriptsEtStylesAvant() {
		wp_enqueue_script('jquery', $this->urlOutil . 'bower_components/jquery/dist/jquery.js');
		wp_enqueue_script('bootstrap-js', $this->urlOutil . 'bower_components/bootstrap/dist/js/bootstrap.js');
		wp_enqueue_script('angular', $this->urlOutil . 'bower_components/angular/angular.js');
		// @WTF le style n'est pas écrasé par le BS du thème, malgré son ID
		// identique et sa priorité faible, c'est lui qui écrase l'autre :-/
		// @TODO trouver une solution, car si on utilise le plugin sans le thème,
		// y aura pas de BS et ça marchera pas :'(
		//wp_enqueue_style('bootstrap-css', $this->urlOutil . 'bower_components/bootstrap/dist/css/bootstrap.min.css');
	}

	public function scriptsEtStylesApres() {
		wp_enqueue_script('app', $this->urlOutil . 'app.js');
		wp_enqueue_script('autofocus', $this->urlOutil . 'utils/autofocus.directive.js');
		wp_enqueue_script('mimetype', $this->urlOutil . 'utils/mimetype-icon.directive.js');
		wp_enqueue_script('click', $this->urlOutil . 'utils/select-on-click.directive.js');
		wp_enqueue_script('config', $this->urlOutil . 'utils/main-config.js');
		wp_enqueue_script('details-pane', $this->urlOutil . 'details-pane/details-pane.directive.js');
		wp_enqueue_script('data-cell', $this->urlOutil . 'details-pane/data-cell.directive.js');
		wp_enqueue_script('modal', $this->urlOutil . 'modal/modal.controller.js');
		wp_enqueue_script('files', $this->urlOutil . 'files/files.controller.js');
		wp_enqueue_script('files-service', $this->urlOutil . 'files/files.service.js');
		wp_enqueue_script('add-files', $this->urlOutil . 'files/add-files.controller.js');
		wp_enqueue_script('breadcrumbs', $this->urlOutil . 'breadcrumbs/breadcrumbs.directive.js');
		wp_enqueue_script('breadcrumbs-service', $this->urlOutil . 'breadcrumbs/breadcrumbs.service.js');
		wp_enqueue_script('files-search', $this->urlOutil . 'search/files-search.directive.js');

		wp_enqueue_script('ng-file-upload-shim', $this->urlOutil . 'bower_components/ng-file-upload-shim/ng-file-upload-shim.js');
		wp_enqueue_script('ng-file-upload', $this->urlOutil . 'bower_components/ng-file-upload/ng-file-upload.js');
		wp_enqueue_script('ng-contextmenu', $this->urlOutil . 'bower_components/ng-contextmenu/dist/ng-contextmenu.js');
		wp_enqueue_script('moment', $this->urlOutil . 'bower_components/moment/moment.js');
		wp_enqueue_script('angular-moment', $this->urlOutil . 'bower_components/angular-moment/angular-moment.js');
		wp_enqueue_script('angular-modal-service', $this->urlOutil . 'bower_components/angular-modal-service/dst/angular-modal-service.js');
		wp_enqueue_script('angular-sanitize', $this->urlOutil . 'bower_components/angular-sanitize/angular-sanitize.js');
		wp_enqueue_script('ngtoast', $this->urlOutil . 'bower_components/ngtoast/dist/ngToast.js');


		wp_enqueue_style('html5-boilerplate-normalize', $this->urlOutil . 'bower_components/html5-boilerplate/dist/css/normalize.css');
		wp_enqueue_style('html5-boilerplate', $this->urlOutil . 'bower_components/html5-boilerplate/dist/css/main.css');
		wp_enqueue_style('bootstrap-css', $this->urlOutil . 'bower_components/bootstrap/dist/css/bootstrap.css');
		wp_enqueue_style('ngtoast-css', $this->urlOutil . 'bower_components/ngtoast/dist/ngToast.min.css');
		wp_enqueue_style('app-css', $this->urlOutil . 'app.css');
	}

	/* Vue onglet admin */
	function edit_screen() {
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

		<!-- Marche pas
		<p class="editfield">
			<label for="confidentialite-outil">Outil privé <br/>(<?php echo $this->prive ?>)</label>
			<input type="range" min="0" max="1" id="confidentialite-outil" class="pointer on-off" name="confidentialite-outil" value="<?php echo $this->prive ?>"/>
		</p>
		-->

		<?php

		wp_nonce_field( 'groups_edit_save_' . $this->slug );
		do_action( 'bp_after_group_settings_admin' );
	}

	function edit_screen_save() {
		global $wpdb, $bp;
		$id_projet = bp_get_current_group_id();
		if ( !isset( $_POST ) )	return false;
		check_admin_referer( 'groups_edit_save_' . $this->slug );

		/* Mise à jour de la ligne dans la base de données */
		$table = "{$wpdb->prefix}tb_outils_reglages";
		$data = array(
			'enable_nav_item' => $_POST['activation-outil'],
			'name' => $_POST['nom-outil'],
			'nav_item_position' => $_POST['position-outil']
			//'prive' => $_POST['confidentialite-outil']
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


	/* Vue onglet principal */
	function display() {
		$id_projet = bp_get_current_group_id();

		if ($this->prive) {
			// on ne devrait passer là que si les contrôles de sécurités précédents
			// ont réussi, càd si on est dans un groupe auquel on a droit (soit
			// le groupe est public, soit l'utilisateur en est membre)
			echo "<h4>L'outil <?php echo $this->name ?> est réservé aux membres du groupe</h4>";
			return;
		}

		$this->config['path'] = 'http://betatela.dev/wordpress/wp-content/plugins/ep-plugin/outils/porte-documents/';
		$this->config['path2'] = '_projets/' . $id_projet;
		// var_dump($this->config);

		// paramètres automatiques :
		// - nom de la liste
		// - domaine racine
		// - URI de base
		// - titre de la page

		// amorcer l'outil
		chdir(dirname(__FILE__) . "/porte-documents");
		$code = file_get_contents('index_pouet.html');

		echo '<div class="wp-bootstrap" ng-app="cumulus">';
		echo "<script>var tarace = JSON.parse('" . json_encode($this->config) . "');</script>";
		echo $code;
		echo '</div>';
	}
}

bp_register_group_extension( 'Porte_Documents' );

?>
