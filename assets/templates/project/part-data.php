<?php
defined( 'ABSPATH' ) || exit;
if ( empty( $args['fields'] ) ) {
	return;
}
?>
<section class="project-data">
	<h2><?php esc_html_e( 'Project data', 'my-car' ); ?></h2>
	<table class="iworks-project-details">
		<tbody>
<?php
foreach ( $args['fields'] as $key => $one ) {
	if ( ! isset( $one['value'] ) ) {
		continue;
	}
	if ( isset( $one['hide'] ) && $one['hide'] ) {
		continue;
	}
	$sufix = isset( $one['sufix'] ) ? ' ' . $one['sufix'] : '';
	switch ( $key ) {
		case '_project_funding':
		case '_project_cost':
			if (
			isset( $args['fields']['_project_currency'] )
			&& isset( $args['fields']['_project_currency']['value'] )
			&& ! empty( $args['fields']['_project_currency']['value'] )
			) {
				$sufix = ' ' . $args['fields']['_project_currency']['value'];
			}
			break;
	}
	printf(
		'<tr class="%s"><td><strong>%s</strong></td><td class="%s">%s%s</td></tr>',
		esc_attr( $key ),
		esc_html( $one['label'] ),
		esc_attr( isset( $one['type'] ) ? $one['type'] : 'default' ),
		// $value should be already escaped or contain HTML.
		$one['value'], // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$sufix // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
}
?>
		</tbody>
	</table>
</section>

