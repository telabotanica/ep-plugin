<?php

class Forum extends BP_Group_Extension {

	function forum() {
		$this->name = 'FORUM';
		$this->slug = 'forum';
		$this->create_step_position = 21;
		$this->nav_item_position = 1001;
	}

	function create_screen() {
		if ( !bp_is_group_creation_step( $this->slug ) )
		return false;
		?>
		The HTML for my creation step goes here.
		<?php
		wp_nonce_field( 'groups_create_save_' . $this->slug );
	}

	function create_screen_save() {
		global $bp;
		check_admin_referer( 'groups_create_save_' . $this->slug );
		/* Save any details submitted here */
		groups_update_groupmeta( $bp->groups->new_group_id, 'my_meta_name', 'value' );
	}

	function edit_screen() {
		if ( !bp_is_group_admin_screen( $this->slug ) )
		return false; ?>
		<h3>Paramètres de l'outil <?php echo $this->name ?></h3>
		<?php
		wp_nonce_field( 'groups_edit_save_' . $this->slug );
	}

	function edit_screen_save() {
		global $bp;
		if ( !isset( $_POST ) )
		return false;
		check_admin_referer( 'groups_edit_save_' . $this->slug );
		/* Insert your edit screen save code here */
		/* To post an error/success message to the screen, use the following */
		if ( !$success )
		bp_core_add_message( __( 'There was an error saving, please try again', 'buddypress' ), 'error' );
		else
		bp_core_add_message( __( 'Settings saved successfully', 'buddypress' ) );
		bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . '/admin/' . $this->slug );
	}

	function display() {
		?>Onglet <?php echo $this->name;
	}

	function widget_display() { ?>
		<div class="info-group">
		<h4>name ) ?></h4>
		You could display a small snippet of information from your group extension here. It will show on the group
		home screen.
		</div>
		<?php
	}
	
}

bp_register_group_extension( 'Forum' );


?>
