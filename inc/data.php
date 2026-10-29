<?php
/**
 * Accès aux données : la seule couche qui lit les champs.
 *
 * Fonctionne avec ACF / Secure Custom Fields, ou sans (lecture directe des métas).
 */

defined( 'ABSPATH' ) || exit;

/**
 * Le mapping de inc/fields-map.php, ou une de ses sections.
 */
function smash_map( $section = null ) {
	static $map = null;
	if ( null === $map ) {
		$map = (array) apply_filters( 'smash_fields_map', require SMASH_DIR . '/inc/fields-map.php' );
	}
	return null === $section ? $map : ( $map[ $section ] ?? array() );
}

/**
 * Valeur brute d'un champ d'un contenu (ou de la page d'options avec $post_id = 'option').
 */
function smash_raw( $post_id, $key ) {
	if ( function_exists( 'get_field' ) ) {
		return get_field( $key, $post_id, false );
	}
	if ( 'option' === $post_id ) {
		return get_option( 'options_' . $key );
	}
	return get_post_meta( $post_id, $key, true );
}

/**
 * Convertit une valeur brute selon le type déclaré dans le mapping.
 */
function smash_cast( $value, $type ) {
	switch ( $type ) {
		case 'image':
			if ( is_array( $value ) ) {
				$value = $value['ID'] ?? $value['id'] ?? $value['url'] ?? 0;
			}
			if ( is_string( $value ) && ! is_numeric( $value ) && '' !== $value ) {
				$value = attachment_url_to_postid( $value );
			}
			return (int) $value;
		case 'number':
			return '' === $value || null === $value ? '' : str_replace( '.', ',', (string) (float) str_replace( ',', '.', (string) $value ) );
		case 'html':
			return wp_kses_post( (string) $value );
		default:
			return is_scalar( $value ) ? trim( (string) $value ) : '';
	}
}

/**
 * Lit tous les champs d'une section du mapping pour un contenu.
 */
function smash_fields( $post_id, $section ) {
	$out = array();
	foreach ( smash_map( $section ) as $name => $field ) {
		$out[ $name ] = smash_cast( smash_raw( $post_id, $field['key'] ), $field['type'] );
	}
	return $out;
}

/**
 * Gamme d'une bière : 'perm', 'eph', 'old' ou ''.
 */
function smash_beer_range( $post_id ) {
	foreach ( smash_map( 'ranges' ) as $range => $slug ) {
		if ( has_term( $slug, 'type-de-bieres', $post_id ) ) {
			return $range;
		}
	}
	return '';
}

/**
 * Une fiche bière, prête à afficher.
 */
function smash_beer( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return null;
	}
	return array_merge(
		smash_fields( $post->ID, 'beer' ),
		array(
			'id'    => $post->ID,
			'name'  => get_the_title( $post ),
			'url'   => get_permalink( $post ),
			'range' => smash_beer_range( $post->ID ),
		)
	);
}

/**
 * Les bières d'une gamme, dans l'ordre du menu (puis alphabétique).
 */
function smash_beers( $range, $limit = -1 ) {
	$slug = smash_map( 'ranges' )[ $range ] ?? '';
	if ( ! $slug ) {
		return array();
	}
	$posts = get_posts( array(
		'post_type'      => 'bieres',
		'posts_per_page' => $limit,
		'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		'tax_query'      => array( array( 'taxonomy' => 'type-de-bieres', 'field' => 'slug', 'terms' => $slug ) ),
	) );
	return array_map( 'smash_beer', $posts );
}

/**
 * Un membre de l'équipe.
 */
function smash_member( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return null;
	}
	return array_merge( smash_fields( $post->ID, 'member' ), array(
		'id'   => $post->ID,
		'name' => get_the_title( $post ),
	) );
}

/**
 * Les bières citées dans un article : l'équipe met déjà des liens /bieres/xxx/ dans le texte.
 */
function smash_post_beers( $post = null ) {
	$post = get_post( $post );
	if ( ! $post || ! preg_match_all( '#/bieres/([a-z0-9-]+)/?#i', $post->post_content, $m ) ) {
		return array();
	}
	$beers = array();
	foreach ( array_unique( $m[1] ) as $slug ) {
		$beer = get_page_by_path( $slug, OBJECT, 'bieres' );
		if ( $beer && 'publish' === $beer->post_status ) {
			$beers[] = smash_beer( $beer );
		}
	}
	return $beers;
}

/**
 * Une info globale de la page d'options « Infos smash ».
 */
function smash_option( $name, $default = '' ) {
	$value = smash_raw( 'option', $name );
	return is_scalar( $value ) && '' !== (string) $value ? (string) $value : $default;
}

/**
 * Un champ de la page d'accueil (voir smash_front_fields() dans inc/options.php).
 */
function smash_front( $name ) {
	$front = (int) get_option( 'page_on_front' );
	$value = $front ? smash_raw( $front, $name ) : '';
	return is_scalar( $value ) ? trim( (string) $value ) : '';
}

/**
 * URL d'un champ lien : ID de page => permalien, sinon l'URL telle quelle.
 */
function smash_link_url( $value ) {
	if ( is_numeric( $value ) && (int) $value ) {
		return get_permalink( (int) $value ) ?: '';
	}
	return is_string( $value ) ? $value : '';
}

/**
 * Le dernier article publié (bloc « dernière série » de l'accueil).
 */
function smash_latest_post() {
	$posts = get_posts( array( 'numberposts' => 1, 'post_type' => 'post' ) );
	return $posts ? $posts[0] : null;
}

/**
 * Un champ texte d'une page (ex. intro_kick, intro_title, intro_text).
 */
function smash_page_field( $post_id, $name ) {
	$value = $post_id ? smash_raw( $post_id, $name ) : '';
	return is_scalar( $value ) ? trim( (string) $value ) : '';
}

/**
 * Gammes du catalogue : clé du thème => ancre d'URL (liens du méga-menu, onglets).
 */
function smash_range_anchors() {
	return array( 'perm' => 'permanentes', 'eph' => 'ephemeres', 'old' => 'anciennes' );
}

/**
 * La page catalogue « nos bières » (slug du site actuel).
 */
function smash_catalog_page() {
	return get_page_by_path( 'nos-bieres' );
}
