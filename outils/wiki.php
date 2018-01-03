<?php

/**
 * Pseudo-outil : intégration d'un wiki existant (Wikini) dans un <iframe>
 */
class Wiki extends TB_Outil {

	public function __construct()
	{
		// identifiant de l'outil et nom par défaut
		$this->slug = 'wiki';
		$this->name = 'Wiki';

		// init du parent
		$this->initialisation();
	}

	public static function getConfigDefautOutil()
	{
		$cheminConfig = __DIR__ . "/wiki_config-defaut.json";
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
		unset($config_locale['_comments']);
		unset($config_locale['rootUrl']);
		unset($config_locale['wikiName']);

		return $config_locale;
	}

	/**
	 * Exécuté lors de l'installation du plugin TelaBotanica; ATTENTION, à ce
	 * moment elle est appelée en contexte non-objet
	 */
	public function installation()
	{
		$configDefaut = Wiki::getConfigDefautOutil();
		// l'id outil "wiki" n'est pas tiré de $this->slug car la méthode d'install
		// est appelée en contexte non-objet => mettre le slug dans un attribut statique ?
		add_option('tb_wiki_config',json_encode($configDefaut));
	}

	/**
	 * Exécuté lors de la désinstallation du plugin TelaBotanica; ATTENTION, à
	 * ce moment elle est appelée en contexte non-objet
	 */
	public function desinstallation()
	{
		// l'id outil "wiki" n'est pas tiré de $this->slug car la méthode d'install
		// est appelée en contexte non-objet => mettre le slug dans un attribut statique ?
		delete_option('tb_wiki_config');
	}

	public function scriptsEtStylesAvant()
	{
		// wp_enqueue_script('bootstrap-js', $this->urlOutil . 'bower_components/bootstrap/dist/js/bootstrap.min.js');
		// @WTF le style n'est pas écrasé par le BS du thème, malgré son ID
		// identique et sa priorité faible, c'est lui qui écrase l'autre :-/
		// @TODO trouver une solution, car si on utilise le plugin sans le thème,
		// y aura pas de BS et ça marchera pas :'(
		// wp_enqueue_style('bootstrap-css', $this->urlOutil . 'bower_components/bootstrap/dist/css/bootstrap.min.css');
	}


	public function scriptsEtStylesApres()
	{
		//wp_enqueue_style('EzmlmForum-CSS', $this->urlOutil . 'css/ezmlm-forum-internal.css');
	}

	/*
	 * Vue onglet principal - affichage du wiki dans la page
	 */
	function display($group_id = null)
	{
		$urlRacine = $this->config['rootUrl'];
		$nomWiki = $this->config['wikiName'];

		if (! empty($nomWiki) && ! empty($urlRacine)) {
			$adresseWiki = $urlRacine . '/' . $nomWiki . '/wakka.php?wiki=PagePrincipale';
			;
			?>
			<div style="text-align:center">
				<a class="button" href="<?php echo $adresseWiki ?>" target="_blank">
					<span class="button-text"><?php _e('accueil du wiki', 'telabotanica') ?></span>
				</a>
			</div>
			<?php
		} else {
			?>
			<p>
				<?php _e("Aucun wiki défini ou URL racine manquante; vérifiez la configuration", 'telabotanica') ?>
			</p>
			<?php
		}
	}

	/* Vue onglet admin */
	function edit_screen($group_id = null) {
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
			<label for="liste-outil">
				<?php _e("Nom du wiki", 'telabotanica') ?>
			</label>
			<input type="text" <?php echo is_super_admin() ? '' : 'disabled="disabled"' ?> id="nom-wiki" name="wikiName"
				   placeholder="<?php _e("automatique", 'telabotanica') ?> (<?php _e("nom du projet", 'telabotanica') ?>)"
				   value="<?php echo $this->config['wikiName'] ?>" />
			<?php if (! is_super_admin()) { ?>
				<span class="description">
					<?php _e("Vous ne pouvez pas modifier ce paramètre", 'telabotanica') ?>
				</span>
			<?php } ?>
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

		// mise à jour de la config
		$configModifiee = Wiki::getConfigDefautOutil();
		$configModifiee = $this->preparer_config_locale($configModifiee);
		if (is_super_admin()) {
			$configModifiee['wikiName'] = $_POST['wikiName'];
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

bp_register_group_extension( 'Wiki' );
