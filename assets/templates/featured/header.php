<?php defined( 'ABSPATH' ) || exit; ?>
<section class="wp-block-group alignfull work-with-us iworks-heroes">
		<div class="wp-block-group__inner-container">
<?php
if ( ! empty( $args['title'] ) ) {
	printf( '<h2>%s</h2>', esc_html( $args['title'] ) );
}
if ( ! empty( $args['subtitle'] ) ) {
	printf( '<p class="become-one-of-them">%s</p>', esc_html( $args['subtitle'] ) );
}
?>
			<ul>