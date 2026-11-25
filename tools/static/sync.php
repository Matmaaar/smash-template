<?php
/**
 * Mise à jour du WordPress local d'export SANS tout réimporter (tools/static/sync.sh).
 *
 * Relit tools/demo/data.json (accueil, en-têtes et champs des pages, options) et l'applique au site
 * déjà en marche. Les images « img:… » absentes de la médiathèque sont ajoutées (une seule fois) ;
 * celles déjà présentes sont retrouvées par leur nom de fichier.
 *
 * Usage local uniquement : refuse toute requête qui ne vient pas de la machine elle-même.
 * Exclu du thème livré (dossier tools/, voir .distignore).
 */

require '/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

if ( ! in_array( $_SERVER['REMOTE_ADDR'] ?? '', array( '127.0.0.1', '::1' ), true ) ) {
	http_response_code( 403 );
	exit;
}

$theme = get_theme_root() . '/smash';
$data  = json_decode( file_get_contents( $theme . '/tools/demo/data.json' ), true );
$added = array();

/** ID de la pièce jointe pour « img:fichier » (maquette/img/) ou « img:scraped/… » (demo/scraped/). */
function smash_sync_image( $rel, $theme, &$added ) {
	$src  = 0 === strpos( $rel, 'scraped/' ) ? $theme . '/demo/' . $rel : $theme . '/maquette/img/' . $rel;
	$name = pathinfo( $rel, PATHINFO_FILENAME );
	$hit  = get_posts( array( 'post_type' => 'attachment', 'name' => sanitize_title( $name ), 'posts_per_page' => 1, 'post_status' => 'inherit', 'fields' => 'ids' ) );
	if ( $hit ) {
		return (int) $hit[0];
	}
	if ( ! is_file( $src ) ) {
		return 0;
	}
	$tmp = wp_tempnam( basename( $src ) );
	copy( $src, $tmp );
	$id = media_handle_sideload( array( 'name' => basename( $src ), 'tmp_name' => $tmp ), 0, $name );
	if ( is_wp_error( $id ) ) {
		return 0;
	}
	$added[] = $rel;
	return (int) $id;
}

/** Valeur de démo → valeur stockée : « img:… » = ID d'image, « page:slug » = ID de page. */
function smash_sync_value( $value, $theme, &$added ) {
	if ( ! is_string( $value ) ) {
		return $value;
	}
	if ( 0 === strpos( $value, 'img:' ) ) {
		return smash_sync_image( substr( $value, 4 ), $theme, $added );
	}
	if ( 0 === strpos( $value, 'page:' ) ) {
		$page = get_page_by_path( substr( $value, 5 ) );
		return $page ? $page->ID : '';
	}
	return $value;
}

foreach ( $data['options'] ?? array() as $key => $value ) {
	update_option( 'options_' . $key, smash_sync_value( $value, $theme, $added ) );
}
$front = (int) get_option( 'page_on_front' );
foreach ( $data['front'] ?? array() as $key => $value ) {
	update_post_meta( $front, $key, smash_sync_value( $value, $theme, $added ) );
}
foreach ( $data['page_fields'] ?? array() as $slug => $fields ) {
	$page = get_page_by_path( $slug );
	if ( $page ) {
		foreach ( $fields as $key => $value ) {
			update_post_meta( $page->ID, $key, smash_sync_value( $value, $theme, $added ) );
		}
	}
}

header( 'Content-Type: text/plain; charset=utf-8' );
echo 'ok : ' . count( $added ) . " image(s) ajoutée(s)\n" . implode( "\n", $added );
