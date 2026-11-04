<?php
/**
 * Réglages de base du thème : supports, menus, tailles d'images.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'smash', SMASH_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support( 'responsive-embeds' );

	register_nav_menus( array(
		'primary' => __( 'Menu principal', 'smash' ),
		'footer'  => __( 'Menu du pied de page', 'smash' ),
		'legal'   => __( 'Liens légaux', 'smash' ),
	) );

	// Les étiquettes sont au format 7:4 (celui des visuels du catalogue de smash.bzh).
	add_image_size( 'smash-label', 1200, 686, true );
	add_image_size( 'smash-label-sm', 600, 343, true );
} );

// Pas d'emojis WordPress : inutile ici et ça charge un script.
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

// Titres d'archives sans préfixe (« Gamme : », « Catégorie : »…).
add_filter( 'get_the_archive_title_prefix', '__return_empty_string' );

/*
 * Images : les tailles générées à l'envoi (vignettes, étiquettes…) sont enregistrées en WebP,
 * 25 à 35 % plus légères que le JPEG à qualité égale. WordPress garde le format d'origine
 * si le serveur ne sait pas écrire le WebP. Ne touche pas aux images déjà envoyées
 * (pour elles : « Regenerate Thumbnails » ou `wp media regenerate`).
 */
add_filter( 'image_editor_output_format', function ( $formats ) {
	$formats['image/jpeg'] = 'image/webp';
	return $formats;
} );

/*
 * Templates des pages clés : choisis selon les slugs de la section `pages` de inc/fields-map.php,
 * pas seulement d'après le nom du fichier (page-{slug}.php).
 */
add_filter( 'page_template_hierarchy', function ( $templates ) {
	$page = get_queried_object();
	if ( ! $page instanceof WP_Post ) {
		return $templates;
	}
	$files = array(
		'catalog'   => 'page-nos-bieres.php',
		'location'  => 'page-location-tireuses-a-bieres.php',
		'brewery'   => 'page-la-brasserie.php',
		'contact'   => 'page-contact.php',
		'downloads' => 'page-telechargements.php',
	);
	foreach ( $files as $role => $file ) {
		if ( smash_page_slug( $role ) === $page->post_name ) {
			array_unshift( $templates, $file );
		}
	}
	return $templates;
} );
