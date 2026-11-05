<?php
defined( 'ABSPATH' ) || exit;
printf( '<li class="%s">', esc_attr( implode( ' ', get_post_class() ) ) );
printf( '<h3>%s</h3>', esc_html( get_the_title() ) );
echo '<div class="post-inner">';
echo '<blockquote class="post-content">';
echo wp_kses_post( get_the_content() );
echo '</blockquote>';
echo '</div>';
echo get_the_post_thumbnail( get_the_ID(), 'full' );
echo '<div class="post-excerpt">';
echo esc_html( get_the_excerpt() );
echo '</div>';
echo '</li>';
