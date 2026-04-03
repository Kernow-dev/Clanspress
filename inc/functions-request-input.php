<?php
/**
 * Shared sanitization for superglobals used across extensions (POST, request URI).
 *
 * @package clanspress
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sanitize `$_SERVER['REQUEST_URI']` for parsing or URL building.
 *
 * @param string $when_missing Value when the header is missing or empty after sanitization.
 * @return string
 */
function clanspress_sanitize_request_uri( string $when_missing = '' ): string {
	if ( empty( $_SERVER['REQUEST_URI'] ) ) {
		return $when_missing;
	}

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Request path; normalized below.
	$out = sanitize_text_field( wp_unslash( (string) $_SERVER['REQUEST_URI'] ) );

	return '' === $out ? $when_missing : $out;
}

/**
 * Request path after the home URL path (no leading/trailing slashes), for virtual route matching.
 *
 * @return string
 */
function clanspress_get_canonical_request_path(): string {
	$uri = clanspress_sanitize_request_uri( '' );
	if ( '' === $uri ) {
		return '';
	}

	$path = wp_parse_url( $uri, PHP_URL_PATH );
	if ( ! is_string( $path ) ) {
		return '';
	}

	$path = rawurldecode( $path );

	$home_path = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	$home_path = is_string( $home_path ) ? $home_path : '';

	if ( '' !== $home_path && '/' !== $home_path ) {
		$home_trim = untrailingslashit( $home_path );
		if ( str_starts_with( $path, $home_trim ) ) {
			$path = substr( $path, strlen( $home_trim ) );
		}
	}

	$path = ltrim( $path, '/' );
	$path = preg_replace( '#^index\.php/?#i', '', $path );

	return trim( (string) $path, '/' );
}

/**
 * Read a scalar `$_POST` field as plain text.
 *
 * Call only after nonce/capability checks in the same request.
 *
 * @param string $key     Field name.
 * @param string $default Default when missing.
 * @return string
 */
function clanspress_request_post_text( string $key, string $default = '' ): string {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Caller must verify a nonce before reading POST.
	if ( ! isset( $_POST[ $key ] ) ) {
		return $default;
	}

	return sanitize_text_field( wp_unslash( (string) $_POST[ $key ] ) );
}

/**
 * Read a `$_POST` field as block-safe HTML (`wp_kses_post`).
 *
 * @param string $key     Field name.
 * @param string $default Default when missing.
 * @return string
 */
function clanspress_request_post_html( string $key, string $default = '' ): string {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Caller must verify a nonce before reading POST.
	if ( ! isset( $_POST[ $key ] ) ) {
		return $default;
	}

	return wp_kses_post( wp_unslash( (string) $_POST[ $key ] ) );
}

/**
 * Read a `$_POST` field as a non-negative integer.
 *
 * @param string $key     Field name.
 * @param int    $default Default when missing or invalid.
 * @return int
 */
function clanspress_request_post_absint( string $key, int $default = 0 ): int {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Caller must verify a nonce before reading POST.
	if ( ! isset( $_POST[ $key ] ) ) {
		return $default;
	}

	return absint( wp_unslash( $_POST[ $key ] ) );
}

/**
 * Whether a POST key is present, even when the value is empty (vs `isset` alone).
 *
 * @param string $key Field name.
 * @return bool
 */
function clanspress_request_post_has_key( string $key ): bool {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Caller must verify a nonce before reading POST.
	return array_key_exists( $key, $_POST );
}

/**
 * Whether a POST field is present and truthy (checkbox / remove flag).
 *
 * Matches historical `! empty( $_POST[ $key ] )` for typical HTML checkboxes (`1`, `on`, etc.).
 *
 * @param string $key Field name.
 * @return bool
 */
function clanspress_request_post_flag( string $key ): bool {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Caller must verify a nonce before reading POST.
	if ( ! isset( $_POST[ $key ] ) ) {
		return false;
	}

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- Intentional loose truthy check for checkbox/removal fields.
	$raw = wp_unslash( $_POST[ $key ] );
	if ( is_array( $raw ) ) {
		return false;
	}

	return ! empty( $raw );
}
