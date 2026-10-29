<?php
/**
 * Pages d'options et groupes de champs déclarés par le thème.
 *
 * Nécessite ACF ou Secure Custom Fields. Sans eux, le site s'affiche quand même :
 * les valeurs sont lues directement dans les métas (inc/data.php).
 */

defined( 'ABSPATH' ) || exit;

add_action( 'acf/init', function () {
	if ( function_exists( 'acf_add_options_page' ) ) {
		acf_add_options_page( array(
			'page_title' => __( 'Infos smash', 'smash' ),
			'menu_title' => __( 'Infos smash', 'smash' ),
			'menu_slug'  => 'smash-infos',
			'capability' => 'edit_pages',
			'icon_url'   => 'dashicons-store',
			'position'   => 30,
			'redirect'   => false,
		) );
	}

	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	// Infos globales, réutilisées partout (header, footer, contact, données structurées).
	$infos = array(
		'address'   => array( 'textarea', __( 'Adresse', 'smash' ) ),
		'hours'     => array( 'textarea', __( 'Horaires', 'smash' ) ),
		'phone'     => array( 'text', __( 'Téléphone', 'smash' ) ),
		'email'     => array( 'email', __( 'E-mail', 'smash' ) ),
		'map_url'   => array( 'url', __( 'Lien itinéraire', 'smash' ) ),
		'instagram' => array( 'url', __( 'Instagram', 'smash' ) ),
		'facebook'  => array( 'url', __( 'Facebook', 'smash' ) ),
		'linkedin'  => array( 'url', __( 'LinkedIn', 'smash' ) ),
		'untappd'   => array( 'url', __( 'Untappd', 'smash' ) ),
		'warning'   => array( 'text', __( 'Mention « abus d\'alcool »', 'smash' ) ),
	);
	$fields = array();
	foreach ( $infos as $name => list( $type, $label ) ) {
		$fields[] = array(
			'key'       => 'field_smash_opt_' . $name,
			'name'      => $name,
			'label'     => $label,
			'type'      => $type,
			'rows'      => 3,
			'new_lines' => 'br',
		);
	}
	acf_add_local_field_group( array(
		'key'      => 'group_smash_infos',
		'title'    => __( 'Infos smash', 'smash' ),
		'fields'   => $fields,
		'location' => array( array( array( 'param' => 'options_page', 'operator' => '==', 'value' => 'smash-infos' ) ) ),
	) );

	// Groupes « démo » des bières et de l'équipe, générés depuis le mapping.
	// Sur le vrai site, leurs propres groupes ACF prennent le relais (SMASH_DEMO_FIELDS = false).
	if ( ! SMASH_DEMO_FIELDS ) {
		return;
	}
	$acf_types = array( 'image' => 'image', 'html' => 'wysiwyg', 'number' => 'text', 'text' => 'text' );
	foreach ( array( 'beer' => 'bieres', 'member' => 'membre' ) as $section => $post_type ) {
		$fields = array();
		foreach ( smash_map( $section ) as $name => $field ) {
			$fields[] = array(
				'key'           => 'field_smash_' . $section . '_' . $name,
				'name'          => $field['key'],
				'label'         => $field['label'],
				'type'          => $acf_types[ $field['type'] ],
				'return_format' => 'id',
				'preview_size'  => 'medium',
				'media_upload'  => 0,
				'tabs'          => 'visual',
				'toolbar'       => 'basic',
			);
		}
		acf_add_local_field_group( array(
			'key'      => 'group_smash_demo_' . $section,
			'title'    => 'beer' === $section ? __( 'Fiche bière', 'smash' ) : __( 'Membre', 'smash' ),
			'fields'   => $fields,
			'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => $post_type ) ) ),
		) );
	}
} );
