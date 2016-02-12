<?php

function categorie() {
	
	// Retourne l'id du projet courant et la valeur meta_key de ce projet
	function get_valeur($meta_key='') {
		return groups_get_groupmeta(bp_get_group_id(), $meta_key);
	}
	
	// Formulaire pour la catégorie du projet
	function categorie_formulaire() {
	
		global $bp, $wpdb;
		
		/* Lecture de la table "wp_tb_categories_projets" */
		$requete = "
			SELECT * 
			FROM {$wpdb->prefix}tb_categories_projets C, {$wpdb->prefix}bp_groups P
			GROUP BY C.id_categorie
		";
		$res = $wpdb->get_results($requete) ;
		
		?>
		<p class="editfield">
			<label for="categorie">Catégorie</label>
			<select id="categorie" name="categorie">
		<?php
	
		/* Construction de l'objet */
		foreach ($res as $meta) {	
			if ( $meta->nom_categorie == get_valeur('categorie') ) {
			?>
				<option value="<?php echo $meta->nom_categorie; ?>" selected><?php echo $meta->nom_categorie; ?></option>
			<?php
			}
			else {
			?>
				<option value="<?php echo $meta->nom_categorie; ?>"><?php echo $meta->nom_categorie; ?></option>
			<?php
			}
		}
		?>	
			</select>
		</p>
		<?php
	}

	// Enregistrement des meta-données du projet
	function categorie_enregistrement($id_projet) {
		global $bp, $wpdb;
		$tab_champs = array('categorie');	// Plusieurs champs possibles
		foreach( $tab_champs as $champ ) {
			$key = $champ;
			if ( isset( $_POST[$key] ) ) {
				$valeur = $_POST[$key];
				groups_update_groupmeta( $id_projet, $champ, $valeur );
			}
		}
	}
	/* Ajout des filtres & actions */
	add_filter( 'groups_group_fields_editable', 'categorie_formulaire' );
	add_filter( 'groups_custom_group_fields_editable', 'categorie_formulaire' );
	add_action( 'groups_group_details_edited', 'categorie_enregistrement' );
	add_action( 'groups_created_group', 'categorie_enregistrement' );
 
	// Affichage du formulaire
	function affichage_categorie($description) {
		$description .= '<div class="center categorie-projet">' . nl2br(get_value('categorie')) . '</div>';
		return $description;
	}
	add_filter('bp_get_group_avatar' , 'affichage_categorie');
}



?>
