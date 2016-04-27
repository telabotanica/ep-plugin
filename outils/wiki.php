<?php

/**
 * Pseudo-outil en attendant de faire mieux : un onglet qui affiche un lien vers
 * le wiki existant (Wikini) dans lea métadonnée "wiki-externe" du groupe
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
		echo "<h3>Retrouvez votre espace wiki à l'adresse ci-dessous</h3>";
		echo "<p>Prochainement, un wiki intégré sera disponible ici pour accueillir vos pages.</p>";
		$adresseWiki = groups_get_groupmeta(bp_get_group_id(), "wiki-externe");
		echo '<a style="font-size: 1.3em;" target="_blank" href="' . $adresseWiki . '">' . $adresseWiki . '</a>';
	}
}

bp_register_group_extension( 'Wiki' );
