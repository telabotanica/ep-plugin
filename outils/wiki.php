<?php

/**
 * Pseudo-outil en attendant de faire mieux : un onglet qui affiche un lien vers
 * le wiki existant (Wikini)
 */
class Wiki extends TB_Outil {

	function wiki()
	{
		// identifiant de l'outil et nom par défaut
		$this->slug = 'wiki';
		$this->name = 'Wiki';

		// init du parent
		$this->initialisation();
	}

	public function scriptsEtStylesApres()
	{
		//wp_enqueue_style('EzmlmForum-CSS', $this->urlOutil . 'css/ezmlm-forum-internal.css');
	}

	/*
	 * Vue onglet principal - affichage du pseudo-wiki dans la page
	 */
	function display($group_id = null)
	{
		echo "<h3>Retrouvez votre espace wiki ici :</h3>";
		$adresseWiki = groups_get_groupmeta(bp_get_group_id(), "espace-internet");
		echo '<a target="_blank" href="' . $adresseWiki . '">' . $adresseWiki . '</a>';
	}
}

bp_register_group_extension( 'Wiki' );
