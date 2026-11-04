<?php
/**
 * Données structurées (JSON-LD).
 *
 * Avec Rank Math (site actuel), on ajoute nos entités à son graphe ; sans lui, on les imprime nous-mêmes.
 * Brewery partout, Product sur les fiches bières, FAQPage sur la location, BlogPosting sur les articles.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Entités propres à la page courante, indexées par un identifiant.
 */
function smash_schema_entities() {
	$out = array();

	// La brasserie elle-même, sur tout le site (infos de la page d'options « Infos smash »).
	$address = trim( wp_strip_all_tags( str_replace( array( '<br>', '<br/>', '<br />' ), "\n", smash_option( 'address' ) ) ) );
	$lines   = array_values( array_filter( array_map( 'trim', explode( "\n", $address ) ) ) );
	$city    = $lines && preg_match( '/^(\d{5})\s+(.+)$/u', end( $lines ), $m ) ? $m : null;
	$brewery = array(
		'@type'     => 'Brewery',
		'@id'       => home_url( '/#brewery' ),
		'name'      => get_bloginfo( 'name' ),
		'url'       => home_url( '/' ),
		'logo'      => SMASH_URI . '/assets/img/logo-white.png',
		'telephone' => smash_option( 'phone' ),
		'email'     => smash_option( 'email' ),
		'sameAs'    => array_values( array_filter( array( smash_option( 'instagram' ), smash_option( 'facebook' ), smash_option( 'linkedin' ), smash_option( 'untappd' ) ) ) ),
		'hasMap'    => smash_option( 'map_url' ),
	);
	if ( $lines ) {
		$brewery['address'] = array_filter( array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => $city ? implode( ', ', array_slice( $lines, 0, -1 ) ) : implode( ', ', $lines ),
			'postalCode'      => $city[1] ?? '',
			'addressLocality' => $city[2] ?? '',
			'addressCountry'  => 'FR',
		) );
	}
	$out['smashBrewery'] = array_filter( $brewery );

	if ( is_singular( 'bieres' ) ) {
		$beer    = smash_beer();
		$product = array(
			'@type'       => 'Product',
			'@id'         => $beer['url'] . '#product',
			'name'        => $beer['name'],
			'url'         => $beer['url'],
			'description' => wp_strip_all_tags( $beer['intro'] ?: $beer['desc'] ),
			'brand'       => array( '@type' => 'Brand', 'name' => get_bloginfo( 'name' ) ),
			'category'    => $beer['style'],
		);
		if ( $beer['label'] ) {
			$product['image'] = wp_get_attachment_image_url( $beer['label'], 'full' );
		}
		if ( '' !== $beer['abv'] ) {
			$product['additionalProperty'] = array(
				'@type'    => 'PropertyValue',
				'name'     => __( "Degré d'alcool", 'smash' ),
				'value'    => (float) str_replace( ',', '.', $beer['abv'] ),
				'unitText' => '% vol.',
			);
		}
		$out['smashProduct'] = array_filter( $product );
	}

	// Articles du blog.
	if ( is_singular( 'post' ) ) {
		$post  = get_queried_object();
		$beers = smash_post_beers( $post );
		$image = has_post_thumbnail( $post ) ? get_the_post_thumbnail_url( $post, 'full' ) : ( $beers && $beers[0]['label'] ? wp_get_attachment_image_url( $beers[0]['label'], 'full' ) : '' );
		$org   = array( '@type' => 'Organization', 'name' => get_bloginfo( 'name' ), 'url' => home_url( '/' ) );
		$out['smashPost'] = array_filter( array(
			'@type'            => 'BlogPosting',
			'@id'              => get_permalink( $post ) . '#article',
			'headline'         => wp_strip_all_tags( get_the_title( $post ) ),
			'url'              => get_permalink( $post ),
			'datePublished'    => get_the_date( 'c', $post ),
			'dateModified'     => get_the_modified_date( 'c', $post ),
			'description'      => wp_strip_all_tags( get_the_excerpt( $post ) ),
			'image'            => $image,
			'author'           => $org,
			'publisher'        => $org,
			'mainEntityOfPage' => get_permalink( $post ),
		) );
	}

	// FAQ de la page location : les blocs « Détails » du contenu (question = résumé, réponse = le reste).
	if ( smash_page_slug( 'location' ) && is_page( smash_page_slug( 'location' ) ) ) {
		$questions = array();
		foreach ( parse_blocks( get_post_field( 'post_content', get_queried_object_id() ) ) as $block ) {
			if ( 'core/details' !== $block['blockName'] ) {
				continue;
			}
			$html = render_block( $block );
			if ( ! preg_match( '#<summary[^>]*>(.*?)</summary>#s', $html, $m ) ) {
				continue;
			}
			$answer = trim( wp_strip_all_tags( str_replace( $m[0], '', $html ) ) );
			if ( $answer ) {
				$questions[] = array(
					'@type'          => 'Question',
					'name'           => trim( wp_strip_all_tags( $m[1] ) ),
					'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $answer ),
				);
			}
		}
		if ( $questions ) {
			$out['smashFaq'] = array(
				'@type'      => 'FAQPage',
				'@id'        => get_permalink() . '#faq',
				'mainEntity' => $questions,
			);
		}
	}

	return $out;
}

add_filter( 'rank_math/json_ld', function ( $data ) {
	return array_merge( (array) $data, smash_schema_entities() );
}, 20 );

add_action( 'wp_head', function () {
	if ( class_exists( 'RankMath' ) ) {
		return; // Rank Math imprime le graphe, avec nos entités.
	}
	$entities = array_values( smash_schema_entities() );
	if ( ! $entities ) {
		return;
	}
	$graph = array( '@context' => 'https://schema.org', '@graph' => $entities );
	// JSON_HEX_TAG : un « </script> » dans un champ ne peut pas fermer la balise.
	echo '<script type="application/ld+json">' . wp_json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . "</script>\n";
} );
