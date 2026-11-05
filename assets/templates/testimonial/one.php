<?php
defined( 'ABSPATH' ) || exit;
$classes = array(
	'iworks-testimonial',
);
if ( 'slider' === $atts['type'] ) {
	if ( 0 === $testimonials_query->current_post ) {
		$classes[] = 'current';
	} else {
		$classes[] = 'hide';
	}
}
printf(
	'<li id="iworks-testimonial-item-%1$d" data-id="%1$d" class="%2$s">',
	esc_attr( get_the_ID() ),
	esc_attr( implode( ' ', get_post_class( $classes ) ) )
);
echo '<div class="iworks-testimonial-content">';
the_content();
echo '</div>';
echo '<div class="iworks-testimonial-footer">';
printf(
	'<p class="iworks-testimonial-footer-person">%s</p>',
	esc_html( get_post_meta( get_the_ID(), 'iworks_testimonial_person', true ) )
);
printf(
	'<p class="iworks-testimonial-footer-position">%s</p>',
	esc_html( get_post_meta( get_the_ID(), 'iworks_testimonial_position', true ) )
);
echo '</div>';
echo '</li>';
