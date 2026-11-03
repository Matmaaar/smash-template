<?php
/**
 * Téléchargements (/telechargements/) : les fichiers du CPT `telechargement` (champ « fichier »).
 */

defined( 'ABSPATH' ) || exit;

get_header();

$smash_files = array_filter(
	array_map( 'smash_download', get_posts( array( 'post_type' => 'telechargement', 'posts_per_page' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) ) ),
	fn( $f ) => $f && $f['url']
);
?>
<main class="page light" id="contenu">
	<?php get_template_part( 'template-parts/page-head' ); ?>
	<section class="sec" style="padding-top:0">
		<?php if ( $smash_files ) : ?>
			<ul class="dl">
				<?php foreach ( $smash_files as $smash_file ) : ?>
					<li>
						<a href="<?php echo esc_url( $smash_file['url'] ); ?>" download>
							<b><?php echo esc_html( $smash_file['title'] ); ?></b>
							<span class="mono"><?php echo esc_html( $smash_file['ext'] ); ?></span>
							<span class="mono dl__go"><?php esc_html_e( 'Télécharger', 'smash' ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p><?php esc_html_e( 'Aucun fichier pour le moment.', 'smash' ); ?></p>
		<?php endif; ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<?php if ( trim( get_the_content() ) ) : ?>
				<div class="prose" style="margin-top:6vh"><?php the_content(); ?></div>
			<?php endif; ?>
		<?php endwhile; ?>
	</section>
</main>
<?php
get_footer();
