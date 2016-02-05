<?php
/*
Plugin Name: Tela Botanica
Description: Plugin permettant d'ajouter les outils de Tela Botanica à l'espace projets
Version: 1.0 BETA
Author: Tela Botanica
*/


/* Chargement du code nécessitant BuddyPress */
function initialisation_bp() {

	require( dirname( __FILE__ ) . '/description-complete.php' );
	require( dirname( __FILE__ ) . '/outils/porte-documents.php' );
	require( dirname( __FILE__ ) . '/outils/forum.php' );
	

}
add_action( 'bp_include', 'initialisation_bp' );
add_action( 'bp_include', 'description_complete' );



class TelaBotanica
{

	/* Constructeur de la classe TelaBotanica */
	public function __construct()
	{	

		/* On déclenche la fonction ajout_menu_admin lors du chargement des menus de WordPress */
		add_action('admin_menu',array($this,'ajout_menu_admin'));
		
		/* On lance la création de la table Outils Réglages lorsque le plugin est activé */
		register_activation_hook(__FILE__,array('TelaBotanica','installation'));
		
		/* On lance la supression de la table Outils Réglages lorsque le plugin est désinstallé */
		register_deactivation_hook(__FILE__,array('TelaBotanica','desinstallation'));
	
	}
	
	
	/* Méthode qui crée la table "{$wpdb->prefix}tb_outils_reglages" dans la base de données lors de l'installation du plugin */
	public function installation()
	{
		global $wpdb;
		$requete1 = "
			CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}tb_outils_reglages` (
			  `id_projet` bigint(11) NOT NULL,
			  `id_outil` varchar(50) NOT NULL,
			  `name` varchar(50) NOT NULL,
			  `prive` tinyint(1) NOT NULL,
			  `create_step_position` tinyint(3) NOT NULL,
			  `nav_item_position` tinyint(3) NOT NULL,
			  `enable_nav_item` tinyint(1) NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";
		$requete2 = "
			ALTER TABLE `{$wpdb->prefix}tb_outils_reglages`
			ADD PRIMARY KEY (`id_projet`,`id_outil`),
			ADD KEY `id_projet` (`id_projet`);
		";
		$requete3 = "
			ALTER TABLE `{$wpdb->prefix}tb_outils_reglages`
			ADD CONSTRAINT `fk_id-projet_id-group` FOREIGN KEY (`id_projet`) REFERENCES `{$wpdb->prefix}bp_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
		";
		$wpdb->query($requete1);
		$wpdb->query($requete2);
		$wpdb->query($requete3);	
	}
	
	
	/* Méthode qui supprime la table "{$wpdb->prefix}tb_outils_reglages" dans la base de données lors de la désinstallation du plugin */
	public function desinstallation()
	{
		/* Classe d'accès à la base de données dans WordPress */
		global $wpdb;
	
		/* On vérifie que la table existe puis on la supprime */
		$requete = "DROP TABLE IF EXISTS {$wpdb->prefix}tb_outils_reglages;";		
		$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}tb_outils_reglages;");
	}
	
	
	
	/* Méthode qui crée des menus ayant pour paramètres :
	 * - Titre de la page
	 * - Libellé du menu
	 * - Intitulé des droits
	 * - Clé d'identification du menu
	 * - La fonction de rendu à appeler
	 */
	public function ajout_menu_admin()
	{
		/* Menu */
		add_menu_page('Tela Botanica','Tela Botanica','manage_options','tela-botanica',array($this,'vue_presentation'));									
		/* Sous-menus */
		add_submenu_page('tela-botanica','Outils','Outils','manage_options','outils',array($this,'vue_outils'));								
	}
	
		
	
	/* Méthode qui affiche la vue Apercu */
	public function vue_presentation()
	{
		$titre = get_admin_page_title();
		/* On définit l'URL de la vue HTML */
		$url_html = plugin_dir_path(__FILE__).'backend/vue_presentation.html';
		/* On récupère la vue HTML et on l'affiche */
		$html = TelaBotanica::lecture_vue($url_html,array($titre,'Tela Botanica'));
		echo $html;
	}
	
	
	
	/* Méthode qui affiche la vue A propos */
	public function vue_outils()
	{
		$titre = get_admin_page_title();
		/* On définit l'URL de la vue HTML */
		$url_html = plugin_dir_path(__FILE__).'backend/vue_outils.html';
		/* On récupère la vue HTML et on l'affiche */
		$html = TelaBotanica::lecture_vue($url_html,array('Tela Botanica'));
		echo $html;
	}
	
	
	/* Méthode statique qui extrait du code HTML depuis une vue, avec en paramètres l'URL du fichier HTML et les variables PHP à faire passer à la méthode */
	public static function lecture_vue($html,$donnees = array())
	{
		$sortie = false;
		/* On vérifie que le fichier existe */
		if (file_exists($html))
		{
			/* On ouvre le buffer et on lit le fichier */
			ob_start();
			include $html;
			/* On stocke le contenu du buffer dans une variable de sortie */
			$sortie = ob_get_contents();
			/* On vide le buffer */	
			ob_end_clean();		
		}
		return $sortie;
	}
	
		
}

new TelaBotanica();
