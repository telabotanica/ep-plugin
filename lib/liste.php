<?php

/**
 * Gère une liste de discussion ezmlm, en utilisant cURL pour envoyer des ordres
 * au service ezmlm-php, avec le jeton admin donnant tous les pouvoirs; lit ce
 * jeton, l'adresse du service ezmlm-php et le nom de l'entête d'autorisation à
 * lui fournir, dans la configuration "tb_general_config" qui se règle dans
 * "Tela Botanica" > "Sécurité" @TODO renommer cette section
 */
class TB_ListeEzmlm {

	protected $nomListe;
	protected $jetonAdmin;
	protected $urlRacineEzmlmPhp;
	protected $nomEnteteAuth;

	public function __construct($nomListe) {

		$this->nomListe = $nomListe;

		$securiteConfig = json_decode(get_option('tb_general_config'), true);
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

	/**
	 * Retourne true si la liste $this->nomListe existe, false sinon
	 */
	public function existe() {

		$existenceListe = false;

		$url = $this->urlRacineEzmlmPhp . '/lists/' . $this->nomListe;

		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_FAILONERROR => 1,
			CURLOPT_URL => $url
		));
		// jeton dans l'entête choisi (on ne s'occupe pas du domaine ici)
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			$this->nomEnteteAuth . ': ' . $this->jetonAdmin
		));

		curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($http_code == 200) {
			$existenceListe = true;
		}

		return $existenceListe;
	}

	/**
	 * Demande au service ezmlm de créer la liste configurée
	 */
	public function creer() {

		$listeCreee = false;

		$url = $this->urlRacineEzmlmPhp . '/lists';

		$ch = curl_init();
		$headers = array();
		$headers[] = 'Content-Type:application/json';
		// jeton dans l'entête choisi (on ne s'occupe pas du domaine ici)
		$headers[] = $this->nomEnteteAuth . ': ' . $this->jetonAdmin;

		curl_setopt_array($ch, array(
			CURLOPT_POST => 1,
			CURLOPT_POSTFIELDS => json_encode(array(
				"name" => $this->nomListe
			)),
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_FAILONERROR => 1,
			CURLOPT_URL => $url,
			CURLOPT_HTTPHEADER => $headers
		));

		curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		// un jour on retournera peut-être un 201:created (mieux)
		if (in_array($http_code, array(200, 201))) {
			$listeCreee = true;
		} else {
			// affichage rudimentaire pour dépanner l'admin
			echo curl_error($ch);
		}
		curl_close($ch);

		return $listeCreee;
	}

	/**
	 * Retourne true si l'utilisateur $emailUtilisateur est abonné à la liste en
	 * cours, false sinon
	 */
	public function statutAbonnement($emailUtilisateur) {

		$this->statutAbonnement = false;

		$url = $this->urlRacineEzmlmPhp . '/users/' . $emailUtilisateur . '/subscriber-of/' . $this->nomListe;

		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_FAILONERROR => 1,
			CURLOPT_URL => $url
		));
		// jeton dans l'entête choisi (on ne s'occupe pas du domaine ici)
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			$this->nomEnteteAuth . ': ' . $this->jetonAdmin
		));

		$resultat = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($http_code != 200) {
			return false;
		} else {
			return ($resultat === 'true'); // pas la peine de json_decode()r pour ça
		}
	}

	/**
	 * Si $nouvelEtatAbonnement est true, inscrit l'utilisateur en cours à la
	 * liste en cours en utilisant le service ezmlm; si $nouvelEtatAbonnement
	 * est false, le désinscrit
	 */
	public function modifierStatutAbonnement($nouvelEtatAbonnement, $emailUtilisateur) {

		$url = $this->urlRacineEzmlmPhp . '/lists/' . $this->nomListe . '/subscribers';

		$ch = curl_init();
		$headers = array();
		if ($nouvelEtatAbonnement === true) {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array("address" => $emailUtilisateur)));
			$headers[] = 'Content-Type:application/json';
		} else {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
			$url .= '/' . $emailUtilisateur;
		}

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
