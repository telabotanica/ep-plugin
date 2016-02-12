<?php

function description_complete() {
	
	// Retourne l'id du projet courant et la valeur meta_key de ce projet
	function get_value($meta_key='') {
		return groups_get_groupmeta(bp_get_group_id(), $meta_key);
	}
	
	// Formulaire pour la description complète du projet
	function description_complete_formulaire() {
		global $bp, $wpdb;
		?>
		<p class="editfield">
			<label for="description-complete">Description complète</label>
			<textarea style="width: 100%; max-width: 100%; min-height: 200px; resize: vertical;" id="description-complete" name="description-complete"><?php echo get_value('description-complete'); ?></textarea>
		</p>
		<?php
	}

	// Enregistrement des meta-données du projet
	function description_complete_enregistrement($id_projet) {
		global $bp, $wpdb;
		$tab_champs = array('description-complete');	// Plusieurs champs possibles
		foreach( $tab_champs as $champ ) {
			$key = $champ;
			if ( isset( $_POST[$key] ) ) {
				$valeur = $_POST[$key];
				groups_update_groupmeta( $id_projet, $champ, $valeur );
			}
		}
	}
	/* Ajout des filtres & actions */
	add_filter( 'groups_group_fields_editable', 'description_complete_formulaire' );
	add_filter( 'groups_custom_group_fields_editable', 'description_complete_formulaire' );
	add_action( 'groups_group_details_edited', 'description_complete_enregistrement' );
	add_action( 'groups_created_group',  'description_complete_enregistrement' );
 
	// Affichage du formulaire
	function affichage_description() {
		echo '<div id="description-complete">' . nl2br(get_value('description-complete')) . '</div>';
	}
	add_action('bp_group_header_meta' , 'affichage_description');
}



?>
