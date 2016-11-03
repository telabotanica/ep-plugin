<?php

class Hooks {

	const STORAGE_OPTION_NAME = 'tb_hooks_config';

	function __construct() {
		add_action('profile_update', array($this, 'callCaptainHooks'), 10, 2);
	}

	/**
	 * Retourne un tableau de config des hooks
	 *
	 * @return     array
	 */
	private function chargerHooksConfig() {
		// chargement de la config depuis la BdD
		$hooks_config = json_decode(get_option(self::STORAGE_OPTION_NAME), true);

		// si elle est vide on charge celle par défaut
		if (empty($hooks_config)) {
			$hooks_config = json_decode(file_get_contents(__DIR__ . '/hooks_config.json'), true);
		}

		return $hooks_config;
	}

	private function handleErrors($ch) {
		$message = 'Uhoh, we\'ve got a problemouz ! ' . "\r\n"
			. "\r\n"
			. 'URL du hook appelé : ' . curl_getinfo($ch, CURLINFO_EFFECTIVE_URL) . "\r\n"
			. 'et son code d\'erreur : ' . curl_error($ch)
		;

		$headers = 'Content-Type: text/plain; charset="utf-8"' . "\r\n"
			. 'Content-Transfer-Encoding: 8bit' . "\r\n"
			. 'From: wp-hooks@tela-botanica.org' . "\r\n"
			. 'Reply-To: no-reply@example.com' . "\r\n"
			. 'X-Mailer: PHP/' . phpversion()
		;

		foreach ($this->chargerHooksConfig()['error-recipients-emails'] as $error_recipient) {
			if ('' != $error_recipients) {
				error_log($message, 1, $error_recipient, $headers);
			}
		}
	}

	function callCaptainHooks($user_id, $old_user_data) {
		$user = get_userdata( $user_id );

		if ($old_user_data->user_email != $user->user_email) {
			foreach ($this->chargerHooksConfig()['email-modification-urls'] as $hook_service_pattern) {
				if ('' != $hook_service_pattern) {
					$count = 0;

					$hook_service_url = preg_replace(
						array('/{old_email}/', '/{new_email}/', '/{user_id}/'),
						array($old_user_data->user_email, $user->user_email, $user_id),
						$hook_service_pattern, -1, $count
					);

					if ($count > 0) {
						$ch = curl_init();
						curl_setopt_array($ch, array(
							CURLOPT_RETURNTRANSFER => 1,
							CURLOPT_FAILONERROR => 1,
							CURLOPT_URL => $hook_service_url
						));

						curl_exec($ch);

						$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
						if ($http_code != 200) {
							$this->handleErrors($ch);
						}

						curl_close($ch);
					}
				}
			}
		}
	}
}

