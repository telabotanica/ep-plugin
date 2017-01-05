/**
 * Restreint le choix des catégories de projet (group type) à une et une seule,
 * en appliquant au jeu de cases à cocher un comportement de boutons radio;
 * au moment du chargement, une case doit être déjà cochée : dans le cas
 * contraire, ce script n'interdira pas de n'en cocher aucune
 */
jQuery(document).ready( function() {
	var jq = jQuery;
	// toutes les catégories
	var categories = jq('.group-create-types .checkbox input[name="group-types[]"]');
	// lorsqu'on clique sur une catégorie
	categories.click(function() {
		var coche = jq(this).prop("checked");
		if (coche) { // si on coche
			// décocher toutes les autres catégories
			jq(this).closest('.checkbox').siblings('.checkbox').each(function() {
				var categ = jq(this).find('input[name="group-types[]"]');
				categ.prop("checked", false);
			});
		} else { // sinon, si on décoche... ben on n'a pas le droit
			jq(this).prop("checked", true);
		}
	});
});
