<?php
/**
 * Ajout d'une page d'administration au tableau de bord Wordpress.
 * Permet à l'administrateur de définir la configuration globale des outils
 * (chemins des services, des applications, etc.)
 */

// Hook pour le menu Admin
add_action('admin_menu', 'tb_ajout_pages');

// Actions du hook
function tb_ajout_pages() {

	// Ajoute un menu 'Tela Botanica' au tableau de bord Wordpress
	add_menu_page(
		'Tela Botanica',
		'Tela Botanica',
		'manage_options',
		'telabotanica',
		'',
		'dashicons-carrot'
	);

	// Ajoute un sous-menu 'Présentation' dans 'Tela Botanica'
	add_submenu_page(
		'telabotanica',
		'Présentation',
		'Présentation',
		'manage_options',
		'telabotanica', // On donne le même 'menu_slug' que celui du menu pour écraser le sous-menu automatique
		'tb_menu_home'
	);

	// Ajoute un sous-menu 'Espace projets' dans 'Tela Botanica'
	add_submenu_page(
		'telabotanica',
		'Espace projets',
		'Espace projets',
		'manage_options',
		'espace_projets',
		'tb_menu_espace_projets'
	);

	add_submenu_page(
		'telabotanica',
		'Hooks',
		'Hooks',
		'manage_options',
		'hooks',
		'tb_menu_hooks'
	);

	add_submenu_page(
		'telabotanica',
		'Réglages communs',
		'Réglages communs',
		'manage_options',
		'securite',
		'tb_menu_securite'
	);
}

/**
 * Page de présentation et documentation du plugin
 */
function tb_menu_home() {
	include "admin_presentation.php";
}

function tb_menu_hooks() {

?>
	<div class="wrap">

		<?php
		if (!current_user_can('manage_options'))
		{
			wp_die( __('Vous n\'avez pas les droits suffisants pour accéder à cette page.', 'telabotanica') );
		}
		?>

		<?php screen_icon(); ?>

		<!-- Titre -->
		<h2>Configuration des hooks de synchro</h2>

		<!-- Description -->
		<div class="description">
			<p>
				Permet de modifier les URLs à appeler pour synchroniser des modifications de données entre les différents outils Tela.
				<br>
				Par exemple, lorsqu'un utilisateur change son adresse email dans le profil.
				<br>
				Le jeton SSO défini dans "Réglages communs" sera transmis aux services de son domaine et de ses sous-domaines, dans l'entête choisi ci-dessous.
			</p>
		</div>

		<?php settings_errors(); ?>

		<?php

			require_once(dirname( __FILE__ ) . '/../hooks/hooks.php');

			$hidden_field_name = 'tb_submit_hidden';

			$hooks_config = Hooks::getConfig();

			// enregistre les changements de config en BdD
			if (isset($_POST[$hidden_field_name]) && $_POST[$hidden_field_name] == 'Y') {
				$hooks_config['email-modification-urls'] = preg_split('/\r\n|[\r\n]/', stripslashes($_POST['email-modification-urls']));
				$hooks_config['user-creation-urls'] = preg_split('/\r\n|[\r\n]/', stripslashes($_POST['user-creation-urls']));
				$hooks_config['user-deletion-urls'] = preg_split('/\r\n|[\r\n]/', stripslashes($_POST['user-deletion-urls']));
				$hooks_config['error-recipients-emails'] = preg_split('/\r\n|[\r\n]/', stripslashes($_POST['error-recipients-emails']));
				$hooks_config['header-name'] = $_POST['header-name'];
				// suppression des lignes vides
				$hooks_config['email-modification-urls'] = array_filter($hooks_config['email-modification-urls']);
				$hooks_config['user-creation-urls'] = array_filter($hooks_config['user-creation-urls']);
				$hooks_config['user-deletion-urls'] = array_filter($hooks_config['user-deletion-urls']);
				$hooks_config['error-recipients-emails'] = array_filter($hooks_config['error-recipients-emails']);

				update_option(Hooks::STORAGE_OPTION_NAME, json_encode($hooks_config));
		?>

				<!-- Confirmation de l'enregistrement -->
				<div class="updated">
					<p><strong>Mise à jour effectuée</strong></p>
				</div>

		<?php

			}

		?>

		<form method="post" action="">
			<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="email-modification-urls">URLs à appeler en cas de modification de l'adresse mail d'un utilisateur</label>
						</th>
						<td>
							<textarea id="email-modification-urls" name="email-modification-urls" rows="5" cols="80"><?php echo implode(PHP_EOL, $hooks_config['email-modification-urls']); ?></textarea>
							<p class="description">
								Une URL par ligne.<br>
								Ex : http://example.org/changeusermail/{user_id}/{old_email}/to/{new_email}<br>
								Les paramètres {user_id}, {old_email} et {new_email} sont remplacés par les valeurs utilisateur lors de l'appel<br>
								Les lignes commençant par # seront ignorées
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="user-creation-urls">URLs à appeler en cas de création d'un utilisateur</label>
						</th>
						<td>
							<textarea id="user-creation-urls" name="user-creation-urls" rows="3" cols="80"><?php echo implode(PHP_EOL, $hooks_config['user-creation-urls']); ?></textarea>
							<p class="description">
								Une URL par ligne.<br>
								Ex : http://example.org/createuser/{user_id}/{new_email}<br>
								Les paramètres {user_id} et {new_email} sont remplacés par les valeurs utilisateur lors de l'appel<br>
								Les lignes commençant par # seront ignorées
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="user-deletion-urls">URLs à appeler en cas de suppression d'un utilisateur</label>
						</th>
						<td>
							<textarea id="user-deletion-urls" name="user-deletion-urls" rows="3" cols="80"><?php echo implode(PHP_EOL, $hooks_config['user-deletion-urls']); ?></textarea>
							<p class="description">
								Une URL par ligne.<br>
								Ex : http://example.org/deleteuser/{user_id}/{user_email}<br>
								Les paramètres {user_id} et {user_email} sont remplacés par les valeurs utilisateur lors de l'appel<br>
								Les lignes commençant par # seront ignorées
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="error-recipients-emails">Destinataires des emails d'erreurs des hooks</label>
						</th>
						<td>
							<textarea id="error-recipients-emails" name="error-recipients-emails" rows="3" cols="80"><?php echo implode(PHP_EOL, $hooks_config['error-recipients-emails']); ?></textarea>
							<p class="description">
								Une adresse par ligne<br>
								Les lignes commençant par # seront ignorées
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="header-name">Entête (header) dans lequel envoyer le jeton SSO</label>
						</th>
						<td>
							<input type="text" id="header-name" name="header-name" value="<?php echo $hooks_config['header-name']; ?>" />
							<p class="description">"Authorization" (par défaut) est refusé / traité incorrectement par certains serveurs.</p>
						</td>
					</tr>
				</tbody>
			</table>
			<hr/>

			<p class="submit">
				<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
			</p>
		</form>
<?php
}

/**
 * Réglages communs
 */
function tb_menu_securite() {
	$opt_name_general = 'tb_general_config';
	$hidden_field_name_general = 'tb_submit_hidden';

	// chargement de la config actuelle
	$configActuelleGeneral = json_decode(get_option($opt_name_general), true);

	// si le formulaire est validé
	if( isset($_POST[$hidden_field_name_general]) && $_POST[$hidden_field_name_general] == 'Y' ) {
		// récupération des valeurs du formulaire
		$adminToken = $_POST['adminToken'];
		$adminTokenDomain = $_POST['adminTokenDomain'];
		$ezmlmRootUri = $_POST['ezmlmRootUri'];
		$ezmlmAuthHeaderName = $_POST['ezmlmAuthHeaderName'];
		// injection des valeurs du formulaire
		$configActuelleGeneral['adminToken'] = $adminToken;
		$configActuelleGeneral['adminTokenDomain'] = $adminTokenDomain;
		$configActuelleGeneral['ezmlmRootUri'] = $ezmlmRootUri;
		$configActuelleGeneral['ezmlmAuthHeaderName'] = $ezmlmAuthHeaderName;
		// mise à jour de la BDD
		update_option($opt_name_general, json_encode($configActuelleGeneral));
		?>
		<!-- Confirmation de l'enregistrement -->
		<div class="updated">
			<p>
				<strong>Options mises à jour</strong>
			</p>
		</div>
	<?php } ?>

	<div class="wrap">

		<?php
		if (!current_user_can('manage_options'))
		{
			wp_die( __('Vous n\'avez pas les droits suffisants pour accéder à cette page.', 'telabotanica') );
		}
		?>

		<?php screen_icon(); ?>

		<!-- Titre -->
		<h2>Réglages communs</h2>

		<!-- Description -->
		<div class="description">
			Cette section affecte les autres sections du plugin Tela Botanica.
		</div>

		<?php settings_errors(); ?>

		<form method="post" action="">
			<input type="hidden" name="<?php echo $hidden_field_name_general; ?>" value="Y">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="ezmlmRootUri">URL du service ezmlm-php</label>
						</th>
						<td>
							<input class="regular-text" type="text" id="ezmlmRootUri" name="ezmlmRootUri" value="<?php echo isset($configActuelleGeneral['ezmlmRootUri']) ? $configActuelleGeneral['ezmlmRootUri'] : ''; ?>" />
							<p class="description">
								Utilisé par la newsletter, l'espace projets...
								<br>
								Ne pas mettre de "/" (slash) à la fin.
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="ezmlmAuthHeaderName">Entête (header) à envoyer à ezmlm-php</label>
						</th>
						<td>
							<input type="text" id="ezmlmAuthHeaderName" name="ezmlmAuthHeaderName" value="<?php echo isset($configActuelleGeneral['ezmlmAuthHeaderName']) ? $configActuelleGeneral['ezmlmAuthHeaderName'] : ''; ?>" />
							<p class="description">"Authorization" (par défaut) est refusé / traité incorrectement par certains serveurs.</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="adminToken">Jeton SSO administrateur</label>
						</th>
						<td>
							<textarea id="adminToken" name="adminToken" rows="10" cols="80"><?php echo isset($configActuelleGeneral['adminToken']) ? $configActuelleGeneral['adminToken'] : ''; ?></textarea>
							<p class="description">
								Placer ici un jeton SSO administrateur longue durée (utilisé par la la newsletter, l'espace projets...)
								<br>
								Ce jeton peut être forgé à l'aide du script "admin.php forger_jeton" de l'annuaire.
								<br>
								Il doit rester ABSOLUMENT CONFIDENTIEL.
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="adminTokenDomain">Domaine du jeton</label>
						</th>
						<td>
							<input type="text" id="adminTokenDomain" name="adminTokenDomain" value="<?php echo isset($configActuelleGeneral['adminTokenDomain']) ? $configActuelleGeneral['adminTokenDomain'] : ''; ?>" />
							<p class="description">Le jeton SSO administrateur ne sera envoyé qu'à des services hébergés sur ce domaine et ses sous-domaines.</p>
						</td>
					</tr>
				</tbody>
			</table>
			<hr/>
			<!-- Enregistrer les modifications -->
			<p class="submit">
				<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
			</p>
		</form>
	</div>
	<?php
}

/**
 * Affiche le sous-menu 'Espace projets'; lit et enregistre les réglages dans
 * la table "options" de WP (standard)
 */
function tb_menu_espace_projets() {

    /* Gestion des onglets */
	if( isset( $_GET[ 'onglet' ] ) ) {
		$onglet_actif = $_GET[ 'onglet' ];
	} else {
		// onglet par défaut
		$onglet_actif = 'porte-documents';
	}
?>
	<!-- Vue du sous-menu 'espace_projets' -->
	<div class="wrap">

		<?php
		if (!current_user_can('manage_options'))
		{
			wp_die( __('Vous n\'avez pas les droits suffisants pour accéder à cette page.', 'telabotanica') );
		}
		?>

		<?php screen_icon(); ?>

		<!-- Titre -->
		<h2>Configuration des outils de l'Espace projets</h2>

		<!-- Description -->
		<div class="description">
			Cette configuration affecte les outils pour l'ensemble des projets.
			Elle est écrasée par la configuration de chaque projet, si celle-ci est présente.
		</div>

		<?php settings_errors(); ?>

		<!-- Menu des onglets -->
		<h2 class="nav-tab-wrapper">
		    <a href="?page=espace_projets&onglet=porte-documents" class="nav-tab <?php echo $onglet_actif == 'porte-documents' ? 'nav-tab-active' : ''; ?>">Porte-documents</a>
		    <a href="?page=espace_projets&onglet=forum" class="nav-tab <?php echo $onglet_actif == 'forum' ? 'nav-tab-active' : ''; ?>">Forum</a>
			<a href="?page=espace_projets&onglet=wiki" class="nav-tab <?php echo $onglet_actif == 'wiki' ? 'nav-tab-active' : ''; ?>">Wiki</a>
			<a href="?page=espace_projets&onglet=flora-data" class="nav-tab <?php echo $onglet_actif == 'flora-data' ? 'nav-tab-active' : ''; ?>">FloraData</a>
			<!--<a href="?page=espace_projets&onglet=autre" class="nav-tab <?php echo $onglet_actif == 'autre' ? 'nav-tab-active' : ''; ?>">Autre machin</a>  -->
		</h2>

		<!-- Onglet Porte-documents -->
		<?php if( $onglet_actif == 'porte-documents' ) {
	       	$opt_name_pd = 'tb_porte-documents_config';
			$hidden_field_name_pd = 'tb_submit_hidden';

			// chargement de la config actuelle
			$configActuellePd = json_decode(get_option($opt_name_pd), true);
			//var_dump($configActuellePd);

			// si la config actuelle est vide, on charge la config par défaut
			if (empty($configActuellePd)) {
				// config par défaut de l'outil
				$cheminConfigDefautPd = __DIR__ . '/../outils/porte-documents_config-defaut.json';
				$configDefautPd = json_decode(file_get_contents($cheminConfigDefautPd), true);
				$configActuellePd = $configDefautPd;
			}

			// si le formulaire est validé
			if( isset($_POST[$hidden_field_name_pd]) && $_POST[$hidden_field_name_pd] == 'Y' ) {
				// récupération des valeurs du formulaire
				$filesServiceUrl = $_POST['filesServiceUrl'];
				$active = ($_POST['active'] == 'true');
				$abstractionPath = $_POST['abstractionPath'];
				$userInfoByIdUrl = $_POST['userInfoByIdUrl'];
				$authUrl = $_POST['authUrl'];
				// injection des valeurs du formulaire
				$configActuellePd['filesServiceUrl'] = $filesServiceUrl;
				$configActuellePd['active'] = $active;
				$configActuellePd['abstractionPath'] = $abstractionPath;
				$configActuellePd['userInfoByIdUrl'] = $userInfoByIdUrl;
				$configActuellePd['authUrl'] = $authUrl;
				// mise à jour de la BDD
				update_option($opt_name_pd, json_encode($configActuellePd));
		?>

				<!-- Confirmation de l'enregistrement -->
				<div class="updated">
					<p>
						<strong>Options mises à jour</strong>
					</p>
				</div>

		<?php

			}

		?>

			<form method="post" action="">
				<input type="hidden" name="<?php echo $hidden_field_name_pd; ?>" value="Y">
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label>Disponibilité</label>
							</th>
							<td>
								<select name="active">
									<option value="true" <?php echo ($configActuellePd['active'] ? 'selected' : '') ?>>Activé</option>
									<option value="false" <?php echo ($configActuellePd['active'] ? '' : 'selected') ?>>Désactivé</option>
								</select>
								<p class="description">Si "désactivé", l'outil ne sera disponible dans aucun projet.</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label>URL du service Cumulus</label>
							</th>
							<td>
								<input name="filesServiceUrl" type="text" value="<?php echo $configActuellePd['filesServiceUrl']; ?>" class="regular-text">
								<p class="description">Ne pas mettre de "/" (slash) à la fin.</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label>Chemin du dossier "projets"</label>
							</th>
							<td>
								<input type="text" name="abstractionPath" value="<?php echo $configActuellePd['abstractionPath']; ?>" />
								<p class="description">Chemin de Cumulus contenant les dossiers des projets.</p>
								<p class="description">Doit commencer par "/" (slash). Ne pas mettre de "/" (slash) à la fin.</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label>URL du service d'authentification (SSO)</label>
							</th>
							<td>
								<input type="text" name="authUrl" value="<?php echo $configActuellePd['authUrl']; ?>" class="regular-text" />
								<p class="description">Ne pas mettre de "/" (slash) à la fin.</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label>URL du service d'information sur les utilisateurs</label>
							</th>
							<td>
								<input type="text" name="userInfoByIdUrl" value="<?php echo $configActuellePd['userInfoByIdUrl']; ?>" class="regular-text" />
								<p class="description">Ne pas mettre de "/" (slash) à la fin.</p>
							</td>
						</tr>
					</tbody>
				</table>
				<hr/>
				<!-- Enregistrer les modifications -->
				<p class="submit">
					<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
				</p>
			</form>

    	<?php
	    }

	    /* Onglet Forum */
	    elseif( $onglet_actif == 'forum' ) {
			$opt_name_forum = 'tb_forum_config';
			$hidden_field_name_forum = 'tb_submit_hidden';

			// chargement de la config actuelle
			$configActuelleForum = json_decode(get_option($opt_name_forum), true);
			//var_dump($configActuelleForum);

			// si la config actuelle est vide, on charge la config par défaut
			if (empty($configActuelleForum)) {
				// config par défaut de l'outil
				$cheminConfigDefautForum = __DIR__ . '/../outils/forum_config-defaut.json';
				$configDefautForum = json_decode(file_get_contents($cheminConfigDefautForum), true);
				$configActuelleForum = $configDefautForum;
			}

			// si le formulaire est validé
			if( isset($_POST[$hidden_field_name_forum]) && $_POST[$hidden_field_name_forum] == 'Y' ) {
				// récupération des valeurs du formulaire
				$ezmlmPhpRootUri = $_POST['ezmlmPhpRootUri'];
				$annuaireUrl = $_POST['annuaireUrl'];
				$headerName = $_POST['headerName'];
				$avatarService = $_POST['avatarService'];
				$active = ($_POST['active'] == 'true');
				$displayListTitle = ($_POST['displayListTitle'] == 'true');
				// injection des valeurs du formulaire
				$configActuelleForum['ezmlm-php']['rootUri'] = $ezmlmPhpRootUri;
				$configActuelleForum['adapters']['AuthAdapterTB']['annuaireURL'] = $annuaireUrl;
				$configActuelleForum['adapters']['AuthAdapterTB']['headerName'] = $headerName;
				$configActuelleForum['avatarService'] = $avatarService;
				$configActuelleForum['displayListTitle'] = $displayListTitle;
				$configActuelleForum['active'] = $active;
				// mise à jour de la BDD
				update_option($opt_name_forum, json_encode($configActuelleForum));
		?>

				<!-- Confirmation de l'enregistrement -->
				<div class="updated">
					<p>
						<strong>Options mises à jour</strong>
					</p>
				</div>

		<?php

			}

		?>

			<form method="post" action="">
				<input type="hidden" name="<?php echo $hidden_field_name_forum; ?>" value="Y">
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label>Disponibilité</label>
							</th>
							<td>
								<select name="active">
									<option value="true" <?php echo ($configActuelleForum['active'] ? 'selected' : '') ?>>Activé</option>
									<option value="false" <?php echo ($configActuelleForum['active'] ? '' : 'selected') ?>>Désactivé</option>
								</select>
								<p class="description">Si "désactivé", l'outil ne sera disponible dans aucun projet.</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label>URL du service ezmlm-php</label>
							</th>
							<td>
								<input type="text" name="ezmlmPhpRootUri" value="<?php echo $configActuelleForum['ezmlm-php']['rootUri']; ?>" class="regular-text" />
								<p class="description">Ne pas mettre de "/" (slash) à la fin.</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label>URL du service d'authentification (SSO)</label>
							</th>
							<td>
								<input type="text" name="annuaireUrl" value="<?php echo $configActuelleForum['adapters']['AuthAdapterTB']['annuaireURL']; ?>" class="regular-text" />
								<p class="description">Ne pas mettre de "/" (slash) à la fin.</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label>Entête (header) à envoyer au service d'authentification</label>
							</th>
							<td>
								<input type="text" name="headerName" value="<?php echo $configActuelleForum['adapters']['AuthAdapterTB']['headerName']; ?>" />
								<p class="description">"Authorization" (par défaut) est refusé / traité incorrectement par certains serveurs.</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label>Afficher l'adresse de la liste dans l'interface</label>
							</th>
							<td>
								<select name="displayListTitle">
									<option value="true" <?php echo ($configActuelleForum['displayListTitle'] ? 'selected' : '') ?>>Oui</option>
									<option value="false" <?php echo ($configActuelleForum['displayListTitle'] ? '' : 'selected') ?>>Non</option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label>URL du service de récupération des avatars</label>
							</th>
							<td>
								<input type="text" name="avatarService" value="<?php echo $configActuelleForum['avatarService']; ?>" class="regular-text" />
								<p class="description">
									La chaîne "{email}" sera remplacée par l'adresse email de l'utilisateur.
									<br>
									Si laissé vide, les avatars ne seront pas gérés.
								</p>
							</td>
						</tr>
					</tbody>
				</table>
				<hr/>
				<!-- Enregistrer les modifications -->
				<p class="submit">
					<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
				</p>
			</form>
	<?php }

	    /* Onglet Wiki */
	    elseif( $onglet_actif == 'wiki' ) {
			$opt_name_wiki = 'tb_wiki_config';
			$hidden_field_name_wiki = 'tb_submit_hidden';

			// chargement de la config actuelle
			$configActuelleWiki = json_decode(get_option($opt_name_wiki), true);
			//var_dump($configActuelleForum);

			// si la config actuelle est vide, on charge la config par défaut
			if (empty($configActuelleWiki)) {
				// config par défaut de l'outil
				$cheminConfigDefautWiki = __DIR__ . '/../outils/wiki_config-defaut.json';
				$configDefautWiki = json_decode(file_get_contents($cheminConfigDefautWiki), true);
				$configActuelleWiki = $configDefautWiki;
			}

			// si le formulaire est validé
			if( isset($_POST[$hidden_field_name_wiki]) && $_POST[$hidden_field_name_wiki] == 'Y' ) {
				// récupération des valeurs du formulaire
				$rootUrl = $_POST['rootUrl'];
				$active = ($_POST['active'] == 'true');
				// injection des valeurs du formulaire
				$configActuelleWiki['rootUrl'] = $rootUrl;
				$configActuelleWiki['active'] = $active;
				// mise à jour de la BDD
				update_option($opt_name_wiki, json_encode($configActuelleWiki));
			?>

			<!-- Confirmation de l'enregistrement -->
			<div class="updated">
				<p>
					<strong>Options mises à jour</strong>
				</p>
			</div>

			<?php
			}

			?>

			<form method="post" action="">
				<input type="hidden" name="<?php echo $hidden_field_name_wiki; ?>" value="Y">
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label>Disponibilité</label>
							</th>
							<td>
								<select name="active">
									<option value="true" <?php echo ($configActuelleWiki['active'] ? 'selected' : '') ?>>Activé</option>
									<option value="false" <?php echo ($configActuelleWiki['active'] ? '' : 'selected') ?>>Désactivé</option>
								</select>
								<p class="description">Si "désactivé", l'outil ne sera disponible dans aucun projet.</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label>URL racine des wikini</label>
							</th>
							<td>
								<input type="text" name="rootUrl" value="<?php echo $configActuelleWiki['rootUrl']; ?>" class="regular-text" />
								<p class="description">Ne pas mettre de "/" (slash) à la fin.</p>
							</td>
						</tr>
					</tbody>
				</table>
				<hr/>
				<!-- Enregistrer les modifications -->
				<p class="submit">
					<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
				</p>
			</form>
	<?php }

	    /* Onglet FloraData */
	    elseif( $onglet_actif == 'flora-data' ) {
			$opt_name_floradata = 'tb_flora-data_config';
			$hidden_field_name_floradata = 'tb_submit_hidden';

			// chargement de la config actuelle
			$configActuelleFloraData = json_decode(get_option($opt_name_floradata), true);
			//var_dump($configActuelleFloraData);

			// si la config actuelle est vide, on charge la config par défaut
			if (empty($configActuelleFloraData)) {
				// config par défaut de l'outil
				$cheminConfigDefautFloraData = __DIR__ . '/../outils/flora-data_config-defaut.json';
				$configDefautFloraData = json_decode(file_get_contents($cheminConfigDefautFloraData), true);
				$configActuelleFloraData = $configDefautFloraData;
			}

			// si le formulaire est validé
			if( isset($_POST[$hidden_field_name_floradata]) && $_POST[$hidden_field_name_floradata] == 'Y' ) {
				// récupération des valeurs du formulaire
				$rootUrl = $_POST['rootUrl'];
				$active = ($_POST['active'] == 'true');
				// injection des valeurs du formulaire
				$configActuelleFloraData['rootUrl'] = $rootUrl;
				$configActuelleFloraData['active'] = $active;
				// mise à jour de la BDD
				update_option($opt_name_floradata, json_encode($configActuelleFloraData));
			?>

			<!-- Confirmation de l'enregistrement -->
			<div class="updated">
				<p>
					<strong>Options mises à jour</strong>
				</p>
			</div>

			<?php
			}

			?>

			<form method="post" action="">
				<input type="hidden" name="<?php echo $hidden_field_name_floradata; ?>" value="Y">
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label>Disponibilité</label>
							</th>
							<td>
								<select name="active">
									<option value="true" <?php echo ($configActuelleFloraData['active'] ? 'selected' : '') ?>>Activé</option>
									<option value="false" <?php echo ($configActuelleFloraData['active'] ? '' : 'selected') ?>>Désactivé</option>
								</select>
								<p class="description">Si "désactivé", l'outil ne sera disponible dans aucun projet.</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label>URL racine des widgets</label>
							</th>
							<td>
								<input type="text" name="rootUrl" value="<?php echo $configActuelleFloraData['rootUrl']; ?>" class="regular-text" />
								<p class="description">Ne pas mettre de ":" (deux points) à la fin (ex: ".../widget:cel").</p>
							</td>
						</tr>
					</tbody>
				</table>
				<hr/>
				<!-- Enregistrer les modifications -->
				<p class="submit">
					<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
				</p>
			</form>
		<?php } ?>
	</div>
<?php }?>
