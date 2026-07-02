<?php
/**
 * Main template file.
 *
 * @package PikLight
 */

get_header();
?>
<main id="primary" class="site-main piklight-page">
	<?php
	if ( have_posts() ) :
		while ( have_posts() ) :
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'piklight-content' ); ?>>
				<?php the_content(); ?>
			</article>
			<?php
		endwhile;
	else :
		?>
		<section class="piklight-empty">
			<h1><?php esc_html_e( 'Pik-Light', 'piklight' ); ?></h1>
			<p><?php esc_html_e( 'Dodaj sekcje strony za pomocą bloków Pik-Light w edytorze Gutenberg.', 'piklight' ); ?></p>
		</section>
		<?php
	endif;
	?>
</main>
<?php
get_footer();
