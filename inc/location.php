<?php
/**
 * Location de tireuses : tarifs, calcul du devis, envoi.
 *
 * Une seule source de prix :
 * - production : les produits du formulaire Gravity Forms de location (n° dans fields-map.php) ;
 * - démo / formulaire absent : champs « prix du fût » des bières + page d'options « Location ».
 * Les règles (tireuse offerte, livraison pro, caution…) sont toujours dans la page d'options « Location ».
 *
 * L'envoi passe par la route REST smash/v1/devis, qui recalcule tout côté serveur puis
 * soumet le formulaire Gravity Forms (notifications et exports inchangés) ou, en démo,
 * enregistre une « demande de devis » dans l'admin.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Le formulaire Gravity Forms de location, s'il existe sur le site.
 */
function smash_gf_location_form() {
	$id = (int) ( smash_map( 'gf' )['location'] ?? 0 );
	if ( ! $id || ! class_exists( 'GFAPI' ) ) {
		return null;
	}
	$form = GFAPI::get_form( $id );
	return is_array( $form ) ? $form : null;
}

/**
 * Un champ d'un formulaire Gravity Forms, par ID.
 */
function smash_gf_field( $form, $field_id ) {
	foreach ( (array) ( $form['fields'] ?? array() ) as $field ) {
		if ( (int) ( is_object( $field ) ? $field->id : $field['id'] ) === (int) $field_id ) {
			return is_object( $field ) ? $field : (object) $field;
		}
	}
	return null;
}

/**
 * Nombre depuis une saisie « 146,00 € », « 0.3 »…
 */
function smash_to_number( $value ) {
	if ( class_exists( 'GFCommon' ) ) {
		return (float) GFCommon::to_number( $value );
	}
	$value = preg_replace( '/[^\d,.\-]/', '', (string) $value );
	return (float) str_replace( ',', '.', $value );
}

/**
 * Montant affiché : « 146 € », « 0,30 € ».
 */
function smash_money( $amount ) {
	$amount = (float) $amount;
	$text   = floor( $amount ) === $amount ? number_format( $amount, 0, ',', ' ' ) : number_format( $amount, 2, ',', ' ' );
	return $text . ' €';
}

/**
 * Règles de location (page d'options « Location »), avec les valeurs du site actuel par défaut.
 */
function smash_location_rules() {
	$days = function ( $name, $default ) {
		return array_map( 'intval', array_filter( array_map( 'trim', explode( ',', smash_option( $name, $default ) ) ), 'strlen' ) );
	};
	return array(
		'free_tap_from'          => (int) smash_option( 'free_tap_from', '3' ),
		'pro_free_delivery_from' => (int) smash_option( 'pro_free_delivery_from', '12' ),
		'delivery_radius'        => (int) smash_option( 'delivery_radius', '15' ),
		'deposit'                => smash_to_number( smash_option( 'deposit', '200' ) ),
		'cup_lost_price'         => smash_to_number( smash_option( 'cup_lost_price', '1' ) ),
		'pickup_days'            => $days( 'pickup_days', '3,4,5' ),
		'return_days'            => $days( 'return_days', '1,2' ),
		'pro_gear'               => array_values( array_filter( array_map( 'trim', explode( "\n", smash_option( 'pro_gear' ) ) ) ) ),
	);
}

/**
 * Tarifs de location, normalisés (mis en cache pour la requête).
 *
 * @return array{source:string, kegs:array, taps:array, cups:array, rules:array}
 */
function smash_rental() {
	static $rental = null;
	if ( null !== $rental ) {
		return $rental;
	}

	$map  = smash_map( 'gf_location' );
	$form = smash_gf_location_form();
	$kegs = array();

	if ( $form ) {
		$price = function ( $field_id ) use ( $form ) {
			$field = smash_gf_field( $form, $field_id );
			return $field ? smash_to_number( $field->basePrice ?? 0 ) : null;
		};
		foreach ( $map['kegs'] as $slug => list( $part, $pro ) ) {
			$post  = get_page_by_path( $slug, OBJECT, 'bieres' );
			$field = smash_gf_field( $form, $part );
			if ( ! $post || ! $field ) {
				continue;
			}
			$kegs[] = array(
				'beer'   => smash_beer( $post ),
				'price'  => smash_to_number( $field->basePrice ?? 0 ),
				// Le volume est dans le libellé du produit : « Rye Bomb - Triple Belge - 8° - 20L ».
				'volume' => preg_match( '/(\d+)\s*L\b/i', (string) $field->label, $m ) ? $m[1] . ' L' : '',
				'gf'     => array( $part, $pro ),
			);
		}
		$taps = array( 't1' => $price( $map['taps']['t1'] ), 't2' => $price( $map['taps']['t2'] ) );
		$cups = array();
		foreach ( $map['cups'] as $size => list( $part ) ) {
			$cups[ $size ] = $price( $part );
		}
	} else {
		$posts = get_posts( array(
			'post_type'      => 'bieres',
			'posts_per_page' => -1,
			'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
			'tax_query'      => array( array( 'taxonomy' => 'type-de-location', 'operator' => 'EXISTS' ) ),
		) );
		foreach ( $posts as $post ) {
			$beer = smash_beer( $post );
			if ( '' === $beer['keg_price'] ) {
				continue;
			}
			$kegs[] = array(
				'beer'   => $beer,
				'price'  => smash_to_number( $beer['keg_price'] ),
				'volume' => $beer['keg_volume'],
				'gf'     => $map['kegs'][ $post->post_name ] ?? array(),
			);
		}
		$taps = array(
			't1' => smash_to_number( smash_option( 'tap1_price', '50' ) ),
			't2' => smash_to_number( smash_option( 'tap2_price', '80' ) ),
		);
		$cup  = smash_to_number( smash_option( 'cup_price', '0.3' ) );
		$cups = array( '20' => $cup, '50' => $cup );
	}

	$rental = array(
		'source' => $form ? 'gf' : 'demo',
		'kegs'   => $kegs,
		'taps'   => $taps,
		'cups'   => $cups,
		'rules'  => smash_location_rules(),
	);
	return $rental;
}

/**
 * Prix de location d'une bière en fût (bouton de la fiche), ou null.
 */
function smash_keg_price( $beer_id ) {
	foreach ( smash_rental()['kegs'] as $keg ) {
		if ( (int) $keg['beer']['id'] === (int) $beer_id ) {
			return $keg['price'];
		}
	}
	return null;
}

/**
 * Calcule un devis à partir de la saisie (mêmes règles que assets/js/pages/location.js).
 *
 * @param array $in { prof, kegs: {slug: qty}, eph, taps: {t1, t2}, gear: [], cups: 0|20|50, cups_n }
 */
function smash_rental_quote( $in ) {
	$rental = smash_rental();
	$rules  = $rental['rules'];
	$pro    = 'pro' === ( $in['prof'] ?? 'part' );
	$qty    = function ( $v, $max = 999 ) {
		return max( 0, min( $max, (int) $v ) );
	};
	$lines  = array();
	$total  = 0.0;
	$count  = 0;
	$kegs   = array();

	foreach ( $rental['kegs'] as $keg ) {
		$slug = get_post_field( 'post_name', $keg['beer']['id'] );
		$n    = $qty( $in['kegs'][ $slug ] ?? 0 );
		if ( ! $n ) {
			continue;
		}
		$count        += $n;
		$kegs[ $slug ] = array( 'qty' => $n, 'keg' => $keg );
		$lines[]       = array( sprintf( '%d × %s', $n, $keg['beer']['name'] ), $pro ? null : $n * $keg['price'] );
		$total        += $pro ? 0 : $n * $keg['price'];
	}
	$eph = $qty( $in['eph'] ?? 0 );
	if ( $eph ) {
		$count  += $eph;
		$lines[] = array( sprintf( __( '%d × éphémère (sur demande)', 'smash' ), $eph ), null );
	}

	$free = ! $pro && $count >= $rules['free_tap_from'];
	$taps = array( 't1' => 0, 't2' => 0 );
	$gear = array();
	if ( ! $pro ) {
		$taps = array( 't1' => $qty( $in['taps']['t1'] ?? 0, 99 ), 't2' => $qty( $in['taps']['t2'] ?? 0, 99 ) );
		if ( $taps['t1'] ) {
			$paid = $free ? $taps['t1'] - 1 : $taps['t1'];
			if ( $free ) {
				$lines[] = array( __( '1 tireuse 1 bec (offerte)', 'smash' ), 0.0 );
			}
			if ( $paid ) {
				$lines[] = array( sprintf( __( '%d × tireuse 1 bec', 'smash' ), $paid ), $paid * $rental['taps']['t1'] );
				$total  += $paid * $rental['taps']['t1'];
			}
		}
		if ( $taps['t2'] ) {
			$lines[] = array( sprintf( __( '%d × tireuse 2 becs', 'smash' ), $taps['t2'] ), $taps['t2'] * $rental['taps']['t2'] );
			$total  += $taps['t2'] * $rental['taps']['t2'];
		}
	} else {
		$gear = array_values( array_intersect( $rules['pro_gear'], (array) ( $in['gear'] ?? array() ) ) );
		foreach ( $gear as $item ) {
			$lines[] = array( $item, null );
		}
	}

	$size  = (string) (int) ( $in['cups'] ?? 0 );
	$cupsn = isset( $rental['cups'][ $size ] ) ? $qty( $in['cups_n'] ?? 0, 100000 ) : 0;
	if ( $cupsn ) {
		$amount  = $cupsn * $rental['cups'][ $size ];
		$lines[] = array( sprintf( __( '%1$d éco-cups %2$s cl', 'smash' ), $cupsn, $size ), $pro ? null : $amount );
		$total  += $pro ? 0 : $amount;
	}

	return compact( 'pro', 'lines', 'total', 'count', 'kegs', 'eph', 'free', 'taps', 'gear', 'size', 'cupsn' );
}

/**
 * Récapitulatif texte d'un devis (mail, admin, champ « précisions »).
 */
function smash_quote_summary( $quote ) {
	$out = array();
	foreach ( $quote['lines'] as list( $label, $amount ) ) {
		$out[] = '- ' . $label . ' : ' . ( null === $amount ? __( 'sur devis', 'smash' ) : smash_money( $amount ) );
	}
	$out[] = sprintf( __( 'Total TTC : %s', 'smash' ), $quote['pro'] ? __( 'sur devis', 'smash' ) : smash_money( $quote['total'] ) );
	return implode( "\n", $out );
}

/* =========================================================================
 * Envoi : route REST smash/v1/devis
 * ========================================================================= */

add_action( 'init', function () {
	// Demandes reçues quand Gravity Forms est absent (démo Playground).
	register_post_type( 'smash_devis', array(
		'labels'          => array(
			'name'          => __( 'Demandes de devis (démo)', 'smash' ),
			'singular_name' => __( 'Demande de devis', 'smash' ),
		),
		'public'          => false,
		'show_ui'         => ! smash_gf_location_form(),
		'menu_icon'       => 'dashicons-clipboard',
		'supports'        => array( 'title', 'editor' ),
		'capability_type' => 'post',
		'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
		'map_meta_cap'    => true,
	) );
} );

add_action( 'rest_api_init', function () {
	register_rest_route( 'smash/v1', '/devis', array(
		'methods'             => 'POST',
		'permission_callback' => '__return_true',
		'callback'            => 'smash_rest_devis',
	) );
} );

/**
 * Traite une demande de devis.
 */
function smash_rest_devis( WP_REST_Request $request ) {
	$in = (array) $request->get_json_params();

	if ( ! wp_verify_nonce( (string) $request->get_header( 'X-Smash-Nonce' ), 'smash_devis' ) ) {
		return new WP_Error( 'smash_nonce', __( 'La page a expiré : recharge-la puis renvoie ta demande.', 'smash' ), array( 'status' => 403 ) );
	}
	if ( ! empty( $in['website'] ) ) { // champ piège : rempli = robot
		return rest_ensure_response( array( 'ok' => true ) );
	}

	$contact = array(
		'name'  => sanitize_text_field( $in['name'] ?? '' ),
		'email' => sanitize_email( $in['email'] ?? '' ),
		'phone' => sanitize_text_field( $in['phone'] ?? '' ),
		'event' => sanitize_text_field( $in['event'] ?? '' ),
		'notes' => sanitize_textarea_field( $in['notes'] ?? '' ),
	);
	$errors  = array();
	if ( '' === $contact['name'] ) {
		$errors['name'] = __( 'Indique ton nom.', 'smash' );
	}
	if ( ! is_email( $contact['email'] ) ) {
		$errors['email'] = __( 'Indique un e-mail valide.', 'smash' );
	}
	if ( '' === $contact['phone'] ) {
		$errors['phone'] = __( 'Indique ton téléphone.', 'smash' );
	}
	$dates = array();
	foreach ( array( 'pickup', 'return' ) as $key ) {
		$date          = DateTime::createFromFormat( '!Y-m-d', (string) ( $in[ $key ] ?? '' ) );
		$dates[ $key ] = $date ?: null;
	}

	$quote = smash_rental_quote( $in );
	if ( ! $quote['count'] && ! $quote['taps']['t1'] && ! $quote['taps']['t2'] && ! $quote['gear'] ) {
		$errors['kegs'] = __( 'Ajoute au moins un fût ou une tireuse.', 'smash' );
	}
	if ( $errors ) {
		return new WP_Error( 'smash_invalid', implode( ' ', $errors ), array( 'status' => 422, 'fields' => $errors ) );
	}

	$form = smash_gf_location_form();
	if ( $form ) {
		$result = GFAPI::submit_form( (int) $form['id'], smash_gf_location_values( $form, $quote, $contact, $dates ) );
		if ( is_wp_error( $result ) ) {
			return new WP_Error( 'smash_gf', $result->get_error_message(), array( 'status' => 500 ) );
		}
		if ( empty( $result['is_valid'] ) ) {
			$messages = array_map( 'wp_strip_all_tags', (array) ( $result['validation_messages'] ?? array() ) );
			return new WP_Error( 'smash_gf_invalid', implode( ' ', $messages ) ?: __( "Le formulaire n'a pas été accepté.", 'smash' ), array( 'status' => 422 ) );
		}
		return rest_ensure_response( array( 'ok' => true, 'entry' => (int) ( $result['entry_id'] ?? 0 ) ) );
	}

	// Démo : la demande est enregistrée dans l'admin (« Demandes de devis (démo) »).
	$body = implode( "\n", array_filter( array(
		$contact['name'] . ' · ' . $contact['email'] . ' · ' . $contact['phone'],
		$quote['pro'] ? __( 'Pro / association', 'smash' ) . ( $contact['event'] ? ' : ' . $contact['event'] : '' ) : __( 'Particulier', 'smash' ),
		sprintf( __( 'Retrait : %1$s · retour : %2$s', 'smash' ), $dates['pickup'] ? $dates['pickup']->format( 'd/m/Y' ) : '?', $dates['return'] ? $dates['return']->format( 'd/m/Y' ) : '?' ),
		'',
		smash_quote_summary( $quote ),
		$contact['notes'] ? "\n" . $contact['notes'] : '',
	), 'strlen' ) );
	$id = wp_insert_post( array(
		'post_type'    => 'smash_devis',
		'post_status'  => 'private',
		'post_title'   => sprintf( '%s · %s', $contact['name'], wp_date( 'd/m/Y H:i' ) ),
		'post_content' => $body,
	) );
	return rest_ensure_response( array( 'ok' => (bool) $id ) );
}

/**
 * Valeurs à soumettre au formulaire Gravity Forms de location (mêmes champs qu'aujourd'hui).
 */
function smash_gf_location_values( $form, $quote, $contact, $dates ) {
	$map    = smash_map( 'gf_location' );
	$col    = $quote['pro'] ? 1 : 0; // colonne particulier / pro des champs produits
	$values = array();

	// Qui : 1er choix = particulier, 2e = pro (valeurs lues dans le formulaire).
	$who = smash_gf_field( $form, $map['who'] );
	if ( $who && ! empty( $who->choices[ $col ] ) ) {
		$choice                         = (array) $who->choices[ $col ];
		$values[ 'input_' . $map['who'] ] = $choice['value'] ?? $choice['text'];
	}

	// Nom : prénom / nom (sous-champs .3 et .6 du champ « Nom »).
	$parts = preg_split( '/\s+/', $contact['name'], 2 );
	$values[ 'input_' . $map['name'] . '_3' ] = $parts[0];
	$values[ 'input_' . $map['name'] . '_6' ] = $parts[1] ?? '';
	$values[ 'input_' . $map['email'] ]       = $contact['email'];
	$values[ 'input_' . $map['phone'] ]       = $contact['phone'];
	if ( $quote['pro'] && $contact['event'] ) {
		$values[ 'input_' . $map['event'] ] = $contact['event'];
	}

	// Dates au format du champ (jj/mm/aaaa par défaut en France).
	$formats = array( 'mdy' => 'm/d/Y', 'dmy' => 'd/m/Y', 'dmy_dash' => 'd-m-Y', 'dmy_dot' => 'd.m.Y', 'ymd_slash' => 'Y/m/d', 'ymd_dash' => 'Y-m-d', 'ymd_dot' => 'Y.m.d' );
	foreach ( array( 'pickup', 'return' ) as $key ) {
		$field = smash_gf_field( $form, $map[ $key ] );
		if ( $field && $dates[ $key ] ) {
			$values[ 'input_' . $map[ $key ] ] = $dates[ $key ]->format( $formats[ $field->dateFormat ?? 'dmy' ] ?? 'd/m/Y' );
		}
	}

	// Produits : nom (.1), prix (.2), quantité (.3).
	$product = function ( $field_id, $qty ) use ( $form, &$values ) {
		$field = smash_gf_field( $form, $field_id );
		if ( ! $field || ! $qty ) {
			return;
		}
		$values[ "input_{$field_id}_1" ] = $field->label;
		$values[ "input_{$field_id}_2" ] = $field->basePrice ?? '';
		$values[ "input_{$field_id}_3" ] = $qty;
	};
	foreach ( $quote['kegs'] as $row ) {
		if ( ! empty( $row['keg']['gf'][ $col ] ) ) {
			$product( $row['keg']['gf'][ $col ], $row['qty'] );
		}
	}
	foreach ( $quote['taps'] as $tap => $qty ) {
		$product( $map['taps'][ $tap ], $qty );
	}
	if ( $quote['cupsn'] && isset( $map['cups'][ $quote['size'] ] ) ) {
		$product( $map['cups'][ $quote['size'] ][ $col ], $quote['cupsn'] );
	}

	// Précisions : saisie libre + ce que le formulaire ne sait pas représenter.
	$extra = array();
	if ( $quote['eph'] ) {
		$extra[] = sprintf( __( 'Éphémère en fût (selon dispo) : %d', 'smash' ), $quote['eph'] );
	}
	if ( $quote['gear'] ) {
		$extra[] = __( 'Matériel souhaité : ', 'smash' ) . implode( ', ', $quote['gear'] );
	}
	if ( $quote['free'] && $quote['taps']['t1'] ) {
		$extra[] = __( 'Une tireuse 1 bec offerte (3 fûts ou plus).', 'smash' );
	}
	$values[ 'input_' . $map['notes'] ] = trim( $contact['notes'] . ( $extra ? "\n\n— " . implode( "\n— ", $extra ) : '' ) );

	return $values;
}
