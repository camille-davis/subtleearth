<?php
/**
 * Template part for displaying content
 *
 * @package Subtle
 */

$hide_title = get_post_meta( get_the_ID(), '_subtle_hide_page_title', true );
if ( '1' !== $hide_title ) {
  the_title( '<div class="entry-title-outer"><h1 class="entry-title" id="title">', '</h1></div>' );
}
?>
<div class="entry-content">
	<?php the_content(); ?>
</div>
