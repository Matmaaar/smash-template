<?php
/**
 * Résultats de recherche (bières, articles, pages).
 */

defined( 'ABSPATH' ) || exit;

get_header();

$smash_types = array(
	'bieres' => __( 'Bière', 'smash' ),
	'post'   => __( 'Article', 'smash' ),
	'page'   => __( 'Page', 'smash' ),
);
?>
<main class="page light" id="contenu">
	<?php
	get_template_part( 'template-parts/page-head', null, array(
		'kick'  => __( 'Recherche', 'smash' ),
		/* translators: %s : termes recherchés */
		'title' => sprintf( __( '« %s »', 'smash' ), get_search_query() ),
		/* translators: %d : nombre de résultats */
		'text'  => sprintf( _n( '%d résultat', '%d résultats', (int) $wp_query->found_posts, 'smash' ), (int) $wp_query->found_posts ),
	) );
	?>
	<section class="sec sec--flush">
		<?php get_search_form(); ?>
		<?php if ( have_posts() ) : ?>
			<div class="posts">
				<?php while ( have_posts() ) : the_post(); ?>
					<a class="post-row" href="<?php the_permalink(); ?>">
						<span class="mono"><?php echo esc_html( $smash_types[ get_post_type() ] ?? '' ); ?></span>
						<h2><?php the_title(); ?></h2>
						<span class="mini" aria-hidden="true">
							<?php if ( 'bieres' === get_post_type() ) : ?>
								<i><?php echo smash_label_img( smash_beer(), 'smash-label-sm', true ); ?></i>
							<?php endif; ?>
						</span>
					</a>
				<?php endwhile; ?>
			</div>
			<?php the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => __( 'Précédents', 'smash' ), 'next_text' => __( 'Suivants', 'smash' ) ) ); ?>
		<?php endif; ?>
	</section>
</main>
<?php
get_footer();
