<?php
/**
 * Template de secours : affiche n'importe quel contenu proprement.
 * Les templates dédiés (accueil, bières, location…) arrivent aux étapes suivantes.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main class="page light" id="contenu">
	<?php if ( is_singular() ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<div class="ph-head">
				<?php smash_kick( is_singular( 'post' ) ? get_the_date() : '' ); ?>
				<h1 class="big" data-lines><?php the_title(); ?></h1>
			</div>
			<section class="sec" style="padding-top:0">
				<div class="prose"><?php the_content(); ?></div>
			</section>
		<?php endwhile; ?>
	<?php else : ?>
		<div class="ph-head">
			<h1 class="big" data-lines>
				<?php
				if ( is_404() ) {
					esc_html_e( 'Page introuvable', 'smash' );
				} elseif ( is_search() ) {
					/* translators: %s : recherche */
					printf( esc_html__( 'Recherche : %s', 'smash' ), esc_html( get_search_query() ) );
				} elseif ( is_home() ) {
					echo esc_html( get_the_title( get_option( 'page_for_posts' ) ) );
				} else {
					echo esc_html( wp_strip_all_tags( get_the_archive_title() ) );
				}
				?>
			</h1>
		</div>
		<section class="sec" style="padding-top:0">
			<?php if ( have_posts() ) : ?>
				<div class="posts">
					<?php while ( have_posts() ) : the_post(); ?>
						<a class="post-row" href="<?php the_permalink(); ?>">
							<span class="mono"><?php echo esc_html( get_the_date() ); ?></span>
							<h2><?php the_title(); ?></h2>
						</a>
					<?php endwhile; ?>
				</div>
				<?php the_posts_pagination(); ?>
			<?php elseif ( ! is_404() ) : ?>
				<p><?php esc_html_e( 'Aucun contenu pour le moment.', 'smash' ); ?></p>
			<?php endif; ?>
		</section>
	<?php endif; ?>
</main>
<?php
get_footer();
