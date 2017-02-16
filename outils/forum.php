<?php

require_once dirname(__FILE__) . '/../lib/liste.php';

class Forum extends TB_Outil {

	/** nom de la liste sur laquelle est branchée le forum */
	protected $nomListe;

	/** adresse email de l'utilisateur en cours */
	protected $emailUtilisateur = null;

	/** true si l'utilisateur en cours est abonné à la liste en cours, false sinon */
	protected $statutAbonnement = false;

	/** instance du gestionnaire de liste, pour la liste en cours */
	protected $liste;

	public function __construct()
	{
		// identifiant de l'outil et nom par défaut
		$this->slug = 'forum';
		$this->name = 'Forum';	

		// init du parent
		$this->initialisation();

		// nom de la liste, répercuté dans la config de l'outil
		if (empty($this->config['ezmlm-php']['list'])) {
			$this->config['ezmlm-php']['list'] = bp_get_current_group_slug();
		}
		$this->nomListe = $this->config['ezmlm-php']['list'];

		// lib de gestion de liste
		$this->liste = new TB_ListeEzmlm($this->nomListe);

		// lorsqu'un membre quitte le groupe, on le désabonne du forum
		add_action('groups_leave_group', array($this, 'desabonnerUtilisateurQuittantLeGroupe'), 10, 2);

		// lorsque le groupe est supprimé, on désabonne du forum tous ses mebres
		add_action('groups_before_group_deleted', array($this, 'desabonnerTousLesMembres'), 10, 1);
	}

	public static function getConfigDefautOutil()
	{
		$cheminConfig = __DIR__ . "/forum_config-defaut.json";
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
		unset($config_locale['domainRoot']); // inutile, toujours redéfini
		unset($config_locale['displayListTitle']);
		unset($config_locale['ezmlm-php']['rootUri']);
		unset($config_locale['adapters']['AuthAdapterTB']['annuaireURL']);
		unset($config_locale['adapters']['AuthAdapterTB']['headerName']);
		unset($config_locale['avatarService']);

		return $config_locale;
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
		wp_enqueue_script('bootstrap-js', $this->urlOutil . 'bower_components/bootstrap/dist/js/bootstrap.min.js', array('jquery'));
		// @WTF le style n'est pas écrasé par le BS du thème, malgré son ID
		// identique et sa priorité faible, c'est lui qui écrase l'autre :-/
		// @TODO trouver une solution, car si on utilise le plugin sans le thème,
		// y aura pas de BS et ça marchera pas :'(
		wp_enqueue_style('bootstrap-css', $this->urlOutil . 'bower_components/bootstrap/dist/css/bootstrap.min.css');
	}

	public function scriptsEtStylesApres()
	{
		//wp_enqueue_script('jquery-noconflict-compat', $this->urlOutil . 'js/jquery-noconflict-compat.js');
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

	/**
	 * Détecte si une commande d'abonnement / désabonnement a été envoyée par le
	 * petit formulaire en haut de l'onglet, et la traite à l'aide du service
	 * ezmlm
	 */
	protected function traiterCommandeAbonnement()
	{
		if (isset($_REQUEST['tb-forum-action-inscription'])) {
			$commandeAbonnement = $_REQUEST['tb-forum-action-inscription'];
			if ($commandeAbonnement === '0') {
				$this->modifierAbonnement(false);
			} elseif ($commandeAbonnement === '1') {
				$this->modifierAbonnement(true);
			} // else moi pas comprendre
		}
	}

	/**
	 * Détecte si une commande de création de liste a été envoyée par le petit
	 * formulaire en haut de l'onglet, et la traite à l'aide du service ezmlm
	 */
	protected function traiterCommandeCreationListe()
	{
		if (isset($_REQUEST['tb-forum-action-creation-liste'])) {
			$commandeCreationListe = $_REQUEST['tb-forum-action-creation-liste'];
			if ($commandeCreationListe === '1') {
				$this->creerListe();
			}
		}
	}

	/**
	 * Lit l'adresse email de l'utilisateur en cours et le statut abonné ou non
	 * à la liste en cours; place tout cela dans les attributs de
	 * la classe - n'est pas appelé dans le constructeur, car celui-ci est
	 * exécuté même si l'onglet forum n'est pas actif; n'ayant pas besoin de ces
	 * infos dans les autres onglets, on évite des appels au service ezmlm pour
	 * rien
	 */
	protected function lireStatutUtilisateurEtListe()
	{
		// adresse email de l'utilisateur en cours
		$wpUser = new WP_User($this->userId);
		if ($wpUser) {
			$this->emailUtilisateur = $wpUser->user_email;
		}

		// l'utilisateur en cours est-il inscrit au forum ?
		$this->statutAbonnement();
	}

	/**
	 * Interroge le service ezmlm pour savoir si la liste en cours existe
	 */
	protected function existenceListe()
	{
		return $this->liste->existe();
	}

	/**
	 * Demande au service ezmlm de créer la liste configurée; pour les
	 * administrateurs seulement
	 */
	protected function creerListe()
	{
		if (! is_super_admin()) {
			return false;
		}
		if (! $this->nomListe) {
			return false;
		}
		return $this->liste->creer();
	}

	/**
	 * Retourne true si l'utilisateur en cours est abonné à la liste en cours,
	 * false sinon (ou si aucun utilisateur n'est identifié)
	 */
	protected function statutAbonnement()
	{
		if (! $this->emailUtilisateur) {
			return false;
		}
		$this->statutAbonnement = $this->liste->statutAbonnement($this->emailUtilisateur);
		return $this->statutAbonnement;
	}

	/**
	 * Si $nouvelEtatAbonnement est true, inscrit l'utilisateur en cours à la
	 * liste en cours en utilisant le service ezmlm; si $nouvelEtatAbonnement
	 * est false, le désinscrit
	 */
	protected function modifierAbonnement($nouvelEtatAbonnement, $emailUtilisateur=false) {
		if (! $emailUtilisateur) {
			if ($this->emailUtilisateur) {
				$emailUtilisateur = $this->emailUtilisateur;
			} else {
				return false;
			}
		}
		$ok = $this->liste->modifierStatutAbonnement($nouvelEtatAbonnement, $emailUtilisateur);
		if ($ok) {
			$this->statutAbonnement = $nouvelEtatAbonnement;
		}
		return $this->statutAbonnement;
	}

	// appelé lorsqu'un membre quitte le groupe
	public function desabonnerUtilisateurQuittantLeGroupe($group_id, $user_id)
	{
		$utilisateur = new WP_User($user_id);
		if ($utilisateur) {
			$this->modifierAbonnement(false, $utilisateur->user_email);
		}
	}

	// appelé lors de la suppression du groupe
	public function desabonnerTousLesMembres($group_id)
	{
		$infosMembres = groups_get_group_members(array(
			'group_id' => $group_id
		));
		if (! empty($infosMembres['members'])) {
			$membres = $infosMembres['members'];
			foreach ($membres as $utilisateur) {
				if ($utilisateur) {
					$this->modifierAbonnement(false, $utilisateur->user_email);
				}
			}
		}
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

		// lire le statut de l'utilisateur et de la liste
		$this->lireStatutUtilisateurEtListe();
		// traitement de la commande d'abonnement ou désabonnement
		// potentiellement envoyée par le formulaire ci-dessous
		$this->traiterCommandeAbonnement();
		?>

		<?php if ($this->userId != 0): ?>
		<div id="tb-forum-commande-inscription" class="tab-project-meta">
			<!-- état de l'inscription -->
			<div id="tb-forum-etat-inscription" class="tab-meta-info">
				<?php echo $this->statutAbonnement ? __("Vous êtes abonné", 'telabotanica') : __("Vous n'êtes pas abonné", 'telabotanica') ?>
			</div>
			<!-- mini-formulaire d'inscription / désinscription -->
			<div class="tab-meta-info">
				<form id="tb-forum-inscription" action="" method="GET">
					<input type="hidden" name="tb-forum-action-inscription" value="<?php echo $this->statutAbonnement ? '0' : '1' ?>">
					<input class="button <?php echo $this->statutAbonnement ? 'abonne' : 'non-abonne' ?>" type="submit"
						value="<?php echo $this->statutAbonnement ? __("Se désabonner", 'telabotanica') : __("S'abonner", 'telabotanica') ?>"
						title="<?php _e("Confirmer " . ($this->statutAbonnement ? "le dés" : "l'") . "abonnement ?", 'telabotanica') ?>">
				</form>
			</div>
		</div>
		<?php endif; ?>

		<!-- portée des styles -->
		<div class="wp-bootstrap">
		<div id="ezmlm-forum-main">

		<!-- réutilisation propre du jQuery de Wordpress -->
		<script type="text/javascript">$jq = jQuery.noConflict();</script>

		<?php
		// amorcer l'outil
		chdir(dirname(__FILE__) . "/forum/");
		require "ezmlm-forum.php";
		$fc = new EzmlmForum($this->config); // front controller

		// - définir le titre

		// - inclure le corps de page
		$fc->renderPage();
		?>
		</div>
		</div>
		<?php
	}

	/* Vue onglet admin */
	function edit_screen($group_id = null) {
		$this->controleAccesReglages();

		// traitement de la commande de création de liste
		// potentiellement envoyée par le formulaire ci-dessous
		$this->traiterCommandeCreationListe();

		// La liste configurée existe-t-elle ?
		$existenceListe = $this->existenceListe();
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
				<?php _e("Nom de la liste", 'telabotanica') ?>
			</label>
			<input type="text" <?php echo is_super_admin() ? '' : 'disabled="disabled"' ?> id="liste-outil"
				   name="list" placeholder="<?php _e("automatique", 'telabotanica') ?> (<?php echo $this->nomListe ?>)"
				   value="<?php echo $this->config['ezmlm-php']['list'] ?>" />

			<?php if ($existenceListe): ?>
				<span class="description" title="<?php _e("La liste est correctement configurée", 'telabotanica') ?>">
					<svg aria-hidden="true" role="img" class="icon icon-check-circle"><use xlink:href="#icon-check-circle"></use></svg>
				</span>
			<?php else: ?>
				<span class="description" title="<?php _e("La liste n'existe pas", 'telabotanica') ?>">
					<svg aria-hidden="true" role="img" class="icon icon-error"><use xlink:href="#icon-error"></use></svg>
				<?php if (is_super_admin()): ?>
					<a id="tb-forum-creer-liste" href="?tb-forum-action-creation-liste=1" class="button outline">
						<?php _e("Créer la liste", 'telabotanica') ?>
					</a>
				<?php endif; ?>
				</span>
			<?php endif; ?>
			<?php if (! is_super_admin()): ?>
				<br><span class="description">
					<?php _e("Vous ne pouvez pas modifier ce paramètre", 'telabotanica') ?>
				</span>
			<?php endif; ?>
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
				<?php _e("Si \"privé\", seuls les membres pourront y accéder (ne s'applique qu'aux groupes publics)", 'telabotanica') ?>
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
		$configModifiee = Forum::getConfigDefautOutil();
		$configModifiee = $this->preparer_config_locale($configModifiee);
		if (is_super_admin()) {
			$configModifiee['ezmlm-php']['list'] = $_POST['list'];
		}

		/* Mise à jour de la ligne dans la base de données */
		$table = "{$wpdb->prefix}tb_outils_reglages";
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

bp_register_group_extension( 'Forum' );
