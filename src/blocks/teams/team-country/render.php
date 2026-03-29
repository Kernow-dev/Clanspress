<?php
/**
 * Render callback: team country.
 *
 * @package clanspress
 */

$team_id = clanspress_team_single_block_team_id( $block );
if ( $team_id < 1 ) {
	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'clanspress-team-country clanspress-team-country--placeholder',
		)
	);
	echo '<div ' . $wrapper . '><span>' . esc_html__( 'Team country', 'clanspress' ) . '</span></div>';
	return;
}

$code = (string) get_post_meta( $team_id, 'cp_team_country', true );

$label    = '' !== $code && function_exists( 'clanspress_team_country_label' ) ? clanspress_team_country_label( $code ) : $code;
$show_code = ! empty( $attributes['showCode'] );

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanspress-team-country' . ( '' === $code ? ' clanspress-team-country--empty' : '' ),
	)
);

if ( '' === $code ) {
	$inner = esc_html__( '—', 'clanspress' );
} else {
	$inner = esc_html( $label );
	if ( $show_code && $label !== $code ) {
		$inner .= ' <span class="clanspress-team-country__code">(' . esc_html( strtoupper( $code ) ) . ')</span>';
	}
}

echo '<div ' . $wrapper_attributes . '><span class="clanspress-team-country__label">' . $inner . '</span></div>';
