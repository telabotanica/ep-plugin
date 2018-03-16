<?php
namespace Migration\App\Users;

class BpPseudoBuilder {

  static public function buildPseudo($intitule) {
    $futur_pseudo = $intitule;
    // removes all tags
    $futur_pseudo = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $futur_pseudo );
    $futur_pseudo = strip_tags($futur_pseudo);
    $futur_pseudo = preg_replace('/[\r\n\t ]+/', ' ', $futur_pseudo);
    // Kill octets
    $futur_pseudo = preg_replace( '|%([a-fA-F0-9][a-fA-F0-9])|', '', $futur_pseudo );
    $futur_pseudo = preg_replace( '/&.+?;/', '', $futur_pseudo ); // Kill entities
    // Remplace les caractères accentués par le caractère correspondant
    $futur_pseudo = iconv('UTF-8', 'ASCII//TRANSLIT', $futur_pseudo);
    // Reduce to ASCII
    $futur_pseudo = preg_replace( '|[^a-z0-9 _.\-@]|i', '', $futur_pseudo );
    // Consolidate contiguous whitespace
    $futur_pseudo = mb_ereg_replace( '\s+', ' ', $futur_pseudo );
    // Retrait des espaces et autres caractères relous en début et fin
    $futur_pseudo = trim($futur_pseudo, " .-_\t\n\r\0\x0B");
    // on remplace les espaces par des tirets
    $futur_pseudo = mb_ereg_replace( ' ', '-', $futur_pseudo );
    // on passe tout en minuscules
    $futur_pseudo = mb_strtolower($futur_pseudo);
    // On coupe pour que ça dépasse pas 45 charactères
    $futur_pseudo = substr($futur_pseudo, 0, 45);

    if (empty($futur_pseudo)) {
        $futur_pseudo = 'Brandom';
    }

    return $futur_pseudo;
  }


}
