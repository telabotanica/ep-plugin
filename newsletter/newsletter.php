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

	remove_submenu_page('newsletter', 'newsletter');
}

function get_config() {
	// chargement de la config depuis la BdD
	$newsletter_config = json_decode(get_option('tb_newsletter_config'), true);

	if (empty($newsletter_config)) {
		// chargement de la config par défaut
		$newsletter_config = json_decode(file_get_contents(__DIR__ . '/newsletter_config.json'), true);
	}

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
 * Gets the post date.
 *
 * Transform it from 2016-11-21 13:37:42 format to timestamp
 *
 * @param      int  $post_date  The post date
 *
 * @return     int  The event date timestamp
 */
function get_post_date($post_date) {
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
	$is_single_date = get_field('is_single_date', $post_id);

	if (is_null($is_single_date)) {
		$date = false;
	} else {
		if ($is_single_date) {
			$date = get_field('date', $post_id);
		} else {
			$date = get_field('date_from', $post_id);
		}

		$date = explode('/', $date);
		$date = mktime(0, 0, 0, $date[1], $date[0], $date[2]);
	}

	return $date;
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

	if ($details) {
		$place = $details['address'];

		if (preg_match('/^.*, (.*), France$/i', $details['address'], $matches)) {
			$departement_number = substr($matches[1], 0, 2);
			$town = substr($matches[1], 6);

			$place = $town . ' (' . $departement_number . ')';
		} elseif (preg_match('/^.*, (.*)$/i', $details['address'], $matches)) {
			$place = matches[1];
		}

		return $place;
	} else {
		return false;
	}
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

					$event_date = get_event_date($post->ID);
					$event_place = get_event_place($post->ID);

					$posts[$category->term_id][$subcategory->term_id][] = [
						'post' 		=> $post,
						'author' 	=> get_the_author_meta('display_name', $post->post_author),
						'link' 		=> get_post_permalink($post->ID),
						'thumbnail'	=> wp_get_attachment_url(get_post_thumbnail_id($post->ID)),
						'date'		=> get_post_date($post->post_date),
						'date_alt'	=> $event_date,
						'place'		=> $event_place,
						'is_event'	=> ($event_date ||$event_place)
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
	$headers = 'Content-Type: multipart/alternative;boundary=' . $boundary . '; charset="utf-8"' . "\r\n"
		. 'Content-Transfer-Encoding: 8bit' . "\r\n"
		. 'From: accueil@tela-botanica.org' . "\r\n"
		. 'Reply-To: accueil@tela-botanica.org' . "\r\n"
		. 'MIME-Version: 1.0' . "\r\n"
		. 'X-Mailer: PHP/' . phpversion()
	;

	$newsletter_config = get_config();

	$oldLocale = setlocale(LC_TIME, 'fr_FR.utf8', 'fr_FR', 'fr_FR@euro', 'fr');

	wp_mail(
		$newsletter_config['newsletter_recipient'],
		'Lettre d\'information de Tela Botanica du ' . utf8_encode(strftime('%e %B %Y')),
		get_newsletter($boundary),
		$headers
	);

	setlocale(LC_TIME, $oldLocale);
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
			<p>Envoi le contenu défini pour la newsletter à l'adresse renseignée</p>
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
							<input type="text" name="newsletter_recipient" id="newsletter_recipient" value="<?php echo $newsletter_config['newsletter_recipient']; ?>" size="40">
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
