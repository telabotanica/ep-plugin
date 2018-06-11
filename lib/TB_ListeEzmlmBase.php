<?php

class TB_ListeEzmlmBase {

	protected $jetonAdmin;
	protected $urlRacineEzmlmPhp;
	protected $nomEnteteAuth;

	public function __construct() {

		$securiteConfig = json_decode(get_option('tb_general_config'), true);
		if (is_array($securiteConfig)) {
			// jeton SSO admin
			if (array_key_exists('adminToken', $securiteConfig)) {
				$this->jetonAdmin = $securiteConfig['adminToken'];
			}
			// URL racine du service ezmlm-php
			if (array_key_exists('ezmlmRootUri', $securiteConfig)) {
				$this->urlRacineEzmlmPhp = $securiteConfig['ezmlmRootUri'];
			}
			// entête d'autorisation, valeur standard par défaut
			$this->nomEnteteAuth = 'Authorization';
			if (array_key_exists('ezmlmAuthHeaderName', $securiteConfig)) {
				$this->nomEnteteAuth = $securiteConfig['ezmlmAuthHeaderName'];
			}
		}
	}
}
