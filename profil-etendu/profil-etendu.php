<?php

require_once dirname(__FILE__) . '/../lib/liste.php';

/**
 * Définit ce qui se produit lorsqu'un utilisateur met à jour son profil étandu
 * (Buddypress xprofile) :
 *  - si la case à cocher "Souhaitez-vous recevoir la lettre d'actualités ?" a
 *    changé de valeur, on répercute ce changement sur l'abonnement via ezmlm
 *  - ...
 * 
 * Attention : ne gère pas les hooks lors du changement d'adresse email (profil
 * principal, voir /hooks/hooks.php)
 */
class TB_GestionProfilEtendu {

	/**
	 * Id du champ xprofile correspondant à la case à cocher "Souhaitez-vous
	 * recevoir la lettre d'actualités ?"
	 */
	protected $idChampLettreDActu;

	public function __construct() {
		// lecture fichier config.json
		$configPlugin = tbChargerConfigPlugin();
		$this->idChampLettreDActu = $configPlugin['profil']['id_case_inscription_lettre_actu'];

		// lorsqu'un utilisateur met à jour son profil
		add_action('xprofile_updated_profile', array($this, 'gererMiseAJourProfilEtendu'), 10, 5);
		// @TODO lorsqu'un utilisateur s'inscrit
		add_action('bp_core_activated_user', array($this, 'gererInscriptionProfilEtendu'), 10, 3);
	}

	public function gererInscriptionProfilEtendu($user_id, $key, $user) {
		// Statut : le champ étant [un groupe d']une case à cocher, si le nombre
		// d'éléments dans "value" est 1, c'est coché; si c'est 0 c'est décoché.
		// @WARNING ne pas changer le type de champ, ne pas rajouter d'options !
		$slugChamp = 'field_' . $this->idChampLettreDActu;
		if (isset($user['meta'][$slugChamp])) {
			$statut = $user['meta'][$slugChamp];
			if (is_array($statut)) {
				$statut = count($statut);
			}
			// utilisateur en cours - $user ne contient pas un vrai WP_User !
			$utilisateur = new WP_User($user_id);

			$this->testerEtRepercuterChangement(null, $statut, $utilisateur);
		}
	}

	public function gererMiseAJourProfilEtendu($user_id, $posted_field_ids, $errors, $old_values, $new_values) {
		// Id du champ xprofile correspondant à la case à cocher "Souhaitez-vous
		// recevoir la lettre d'actualités ?"
		$configPlugin = tbChargerConfigPlugin();
		$this->idChampLettreDActu = $configPlugin['profil']['id_case_inscription_lettre_actu'];

		// Statut : le champ étant [un groupe d']une case à cocher, si le nombre
		// d'éléments dans "value" est 1, c'est coché; si c'est 0 c'est décoché.
		// @WARNING ne pas changer le type de champ, ne pas rajouter d'options !
		$ancienStatut = $old_values[$this->idChampLettreDActu];
		if (is_array($ancienStatut)) {
			$ancienStatut = count($ancienStatut['value']);
		}
		$nouveauStatut = $new_values[$this->idChampLettreDActu];
		if (is_array($nouveauStatut)) {
			$nouveauStatut = count($nouveauStatut['value']);
		}

		// utilisateur en cours
		$utilisateur = new WP_User($user_id);

		$this->testerEtRepercuterChangement($ancienStatut, $nouveauStatut, $utilisateur);
	}

	public function testerEtRepercuterChangement($ancienStatut, $nouveauStatut, $utilisateur) {

		if (is_int($nouveauStatut)
			&& ($ancienStatut !== $nouveauStatut)
			&& ($ancienStatut !== null || $nouveauStatut === 1) // si l'utilisateur est nouveau et ne s'inscrit pas, on ne fait rien
			) {

			//var_dump($utilisateur);
			if (! empty($utilisateur->user_email)) { // devrait toujours être le cas
				// adresse de la liste "lettre d'actu"
				$configLettreActu = json_decode(get_option('tb_newsletter_config'), true);
				if (! empty($configLettreActu['newsletter_recipient'])) {
					// changement de statut dans ezmlm
					//echo "On " . ($nouveauStatut === 1 ? '' : 'dés') . "abonne " . $utilisateur->user_email . " à la liste " . $configLettreActu['newsletter_recipient'] . "<br>";
					// instance du gestionnaire de liste, pour la lettre d'actualités
					$nomListeLettreActu = substr($configLettreActu['newsletter_recipient'], 0, strpos($configLettreActu['newsletter_recipient'], '@'));
					$liste = new TB_ListeEzmlm($nomListeLettreActu);
					$liste->modifierStatutAbonnement(($nouveauStatut === 1), $utilisateur->user_email);
				}
			}
		} // else: pb dans l'évaluation des valeurs de la case à cocher => GFY
	}
}
