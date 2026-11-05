<?php
$partners = ( get_post_meta( get_the_ID(), '_partners', true ) );
if ( empty( $partners ) ) {
	return;
}
echo '<section class="project-partners">';
foreach ( apply_filters( 'opi_pib_get_opi_project_types', array() ) as $type => $label ) {
	if ( ! isset( $partners[ $type ] ) ) {
		continue;
	}
	if ( empty( $partners[ $type ] ) ) {
		continue;
	}
	switch ( $type ) {
		case 'lider':
			$label = _n( 'Lider', 'Liders', count( $partners[ $type ] ), 'opi-pib-theme' );
			break;
		case 'scientific':
			$label = _n( 'Scientific Partner', 'Scientific Partners', count( $partners[ $type ] ), 'opi-pib-theme' );
			break;
		case 'business':
			$label = _n( 'Business Partner', 'Business Partners', count( $partners[ $type ] ), 'opi-pib-theme' );
			break;
		case 'partner':
			$label = _n( 'Partner', 'Partners', count( $partners[ $type ] ), 'opi-pib-theme' );
			break;
		case 'subcontractor':
			$label = _n( 'Subcontractor', 'Subcontractors', count( $partners[ $type ] ), 'opi-pib-theme' );
			break;
	}
	printf(
		'<h3>%s</h3>',
		esc_html( $label )
	);
	echo '<ul>';
	foreach ( $partners[ $type ] as $caption ) {
		printf(
			'<li>%s</li>',
			esc_html( $caption )
		);
	}
	echo '</ul>';
}
echo '</section>';
