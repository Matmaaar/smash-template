<?php
/**
 * Protection des formulaires du thème (devis de location, contact démo).
 *
 * - Jeton : demandé à la route GET smash/v1/jeton au moment de l'envoi, jamais écrit dans le HTML.
 *   Les pages restent donc compatibles avec un cache de page (un nonce en cache expire au bout de 12 à 24 h).
 * - Limite d'envois par adresse IP (SMASH_FORM_RATE envois par SMASH_FORM_WINDOW secondes).
 * - Longueur maximale de chaque champ.
 * - Cloudflare Turnstile, si les clés sont définies dans wp-config.php :
 *     define( 'SMASH_TURNSTILE_SITE_KEY', '…' );
 *     define( 'SMASH_TURNSTILE_SECRET', '…' );
 * - Le champ piège « website » reste en place (inc/location.php, inc/contact.php).
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'SMASH_FORM_RATE' ) ) {
	define( 'SMASH_FORM_RATE', 5 );
}
if ( ! defined( 'SMASH_FORM_WINDOW' ) ) {
	define( 'SMASH_FORM_WINDOW', 10 * MINUTE_IN_SECONDS );
}

/**
 * Longueurs maximales des champs (caractères).
 */
function smash_form_limits() {
	return (array) apply_filters( 'smash_form_limits', array(
		'name'    => 100,
		'email'   => 254,
		'phone'   => 30,
		'event'   => 150,
		'notes'   => 3000,
		'message' => 5000,
	) );
}

/**
 * Premier champ trop long de la saisie, ou ''.
 */
function smash_form_too_long( $in ) {
	foreach ( smash_form_limits() as $key => $max ) {
		if ( isset( $in[ $key ] ) && is_string( $in[ $key ] ) && mb_strlen( $in[ $key ] ) > $max ) {
			return $key;
		}
	}
	return '';
}

/**
 * Adresse IP du visiteur. Derrière un proxy ou Cloudflare, brancher le filtre `smash_client_ip`.
 */
function smash_client_ip() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return (string) apply_filters( 'smash_client_ip', $ip );
}

/**
 * Compte un essai pour l'action et l'IP ; faux si la limite est dépassée.
 */
function smash_rate_limit( $action, $max = SMASH_FORM_RATE, $window = SMASH_FORM_WINDOW ) {
	$key  = 'smash_rl_' . md5( $action . '|' . smash_client_ip() );
	$now  = time();
	$hits = get_transient( $key );
	if ( ! is_array( $hits ) || $hits['start'] + $window <= $now ) {
		$hits = array( 'start' => $now, 'n' => 0 );
	}
	if ( $hits['n'] >= $max ) {
		return false;
	}
	$hits['n']++;
	set_transient( $key, $hits, max( 1, $hits['start'] + $window - $now ) );
	return true;
}

/**
 * Turnstile actif ?
 */
function smash_turnstile_enabled() {
	return defined( 'SMASH_TURNSTILE_SITE_KEY' ) && defined( 'SMASH_TURNSTILE_SECRET' ) && SMASH_TURNSTILE_SITE_KEY && SMASH_TURNSTILE_SECRET;
}

/**
 * Le widget Turnstile, à placer dans un formulaire (charge le script de Cloudflare sur cette page seulement).
 */
function smash_turnstile_widget() {
	if ( ! smash_turnstile_enabled() ) {
		return;
	}
	wp_enqueue_script( 'cf-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', array(), null, array( 'in_footer' => true, 'strategy' => 'defer' ) ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- version gérée par Cloudflare.
	printf( '<div class="cf-turnstile" data-sitekey="%s" data-size="flexible"></div>', esc_attr( SMASH_TURNSTILE_SITE_KEY ) );
}

/**
 * Vérifie la réponse Turnstile (toujours vrai si Turnstile n'est pas configuré).
 */
function smash_turnstile_ok( $token ) {
	if ( ! smash_turnstile_enabled() ) {
		return true;
	}
	if ( ! is_string( $token ) || '' === $token ) {
		return false;
	}
	$res = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', array(
		'timeout' => 8,
		'body'    => array(
			'secret'   => SMASH_TURNSTILE_SECRET,
			'response' => $token,
			'remoteip' => smash_client_ip(),
		),
	) );
	if ( is_wp_error( $res ) ) {
		return false;
	}
	$body = json_decode( wp_remote_retrieve_body( $res ), true );
	return ! empty( $body['success'] );
}

/**
 * Contrôles communs à tous les envois. Renvoie une WP_Error, ou null si tout va bien.
 *
 * @param string          $form    'devis' ou 'contact' (nom de l'action du jeton).
 * @param WP_REST_Request $request La requête.
 * @param array           $in      Les champs envoyés.
 */
function smash_form_guard( $form, WP_REST_Request $request, $in ) {
	if ( ! wp_verify_nonce( (string) $request->get_header( 'X-Smash-Nonce' ), 'smash_' . $form ) ) {
		return new WP_Error( 'smash_nonce', __( 'La page a expiré : recharge-la puis renvoie ta demande.', 'smash' ), array( 'status' => 403 ) );
	}
	if ( ! smash_rate_limit( 'post_' . $form ) ) {
		return new WP_Error( 'smash_rate', __( 'Trop de demandes envoyées : réessaie dans quelques minutes, ou appelle-nous.', 'smash' ), array( 'status' => 429 ) );
	}
	if ( smash_form_too_long( $in ) ) {
		return new WP_Error( 'smash_too_long', __( 'Un des champs est trop long : raccourcis-le puis renvoie.', 'smash' ), array( 'status' => 422 ) );
	}
	if ( ! smash_turnstile_ok( $in['turnstile'] ?? '' ) ) {
		return new WP_Error( 'smash_captcha', __( 'La vérification anti-robot a échoué : réessaie.', 'smash' ), array( 'status' => 403 ) );
	}
	return null;
}

/*
 * Jeton d'envoi : demandé au moment de l'envoi, sans X-WP-Nonce.
 * Sans X-WP-Nonce, WordPress traite la requête REST en visiteur anonyme (utilisateur 0) :
 * le jeton est donc créé et vérifié pour le même utilisateur, connecté ou non.
 */
add_action( 'rest_api_init', function () {
	register_rest_route( 'smash/v1', '/jeton', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'args'                => array(
			'form' => array( 'required' => true, 'enum' => array( 'devis', 'contact' ) ),
		),
		'callback'            => function ( WP_REST_Request $request ) {
			if ( ! smash_rate_limit( 'token', 30 ) ) {
				return new WP_Error( 'smash_rate', __( 'Trop de demandes : réessaie dans quelques minutes.', 'smash' ), array( 'status' => 429 ) );
			}
			$res = rest_ensure_response( array( 'nonce' => wp_create_nonce( 'smash_' . $request['form'] ) ) );
			$res->header( 'Cache-Control', 'no-store, max-age=0' );
			return $res;
		},
	) );
} );
