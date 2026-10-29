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
		'age_title'   => array( 'text', __( 'Porte d\'âge : question', 'smash' ) ),
		'age_yes'     => array( 'text', __( 'Porte d\'âge : bouton oui', 'smash' ) ),
		'age_no'      => array( 'text', __( 'Porte d\'âge : bouton non', 'smash' ) ),
		'age_refused' => array( 'text', __( 'Porte d\'âge : message si non', 'smash' ) ),
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

	// Textes de la page d'accueil (front-page.php).
	$fields = array();
	foreach ( smash_front_fields() as $name => list( $type, $label ) ) {
		$field = array(
			'key'   => 'field_smash_front_' . $name,
			'name'  => $name,
			'label' => $label,
			'type'  => $type,
		);
		if ( 'textarea' === $type ) {
			$field += array( 'rows' => 2, 'new_lines' => '' );
		} elseif ( 'image' === $type ) {
			$field += array( 'return_format' => 'id', 'preview_size' => 'medium' );
		} elseif ( 'page_link' === $type ) {
			$field = array_merge( $field, array( 'type' => 'post_object', 'post_type' => array( 'page' ), 'return_format' => 'id', 'allow_null' => 1 ) );
		} elseif ( 'tab' === $type ) {
			$field['placement'] = 'top';
		}
		$fields[] = $field;
	}
	acf_add_local_field_group( array(
		'key'            => 'group_smash_front',
		'title'          => __( 'Accueil', 'smash' ),
		'fields'         => $fields,
		'location'       => array( array( array( 'param' => 'page_type', 'operator' => '==', 'value' => 'front_page' ) ) ),
		'hide_on_screen' => array( 'the_content' ),
	) );

	// En-tête des pages intérieures (template-parts/page-head.php).
	acf_add_local_field_group( array(
		'key'      => 'group_smash_page_head',
		'title'    => __( 'En-tête de page', 'smash' ),
		'position' => 'acf_after_title',
		'fields'   => array(
			array( 'key' => 'field_smash_intro_kick', 'name' => 'intro_kick', 'label' => __( 'Sur-titre', 'smash' ), 'type' => 'text' ),
			array( 'key' => 'field_smash_intro_title', 'name' => 'intro_title', 'label' => __( 'Titre affiché (H1)', 'smash' ), 'type' => 'textarea', 'rows' => 2, 'new_lines' => '', 'instructions' => __( 'Retour à la ligne = saut de ligne dans le titre. Vide : titre de la page.', 'smash' ) ),
			array( 'key' => 'field_smash_intro_text', 'name' => 'intro_text', 'label' => __( 'Texte d\'introduction', 'smash' ), 'type' => 'textarea', 'rows' => 3, 'new_lines' => '' ),
		),
		'location' => array( array(
			array( 'param' => 'post_type', 'operator' => '==', 'value' => 'page' ),
			array( 'param' => 'page_type', 'operator' => '!=', 'value' => 'front_page' ),
		) ),
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

/**
 * Champs de la page d'accueil : nom => [type ACF, libellé].
 * Les titres en `textarea` passent à la ligne comme dans la maquette.
 */
function smash_front_fields() {
	return array(
		'tab_hero'        => array( 'tab', __( 'Hero', 'smash' ) ),
		'hero_kick'       => array( 'text', __( 'Sur-titre', 'smash' ) ),
		'hero_title'      => array( 'textarea', __( 'Titre (H1)', 'smash' ) ),
		'hero_text'       => array( 'textarea', __( 'Texte', 'smash' ) ),
		'hero_btn_label'  => array( 'text', __( 'Bouton : texte', 'smash' ) ),
		'hero_btn_link'   => array( 'page_link', __( 'Bouton : page', 'smash' ) ),
		'hero_image'      => array( 'image', __( 'Image de fond', 'smash' ) ),
		'tab_perm'        => array( 'tab', __( 'Gamme permanente', 'smash' ) ),
		'perm_kick'       => array( 'text', __( 'Sur-titre', 'smash' ) ),
		'perm_title'      => array( 'textarea', __( 'Titre', 'smash' ) ),
		'perm_btn_label'  => array( 'text', __( 'Bouton : texte', 'smash' ) ),
		'perm_btn_link'   => array( 'page_link', __( 'Bouton : page', 'smash' ) ),
		'tab_series'      => array( 'tab', __( 'Dernière série', 'smash' ) ),
		'series_kick'     => array( 'text', __( 'Sur-titre (suivi de la date du dernier article)', 'smash' ) ),
		'series_btn_label'=> array( 'text', __( 'Bouton : texte', 'smash' ) ),
		'tab_wall'        => array( 'tab', __( 'Mur d\'étiquettes', 'smash' ) ),
		'wall_kick'       => array( 'text', __( 'Sur-titre', 'smash' ) ),
		'wall_title'      => array( 'textarea', __( 'Titre', 'smash' ) ),
		'wall_text'       => array( 'textarea', __( 'Texte', 'smash' ) ),
		'tab_brew'        => array( 'tab', __( 'La brasserie', 'smash' ) ),
		'brew_kick'       => array( 'text', __( 'Sur-titre', 'smash' ) ),
		'brew_text'       => array( 'textarea', __( 'Texte', 'smash' ) ),
		'brew_btn_label'  => array( 'text', __( 'Bouton : texte', 'smash' ) ),
		'brew_btn_link'   => array( 'page_link', __( 'Bouton : page', 'smash' ) ),
		'brew_image'      => array( 'image', __( 'Image', 'smash' ) ),
		'tab_loc'         => array( 'tab', __( 'Location', 'smash' ) ),
		'loc_kick'        => array( 'text', __( 'Sur-titre', 'smash' ) ),
		'loc_title'       => array( 'textarea', __( 'Titre', 'smash' ) ),
		'loc_text'        => array( 'textarea', __( 'Texte', 'smash' ) ),
		'loc_btn_label'   => array( 'text', __( 'Bouton : texte', 'smash' ) ),
		'loc_btn_link'    => array( 'page_link', __( 'Bouton : page', 'smash' ) ),
		'loc_image'       => array( 'image', __( 'Image', 'smash' ) ),
		'tab_insta'       => array( 'tab', __( 'Instagram', 'smash' ) ),
		'insta_kick'      => array( 'text', __( 'Sur-titre', 'smash' ) ),
		'insta_title'     => array( 'textarea', __( 'Titre', 'smash' ) ),
		'insta_btn_label' => array( 'text', __( 'Bouton : texte (lien = Instagram dans « Infos smash »)', 'smash' ) ),
		'insta_image_1'   => array( 'image', __( 'Image 1', 'smash' ) ),
		'insta_image_2'   => array( 'image', __( 'Image 2', 'smash' ) ),
		'insta_image_3'   => array( 'image', __( 'Image 3', 'smash' ) ),
	);
}
