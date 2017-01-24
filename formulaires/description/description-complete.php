<?php

function description_complete() {

	// Retourne la valeur meta_key pour le projet courant
	function get_value($meta_key) {
		$id = bp_get_group_id();
		if ($id != null) {
			return groups_get_groupmeta($id, $meta_key);
		} else {
			return '';
		}
	}

	// Formulaire pour la description complète du projet
	function description_complete_formulaire() {
		global $bp, $wpdb;
		?>
		<p class="editfield">
			<label for="description-complete"><?php _e("Description complète", 'telabotanica') ?></label>
			<span class="description"><?php _e("Sera affichée sur la page d'accueil du projet", 'telabotanica') ?></span>
			<?php
			wp_editor(get_value('description-complete'), 'description-complete', array(
				'media_buttons' => false,
				//'teeny' => true,
				'quicktags' => false
			));
			?>
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
}
