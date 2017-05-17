<?php
/**
 * Rediriger vers un profil utilisateur public en fonction de son ID
 */

require_once __DIR__ . "/../../../wp-load.php";

$id = false;
// si le paramètre "id" est fourni
if (! empty($_GET['id']) && is_numeric($_GET['id'])) {
	$id = $_GET['id'];
} else { // sinon on fouille dans l'URL
	$parsedUrl = wp_parse_url("//".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
	if (! empty($parsedUrl['path'])) {
		$urlParts = explode('/', $parsedUrl['path']);
		if (count($urlParts > 0)) {
			$lastPart = array_pop($urlParts);
			while ($lastPart == "") {
				$lastPart = array_pop($urlParts);
			}
			if (is_numeric($lastPart)) {
				$id = $lastPart;
			}
		}
	}
}

// recherche de l'URL du profil de l'utilisateur par son ID
// si l'utilisateur n'existe pas, le permalink pointera sur la liste des utilisateurs (annuaire)
$user = new WP_User($id);
$permalink = bp_core_get_user_domain($id);
wp_redirect($permalink);
