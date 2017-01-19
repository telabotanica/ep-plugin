<?php

/**
 * Pseudo-outil : intégration d'une suite d'outils flora-data existants dans
 * des <iframe>
 */
class Flora_Data extends TB_Outil {

	public function __construct()
	{
		// identifiant de l'outil et nom par défaut
		$this->slug = 'flora-data';
		$this->name = 'FloraData';
		//$this->template_file = 'groups/single/flora-data';

		// init du parent
		$this->initialisation();

		/*bp_core_new_subnav_item( array( 
			'name' => 'My Wall', 
			'slug' => 'wall', 
			'parent_url' => 'http://go-david.com/fsc/members/gabriel/activity/',
			'parent_slug' => 'flora-data',
			'screen_function' => 'my_profile_page_function_to_show_screen',
			'position' => 11,
			'item_css_id' => 'home'
		));*/
	}

	public static function getConfigDefautOutil()
	{
		$cheminConfig = __DIR__ . "/flora-data_config-defaut.json";
		$configDefaut = json_decode(file_get_contents($cheminConfig), true);
		return $configDefaut;
	}

	/**
	 * Prend en entrée un tableau de config (si $config est null, prendra
	 * $this->config) et retire tous les paramètres qui ne se définissent pas
	 * au niveau local (projet en cours) mais au niveau général (TdB WP)
	 */
	protected function preparer_config_locale($config=null)
	{
		$config_locale = $config;
		if ($config_locale === null) {
			$config_locale = $this->config;
		}

		unset($config_locale['active']);
		unset($config_locale['rootUrl']);

		return $config_locale;
	}

	/**
	 * Exécuté lors de l'installation du plugin TelaBotanica; ATTENTION, à ce
	 * moment elle est appelée en contexte non-objet
	 */
	public function installation()
	{
		$configDefaut = Flora_Data::getConfigDefautOutil();
		// l'id outil "flora-data" n'est pas tiré de $this->slug car la méthode d'install
		// est appelée en contexte non-objet => mettre le slug dans un attribut statique ?
		add_option('tb_flora-data_config',json_encode($configDefaut));
	}

	/**
	 * Exécuté lors de la désinstallation du plugin TelaBotanica; ATTENTION, à
	 * ce moment elle est appelée en contexte non-objet
	 */
	public function desinstallation()
	{
		// l'id outil "flora-data" n'est pas tiré de $this->slug car la méthode d'install
		// est appelée en contexte non-objet => mettre le slug dans un attribut statique ?
		delete_option('tb_flora-data_config');
	}

	public function scriptsEtStylesAvant()
	{
		// astuce crado pour éviter d'utiliser un fichier CSS @TODO faire mieux
		function styleEnLigneEPFloraData() {
			echo '
			<style type="text/css">
				iframe {
					border: none;
					margin-bottom: 50px;
				}
				#flora-data-cartoPoint {
					height: 600px;
					width: 100%;
				}
				#flora-data-saisie {
					height: 1600px;
					width: 100%;
				}
				#flora-data-export {
					height: 600px;
					width: auto;
				}
				#flora-data-photo {
					height: 600px;
					width: 100%;
				}
				#flora-data-observation {
					height: 400px;
					width: 100%;
				}
			</style>
			';
		}
		add_action( 'wp_print_styles', 'styleEnLigneEPFloraData' );
	}


	public function scriptsEtStylesApres()
	{
	}

	/*
	 * Vue onglet principal - affichage du flora-data dans la page
	 */
	function display($group_id = null)
	{
		$urlRacine = $this->config['rootUrl'];
		$modules = $this->config['modules'];
		$projet = $this->config['projet'];

		// @TODO mettre dans une config qqpart
		$titres = array(
			"cartoPoint" => "Carte des observations",
			"photo" => "Galerie photo",
			"observation" => "Flux des dernières observations",
			"saisie" => "Saisie de nouvelles observations",
			"export" => "Export des observations"
		);

		if (! empty($urlRacine) && !empty($projet)) {
			?>
			<div id="ep-flora-data-tabs">
			<?php
			foreach($modules as $nomModule => $actif) {
				if (! $actif) continue;
				// URL iframe
				$adresseWidget = $urlRacine . ':' . $nomModule . '?projet=' . $projet;
				?>
				<div class="ep-flora-data-tab" id="ep-flora-data-tab-<?php echo $nomModule ?>">
					<div class="component component-title level-2">
						<h1 id="recherche-collections"><?php echo $titres[$nomModule] ?></h1>
					</div>

					<iframe id="ep-flora-data-iframe-<?php echo $nomModule ?>" src="<?php echo $adresseWidget ?>">
					</iframe>
				</div>
				<?php
			}
			echo '</div>';
		} else {
			?>
			<p class="notice notice-warning">
				<?php echo __("Mot-clé du projet vide ou URL racine manquante; vérifiez la configuration", "telabotanica") ?>
			</p>
			<?php
		}
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
			<label for="mot-cle-projet">Mot-clé du projet flora-data</label>
			<input type="text" name="mot-cle-projet" value="<?php echo $this->config['projet'] ?>" />
		</p>

		<p class="editfield">
			<label>Modules</label>
			<label for="module-cartoPoint">
				<input id="module-cartoPoint" type="checkbox" name="module-cartoPoint" <?php echo $this->config['modules']['cartoPoint'] ? 'checked' : '' ?> >
				Carte des observations
			</label>
			<label for="module-photo">
				<input id="module-photo" type="checkbox" name="module-photo" <?php echo $this->config['modules']['photo'] ? 'checked' : '' ?> >
				Galerie photo
			</label>
			<label for="module-observation">
				<input id="module-observation" type="checkbox" name="module-observation" <?php echo $this->config['modules']['observation'] ? 'checked' : '' ?> >
				Flux des dernières observations
			</label>
			<label for="module-export">
				<input id="module-export" type="checkbox" name="module-export" <?php echo $this->config['modules']['export'] ? 'checked' : '' ?> >
				Export des observations
			</label>
			<label for="module-saisie">
				<input id="module-saisie" type="checkbox" name="module-saisie" <?php echo $this->config['modules']['saisie'] ? 'checked' : '' ?> >
				Saisie d'observations
			</label>
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

		// mise à jour de la config
		$configModifiee = Flora_Data::getConfigDefautOutil();
		$configModifiee = $this->preparer_config_locale($configModifiee);
		$configModifiee['projet'] = $_POST['mot-cle-projet'];

		foreach ($configModifiee['modules'] as $nomModule => &$active) {
			$cle = 'module-' . $nomModule;
			$active = false;
			if (isset($_POST[$cle])) {
				$active = ($_POST[$cle] == 'on');
			}
		}
		/* Mise à jour de la ligne dans la base de données */
		$table = "{$wpdb->prefix}tb_outils_reglages";
		//var_dump($_POST); exit;
		$data = array( 												
			'enable_nav_item' => ($_POST['activation-outil'] == 'true'),
			'name' => $_POST['nom-outil'],
			//'nav_item_position' => $_POST['position-outil'],
			'prive' => ($_POST['confidentialite-outil'] == 'true'),
			'config' => json_encode($configModifiee)
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

bp_register_group_extension( 'Flora_Data' );

function bp_tb_outil_floradata_menu_tabs() {
	?>
		<li>merde</li>
		<li>merde 2</li>
		<li>merde 3</li>
	<?php
}
