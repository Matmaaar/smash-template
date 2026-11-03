<?php
/**
 * Article : date, titre, étiquettes des bières citées (liens /bieres/… du texte), contenu.
 */

defined( 'ABSPATH' ) || exit;

smash_page_script( 'article' );
get_header();

$smash_blog = (int) get_option( 'page_for_posts' );
?>
<main class="page light" id="contenu">
	<?php while ( have_posts() ) : the_post(); ?>
		<?php $smash_beers = smash_post_beers(); ?>
		<section class="sec article-sec">
			<div class="article">
				<?php if ( $smash_blog ) : ?>
					<a href="<?php echo esc_url( get_permalink( $smash_blog ) ); ?>" class="mono fiche__back">← <?php echo esc_html( get_the_title( $smash_blog ) ); ?></a>
				<?php endif; ?>
				<?php smash_kick( get_the_date() ); ?>
				<h1 class="big big--sm" id="a-title"><?php the_title(); ?></h1>
			</div>
			<?php if ( $smash_beers ) : ?>
				<div class="labels article__beers" style="--n:<?php echo (int) min( 4, count( $smash_beers ) ); ?>">
					<?php
					foreach ( $smash_beers as $smash_beer ) {
						smash_label_card( $smash_beer, 'h2' );
					}
					?>
				</div>
			<?php endif; ?>
			<div class="article prose article__body"><?php the_content(); ?></div>
			<?php
			$smash_prev = get_previous_post();
			$smash_next = get_next_post();
			if ( $smash_prev || $smash_next ) :
				?>
				<nav class="article article__nav" aria-label="<?php esc_attr_e( 'Autres articles', 'smash' ); ?>">
					<?php if ( $smash_prev ) : ?>
						<a href="<?php echo esc_url( get_permalink( $smash_prev ) ); ?>"><span class="mono"><?php esc_html_e( 'Article précédent', 'smash' ); ?></span><b><?php echo esc_html( get_the_title( $smash_prev ) ); ?></b></a>
					<?php endif; ?>
					<?php if ( $smash_next ) : ?>
						<a href="<?php echo esc_url( get_permalink( $smash_next ) ); ?>" class="next"><span class="mono"><?php esc_html_e( 'Article suivant', 'smash' ); ?></span><b><?php echo esc_html( get_the_title( $smash_next ) ); ?></b></a>
					<?php endif; ?>
				</nav>
			<?php endif; ?>
		</section>
	<?php endwhile; ?>
</main>
<?php
get_footer();
