<?php
/**
 * Types de contenus et taxonomies.
 *
 * Sur le vrai site, ils existent déjà (déclarés ailleurs que dans le thème actuel) :
 * on ne les déclare que s'ils sont absents, avec les mêmes slugs, pour que la démo
 * et la production partagent les mêmes URL.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	if ( ! post_type_exists( 'bieres' ) ) {
		register_post_type( 'bieres', array(
			'labels'       => array(
				'name'          => __( 'Bières', 'smash' ),
				'singular_name' => __( 'Bière', 'smash' ),
				'add_new_item'  => __( 'Ajouter une bière', 'smash' ),
				'edit_item'     => __( 'Modifier la bière', 'smash' ),
			),
			'public'       => true,
			'has_archive'  => false, // le catalogue est la page /nos-bieres/
			'rewrite'      => array( 'slug' => 'bieres', 'with_front' => false ),
			'menu_icon'    => 'dashicons-beer',
			'supports'     => array( 'title', 'thumbnail', 'excerpt', 'revisions' ),
			'show_in_rest' => true,
		) );
	}

	if ( ! post_type_exists( 'membre' ) ) {
		register_post_type( 'membre', array(
			'labels'             => array(
				'name'          => __( 'Équipe', 'smash' ),
				'singular_name' => __( 'Membre', 'smash' ),
			),
			'public'             => false,
			'show_ui'            => true,
			'menu_icon'          => 'dashicons-groups',
			'supports'           => array( 'title', 'thumbnail', 'page-attributes' ),
			'show_in_rest'       => true,
		) );
	}

	if ( ! post_type_exists( 'telechargement' ) ) {
		register_post_type( 'telechargement', array(
			'labels'       => array(
				'name'          => __( 'Téléchargements', 'smash' ),
				'singular_name' => __( 'Téléchargement', 'smash' ),
			),
			'public'       => false,
			'show_ui'      => true,
			'menu_icon'    => 'dashicons-download',
			'supports'     => array( 'title', 'page-attributes' ),
			'show_in_rest' => true,
		) );
	}

	if ( ! taxonomy_exists( 'type-de-bieres' ) ) {
		register_taxonomy( 'type-de-bieres', 'bieres', array(
			'labels'            => array(
				'name'          => __( 'Gammes', 'smash' ),
				'singular_name' => __( 'Gamme', 'smash' ),
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'type-de-bieres', 'with_front' => false ),
			'show_in_rest'      => true,
		) );
	}

	if ( ! taxonomy_exists( 'type-de-location' ) ) {
		register_taxonomy( 'type-de-location', 'bieres', array(
			'labels'            => array(
				'name'          => __( 'Location', 'smash' ),
				'singular_name' => __( 'Type de location', 'smash' ),
			),
			'hierarchical'      => true,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
		) );
	}
}, 20 );
