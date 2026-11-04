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

	// Étiquettes : redimensionnées sans recadrage (les permanentes sont en 2:1, les éphémères plutôt en 7:4).
	// Le cadre prend la proportion de chaque image : smash_label_ratio().
	add_image_size( 'smash-label', 1200, 0, false );
	add_image_size( 'smash-label-sm', 600, 0, false );
} );

// Pas d'emojis WordPress : inutile ici et ça charge un script.
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

// Titres d'archives sans préfixe (« Gamme : », « Catégorie : »…).
add_filter( 'get_the_archive_title_prefix', '__return_empty_string' );
