<?php
/**
 * Location de tireuses (/location-tireuses-a-bieres/) : devis en direct.
 *
 * Tous les tarifs sont écrits dans le HTML (SEO, lisibles sans JS) ; assets/js/pages/location.js
 * fait le calcul en direct et envoie la demande à la route REST smash/v1/devis (inc/location.php).
 * La FAQ est le contenu de la page : blocs « Détails » de l'éditeur.
 */

defined( 'ABSPATH' ) || exit;

smash_page_script( 'location' );
get_header();

$smash_r     = smash_rental();
$smash_rules = $smash_r['rules'];
$smash_days  = function ( $days ) {
	$names = array( 1 => __( 'lun', 'smash' ), __( 'mar', 'smash' ), __( 'mer', 'smash' ), __( 'jeu', 'smash' ), __( 'ven', 'smash' ), __( 'sam', 'smash' ), __( 'dim', 'smash' ) );
	$days  = array_values( array_filter( $days, fn( $d ) => isset( $names[ $d ] ) ) );
	if ( ! $days ) {
		return '';
	}
	$consecutive = count( $days ) > 2 && end( $days ) - $days[0] === count( $days ) - 1;
	return $consecutive ? $names[ $days[0] ] . ' – ' . $names[ end( $days ) ] : implode( ' – ', array_map( fn( $d ) => $names[ $d ], $days ) );
};
$smash_cup   = reset( $smash_r['cups'] );
$smash_conf  = array(
	'rest'    => esc_url_raw( rest_url( 'smash/v1/devis' ) ),
	'nonce'   => wp_create_nonce( 'smash_devis' ),
	'wpNonce' => wp_create_nonce( 'wp_rest' ), // garde l'utilisateur connecté pendant l'appel REST (sinon le jeton ci-dessus ne correspond plus)
	'rules'   => array(
		'freeTapFrom'    => $smash_rules['free_tap_from'],
		'proFreeFrom'    => $smash_rules['pro_free_delivery_from'],
		'radius'         => $smash_rules['delivery_radius'],
		'pickupDays'     => $smash_rules['pickup_days'],
		'returnDays'     => $smash_rules['return_days'],
		'taps'           => $smash_r['taps'],
		'cups'           => $smash_r['cups'],
	),
	'i18n'    => array(
		'part'        => __( 'particulier', 'smash' ),
		'pro'         => __( 'pro / asso', 'smash' ),
		'quote'       => __( 'devis', 'smash' ),
		'onRequest'   => __( 'sur demande', 'smash' ),
		'free'        => __( 'offerte', 'smash' ),
		'oneFree'     => __( '1 offerte', 'smash' ),
		'empty'       => __( 'ajoute des fûts pour commencer', 'smash' ),
		'eph'         => __( 'éphémère', 'smash' ),
		'tap1'        => __( 'tireuse 1 bec', 'smash' ),
		'tap2'        => __( 'tireuse 2 becs', 'smash' ),
		'freeTapLine' => __( '1 tireuse 1 bec', 'smash' ),
		'cupsLine'    => __( '%1$d éco-cups %2$s cl', 'smash' ),
		/* translators: %d : nombre de fûts manquants ; %s : « fût » ou « fûts » */
		'needKegs'    => __( 'Encore %1$d %2$s et une tireuse 1 bec est offerte.', 'smash' ),
		'needPro'     => __( 'Encore %1$d %2$s et la livraison est offerte.', 'smash' ),
		'keg'         => __( 'fût', 'smash' ),
		'kegs'        => __( 'fûts', 'smash' ),
		'proFree'     => sprintf( __( 'Livraison et reprise offertes (%1$d fûts ou plus, dans un rayon de %2$d km).', 'smash' ), $smash_rules['pro_free_delivery_from'], $smash_rules['delivery_radius'] ),
		'tapFreeOk'   => __( 'Une tireuse 1 bec offerte : bien joué.', 'smash' ),
		'tapFreeAdd'  => __( "Une tireuse 1 bec t'est offerte : ajoute-la.", 'smash' ),
		'ok'          => __( '✓ ok', 'smash' ),
		'badPickup'   => sprintf( __( 'retrait : %s', 'smash' ), $smash_days( $smash_rules['pickup_days'] ) ),
		'badReturn'   => sprintf( __( 'retour : %s', 'smash' ), $smash_days( $smash_rules['return_days'] ) ),
		'sending'     => __( 'envoi…', 'smash' ),
		'error'       => __( "L'envoi n'a pas marché. Réessaie, ou appelle-nous.", 'smash' ),
	),
);
?>
<main class="page light" id="contenu">
	<?php get_template_part( 'template-parts/page-head' ); ?>

	<section class="sec" style="padding-top:0">
		<div class="rent" id="rent" data-config="<?php echo esc_attr( wp_json_encode( $smash_conf ) ); ?>">
			<div class="board">
				<div class="brow">
					<div class="brow__k mono" id="k-prof"><b>01</b><?php esc_html_e( 'tu es', 'smash' ); ?></div>
					<div class="seg" id="seg-prof" role="group" aria-labelledby="k-prof">
						<button type="button" data-v="part" class="on" aria-pressed="true"><?php esc_html_e( 'un particulier', 'smash' ); ?></button>
						<button type="button" data-v="pro" aria-pressed="false"><?php esc_html_e( 'un pro / une asso', 'smash' ); ?></button>
						<i class="seg__pill" aria-hidden="true"></i>
					</div>
				</div>

				<div class="brow">
					<div class="brow__k mono"><b>02</b><?php esc_html_e( 'quand', 'smash' ); ?></div>
					<div class="brow__v when">
						<label class="fld"><span class="mono"><?php esc_html_e( 'retrait', 'smash' ); ?> <em><?php echo esc_html( $smash_days( $smash_rules['pickup_days'] ) ); ?></em></span><input type="date" id="r-pick"><small id="h-pick" aria-live="polite"></small></label>
						<label class="fld"><span class="mono"><?php esc_html_e( 'retour', 'smash' ); ?> <em><?php echo esc_html( $smash_days( $smash_rules['return_days'] ) ); ?></em></span><input type="date" id="r-back"><small id="h-back" aria-live="polite"></small></label>
					</div>
				</div>

				<div class="brow">
					<div class="brow__k mono"><b>03</b><?php esc_html_e( 'les fûts', 'smash' ); ?><small><?php esc_html_e( 'indique le nombre de fûts par bière', 'smash' ); ?></small></div>
					<div class="kegs" id="kegs">
						<?php foreach ( $smash_r['kegs'] as $smash_keg ) : $smash_b = $smash_keg['beer']; ?>
							<div class="keg" data-k="<?php echo esc_attr( get_post_field( 'post_name', $smash_b['id'] ) ); ?>" data-price="<?php echo esc_attr( $smash_keg['price'] ); ?>" data-name="<?php echo esc_attr( $smash_b['name'] ); ?>">
								<button type="button" class="keg__lab" tabindex="-1" aria-hidden="true"><?php echo smash_label_img( $smash_b, 'smash-label-sm', true ); ?></button>
								<div class="keg__meta">
									<div><b><?php echo esc_html( $smash_b['name'] ); ?></b><small><?php echo esc_html( implode( ' · ', array_filter( array( $smash_b['style'], $smash_keg['volume'] ) ) ) ); ?></small></div>
									<span data-p="<?php echo esc_attr( smash_money( $smash_keg['price'] ) ); ?>"><?php echo esc_html( smash_money( $smash_keg['price'] ) ); ?></span>
								</div>
								<div class="stepper">
									<button type="button" data-d="-1" aria-label="<?php echo esc_attr( sprintf( __( 'retirer un fût de %s', 'smash' ), $smash_b['name'] ) ); ?>">−</button>
									<input type="number" min="0" value="0" inputmode="numeric" aria-label="<?php echo esc_attr( sprintf( __( 'nombre de fûts de %s', 'smash' ), $smash_b['name'] ) ); ?>">
									<button type="button" data-d="1" aria-label="<?php echo esc_attr( sprintf( __( 'ajouter un fût de %s', 'smash' ), $smash_b['name'] ) ); ?>">+</button>
								</div>
							</div>
						<?php endforeach; ?>
						<div class="keg eph" data-k="eph">
							<button type="button" class="keg__lab" tabindex="-1" aria-hidden="true"><?php esc_html_e( 'une éphémère', 'smash' ); ?></button>
							<div class="keg__meta"><div><b><?php esc_html_e( 'éphémère', 'smash' ); ?></b><small><?php esc_html_e( 'selon dispo', 'smash' ); ?></small></div><span><?php esc_html_e( 'sur demande', 'smash' ); ?></span></div>
							<div class="stepper">
								<button type="button" data-d="-1" aria-label="<?php esc_attr_e( 'retirer un fût d\'éphémère', 'smash' ); ?>">−</button>
								<input type="number" min="0" value="0" inputmode="numeric" aria-label="<?php esc_attr_e( 'nombre de fûts d\'éphémère', 'smash' ); ?>">
								<button type="button" data-d="1" aria-label="<?php esc_attr_e( 'ajouter un fût d\'éphémère', 'smash' ); ?>">+</button>
							</div>
						</div>
					</div>
				</div>

				<div class="brow">
					<div class="brow__k mono"><b>04</b><span class="part-only"><?php esc_html_e( 'les tireuses', 'smash' ); ?></span><span class="pro-only" hidden><?php esc_html_e( 'le matériel', 'smash' ); ?></span>
						<small class="part-only"><?php echo esc_html( sprintf( __( "autant qu'il t'en faut · une 1 bec offerte dès %d fûts", 'smash' ), $smash_rules['free_tap_from'] ) ); ?></small></div>
					<div>
						<div class="taps part-only" id="taps" style="display:grid">
							<?php
							$smash_taps = array(
								't1' => array( __( '1 bec', 'smash' ), __( 'une bière à la fois', 'smash' ) ),
								't2' => array( __( '2 becs', 'smash' ), __( 'deux bières en même temps', 'smash' ) ),
							);
							foreach ( $smash_taps as $smash_t => list( $smash_name, $smash_desc ) ) :
								?>
								<div class="tapt" data-t="<?php echo esc_attr( $smash_t ); ?>" data-price="<?php echo esc_attr( $smash_r['taps'][ $smash_t ] ); ?>">
									<b><?php echo esc_html( $smash_name ); ?></b><span><?php echo esc_html( $smash_desc ); ?></span><em><?php echo esc_html( smash_money( $smash_r['taps'][ $smash_t ] ) ); ?></em>
									<div class="stepper">
										<button type="button" data-d="-1" aria-label="<?php echo esc_attr( sprintf( __( 'retirer une tireuse %s', 'smash' ), $smash_name ) ); ?>">−</button>
										<input type="number" min="0" value="0" inputmode="numeric" aria-label="<?php echo esc_attr( sprintf( __( 'tireuses %s', 'smash' ), $smash_name ) ); ?>">
										<button type="button" data-d="1" aria-label="<?php echo esc_attr( sprintf( __( 'ajouter une tireuse %s', 'smash' ), $smash_name ) ); ?>">+</button>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
						<?php if ( $smash_rules['pro_gear'] ) : ?>
							<div class="chipset pro-only" id="gear" hidden>
								<?php foreach ( $smash_rules['pro_gear'] as $smash_g ) : ?>
									<button type="button" data-g="<?php echo esc_attr( $smash_g ); ?>" aria-pressed="false"><?php echo esc_html( $smash_g ); ?></button>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<div class="brow">
					<div class="brow__k mono" id="k-cups"><b>05</b><?php esc_html_e( 'gobelets', 'smash' ); ?></div>
					<div class="cups">
						<div class="seg small" id="seg-cups" role="group" aria-labelledby="k-cups">
							<button type="button" data-v="0" class="on" aria-pressed="true"><?php esc_html_e( "j'ai les miens", 'smash' ); ?></button>
							<?php foreach ( array_keys( $smash_r['cups'] ) as $smash_size ) : ?>
								<button type="button" data-v="<?php echo esc_attr( $smash_size ); ?>" aria-pressed="false"><?php echo esc_html( sprintf( __( 'éco-cups %s cl', 'smash' ), $smash_size ) ); ?></button>
							<?php endforeach; ?>
							<i class="seg__pill" aria-hidden="true"></i>
						</div>
						<div class="cupsq" id="cupsq"><label class="mono" for="cups-n"><?php esc_html_e( 'combien ?', 'smash' ); ?></label>
							<div class="stepper big"><button type="button" data-d="-50" aria-label="<?php esc_attr_e( 'moins 50', 'smash' ); ?>">−</button><input type="number" id="cups-n" min="0" step="10" value="100" inputmode="numeric"><button type="button" data-d="50" aria-label="<?php esc_attr_e( 'plus 50', 'smash' ); ?>">+</button></div>
						</div>
						<small class="mono" id="cups-info"><?php echo esc_html( sprintf( __( '%1$s pièce, lavage inclus · %2$s si perdu', 'smash' ), smash_money( $smash_cup ), smash_money( $smash_rules['cup_lost_price'] ) ) ); ?></small>
					</div>
				</div>
			</div>

			<aside class="ticket" id="ticket" aria-labelledby="ticket-title">
				<div class="ticket__top"><h2 class="h3" id="ticket-title"><?php esc_html_e( 'ton devis', 'smash' ); ?></h2><span class="mono" id="t-mode"><?php esc_html_e( 'particulier', 'smash' ); ?></span></div>
				<ul id="t-list" aria-live="polite"><li class="empty"><?php esc_html_e( 'ajoute des fûts pour commencer', 'smash' ); ?></li></ul>
				<div class="ticket__total"><span class="mono"><?php esc_html_e( 'total ttc', 'smash' ); ?></span><b id="t-total">0 €</b></div>
				<div class="nudge" id="t-nudge"><span class="drop"></span><span><?php echo esc_html( sprintf( __( 'Encore %d fûts et une tireuse 1 bec est offerte.', 'smash' ), $smash_rules['free_tap_from'] ) ); ?></span></div>
				<form class="ticket__form" id="t-form" novalidate>
					<label class="screen-reader-text" for="t-name"><?php esc_html_e( 'prénom et nom', 'smash' ); ?></label>
					<input id="t-name" name="name" autocomplete="name" required placeholder="<?php esc_attr_e( 'prénom et nom', 'smash' ); ?>">
					<div class="two">
						<label class="screen-reader-text" for="t-mail"><?php esc_html_e( 'email', 'smash' ); ?></label>
						<input id="t-mail" name="email" type="email" autocomplete="email" required placeholder="<?php esc_attr_e( 'email', 'smash' ); ?>">
						<label class="screen-reader-text" for="t-tel"><?php esc_html_e( 'téléphone', 'smash' ); ?></label>
						<input id="t-tel" name="phone" type="tel" autocomplete="tel" required placeholder="<?php esc_attr_e( 'téléphone', 'smash' ); ?>">
					</div>
					<label class="screen-reader-text pro-only" for="t-event" hidden><?php esc_html_e( "nom de l'évènement", 'smash' ); ?></label>
					<input id="t-event" name="event" class="pro-only" hidden placeholder="<?php esc_attr_e( "nom de l'évènement", 'smash' ); ?>">
					<label class="screen-reader-text" for="t-notes"><?php esc_html_e( 'précisions', 'smash' ); ?></label>
					<textarea id="t-notes" name="notes" rows="2" placeholder="<?php esc_attr_e( 'précisions (facultatif)', 'smash' ); ?>"></textarea>
					<div class="hp" aria-hidden="true"><label for="t-website">Website</label><input id="t-website" name="website" tabindex="-1" autocomplete="off"></div>
					<p class="t-error" id="t-error" role="alert" hidden></p>
					<button type="submit" class="blk" id="t-send"><?php esc_html_e( 'envoyer ma demande', 'smash' ); ?> <span class="drop"></span></button>
				</form>
				<p class="ticket__foot mono"><?php echo esc_html( sprintf( __( '+ caution %s au retrait · on te recontacte pour confirmer', 'smash' ), smash_money( $smash_rules['deposit'] ) ) ); ?></p>
				<div class="ticket__done" id="t-done" role="status" tabindex="-1">
					<b><?php esc_html_e( "c'est envoyé.", 'smash' ); ?></b>
					<p><?php echo esc_html( smash_option( 'success_message', __( 'On revient vers toi très vite.', 'smash' ) ) ); ?></p>
				</div>
				<noscript><p class="ticket__foot"><?php echo esc_html( sprintf( __( 'Le devis en ligne a besoin de JavaScript. Écris-nous à %1$s ou appelle le %2$s.', 'smash' ), smash_option( 'email' ), smash_option( 'phone' ) ) ); ?></p></noscript>
			</aside>
		</div>

		<?php while ( have_posts() ) : the_post(); ?>
			<?php if ( trim( get_the_content() ) ) : ?>
				<div class="faq" style="margin-top:12vh;max-width:900px"><?php the_content(); ?></div>
			<?php endif; ?>
		<?php endwhile; ?>
	</section>
</main>
<?php
get_footer();
