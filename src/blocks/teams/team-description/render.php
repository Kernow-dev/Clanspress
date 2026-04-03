<?php
/**
 * Render callback: team description (post content).
 *
 * @package clanspress
 */

$team_id = clanspress_team_single_block_team_id( $block );
if ( $team_id < 1 ) {
	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'clanspress-team-description clanspress-team-description--placeholder',
		)
	);
	echo '<div ' . $wrapper . '><span>' . esc_html__( 'Team description', 'clanspress' ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
	return;
}

$post = get_post( $team_id );
if ( ! $post || 'cp_team' !== $post->post_type ) {
	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'clanspress-team-description clanspress-team-description--placeholder',
		)
	);
	echo '<div ' . $wrapper . '><span>' . esc_html__( 'Team description', 'clanspress' ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
	return;
}

$content = $post->post_content;
if ( '' === trim( $content ) ) {
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => 'clanspress-team-description clanspress-team-description--empty entry-content',
		)
	);
	echo '<div ' . $wrapper_attributes . '><p>' . esc_html__( 'No description yet.', 'clanspress' ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes.
	return;
}

/** This filter is documented in wp-includes/post-template.php */
$html = apply_filters( 'the_content', $content );

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'clanspress-team-description entry-content',
	)
);

echo '<div ' . $wrapper_attributes . '>' . $html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() returns escaped HTML attributes; $html from the_content filter.
