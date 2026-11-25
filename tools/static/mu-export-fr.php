<?php
/**
 * WordPress local d'export uniquement (pas de fichiers de traduction ici) : site en français,
 * mois et jours traduits pour les dates affichées dans la copie statique.
 */
add_filter( 'locale', fn() => 'fr_FR' );
add_filter( 'gettext', function ( $t, $text, $domain ) {
	static $fr = array(
		'January' => 'janvier', 'February' => 'février', 'March' => 'mars', 'April' => 'avril', 'May' => 'mai', 'June' => 'juin',
		'July' => 'juillet', 'August' => 'août', 'September' => 'septembre', 'October' => 'octobre', 'November' => 'novembre', 'December' => 'décembre',
		'Monday' => 'lundi', 'Tuesday' => 'mardi', 'Wednesday' => 'mercredi', 'Thursday' => 'jeudi', 'Friday' => 'vendredi', 'Saturday' => 'samedi', 'Sunday' => 'dimanche',
	);
	return 'default' === $domain && isset( $fr[ $text ] ) ? $fr[ $text ] : $t;
}, 10, 3 );
