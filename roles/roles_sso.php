<?php

/**
 * Ajoute des rôles spécifiques au SSO, qui donnent des droits sur les
 * différentes applications TB
 */
class RolesSSO {

	const STORAGE_OPTION_NAME = 'tb_roles_config';

	static function getConfig() {
		// chargement de la config depuis la BdD
		$roles_config = json_decode(get_option(self::STORAGE_OPTION_NAME), true);
		if (! $roles_config) {
			$roles_config = array();
		}
		// chargement de la config par défaut
		$roles_config_defaut = json_decode(file_get_contents(__DIR__ . '/roles_config.json'), true);

		// si un champ est vide on utilise celui par défaut
		$roles_config = array_merge($roles_config_defaut, $roles_config);

		return $roles_config;
	}

	/**
	 * Ajoute des rôles à la base de données, d'après la config; les rôles sont
	 * de la forme 'tb_{nom_appli}_{nom_rôle}' et leur description commence par
	 * "Tela Botanica → "
	 * 
	 * Les rôles sont conservés, il faut les remove() avant de pouvoir les
	 * redéfinir
	 * 
	 * N'exécuter cette méthode qu'à l'activation du plugin pour
	 * économiser des performances
	 */
	public static function ajout_roles($supprimerAuLieuDAjouter=false) {
		//throw new Exception("ajout roles"); exit;
		$config = self::getConfig();
		foreach ($config as $appli => $roles) {
			foreach ($roles as $role => $description) {
				$nom_role = 'tb_' . $appli . '_' . $role;
				$description_role = 'Tela Botanica → ' . $appli . " : " . $description;
				// ajout ou suppression
				if ($supprimerAuLieuDAjouter) {
					remove_role($nom_role);
				} else {
					add_role($nom_role, $description_role);
				}
			}
		}
	}
}
