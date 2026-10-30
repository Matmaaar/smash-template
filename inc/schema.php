<?php
/**
 * Données structurées (JSON-LD).
 *
 * Avec Rank Math (site actuel), on ajoute nos entités à son graphe ; sans lui, on les imprime nous-mêmes.
 * Product sur les fiches bières, FAQPage sur la location. Brewery et BlogPosting arrivent à l'étape 5.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Entités propres à la page courante, indexées par un identifiant.
 */
function smash_schema_entities() {
	$out = array();

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

	// FAQ de la page location : les blocs « Détails » du contenu (question = résumé, réponse = le reste).
	if ( is_page( 'location-tireuses-a-bieres' ) ) {
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
	echo '<script type="application/ld+json">' . wp_json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
} );
