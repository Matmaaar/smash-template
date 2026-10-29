<?php
/**
 * Réglages de la démo, à lancer APRÈS l'import de demo/demo-content.xml.
 *
 * - Playground : étape runPHP du blueprint.
 * - wp-env     : npx wp-env run cli wp eval-file wp-content/themes/smash/demo/setup.php
 *
 * Ne pas lancer sur le vrai site.
 */

if ( ! defined( 'ABSPATH' ) ) {
	require_once getenv( 'DOCROOT' ) ? getenv( 'DOCROOT' ) . '/wp-load.php' : '/wordpress/wp-load.php';
}

$smash_site = json_decode( file_get_contents( __DIR__ . '/site.json' ), true );

update_option( 'blogname', $smash_site['site']['name'] );
update_option( 'blogdescription', $smash_site['site']['description'] );
update_option( 'date_format', 'j F Y' );
update_option( 'timezone_string', 'Europe/Paris' );

// Page d'accueil statique et page des articles.
$smash_front = get_page_by_path( $smash_site['front_page'] );
$smash_posts = get_page_by_path( $smash_site['posts_page'] );
if ( $smash_front && $smash_posts ) {
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $smash_front->ID );
	update_option( 'page_for_posts', $smash_posts->ID );
}

// Infos globales (même stockage que la page d'options ACF : options_{nom}).
foreach ( $smash_site['options'] as $smash_key => $smash_value ) {
	update_option( 'options_' . $smash_key, $smash_value );
}

// Textes et images de l'accueil (champs du groupe « Accueil », stockés comme ACF : une méta par champ).
if ( $smash_front ) {
	foreach ( $smash_site['front'] as $smash_key => $smash_value ) {
		if ( is_string( $smash_value ) && 0 === strpos( $smash_value, 'page:' ) ) {
			$smash_page  = get_page_by_path( substr( $smash_value, 5 ) );
			$smash_value = $smash_page ? $smash_page->ID : '';
		}
		update_post_meta( $smash_front->ID, $smash_key, $smash_value );
	}
}

// En-têtes des pages intérieures (groupe « En-tête de page »).
foreach ( $smash_site['page_fields'] ?? array() as $smash_slug => $smash_fields ) {
	$smash_page = get_page_by_path( $smash_slug );
	if ( $smash_page ) {
		foreach ( $smash_fields as $smash_key => $smash_value ) {
			update_post_meta( $smash_page->ID, $smash_key, $smash_value );
		}
	}
}

// Contenus par défaut de WordPress : inutiles ici.
foreach ( array( 'hello-world' => 'post', 'sample-page' => 'page' ) as $smash_slug => $smash_type ) {
	$smash_post = get_page_by_path( $smash_slug, OBJECT, $smash_type );
	if ( $smash_post ) {
		wp_delete_post( $smash_post->ID, true );
	}
}

// Menus, à partir des slugs de pages.
$smash_locations = array();
foreach ( $smash_site['menus'] as $smash_location => $smash_slugs ) {
	$smash_name = 'smash-' . $smash_location;
	$smash_menu = wp_get_nav_menu_object( $smash_name );
	if ( $smash_menu ) {
		wp_delete_nav_menu( $smash_menu->term_id );
	}
	$smash_menu_id = wp_create_nav_menu( $smash_name );
	foreach ( $smash_slugs as $smash_position => $smash_slug ) {
		$smash_page = get_page_by_path( $smash_slug );
		if ( ! $smash_page ) {
			continue;
		}
		wp_update_nav_menu_item( $smash_menu_id, 0, array(
			'menu-item-object-id' => $smash_page->ID,
			'menu-item-object'    => 'page',
			'menu-item-type'      => 'post_type',
			'menu-item-status'    => 'publish',
			'menu-item-position'  => $smash_position + 1,
			'menu-item-title'     => mb_strtolower( $smash_page->post_title ),
		) );
	}
	// Bouton « devis tireuse » à droite du header : élément avec la classe CSS `cta`.
	if ( 'primary' === $smash_location && ! empty( $smash_site['cta'] ) ) {
		$smash_page = get_page_by_path( $smash_site['cta']['page'] );
		if ( $smash_page ) {
			wp_update_nav_menu_item( $smash_menu_id, 0, array(
				'menu-item-object-id' => $smash_page->ID,
				'menu-item-object'    => 'page',
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
				'menu-item-title'     => $smash_site['cta']['title'],
				'menu-item-classes'   => 'cta',
			) );
		}
	}
	$smash_locations[ $smash_location ] = $smash_menu_id;
}
set_theme_mod( 'nav_menu_locations', $smash_locations );

// Permaliens à la racine, comme le vrai site (met aussi à jour l'objet $wp_rewrite de cette requête).
global $wp_rewrite;
$wp_rewrite->set_permalink_structure( '/%postname%/' );
flush_rewrite_rules( false );
echo "smash : démo configurée\n";
