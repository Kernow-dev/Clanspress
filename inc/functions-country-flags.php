<?php
/**
 * Country flag assets (Flagpack) and filterable markup for country blocks.
 *
 * @package clanspress
 */

/**
 * Normalize a stored country code for flag file lookup.
 *
 * @param string $code Raw code from post meta or user meta.
 * @return string Uppercase letters, digits, and hyphens only (e.g. GB, GB-ENG).
 */
function clanspress_normalize_country_code( string $code ): string {
	$code = strtoupper( preg_replace( '/[^a-zA-Z0-9\-]/', '', $code ) );

	return $code;
}

/**
 * Resolve the bundled SVG basename (no extension) for a country code.
 *
 * Flagpack omits some alpha-2 files (e.g. `GB` uses `gb-ukm`). Third parties may filter aliases or slug.
 *
 * @param string               $normalized_code {@see clanspress_normalize_country_code()}.
 * @param array<string, mixed> $context         Optional context for filters.
 * @return string Lowercase slug or empty.
 */
function clanspress_country_flag_asset_slug( string $normalized_code, array $context = array() ): string {
	if ( '' === $normalized_code || ! function_exists( 'clanspress' ) ) {
		return '';
	}

	$main = clanspress();
	if ( ! is_object( $main ) || ! isset( $main->path ) ) {
		return '';
	}

	$dir  = trailingslashit( $main->path ) . 'assets/flags/';
	$slug = strtolower( $normalized_code );

	if ( is_readable( $dir . $slug . '.svg' ) ) {
		return (string) apply_filters( 'clanspress_country_flag_asset_slug', $slug, $normalized_code, $context );
	}

	/**
	 * Map ISO codes to an existing Flagpack filename when alpha-2 SVG is absent.
	 *
	 * @param array<string, string> $aliases         Uppercase code => lowercase slug (no `.svg`).
	 * @param string                $normalized_code Requested code.
	 * @param array<string, mixed>  $context         Optional context.
	 */
	$aliases = (array) apply_filters(
		'clanspress_country_flag_slug_aliases',
		array(
			'GB' => 'gb-ukm',
			'UK' => 'gb-ukm',
		),
		$normalized_code,
		$context
	);

	if ( isset( $aliases[ $normalized_code ] ) ) {
		$alt = strtolower( (string) $aliases[ $normalized_code ] );
		if ( is_readable( $dir . $alt . '.svg' ) ) {
			$slug = $alt;
		}
	}

	return (string) apply_filters( 'clanspress_country_flag_asset_slug', $slug, $normalized_code, $context );
}

/**
 * Absolute filesystem path to a bundled Flagpack SVG, when present.
 *
 * @param string               $normalized_code {@see clanspress_normalize_country_code()}.
 * @param array<string, mixed> $context         Optional context for filters.
 * @return string Path or empty when missing.
 */
function clanspress_country_flag_file_path( string $normalized_code, array $context = array() ): string {
	if ( '' === $normalized_code || ! function_exists( 'clanspress' ) ) {
		return '';
	}

	$main = clanspress();
	if ( ! is_object( $main ) || ! isset( $main->path ) ) {
		return '';
	}

	$slug = clanspress_country_flag_asset_slug( $normalized_code, $context );
	if ( '' === $slug ) {
		return '';
	}

	$path = trailingslashit( $main->path ) . 'assets/flags/' . $slug . '.svg';

	/**
	 * Filter resolved flag SVG path on disk.
	 *
	 * @param string $path            Absolute path or empty.
	 * @param string $normalized_code Normalized country code.
	 * @param array  $context         Arbitrary context: `entity`, `block_name`, `block`, etc.
	 */
	$path = (string) apply_filters( 'clanspress_country_flag_file_path', $path, $normalized_code, $context );

	return is_string( $path ) && is_readable( $path ) ? $path : '';
}

/**
 * Public URL for a country flag asset (bundled SVG or overridden).
 *
 * @param string               $normalized_code {@see clanspress_normalize_country_code()}.
 * @param array<string, mixed> $context         Optional context for filters.
 * @return string URL or empty.
 */
function clanspress_country_flag_url( string $normalized_code, array $context = array() ): string {
	if ( '' === $normalized_code || ! function_exists( 'clanspress' ) ) {
		return '';
	}

	$main = clanspress();
	if ( ! is_object( $main ) || ! isset( $main->url, $main->path ) ) {
		return '';
	}

	$slug = clanspress_country_flag_asset_slug( $normalized_code, $context );
	$url  = '';
	if ( '' !== $slug && is_readable( trailingslashit( $main->path ) . 'assets/flags/' . $slug . '.svg' ) ) {
		$url = trailingslashit( $main->url ) . 'assets/flags/' . $slug . '.svg';
	}

	$url_context = array_merge( $context, array( 'slug' => $slug ) );

	/**
	 * Filter flag asset URL (CDN, different bundle, or empty to hide default file-based URL).
	 *
	 * @param string $url             Default URL from bundled assets.
	 * @param string $normalized_code Normalized country code.
	 * @param array  $url_context     Includes `slug` plus `entity`, `block_name`, `block`, `attributes`.
	 */
	return (string) apply_filters( 'clanspress_country_flag_url', $url, $normalized_code, $url_context );
}

/**
 * Build default &lt;img&gt; markup for a flag, or use filter overrides.
 *
 * @param string               $normalized_code Country code.
 * @param string               $alt             Accessible label (e.g. country name).
 * @param array<string, mixed> $context         Merged with code/alt; may include `class`, `width`, `height`, `entity`, `block_name`, `block`, `attributes`.
 * @return string Safe HTML fragment or empty.
 */
function clanspress_country_flag_img_html( string $normalized_code, string $alt, array $context = array() ): string {
	$context = array_merge(
		array(
			'code' => $normalized_code,
			'alt'  => $alt,
		),
		$context
	);

	/**
	 * Replace default flag markup entirely. Return non-empty HTML to skip the default &lt;img&gt;.
	 *
	 * @param string $html    Empty string by default.
	 * @param array  $context Keys: `code`, `alt`, plus passthrough from the caller.
	 */
	$custom = (string) apply_filters( 'clanspress_country_flag_html', '', $context );
	if ( '' !== trim( $custom ) ) {
		return $custom;
	}

	$url = clanspress_country_flag_url( $normalized_code, $context );
	if ( '' === $url ) {
		return '';
	}

	$class  = isset( $context['class'] ) ? sanitize_html_class( (string) $context['class'] ) : 'clanspress-country-flag__img';
	$width  = isset( $context['width'] ) ? max( 1, (int) $context['width'] ) : 24;
	$height = isset( $context['height'] ) ? max( 1, (int) $context['height'] ) : 18;

	$decorative = ! empty( $context['decorative'] );

	if ( $decorative ) {
		$img = sprintf(
			'<img src="%s" alt="" class="%s" width="%d" height="%d" loading="lazy" decoding="async" aria-hidden="true" />',
			esc_url( $url ),
			esc_attr( $class ),
			$width,
			$height
		);
	} else {
		$img = sprintf(
			'<img src="%s" alt="%s" class="%s" width="%d" height="%d" loading="lazy" decoding="async" />',
			esc_url( $url ),
			esc_attr( $alt ),
			esc_attr( $class ),
			$width,
			$height
		);
	}

	/**
	 * Filter the default flag &lt;img&gt; HTML after it is built.
	 *
	 * @param string $img     Full img element string.
	 * @param array  $context Same as {@see clanspress_country_flag_html}.
	 */
	return (string) apply_filters( 'clanspress_country_flag_img_html', $img, $context );
}

/**
 * Build inner HTML for team/player country blocks (flag and/or label).
 *
 * @param array<string, mixed> $attributes      Block attributes: `countryDisplay`, `flagFirst`, `showCode`.
 * @param string               $code            Raw country code from storage.
 * @param string               $label           Human-readable country name.
 * @param string               $entity          `team` or `player`.
 * @param string               $block_name      Block name, e.g. `clanspress/team-country`.
 * @param \WP_Block|null       $block           Block instance.
 * @return string HTML fragment (escaped); empty when nothing to show.
 */
function clanspress_country_block_inner_html( array $attributes, string $code, string $label, string $entity, string $block_name, $block = null ): string {
	$display = isset( $attributes['countryDisplay'] ) ? sanitize_key( (string) $attributes['countryDisplay'] ) : 'both';
	if ( ! in_array( $display, array( 'both', 'flag', 'text' ), true ) ) {
		$display = 'both';
	}

	$flag_first = ! isset( $attributes['flagFirst'] ) || (bool) $attributes['flagFirst'];
	$show_code  = ! empty( $attributes['showCode'] );

	$norm = clanspress_normalize_country_code( $code );
	$ctx  = array(
		'entity'      => $entity,
		'block_name'  => $block_name,
		'block'       => $block,
		'attributes'  => $attributes,
		'display'     => $display,
		'flag_first'  => $flag_first,
	);

	$text_inner = '';
	if ( '' !== $label ) {
		$text_inner = esc_html( $label );
		if ( $show_code && '' !== trim( $code ) && $label !== $code ) {
			$text_inner .= ' <span class="clanspress-country-display__code">(' . esc_html( $norm ) . ')</span>';
		}
	} elseif ( '' !== $norm && 'text' === $display ) {
		$text_inner = esc_html( $norm );
	}

	$alt = '' !== $label ? $label : ( '' !== $norm ? $norm : __( 'Country', 'clanspress' ) );

	$flag_ctx = array_merge(
		$ctx,
		array(
			'class'  => 'clanspress-country-flag__img',
			'width'  => 24,
			'height' => 18,
		)
	);

	$text_html = '';
	if ( 'flag' !== $display && '' !== $text_inner ) {
		$text_html = '<span class="clanspress-country-display__label">' . $text_inner . '</span>';
	}

	$flag_html = '';
	if ( 'text' !== $display && '' !== $norm ) {
		$flag_render_ctx = $flag_ctx;
		if ( 'both' === $display && '' !== $text_html ) {
			$flag_render_ctx['decorative'] = true;
		}
		$flag_html = clanspress_country_flag_img_html( $norm, $alt, $flag_render_ctx );
	}

	$parts = array();
	if ( 'both' === $display ) {
		if ( $flag_first ) {
			if ( '' !== $flag_html ) {
				$parts[] = '<span class="clanspress-country-flag">' . $flag_html . '</span>';
			}
			if ( '' !== $text_html ) {
				$parts[] = $text_html;
			}
		} else {
			if ( '' !== $text_html ) {
				$parts[] = $text_html;
			}
			if ( '' !== $flag_html ) {
				$parts[] = '<span class="clanspress-country-flag">' . $flag_html . '</span>';
			}
		}
	} elseif ( 'flag' === $display ) {
		if ( '' !== $flag_html ) {
			$parts[] = '<span class="clanspress-country-flag">' . $flag_html . '</span>';
		}
	} else {
		if ( '' !== $text_html ) {
			$parts[] = $text_html;
		}
	}

	$inner = implode( '', $parts );

	if ( '' === $inner && 'flag' === $display && '' !== $norm ) {
		// Missing asset: show code as fallback text.
		$inner = '<span class="clanspress-country-display__label clanspress-country-display__label--fallback">' . esc_html( $norm ) . '</span>';
	}

	/**
	 * Filter full inner HTML for country blocks (flag + text layout).
	 *
	 * @param string               $inner      Built HTML.
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param string               $code       Raw storage code.
	 * @param string               $label      Resolved display label.
	 * @param string               $entity     `team` or `player`.
	 * @param string               $block_name Block name.
	 * @param \WP_Block|null       $block      Block instance.
	 */
	return (string) apply_filters( 'clanspress_country_display_html', $inner, $attributes, $code, $label, $entity, $block_name, $block );
}
