<?php

function description_courte() {
	
	// Retourne l'id du projet courant et la valeur meta_key de ce projet
	function ajout_champ($meta_key='') {
		return groups_get_groupmeta(bp_get_group_id(), $meta_key);
	}
	
	require( dirname( __FILE__ ) . '/tela-botanica' );
	// Formulaire pour la description courte du projet
	function description_courte_formulaire() {
		global $bp, $wpdb;
		?>
		<label for="description-courte">* Description courte</label>
		<input id="description-courte" type="text" name="description-courte" value="<?php echo ajout_champ('description-courte'); ?>" />
		<?php 	echo "<script>window.alert('BOUH')</script>";
	}

	// Enregistrement des meta-données du projet
	function description_courte_enregistrement($id_projet) {
		global $bp, $wpdb;
		$tab_champs = array('description-courte');	// Plusieurs champs possibles
		foreach( $tab_champs as $champ ) {
			$key = $champ;
			if ( isset( $_POST[$cle] ) ) {
				$valeur = $_POST[$cle];
				groups_update_groupmeta( $id_projet, $champ, $valeur );
			}
		}
	}
	
	add_filter( 'groups_group_fields_editable', 'description_courte_formulaire' );
	add_filter( 'groups_custom_group_fields_editable', 'description_courte_formulaire' );
	add_action( 'groups_group_details_edited', 'description_courte_enregistrement' );
	add_action( 'groups_created_group',  'description_courte_enregistrement' );
 
	// Affichage du formulaire
	function affichage() {
		echo '<p id="description-courte"> Description courte : ' . ajout_champ('description-courte') . '</p>';
	}
	
	add_action('bp_group_header_meta' , 'affichage') ;
}

add_action( 'bp_include', 'description_courte' );

?>
