<?php
/**
 * En-tête des pages intérieures (.ph-head de la maquette) : sur-titre, H1, introduction.
 *
 * @var array $args { kick, title, sub, text, image, aside } — sinon lus sur la page courante (groupe « En-tête de page »).
 *                   aside : HTML affiché à droite du titre (ex. les chiffres clés de la location).
 */

defined( 'ABSPATH' ) || exit;

$smash_id    = get_queried_object_id();
$smash_kick  = $args['kick'] ?? smash_page_field( $smash_id, 'intro_kick' );
$smash_title = $args['title'] ?? ( smash_page_field( $smash_id, 'intro_title' ) ?: get_the_title( $smash_id ) );
$smash_sub   = $args['sub'] ?? smash_page_field( $smash_id, 'intro_sub' );
$smash_text  = $args['text'] ?? smash_page_field( $smash_id, 'intro_text' );
$smash_aside = $args['aside'] ?? '';
$smash_image = (int) ( $args['image'] ?? ( isset( $args['title'] ) ? 0 : smash_page_field( $smash_id, 'intro_image' ) ) );
?>
<div class="ph-head<?php echo $smash_image ? ' ph-head--img' : ''; ?><?php echo $smash_aside ? ' ph-head--aside' : ''; ?>">
	<?php if ( $smash_image ) : ?>
		<div class="ph-head__img" aria-hidden="true"><?php echo wp_get_attachment_image( $smash_image, 'full', false, array( 'alt' => '', 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(max-aspect-ratio: 4/3) 130vh, 100vw' ) ); ?></div>
	<?php endif; ?>
	<div class="ph-head__main">
		<?php smash_kick( $smash_kick ); ?>
		<h1 class="big" data-lines><?php echo smash_multiline( $smash_title ); ?></h1>
		<?php if ( $smash_sub ) : ?>
			<p class="ph-head__sub"><?php echo esc_html( $smash_sub ); ?></p>
		<?php endif; ?>
		<?php if ( $smash_text ) : ?>
			<p><?php echo esc_html( $smash_text ); ?></p>
		<?php endif; ?>
	</div>
	<?php if ( $smash_aside ) : ?>
		<div class="ph-head__aside"><?php echo $smash_aside; // phpcs:ignore WordPress.Security.EscapeOutput -- construit et échappé par le gabarit appelant. ?></div>
	<?php endif; ?>
</div>
