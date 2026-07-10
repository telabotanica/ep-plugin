<?php
/**
 * Menu du composant de newsletter
 */

require_once __DIR__ . '/class-brevo-api.php';

// Hook pour le menu Newsletter
add_action('admin_menu', 'tb_newsletter_menu');

function tb_newsletter_menu() {

	add_menu_page(
		__('Newsletter', 'telabotanica'),
		__('Newsletter', 'telabotanica'),
		'manage_options',
		'newsletter',
		'',
		'dashicons-email-alt'
	);

	add_submenu_page(
		'newsletter',
		__('Envoyer', 'telabotanica'),
		__('Envoyer la newsletter', 'telabotanica'),
		'manage_options',
		'newsletter_send',
		'tb_newsletter_send'
	);

	add_submenu_page(
		'newsletter',
		__('Réglages', 'telabotanica'),
		__('Réglages', 'telabotanica'),
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

	if (empty($categories)) {
		return null;
	}

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

	if (is_wp_error($category) || !is_object($category)) {
		return null;
	}

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
 * Gets the event or job offer place.
 *
 * Address should be formatted like "D12, 81630 Saint-Urcisse, France"
 *
 * For addresses in France, returns "Town (departement number)". Eg: Paris (75)
 * For other countries, returns it. Eg: Chine
 * And if address doesn't fit in previous cases, returns it
 *
 * @param      int  $post_id  The post identifier
 *
 * @return     string  The event or job offer place.
 */
function get_place($post_id) {
	$details = get_field('place', $post_id);

	if (is_array($details)) {
		$place = $details['address'];

		if (preg_match('/^.*, (.*), France$/i', $details['address'], $matches)) {
			$departement_number = substr($matches[1], 0, 2);
			$town = substr($matches[1], 6);

			$place = $town . ' (' . $departement_number . ')';
		} elseif (preg_match('/^.*, (.*)$/i', $details['address'], $matches)) {
			$place = $matches[1];
		}

		return $place;
	} elseif (is_object($details)) {
		switch ($details->type) {
			case 'address':
				if ($details->city) {
					$place = $details->city;
				} else {
					$place = $details->name;
				}

				break;
			case 'city':
			case 'country':
			default:
				$place = $details->name;

				break;
		}

		if (in_array($details->type, ['address', 'city'])) {
			if ($details->countryCode === 'fr') {
				$place = $place . ' (' . substr($details->postcode, 0, 2) . ')';
			} else {
				$place = $place . ' ' . $details->country;
			}
		}

		return $place;
	} else {
		return false;
	}
}

function get_event_details($post_id) {
	return get_event_date($post_id) + [
		'details'	=> get_field('description', $post_id)
	];
}

function get_featured_post_details($post) {
	return get_post_details($post, true);
}

function get_post_details($post, $featured = false) {
	return [
		'post' 		=> $post,
		'intro'		=> get_field('intro', $post->ID),
		'author' 	=> get_the_author_meta('display_name', $post->post_author),
		'link' 		=> get_permalink($post->ID),
		'thumbnail'	=> get_the_post_thumbnail_url($post->ID, $featured ? 'home-latest-post' : 'thumbnail'),
		'date'		=> format_post_date($post->post_date),
		'place'		=> get_place($post->ID),
		'event'		=> get_event_details($post->ID)
	];
}

/**
 * Gets the newsletter subject.
 *
 * Tries to change locale to french to insert month name in subject
 *
 * @return     string  The subject.
 */
function get_subject() {
	$oldLocale = setlocale(LC_TIME, ['fr_FR.utf8', 'fr_FR', 'fr_FR@euro', 'fr']);

	$subject = 'Lettre d\'information de Tela Botanica du ' . strftime('%e %B %Y');

	setlocale(LC_TIME, $oldLocale);

	return $subject;
}

function get_newsletter() {
	require_once __DIR__ . '/../vendor/autoload.php';
	require_once __DIR__ . '/tela_twig_extension.php';
	$loader = new \Twig\Loader\FilesystemLoader(get_template_directory() . '/inc/newsletter');

	$twig = new \Twig\Environment($loader, []);
	$twig->addExtension(new \Twig\Extra\Intl\IntlExtension());
	$twig->addExtension(new \Twig\Extra\String\StringExtension());
	$twig->addExtension(new Twig_Extensions_Extension_Tela());
	$twig->getExtension(\Twig\Extension\CoreExtension::class)->setTimezone('Europe/Paris');

	if (have_rows('tb_newsletter_sections', 'option')) {
		$categories = [];
		$subcategories = [];
		$posts = [];
		$featured_post = [];

		while (have_rows('tb_newsletter_sections', 'option')) {
			the_row();

			foreach ((array) get_sub_field('tb_newsletter_sections_items') as $post) {
				// Featured posts "à la une" have to be handled separatly
				if (is_numeric($post)) {
					$post = get_post($post);
				}
				if (!$post) {
					continue;
				}
				if (true === get_field('featured', $post->ID)) {
					$featured_post = get_featured_post_details($post);

					continue;
				}

				$category = get_post_top_category($post->ID);
				if ($category) {
					$subcategory = get_sub_field('tb_newsletter_sections_title');

					$categories[$category->term_id] = [
						'slug'	=> $category->slug,
						'name'	=> $category->name,
						'url'	=> get_category_link($category)
					];

					$subcategories[$category->term_id][$subcategory->term_id] = $subcategory->name;

					$posts[$category->term_id][$subcategory->term_id][] = get_post_details($post);

					// Shortcodes are being incorrectly interpreted
					$posts[$category->term_id][$subcategory->term_id][0]['post']->post_content = strip_shortcodes($posts[$category->term_id][$subcategory->term_id][0]['post']->post_content);
				}
			}
		}
	}

	$params = [
		'logotela'  => get_template_directory_uri() . '/assets/images/logo-horizontal-blanc.png',
		'intro' 	=> get_field('tb_newsletter_introduction', 'option'),
		'categories' => $categories,
		'subcategories' => $subcategories,
		'featured'	=> $featured_post,
		'posts' 	=> $posts,
		'outro' 	=> get_field('tb_newsletter_footer', 'option')
	];

	return [
		'text' => $twig->render('newsletter-text.html', $params),
		'html' => $twig->render('newsletter-html.html', $params),
	];
}

/**
 * Sends a test newsletter to a single email via Brevo Transactional API.
 *
 * @param      string  $email  The test recipient email
 */
function send_test_newsletter($email) {
	$config = get_config();
	$brevo = new Brevo_API($config['brevo_api_key']);
	$newsletter = get_newsletter();
	$subject = get_subject();

	$sender = [
		'name' => $config['brevo_sender_name'],
		'email' => $config['brevo_sender_email'],
	];

	$brevo->send_transactional($email, $subject, $newsletter['html'], $newsletter['text'], $sender);
}

/**
 * Sends the newsletter to a Brevo contact list via Campaign API.
 *
 * @param      int  $list_id  The Brevo list ID
 */
function send_newsletter_to_list($list_id) {
	$config = get_config();
	$brevo = new Brevo_API($config['brevo_api_key']);
	$newsletter = get_newsletter();
	$subject = get_subject();

	$sender = [
		'name' => $config['brevo_sender_name'],
		'email' => $config['brevo_sender_email'],
	];

	$campaign = $brevo->create_campaign($subject, $newsletter['html'], $newsletter['text'], [$list_id], $sender);
	$brevo->send_campaign($campaign['id']);
}

function tb_newsletter_send() {
	$newsletter_config = get_config();
	$hidden_test_field = 'tb_submit_hidden_test';
	$hidden_send_field = 'tb_submit_hidden_send';

	if (!current_user_can('manage_options')) {
		wp_die( __('Vous n\'avez pas les droits suffisants pour accéder à cette page.', 'telabotanica') );
	}

	if (isset($_POST[$hidden_test_field]) && $_POST[$hidden_test_field] == 'Y'):
		$email = sanitize_email($_POST['newsletter_test_recipient']);
		$newsletter_config['newsletter_test_recipient'] = $email;
		update_option('tb_newsletter_config', json_encode($newsletter_config));
		try {
			send_test_newsletter($email);
			echo '<div class="updated"><p><strong>Newsletter de TEST envoyée à ' . esc_html($email) . '</strong></p></div>';
		} catch (\RuntimeException $e) {
			echo '<div class="error"><p><strong>Erreur lors de l\'envoi de test : ' . esc_html($e->getMessage()) . '</strong></p></div>';
		}
	elseif (isset($_POST[$hidden_send_field]) && $_POST[$hidden_send_field] == 'Y'):
		$list_id = intval($_POST['brevo_list_id']);
		try {
			send_newsletter_to_list($list_id);
			echo '<div class="updated"><p><strong>Newsletter envoyée à la liste sélectionnée</strong></p></div>';
		} catch (\RuntimeException $e) {
			echo '<div class="error"><p><strong>Erreur lors de l\'envoi : ' . esc_html($e->getMessage()) . '</strong></p></div>';
		}
	endif;

	$lists = [];
	$lists_error = '';
	if (!empty($newsletter_config['brevo_api_key'])) {
		try {
			$brevo = new Brevo_API($newsletter_config['brevo_api_key']);
			$lists = $brevo->get_lists();
		} catch (\RuntimeException $e) {
			$lists_error = $e->getMessage();
		}
	}
?>
	<div class="wrap">

		<?php screen_icon(); ?>

		<h2>Envoi de la newsletter</h2>

		<div class="description">
			<p>Page de prévisualisation et d'envoi de la newsletter</p>
		</div>

		<?php settings_errors(); ?>

		<form method="post" action="">
			<input type="hidden" name="<?php echo $hidden_test_field; ?>" value="Y">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="newsletter_test_recipient">Adresse de test</label>
						</th>
						<td>
							<input type="text" name="newsletter_test_recipient" id="newsletter_test_recipient" value="<?php echo esc_attr($newsletter_config['newsletter_test_recipient']); ?>" class="regular-text">
							<p class="description">
								Un exemplaire de la newsletter sera envoyé à cette adresse via Brevo.<br>
								Pour tester le rendu.
							</p>
						</td>
						<td>
							<input type="submit" name="Submit" class="button-primary" value="Tester la newsletter" />
						</td>
					</tr>
				</tbody>
			</table>
		</form>

		<hr>

		<div id="poststuff">

			<div id="post-body" class="metabox-holder columns-2">

				<div id="postbox-container-1" class="postbox-container">

					<div id="side-sortables" class="meta-box-sortables ui-sortable">
						<div id="submitdiv" class="postbox ">

							<h2 class="hndle ui-sortable-handle">
								<span>Envoyer</span>
							</h2>

							<div class="inside">
								<div id="major-publishing-actions">

									<div id="publishing-action">
										<span class="spinner"></span>

										<form method="post" action="">
											<input type="hidden" name="<?php echo $hidden_send_field; ?>" value="Y">

											<?php if (!empty($newsletter_config['brevo_api_key'])): ?>
												<p>
													<label for="brevo_list_id">Liste Brevo :</label>
													<select name="brevo_list_id" id="brevo_list_id" style="width: 100%;">
														<?php if (!empty($lists)): ?>
															<?php foreach ($lists as $list): ?>
																<option value="<?php echo intval($list['id']); ?>">
																	<?php echo esc_html($list['name']); ?> (<?php echo intval($list['totalSubscribers']); ?> abonnés)
																</option>
															<?php endforeach; ?>
														<?php elseif ($lists_error): ?>
															<option value="">Erreur : <?php echo esc_html($lists_error); ?></option>
														<?php else: ?>
															<option value="">Aucune liste trouvée</option>
														<?php endif; ?>
													</select>
												</p>
												<p class="submit">
													<input type="submit" name="Submit" class="button-primary" value="Envoyer la newsletter" />
												</p>
												<p class="howto">Après vérification évidemment</p>
											<?php else: ?>
												<p class="howto" style="color: #a00;">Configurez d'abord la clé API Brevo dans les Réglages.</p>
											<?php endif; ?>

										</form>
									</div>

									<div class="clear"></div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div id="postbox-container-2" class="postbox-container">

					<h2>Prévisualisation avant envoi</h2>

					<div id="post-body-content" style="position: relative;">
						<div class="card">

							<?php echo get_newsletter()['html'] ?>

						</div>
					</div>

				</div>

			</div>

		</div>
	</div>
<?php
}


function tb_newsletter_config() {

?>
	<div class="wrap">

		<?php
		if (!current_user_can('manage_options'))
		{
			wp_die( __('Vous n\'avez pas les droits suffisants pour accéder à cette page.', 'telabotanica') );
		}
		?>

		<?php screen_icon(); ?>

		<h2>Réglages de la newsletter</h2>

		<div class="description">
			<p>Configuration de l'API Brevo (ex Sendinblue) pour l'envoi de la newsletter.</p>
		</div>

		<?php settings_errors(); ?>

		<?php
		$hidden_field_name = 'tb_submit_hidden_config';
		$newsletter_config = get_config();

		if (isset($_POST[$hidden_field_name]) && $_POST[$hidden_field_name] == 'Y'):
			$newsletter_config['brevo_api_key'] = sanitize_text_field($_POST['brevo_api_key']);
			$newsletter_config['brevo_sender_email'] = sanitize_email($_POST['brevo_sender_email']);
			$newsletter_config['brevo_sender_name'] = sanitize_text_field($_POST['brevo_sender_name']);
			$newsletter_config['error_recipients_emails'] = preg_split('/\r\n|[\r\n]/', stripslashes($_POST['error_recipients_emails']));
			$newsletter_config['error_recipients_emails'] = array_filter($newsletter_config['error_recipients_emails']);

			update_option('tb_newsletter_config', json_encode($newsletter_config));
		?>
			<div class="updated">
				<p>
					<strong>Options mises à jour</strong>
				</p>
			</div>
		<?php endif; ?>

		<form method="post" action="">
			<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="brevo_api_key">Clé API Brevo</label>
						</th>
						<td>
							<input type="password" name="brevo_api_key" id="brevo_api_key" value="<?php echo esc_attr($newsletter_config['brevo_api_key']); ?>" class="regular-text" autocomplete="off">
							<p class="description">Clé API v3 depuis <a href="https://app.brevo.com/settings/keys/api" target="_blank">Brevo → Settings → API Keys</a></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="brevo_sender_email">Email expéditeur</label>
						</th>
						<td>
							<input type="email" name="brevo_sender_email" id="brevo_sender_email" value="<?php echo esc_attr($newsletter_config['brevo_sender_email']); ?>" class="regular-text">
							<p class="description">Doit être validé dans Brevo (Senderes → Email Addresses).</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="brevo_sender_name">Nom expéditeur</label>
						</th>
						<td>
							<input type="text" name="brevo_sender_name" id="brevo_sender_name" value="<?php echo esc_attr($newsletter_config['brevo_sender_name']); ?>" class="regular-text">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="error_recipients_emails">Destinataires des notifications d'erreur</label>
						</th>
						<td>
							<textarea id="error_recipients_emails" name="error_recipients_emails" rows="3" cols="80" class="regular-text"><?php echo esc_textarea(implode(PHP_EOL, $newsletter_config['error_recipients_emails'])); ?></textarea>
							<p class="description">
								Une adresse par ligne<br>
								Les lignes commençant par # seront ignorées
							</p>
						</td>
					</tr>
				</tbody>
			</table>
			<hr/>
			<p class="submit">
				<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
			</p>
		</form>
	</div>
<?php
}
