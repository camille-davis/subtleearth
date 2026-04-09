<?php
/**
 * The template for displaying all pages
 *
 * @package Subtle
 */

get_header();
?>

<main id="primary" class="site-main">
	<?php
	while ( have_posts() ) :
		the_post();
		?>

		<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

			<?php get_template_part( 'template-parts/content' ); ?>
		</div>

	<?php endwhile; ?>
</main>

<?php
get_footer();
