<?php
/**
 * Menu du composant de newsletter
 */

// Hook pour le menu Newsletter
add_action('admin_menu', 'tb_newsletter_menu');

function tb_newsletter_menu() {

	add_menu_page(
		'Newsletter',
		'Newsletter',
		'manage_options',
		'newsletter',
		'',
		'dashicons-email-alt'
	);

	add_submenu_page(
		'newsletter',
		'Envoyer',
		'Envoyer la newsletter',
		'manage_options',
		'newsletter_send',
		'tb_newsletter_send'
	);

	add_submenu_page(
		'newsletter',
		'Réglages',
		'Réglages',
		'manage_options',
		'newsletter_config',
		'tb_newsletter_config'
	);

	remove_submenu_page('newsletter', 'newsletter');
}

function get_config() {
	// chargement de la config depuis la BdD
	$newsletter_config = json_decode(get_option('tb_newsletter_config'), true);

	// chargement de la config par défaut
	$newsletter_config_defaut = json_decode(file_get_contents(__DIR__ . '/newsletter_config.json'), true);

	// fusion avec priorité aux données de la BDD
	$newsletter_config = array_merge($newsletter_config_defaut, $newsletter_config);

	return $newsletter_config;
}

/**
 * Gets the post top category.
 *
 * Search for the upper category of a post and return its id
 * A post shouldn't have more than one category, but it works anyway
 *
 * @param      int  $id     The post identifier
 *
 * @return     int  The post top category id.
 */
function get_post_top_category($id) {
	$categories = wp_get_post_categories($id, array('fields' => 'all'));

	return get_top_category($categories[0]);
}

/**
 * Gets the top category.
 *
 * @param      int  $category_id  The category identifier
 *
 * @return     object  The top category.
 */
function get_top_category($category_id) {
	$category = get_category($category_id);

	if (0 !== $category->parent) {
		return get_top_category($category->parent);
	} else {
		return $category;
	}
}

/**
 * Format the post date.
 *
 * Transform it from 2016-11-21 13:37:42 format to timestamp
 *
 * @param      int  $post_date  The post date
 *
 * @return     int  The event date timestamp
 */
function format_post_date($post_date) {
	// keep only date part
	$date = explode(' ', $post_date);
	$date = explode('-', $date[0]);
	$date = mktime(0, 0, 0, $date[1], $date[2], $date[0]);

	return $date;
}

/**
 * Gets the event date.
 *
 * Return false if not an event
 * Transform event date from 21/11/2016 format to timestamp
 *
 * @param      int  $post_id  The post identifier
 *
 * @return     int  The event date timestamp
 */
function get_event_date($post_id) {
	$dates = [];
	foreach (['date', 'date_end'] as $label) {
		$date = get_field($label, $post_id);

		if ($date) {
			$date = explode('/', $date);
			$date = mktime(0, 0, 0, $date[1], $date[0], $date[2]);

			$dates[$label] = $date;
		}
	}

	return $dates;
}

/**
 * Gets the event place.
 *
 * Address should be formatted like "D12, 81630 Saint-Urcisse, France"
 *
 * For addresses in France, returns "Town (departement number)". Eg: Paris (75)
 * For other countries, returns it. Eg: Chine
 * And if address doesn't fit in previous cases, returns it
 *
 * @param      int  $post_id  The post identifier
 *
 * @return     string  The event place.
 */
function get_event_place($post_id) {
	$details = get_field('place', $post_id);

	if (is_array($details)) {
		$place = $details['address'];

		if (preg_match('/^.*, (.*), France$/i', $details['address'], $matches)) {
			$departement_number = substr($matches[1], 0, 2);
			$town = substr($matches[1], 6);

			$place = $town . ' (' . $departement_number . ')';
		} elseif (preg_match('/^.*, (.*)$/i', $details['address'], $matches)) {
			$place = matches[1];
		}

		return $place;
	} elseif (is_object($details)) {
		switch ($type) {
			case 'address':
				if ($details->city) {
					$place = $details->city;
					if ($details->countryCode === 'fr') {
						$place = $place . ' (' . substr($details->postcode, 0, 2) . ')';
					} else {
						$place = $place . ' ' . $details->country;
					}
				} else {
					$place = $details->name;
				}

				break;
			case 'city':
				$place = $details->city;
				if ($details->countryCode !== 'fr') {
					$place = $place . ' ' . $details->country;
				}

				break;
			case 'country':
			default:
				$place = $details->name;

				break;
		}

		return $place;
	} else {
		return false;
	}
}

/**
 * Gets the newsletter subject.
 *
 * Tries to change locale to french to insert month name in subject
 *
 * @return     string  The subject.
 */
function get_subject() {
	$oldLocale = setlocale(LC_TIME, 'fr_FR.utf8', 'fr_FR', 'fr_FR@euro', 'fr');

	$subject = 'Lettre d\'information de Tela Botanica du ' . strftime('%e %B %Y');

	setlocale(LC_TIME, $oldLocale);

	return $subject;
}

function get_newsletter($multipart_boundary = null) {
	require_once __DIR__ . '/../vendor/autoload.php';
	$loader = new Twig_Loader_Filesystem(get_template_directory() . '/inc/newsletter');

	$twig = new Twig_Environment($loader, array());
	$twig->addExtension(new Twig_Extensions_Extension_Intl());
	$twig->addExtension(new Twig_Extensions_Extension_Text());
	$twig->getExtension('Twig_Extension_Core')->setTimezone('Europe/Paris');

	if (have_rows('tb_newsletter_sections', 'option')) {
		$categories = [];
		$subcategories = [];
		$posts = [];

		while (have_rows('tb_newsletter_sections', 'option')) {
			the_row();

			foreach (get_sub_field('tb_newsletter_sections_items') as $post) {
				$category = get_post_top_category($post->ID);

				if ($category) {
					$subcategory = get_sub_field('tb_newsletter_sections_title');

					$categories[$category->term_id] = [
						'slug'	=> $category->slug,
						'name'	=> $category->name,
						'url'	=> get_category_link($category)
					];

					$subcategories[$category->term_id][$subcategory->term_id] = $subcategory->name;

					$posts[$category->term_id][$subcategory->term_id][] = [
						'post' 		=> $post,
						'author' 	=> get_the_author_meta('display_name', $post->post_author),
						'link' 		=> get_post_permalink($post->ID),
						'thumbnail'	=> wp_get_attachment_url(get_post_thumbnail_id($post->ID)),
						'date_post'	=> format_post_date($post->post_date),
						'event'		=> get_event_date($post->ID),
						'place'		=> get_event_place($post->ID)
					];
				}
			}
		}
	}

	$params = [
		'intro' 	=> get_field('tb_newsletter_introduction', 'option'),
		'categories' => $categories,
		'subcategories' => $subcategories,
		'posts' 	=> $posts,
		'outro' 	=> get_field('tb_newsletter_footer', 'option')
	];

	if ($multipart_boundary) {
		$params['boundary'] = $multipart_boundary;

		return $twig->render('newsletter-multipart.html', $params);
	} else {
		return $twig->render('newsletter-html.html', $params);
	}

}

function send_newsletter() {
	$boundary = uniqid('tela');

	$headers[] = 'Content-Type: multipart/alternative;boundary=' . $boundary . '; charset=UTF-8';
	$headers[] = 'Content-Transfer-Encoding: 8bit';
	$headers[] = 'From: Tela Botanica <accueil@tela-botanica.org>';
	$headers[] = 'Reply-To: accueil@tela-botanica.org';
	$headers[] = 'MIME-Version: 1.0';
	$headers[] = 'X-Mailer: PHP/' . phpversion();

	add_action( 'phpmailer_init', 'mailer_config', 10, 1);
	function mailer_config(PHPMailer $mailer){
		// $mailer->IsSMTP();
		// $mailer->SMTPDebug = 2; // write 0 if you don't want to see client/server communication in page
		$mailer->CharSet  = "utf-8";
	}

	wp_mail(
		get_config()['newsletter_recipient'],
		get_subject(),
		get_newsletter($boundary),
		$headers
	);
}

function tb_newsletter_send() {

?>
	<div class="wrap">

		<?php
		if (!current_user_can('manage_options'))
		{
			wp_die( __('Vous n\'avez pas les droits suffisants pour accéder à cette page.') );
		}
		?>

		<?php screen_icon(); ?>

		<!-- Titre -->
		<h2>Envoi de la newsletter</h2>

		<!-- Description -->
		<div class="description">
			<p>Envoie le contenu défini pour la newsletter à l'adresse renseignée.</p>
		</div>

		<?php settings_errors(); ?>

		<?php

			$newsletter_config = get_config();

			$hidden_update_address_field_name = 'tb_submit_hidden_update_addres';
			$hidden_send_newsletter_field_name = 'tb_submit_hidden_send_newsletter';

			// enregistre les changements de config en BdD
			if (isset($_POST[$hidden_update_address_field_name]) && $_POST[$hidden_update_address_field_name] == 'Y'):
				$newsletter_config['newsletter_recipient'] = $_POST['newsletter_recipient'];

				update_option('tb_newsletter_config', json_encode($newsletter_config));
		?>

				<!-- Confirmation de l'enregistrement -->
				<div class="updated">
					<p><strong>Mise à jour effectuée</strong></p>
				</div>

		<?php

			// enregistre les changements de config en BdD
			elseif (isset($_POST[$hidden_send_newsletter_field_name]) && $_POST[$hidden_send_newsletter_field_name] == 'Y'):

				send_newsletter();
		?>

				<!-- Confirmation de l'enregistrement -->
				<div class="updated">
					<p><strong>Newsletter envoyée</strong></p>
				</div>

		<?php endif; ?>

		<form method="post" action="">
			<input type="hidden" name="<?php echo $hidden_update_address_field_name; ?>" value="Y">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="newsletter_recipient">Adresse destinataire</label>
						</th>
						<td>
							<input type="text" name="newsletter_recipient" id="newsletter_recipient" value="<?php echo $newsletter_config['newsletter_recipient']; ?>" class="regular-text">
						</td>
						<td>
							<input type="submit" name="Submit" class="button-primary" value="Enregistrer l'adresse" />
						</td>
					</tr>
				</tbody>
			</table>
		</form>

		<hr>

		<div class="card">

			<?php echo get_newsletter() ?>

		</div>

		<form method="post" action="">
			<input type="hidden" name="<?php echo $hidden_send_newsletter_field_name; ?>" value="Y">

			<p class="submit">
				<input type="submit" name="Submit" class="button-primary" value="Envoyer la newsletter" />
			</p>
		</form>

	</div>
<?php
}


function tb_newsletter_config() {

?>
	<div class="wrap">

		<?php
		if (!current_user_can('manage_options'))
		{
			wp_die( __('Vous n\'avez pas les droits suffisants pour accéder à cette page.') );
		}
		?>

		<?php screen_icon(); ?>

		<!-- Titre -->
		<h2>Réglages de la newsletter</h2>

		<!-- Description -->
		<div class="description">
			<p>Cette configuration est utilisée notamment par le template <tt>[newsletter-desinscription]</tt> du thème Tela Botanica.</p>
		</div>

		<?php settings_errors(); ?>

		<?php
		$hidden_update_address_field_name = 'tb_submit_hidden_update_addres';
		// Chargement de la config actuelle
		$newsletter_config = get_config();

		// enregistre les changements de config en BdD
		if (isset($_POST[$hidden_update_address_field_name]) && $_POST[$hidden_update_address_field_name] == 'Y'):
			// préparation des valeurs envoyées
			$newsletter_config['ezmlm_php_url'] = $_POST['ezmlm_php_url'];
			$newsletter_config['ezmlm_php_header'] = $_POST['ezmlm_php_header'];
			// enregistrement
			update_option('tb_newsletter_config', json_encode($newsletter_config));
		?>
			<!-- Confirmation de l'enregistrement -->
			<div class="updated">
				<p>
					<strong>Options mises à jour</strong>
				</p>
			</div>
		<?php endif; ?>

		<form method="post" action="">
			<input type="hidden" name="<?php echo $hidden_update_address_field_name; ?>" value="Y">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="newsletter_recipient">URL racine du service ezmlm-php</label>
						</th>
						<td>
							<input type="text" name="ezmlm_php_url" id="ezmlm_php_url" value="<?php echo $newsletter_config['ezmlm_php_url']; ?>" class="regular-text">
							<p class="description">Ne pas mettre de "/" (slash) à la fin.</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="newsletter_recipient">Entête attendu par le service</label>
						</th>
						<td>
							<input type="text" name="ezmlm_php_header" id="ezmlm_php_header" value="<?php echo $newsletter_config['ezmlm_php_header']; ?>" class="regular-text">
							<p class="description">
								Entête attendu par ezmlm-php pour y lire le jeton SSO.
								<br/>
								Par défaut "Authorization".
								<br/>
								Certains serveurs n'acceptant pas la valeur par défault,
								elle peut être remplacée, par exemple par "Auth".
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
	</div>
<?php
}