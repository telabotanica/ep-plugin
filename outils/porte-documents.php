<?php

class Porte_Documents extends TB_Outil {

	public function __construct()
	{
		$this->slug = 'porte-documents';
		$this->name = 'Porte-documents';

		// init du parent
		$this->initialisation();
	}

	protected static function getConfigDefautOutil()
	{
		$cheminConfig = __DIR__ . "/porte-documents_config-defaut.json";
		$configDefaut = json_decode(file_get_contents($cheminConfig), true);
		return $configDefaut;
	}

	protected function preparer_config_locale($config=null)
	{
		$config_locale = $config;
		if ($config_locale === null) {
			$config_locale = $this->config;
		}

		unset($config_locale['active']);
		unset($config_locale['_comments']);
		unset($config_locale['abstractionPath']);
		unset($config_locale['filesServiceUrl']);
		unset($config_locale['userInfoByIdUrl']);
		unset($config_locale['authUrl']);

		return $config_locale;
	}

	/**
	 * Exécuté lors de l'installation du plugin TelaBotanica
	 */
	public function installation()
	{
		$configDefaut = Porte_Documents::getConfigDefautOutil();
		// l'id outil "porte-documents" n'est pas tiré de $this->slug car la méthode d'install
		// est appelée en contexte non-objet => mettre le slug dans un attribut statique ?
		add_option('tb_porte-documents_config', json_encode($configDefaut));
	}

	/**
	 * Exécuté lors de la désinstallation du plugin TelaBotanica; ATTENTION, à
	 * ce moment elle est appelée en contexte non-objet
	 */
	public function desinstallation()
	{
		// l'id outil "porte-documents" n'est pas tiré de $this->slug car la méthode d'install
		// est appelée en contexte non-objet => mettre le slug dans un attribut statique ?
		delete_option('tb_porte-documents_config');
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
		wp_enqueue_script('app', $this->urlOutil . 'app.js', array(), false, true);
		wp_enqueue_script('autofocus', $this->urlOutil . 'utils/autofocus.directive.js', array(), false, true);
		wp_enqueue_script('mimetype', $this->urlOutil . 'utils/mimetype-icon.directive.js', array(), false, true);
		wp_enqueue_script('click', $this->urlOutil . 'utils/select-on-click.directive.js', array(), false, true);
		wp_enqueue_script('config', $this->urlOutil . 'utils/main-config.js', array(), false, true);
		wp_enqueue_script('details-pane', $this->urlOutil . 'details-pane/details-pane.directive.js', array(), false, true);
		wp_enqueue_script('data-cell', $this->urlOutil . 'details-pane/data-cell.directive.js', array(), false, true);
		wp_enqueue_script('modal', $this->urlOutil . 'modal/modal.controller.js', array(), false, true);
		wp_enqueue_script('files', $this->urlOutil . 'files/files.controller.js', array(), false, true);
		wp_enqueue_script('files-service', $this->urlOutil . 'files/files.service.js', array(), false, true);
		wp_enqueue_script('add-files', $this->urlOutil . 'files/add-files.controller.js', array(), false, true);
		wp_enqueue_script('breadcrumbs', $this->urlOutil . 'breadcrumbs/breadcrumbs.directive.js', array(), false, true);
		wp_enqueue_script('breadcrumbs-service', $this->urlOutil . 'breadcrumbs/breadcrumbs.service.js', array(), false, true);
		wp_enqueue_script('files-search', $this->urlOutil . 'search/files-search.directive.js', array(), false, true);

		wp_enqueue_script('ng-file-upload-shim', $this->urlOutil . 'bower_components/ng-file-upload-shim/ng-file-upload-shim.js', array(), false, true);
		wp_enqueue_script('ng-file-upload', $this->urlOutil . 'bower_components/ng-file-upload/ng-file-upload.js', array(), false, true);
		wp_enqueue_script('ng-contextmenu', $this->urlOutil . 'bower_components/ng-contextmenu/dist/ng-contextmenu.js', array(), false, true);
		wp_enqueue_script('moment', $this->urlOutil . 'bower_components/moment/moment.js', array(), false, true);
		wp_enqueue_script('angular-moment', $this->urlOutil . 'bower_components/angular-moment/angular-moment.js', array(), false, true);
		wp_enqueue_script('angular-modal-service', $this->urlOutil . 'bower_components/angular-modal-service/dst/angular-modal-service.js', array(), false, true);
		wp_enqueue_script('angular-sanitize', $this->urlOutil . 'bower_components/angular-sanitize/angular-sanitize.js', array(), false, true);
		wp_enqueue_script('ngtoast', $this->urlOutil . 'bower_components/ngtoast/dist/ngToast.js', array(), false, true);
		wp_enqueue_script('angular-xeditable-js', $this->urlOutil . 'bower_components/angular-xeditable/dist/js/xeditable.min.js');
		wp_enqueue_script('clipboard', $this->urlOutil . 'bower_components/clipboard/dist/clipboard.min.js');
		wp_enqueue_script('ngclipboard', $this->urlOutil . 'bower_components/ngclipboard/dist/ngclipboard.min.js');

		wp_enqueue_style('bootstrap-css', $this->urlOutil . 'bootstrap-iso.css');
		wp_enqueue_style('ngtoast-css', $this->urlOutil . 'bower_components/ngtoast/dist/ngToast.min.css');
		wp_enqueue_style('angular-xeditable-css', $this->urlOutil . 'bower_components/angular-xeditable/dist/css/xeditable.min.css');
		wp_enqueue_style('app-css', $this->urlOutil . 'app.css');
	}

	/* Vue onglet admin */
	function edit_screen($group_id = null)
	{
		if (! $this->controleAccesReglages()) {
			return false;
		}
		?>
		<h2 class="bp-screen-reader-text">
			<?php echo __("Paramètres de l'outil", 'telabotanica') . ' ' . $this->name; ?> 
		</h2>

		<p class="editfield">
			<label for="activation-outil"><?php _e("Activation de l'outil", 'telabotanica') ?></label>
			<select name="activation-outil">
				<option value="true" <?php echo ($this->enable_nav_item ? 'selected' : '') ?>>
					<?php _e("Activé", 'telabotanica') ?>
				</option>
				<option value="false" <?php echo ($this->enable_nav_item ? '' : 'selected') ?>>
					<?php _e("Désactivé", 'telabotanica') ?>
				</option>
			</select>
		</p>

		<p class="editfield">
			<label for="nom-outil"><?php _e("Nom de l'outil", 'telabotanica') ?></label>
			<input type="text" id="nom-outil" name="nom-outil" value="<?php echo $this->name ?>" />
		</p>

		<p class="editfield">
			<label for="confidentialite-outil">
				<?php _e("Visibilité", 'telabotanica') ?>
			</label>
			<select name="confidentialite-outil">
				<option value="false" <?php echo ($this->prive ? '' : 'selected') ?>>
					<?php _e("Public", 'telabotanica') ?>
				</option>
				<option value="true" <?php echo ($this->prive ? 'selected' : '') ?>>
					<?php _e("Privé", 'telabotanica') ?>
				</option>
			</select>
			<br/>
			<span class="description">
				<?php _e("Si \"privé\", seuls les membres pourront y accéder (ne s'applique qu'aux projets publics)", 'telabotanica') ?>
			</span>
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
		$data = array(
			'enable_nav_item' => ($_POST['activation-outil'] == 'true'),
			'name' => $_POST['nom-outil'],
			//'nav_item_position' => $_POST['position-outil']
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

	/* Vue onglet principal */
	function display($group_id = null) {
		if (!$this->appliquerCaracterePrive()) {
			$id_projet = bp_get_current_group_id();

			$this->config['ressourcesPath'] = $this->getServerRoot() . $this->getDataBaseUri() . '/';
			$this->config['abstractionPath'] .= '/' . $id_projet;
			$this->config['group'] = 'projet:' . $id_projet;

			// amorcer l'outil
			chdir(dirname(__FILE__) . "/porte-documents");
			$code = file_get_contents('index_pouet.html');

			echo '<i id="cumulus-config-holder" data-config=\''. json_encode($this->config, JSON_HEX_APOS) .'\'></i>'; //caca
			echo '<div class="wp-bootstrap bootstrap-iso" ng-app="cumulus">';
			echo $code;
			echo '</div>';
		}
	}
}

bp_register_group_extension('Porte_Documents');
