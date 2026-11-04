<?php
/**
 * Contact : en production, le formulaire Gravity Forms existant (n° dans fields-map.php) est affiché tel quel.
 * Sans Gravity Forms (démo Playground), un formulaire simple envoie à la route REST smash/v1/contact,
 * qui enregistre le message dans « Demandes (démo) ».
 */

defined( 'ABSPATH' ) || exit;

/**
 * ID du formulaire de contact Gravity Forms, s'il existe sur le site (sinon 0).
 */
function smash_gf_contact_id() {
	$id = (int) ( smash_map( 'gf' )['contact'] ?? 0 );
	return $id && class_exists( 'GFAPI' ) && GFAPI::form_id_exists( $id ) ? $id : 0;
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'smash/v1', '/contact', array(
		'methods'             => 'POST',
		'permission_callback' => '__return_true',
		'callback'            => function ( WP_REST_Request $request ) {
			$in = (array) $request->get_json_params();
			if ( ! empty( $in['website'] ) ) { // champ piège : rempli = robot
				return rest_ensure_response( array( 'ok' => true ) );
			}
			$guard = smash_form_guard( 'contact', $request, $in );
			if ( $guard ) {
				return $guard;
			}
			$name    = sanitize_text_field( $in['name'] ?? '' );
			$email   = sanitize_email( $in['email'] ?? '' );
			$phone   = sanitize_text_field( $in['phone'] ?? '' );
			$message = sanitize_textarea_field( $in['message'] ?? '' );
			if ( '' === $name || ! is_email( $email ) || '' === $message ) {
				return new WP_Error( 'smash_invalid', __( 'Indique ton nom, un e-mail valide et ton message.', 'smash' ), array( 'status' => 422 ) );
			}
			$id = wp_insert_post( array(
				'post_type'    => 'smash_devis',
				'post_status'  => 'private',
				'post_title'   => sprintf( __( 'Contact · %1$s · %2$s', 'smash' ), $name, wp_date( 'd/m/Y H:i' ) ),
				'post_content' => implode( "\n", array_filter( array( $name . ' · ' . $email . ( $phone ? ' · ' . $phone : '' ), '', $message ), 'strlen' ) ),
			) );
			return rest_ensure_response( array( 'ok' => (bool) $id ) );
		},
	) );
} );
