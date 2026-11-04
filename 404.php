<?php
/**
 * Page introuvable.
 */

defined( 'ABSPATH' ) || exit;

get_header();

$smash_catalog = smash_catalog_page();
?>
<main class="page light" id="contenu">
	<?php
	get_template_part( 'template-parts/page-head', null, array(
		'kick'  => '404',
		'title' => __( "Cette page\na déjà été bue.", 'smash' ),
		'text'  => __( "Elle n'existe pas, ou plus. Les éphémères, ça ne dure jamais longtemps.", 'smash' ),
	) );
	?>
	<section class="sec sec--flush">
		<div class="e404">
			<?php smash_btn( __( "Retour à l'accueil", 'smash' ), home_url( '/' ), 'dark' ); ?>
			<?php if ( $smash_catalog ) : ?>
				<?php smash_btn( get_the_title( $smash_catalog ), get_permalink( $smash_catalog ), 'ghost' ); ?>
			<?php endif; ?>
		</div>
		<?php get_search_form(); ?>
	</section>
</main>
<?php
get_footer();
