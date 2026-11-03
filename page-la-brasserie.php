<?php
/**
 * La brasserie (/la-brasserie/) : présentation, chiffres, valeurs, machines, équipe.
 * Textes : groupe ACF « La brasserie » ; équipe : CPT `membre`.
 */

defined( 'ABSPATH' ) || exit;

smash_page_script( 'brasserie' );
get_header();

$smash_id = get_queried_object_id();
$smash_f  = fn( $name ) => smash_page_field( $smash_id, $name );

$smash_stats = $smash_values = $smash_equip = array();
for ( $smash_i = 1; $smash_i <= 4; $smash_i++ ) {
	if ( $smash_i <= 3 && '' !== $smash_f( "stat_{$smash_i}_label" ) ) {
		$smash_stats[] = array( (int) $smash_f( "stat_{$smash_i}_value" ), $smash_f( "stat_{$smash_i}_label" ) );
	}
	if ( $smash_i <= 3 && '' !== $smash_f( "value_{$smash_i}_title" ) ) {
		$smash_values[] = array( $smash_f( "value_{$smash_i}_title" ), $smash_f( "value_{$smash_i}_text" ) );
	}
	if ( '' !== $smash_f( "equip_{$smash_i}_title" ) ) {
		$smash_equip[] = array( $smash_f( "equip_{$smash_i}_title" ), $smash_f( "equip_{$smash_i}_text" ) );
	}
}
$smash_team  = array_map( 'smash_member', get_posts( array( 'post_type' => 'membre', 'posts_per_page' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) ) );
$smash_photo = (int) $smash_f( 'brew_photo' );
?>
<main class="page" id="contenu">
	<?php get_template_part( 'template-parts/page-head' ); ?>

	<?php if ( $smash_photo || $smash_f( 'brew_text' ) || $smash_stats || $smash_values ) : ?>
		<section class="sec light">
			<div class="brew">
				<div class="brew__photo"><div data-par><?php echo $smash_photo ? wp_get_attachment_image( $smash_photo, 'large', false, array( 'alt' => '', 'loading' => 'lazy' ) ) : ''; ?></div></div>
				<div>
					<?php if ( $smash_f( 'brew_text' ) ) : ?>
						<p class="brew__big" data-words><?php echo esc_html( $smash_f( 'brew_text' ) ); ?></p>
					<?php endif; ?>
					<?php if ( $smash_stats ) : ?>
						<div class="stats">
							<?php foreach ( $smash_stats as list( $smash_n, $smash_l ) ) : ?>
								<div><b data-n="<?php echo (int) $smash_n; ?>"><?php echo (int) $smash_n; ?></b><span class="mono"><?php echo esc_html( $smash_l ); ?></span></div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<?php if ( $smash_values ) : ?>
						<ul class="values">
							<?php foreach ( $smash_values as list( $smash_t, $smash_x ) ) : ?>
								<li><span class="mark" aria-hidden="true"></span><div><h2 class="values__t"><?php echo esc_html( $smash_t ); ?></h2><?php if ( $smash_x ) : ?><p><?php echo esc_html( $smash_x ); ?></p><?php endif; ?></div></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $smash_equip ) : ?>
		<section class="sec">
			<div class="head">
				<div><?php smash_kick( $smash_f( 'equip_kick' ) ); ?><h2 class="big" data-up><?php echo smash_multiline( $smash_f( 'equip_title' ) ); ?></h2></div>
				<?php if ( $smash_f( 'equip_text' ) ) : ?><p><?php echo esc_html( $smash_f( 'equip_text' ) ); ?></p><?php endif; ?>
			</div>
			<div class="equip">
				<?php foreach ( $smash_equip as list( $smash_t, $smash_x ) ) : ?>
					<div><b><?php echo esc_html( $smash_t ); ?></b><?php if ( $smash_x ) : ?><p><?php echo esc_html( $smash_x ); ?></p><?php endif; ?></div>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $smash_team ) : ?>
		<section class="sec" id="la-team">
			<div class="head">
				<div><?php smash_kick( $smash_f( 'team_kick' ) ); ?><h2 class="big" data-up><?php echo smash_multiline( $smash_f( 'team_title' ) ); ?></h2></div>
				<?php if ( $smash_f( 'team_text' ) ) : ?><p><?php echo esc_html( $smash_f( 'team_text' ) ); ?></p><?php endif; ?>
			</div>
			<div class="team">
				<?php foreach ( $smash_team as $smash_m ) : ?>
					<article class="mate">
						<div class="mate__photo">
							<?php if ( $smash_m['photo'] ) : ?>
								<?php echo wp_get_attachment_image( $smash_m['photo'], 'medium_large', false, array( 'alt' => $smash_m['name'], 'loading' => 'lazy', 'sizes' => '(max-width: 700px) 50vw, 25vw' ) ); ?>
							<?php else : ?>
								<span class="mate__nophoto" aria-hidden="true"></span>
							<?php endif; ?>
						</div>
						<h3 class="mate__name"><?php echo esc_html( $smash_m['name'] ); ?></h3>
						<span class="mono mate__role"><?php echo esc_html( $smash_m['role'] ); ?></span>
						<?php if ( $smash_m['bio'] ) : ?>
							<div class="mate__bio"><?php echo wp_kses_post( wpautop( $smash_m['bio'] ) ); ?></div>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>
</main>
<?php
get_footer();
