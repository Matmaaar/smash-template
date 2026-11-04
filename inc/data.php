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
		case 'file':
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
 * Charge en une fois les images (étiquettes, photos) d'une liste de bières.
 * Sans ça, WordPress lit chaque image avec 2 requêtes (contenu + métas) au moment de l'afficher.
 */
function smash_prime_images( $beers ) {
	$ids = array();
	foreach ( $beers as $beer ) {
		$ids[] = (int) ( $beer['label'] ?? 0 );
		$ids[] = (int) ( $beer['photo'] ?? 0 );
	}
	$ids = array_filter( array_unique( $ids ) );
	if ( $ids ) {
		_prime_post_caches( $ids, false, true );
	}
	return $beers;
}

/**
 * Les bières d'une gamme, dans l'ordre du menu (puis alphabétique).
 * Mémorisé pour la requête : le méga-menu, l'accueil et le catalogue demandent les mêmes listes.
 */
function smash_beers( $range, $limit = -1 ) {
	static $memo = array();
	$slug = smash_map( 'ranges' )[ $range ] ?? '';
	if ( ! $slug ) {
		return array();
	}
	$key = $range . '|' . $limit;
	if ( isset( $memo[ $key ] ) ) {
		return $memo[ $key ];
	}
	$posts = get_posts( array(
		'post_type'      => 'bieres',
		'posts_per_page' => $limit,
		'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		'tax_query'      => array( array( 'taxonomy' => 'type-de-bieres', 'field' => 'slug', 'terms' => $slug ) ),
	) );
	return $memo[ $key ] = smash_prime_images( array_map( 'smash_beer', $posts ) );
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
	return $post ? ( smash_post_beers_many( array( $post ) )[ $post->ID ] ?? array() ) : array();
}

/**
 * Les bières citées dans plusieurs articles, en une seule requête pour toute la liste
 * (liste du blog, aperçu du méga-menu). Mémorisé par article pour la requête.
 *
 * @param array $posts Articles (WP_Post ou ID).
 * @return array<int, array> ID de l'article => bières citées, dans l'ordre du texte.
 */
function smash_post_beers_many( $posts ) {
	static $memo = array();
	$slugs = array();
	$ids   = array();
	foreach ( $posts as $post ) {
		$post = get_post( $post );
		if ( ! $post ) {
			continue;
		}
		$ids[] = $post->ID;
		if ( isset( $memo[ $post->ID ] ) ) {
			continue;
		}
		$slugs[ $post->ID ] = preg_match_all( '#/bieres/([a-z0-9-]+)/?#i', $post->post_content, $m ) ? array_values( array_unique( array_map( 'sanitize_title', $m[1] ) ) ) : array(); // même normalisation que WordPress (« moonspell- » => « moonspell »)
	}
	$all = array_values( array_unique( array_merge( array(), ...array_values( $slugs ) ) ) );
	$by  = array();
	if ( $all ) {
		$found = get_posts( array(
			'post_type'      => 'bieres',
			'post_status'    => 'publish',
			'post_name__in'  => $all,
			'posts_per_page' => count( $all ),
		) );
		foreach ( smash_prime_images( array_map( 'smash_beer', $found ) ) as $i => $beer ) {
			$by[ $found[ $i ]->post_name ] = $beer;
		}
	}
	foreach ( $slugs as $id => $list ) {
		$memo[ $id ] = array_values( array_filter( array_map( fn( $slug ) => $by[ $slug ] ?? null, $list ) ) );
	}
	return array_intersect_key( $memo, array_flip( $ids ) );
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
	return smash_page( 'catalog' );
}

/**
 * Une page clé du site, par son rôle (slugs dans la section `pages` de inc/fields-map.php).
 *
 * @param string $role catalog, location, brewery, contact, downloads.
 */
function smash_page( $role ) {
	$slug = smash_map( 'pages' )[ $role ] ?? '';
	return $slug ? get_page_by_path( $slug ) : null;
}

/**
 * Slug d'une page clé (voir smash_page()).
 */
function smash_page_slug( $role ) {
	return (string) ( smash_map( 'pages' )[ $role ] ?? '' );
}

/**
 * Un fichier à télécharger : titre, URL, extension (pour l'affichage « pdf », « zip »…).
 */
function smash_download( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return null;
	}
	$f   = smash_fields( $post->ID, 'download' );
	$url = $f['file'] ? wp_get_attachment_url( $f['file'] ) : '';
	return array(
		'id'    => $post->ID,
		'title' => get_the_title( $post ),
		'url'   => $url,
		'ext'   => $url ? strtolower( pathinfo( wp_parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION ) ) : '',
	);
}

/* =========================================================================
 * Cache des fragments HTML (méga-menu) : invalidé à chaque modification de contenu.
 * ========================================================================= */

/**
 * Version du cache : changer de version rend tous les fragments obsolètes (ils expirent seuls ensuite).
 */
function smash_cache_version() {
	return (int) get_option( 'smash_cache_ver', 1 );
}

/**
 * Fragment HTML mis en cache 12 h (transient), calculé par $render() s'il manque.
 * Pas de cache dans l'aperçu du personnaliseur, ni si SMASH_NO_CACHE est vrai.
 */
function smash_cached_html( $key, callable $render ) {
	if ( is_customize_preview() || ( defined( 'SMASH_NO_CACHE' ) && SMASH_NO_CACHE ) ) {
		return $render();
	}
	$tkey = 'smash_html_' . md5( $key . '|' . smash_cache_version() . '|' . SMASH_VERSION . '|' . get_locale() );
	$html = get_transient( $tkey );
	if ( ! is_string( $html ) ) {
		$html = (string) $render();
		set_transient( $tkey, $html, 12 * HOUR_IN_SECONDS );
	}
	return $html;
}

/**
 * Vide le cache des fragments (nouvelle version).
 */
function smash_flush_cache() {
	update_option( 'smash_cache_ver', smash_cache_version() + 1, true );
}

add_action( 'save_post', function ( $post_id, $post ) {
	if ( wp_is_post_revision( $post_id ) || in_array( $post->post_type, array( 'smash_devis', 'customize_changeset' ), true ) ) {
		return;
	}
	smash_flush_cache();
}, 10, 2 );
foreach ( array( 'deleted_post', 'trashed_post', 'untrashed_post', 'add_attachment', 'attachment_updated', 'delete_attachment', 'created_term', 'edited_term', 'delete_term', 'wp_update_nav_menu', 'acf/save_post', 'switch_theme' ) as $smash_hook ) {
	add_action( $smash_hook, 'smash_flush_cache', 20, 0 );
}
foreach ( array( 'page_for_posts', 'page_on_front', 'date_format', 'blogname' ) as $smash_option ) {
	add_action( 'update_option_' . $smash_option, 'smash_flush_cache', 10, 0 );
}
unset( $smash_hook, $smash_option );
