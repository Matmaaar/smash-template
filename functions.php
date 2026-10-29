<?php
/**
 * smash : chargement du thème.
 *
 * Tout est rangé dans inc/ ; ce fichier ne fait que l'inclure.
 */

defined( 'ABSPATH' ) || exit;

define( 'SMASH_VERSION', wp_get_theme( get_template() )->get( 'Version' ) );
define( 'SMASH_DIR', get_template_directory() );
define( 'SMASH_URI', get_template_directory_uri() );

/*
 * Groupes de champs « démo » des bières et de l'équipe.
 * À passer à false sur le vrai site : leurs groupes ACF existent déjà (voir PLAN.md §6).
 */
if ( ! defined( 'SMASH_DEMO_FIELDS' ) ) {
	define( 'SMASH_DEMO_FIELDS', true );
}

foreach ( array( 'setup', 'content-types', 'data', 'options', 'assets', 'template-tags', 'menus', 'age-gate', 'schema' ) as $smash_file ) {
	require SMASH_DIR . '/inc/' . $smash_file . '.php';
}
unset( $smash_file );
