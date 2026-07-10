<?php

class Brevo_API {
	private $api_key;
	private $base_url = 'https://api.brevo.com/v3';

	public function __construct($api_key) {
		$this->api_key = $api_key;
	}

	private function request($method, $path, $body = null) {
		$url = $this->base_url . $path;
		$args = [
			'method' => $method,
			'headers' => [
				'api-key' => $this->api_key,
				'Content-Type' => 'application/json',
				'Accept' => 'application/json',
			],
			'timeout' => 30,
		];

		if ($body !== null) {
			$args['body'] = wp_json_encode($body);
		}

		$response = wp_remote_request($url, $args);

		if (is_wp_error($response)) {
			throw new \RuntimeException($response->get_error_message());
		}

		$code = wp_remote_retrieve_response_code($response);
		$body = wp_remote_retrieve_body($response);
		$data = json_decode($body, true);

		if ($code < 200 || $code >= 300) {
			$message = isset($data['message']) ? $data['message'] : "Brevo API error (HTTP $code)";
			throw new \RuntimeException($message);
		}

		return $data;
	}

	public function get_lists() {
		$result = $this->request('GET', '/contacts/lists?limit=50');
		$lists = isset($result['lists']) ? $result['lists'] : [];
		if (WP_DEBUG && !empty($lists)) {
			error_log('Brevo API list keys: ' . implode(', ', array_keys($lists[0])));
		}
		return $lists;
	}

	public function create_campaign($subject, $html_content, $plain_text, $list_ids, $sender) {
		$body = [
			'name' => $subject,
			'subject' => $subject,
			'htmlContent' => $html_content,
			'plainText' => $plain_text,
			'recipients' => ['listIds' => $list_ids],
			'sender' => $sender,
			'type' => 'classic',
		];

		return $this->request('POST', '/emailCampaigns', $body);
	}

	public function send_campaign($campaign_id) {
		return $this->request('POST', "/emailCampaigns/{$campaign_id}/sendNow");
	}

	public function send_transactional($to_email, $subject, $html_content, $plain_text, $sender) {
		$body = [
			'to' => [['email' => $to_email]],
			'subject' => $subject,
			'htmlContent' => $html_content,
			'textContent' => $plain_text,
			'sender' => $sender,
		];

		return $this->request('POST', '/smtp/email', $body);
	}

	public function unsubscribe($email) {
		$body = [
			'emailBlacklisted' => true,
		];

		return $this->request('PATCH', "/contacts/{$email}", $body);
	}
}
