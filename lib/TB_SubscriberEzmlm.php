<?php

require_once dirname(__FILE__) . '/TB_ListeEzmlmBase.php';

class TB_SubscriberEzmlm extends TB_ListeEzmlmBase {

	public function __construct() {

		parent::__construct();
	}

	public function deleteSubscriberFromAllLists($subscriberAddress) {
		$url = $this->urlRacineEzmlmPhp . '/' . $subscriberAddress;

		$ch = curl_init();
		$headers = array();
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");

		// jeton dans l'entête choisi (on ne s'occupe pas du domaine ici)
		$headers[] = $this->nomEnteteAuth . ': ' . $this->jetonAdmin;

		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_FAILONERROR => 1,
			CURLOPT_URL => $url,
			CURLOPT_HTTPHEADER => $headers
		));

		curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		// @TODO gérer les erreurs, vérifier que la commande a bien fonctionné
		if ($http_code != 200) {
			return false;
		}
		return true;
	}

}
