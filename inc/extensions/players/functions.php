<?php

defined( 'ABSPATH' ) || exit;


/**
 * Get an array of countries.
 *
 * Get translated Countries in WordPress. Runs output through a
 * filter before returning to allow for customization through third
 * party plugins and themes, or for select removal/modification/addition
 * of countries for whatever reason.
 *
 * @return array Countries as Country code => Country name
 */
function clanspress_players_get_countries() {
	$countries = array(
		'AF' => __( 'Afghanistan', 'clanspress' ),
		'AL' => __( 'Albania', 'clanspress' ),
		'DZ' => __( 'Algeria', 'clanspress' ),
		'AS' => __( 'American Samoa', 'clanspress' ),
		'AD' => __( 'Andorra', 'clanspress' ),
		'AO' => __( 'Angola', 'clanspress' ),
		'AI' => __( 'Anguilla', 'clanspress' ),
		'AQ' => __( 'Antarctica', 'clanspress' ),
		'AG' => __( 'Antigua and Barbuda', 'clanspress' ),
		'AR' => __( 'Argentina', 'clanspress' ),
		'AM' => __( 'Armenia', 'clanspress' ),
		'AW' => __( 'Aruba', 'clanspress' ),
		'AU' => __( 'Australia', 'clanspress' ),
		'AT' => __( 'Austria', 'clanspress' ),
		'AZ' => __( 'Azerbaijan', 'clanspress' ),
		'BS' => __( 'Bahamas', 'clanspress' ),
		'BH' => __( 'Bahrain', 'clanspress' ),
		'BD' => __( 'Bangladesh', 'clanspress' ),
		'BB' => __( 'Barbados', 'clanspress' ),
		'BY' => __( 'Belarus', 'clanspress' ),
		'BE' => __( 'Belgium', 'clanspress' ),
		'BZ' => __( 'Belize', 'clanspress' ),
		'BJ' => __( 'Benin', 'clanspress' ),
		'BM' => __( 'Bermuda', 'clanspress' ),
		'BT' => __( 'Bhutan', 'clanspress' ),
		'BO' => __( 'Bolivia', 'clanspress' ),
		'BA' => __( 'Bosnia and Herzegovina', 'clanspress' ),
		'BW' => __( 'Botswana', 'clanspress' ),
		'BV' => __( 'Bouvet Island', 'clanspress' ),
		'BR' => __( 'Brazil', 'clanspress' ),
		'BQ' => __( 'British Antarctic Territory', 'clanspress' ),
		'IO' => __( 'British Indian Ocean Territory', 'clanspress' ),
		'VG' => __( 'British Virgin Islands', 'clanspress' ),
		'BN' => __( 'Brunei', 'clanspress' ),
		'BG' => __( 'Bulgaria', 'clanspress' ),
		'BF' => __( 'Burkina Faso', 'clanspress' ),
		'BI' => __( 'Burundi', 'clanspress' ),
		'KH' => __( 'Cambodia', 'clanspress' ),
		'CM' => __( 'Cameroon', 'clanspress' ),
		'CA' => __( 'Canada', 'clanspress' ),
		'CT' => __( 'Canton and Enderbury Islands', 'clanspress' ),
		'CV' => __( 'Cape Verde', 'clanspress' ),
		'KY' => __( 'Cayman Islands', 'clanspress' ),
		'CF' => __( 'Central African Republic', 'clanspress' ),
		'TD' => __( 'Chad', 'clanspress' ),
		'CL' => __( 'Chile', 'clanspress' ),
		'CN' => __( 'China', 'clanspress' ),
		'CX' => __( 'Christmas Island', 'clanspress' ),
		'CC' => __( 'Cocos [Keeling] Islands', 'clanspress' ),
		'CO' => __( 'Colombia', 'clanspress' ),
		'KM' => __( 'Comoros', 'clanspress' ),
		'CG' => __( 'Congo - Brazzaville', 'clanspress' ),
		'CD' => __( 'Congo - Kinshasa', 'clanspress' ),
		'CK' => __( 'Cook Islands', 'clanspress' ),
		'CR' => __( 'Costa Rica', 'clanspress' ),
		'HR' => __( 'Croatia', 'clanspress' ),
		'CU' => __( 'Cuba', 'clanspress' ),
		'CY' => __( 'Cyprus', 'clanspress' ),
		'CZ' => __( 'Czech Republic', 'clanspress' ),
		'CI' => __( 'Côte d’Ivoire', 'clanspress' ),
		'DK' => __( 'Denmark', 'clanspress' ),
		'DJ' => __( 'Djibouti', 'clanspress' ),
		'DM' => __( 'Dominica', 'clanspress' ),
		'DO' => __( 'Dominican Republic', 'clanspress' ),
		'NQ' => __( 'Dronning Maud Land', 'clanspress' ),
		'DD' => __( 'East Germany', 'clanspress' ),
		'EC' => __( 'Ecuador', 'clanspress' ),
		'EG' => __( 'Egypt', 'clanspress' ),
		'SV' => __( 'El Salvador', 'clanspress' ),
		'GQ' => __( 'Equatorial Guinea', 'clanspress' ),
		'ER' => __( 'Eritrea', 'clanspress' ),
		'EE' => __( 'Estonia', 'clanspress' ),
		'ET' => __( 'Ethiopia', 'clanspress' ),
		'FK' => __( 'Falkland Islands', 'clanspress' ),
		'FO' => __( 'Faroe Islands', 'clanspress' ),
		'FJ' => __( 'Fiji', 'clanspress' ),
		'FI' => __( 'Finland', 'clanspress' ),
		'FR' => __( 'France', 'clanspress' ),
		'GF' => __( 'French Guiana', 'clanspress' ),
		'PF' => __( 'French Polynesia', 'clanspress' ),
		'TF' => __( 'French Southern Territories', 'clanspress' ),
		'FQ' => __( 'French Southern and Antarctic Territories', 'clanspress' ),
		'GA' => __( 'Gabon', 'clanspress' ),
		'GM' => __( 'Gambia', 'clanspress' ),
		'GE' => __( 'Georgia', 'clanspress' ),
		'DE' => __( 'Germany', 'clanspress' ),
		'GH' => __( 'Ghana', 'clanspress' ),
		'GI' => __( 'Gibraltar', 'clanspress' ),
		'GR' => __( 'Greece', 'clanspress' ),
		'GL' => __( 'Greenland', 'clanspress' ),
		'GD' => __( 'Grenada', 'clanspress' ),
		'GP' => __( 'Guadeloupe', 'clanspress' ),
		'GU' => __( 'Guam', 'clanspress' ),
		'GT' => __( 'Guatemala', 'clanspress' ),
		'GG' => __( 'Guernsey', 'clanspress' ),
		'GN' => __( 'Guinea', 'clanspress' ),
		'GW' => __( 'Guinea-Bissau', 'clanspress' ),
		'GY' => __( 'Guyana', 'clanspress' ),
		'HT' => __( 'Haiti', 'clanspress' ),
		'HM' => __( 'Heard Island and McDonald Islands', 'clanspress' ),
		'HN' => __( 'Honduras', 'clanspress' ),
		'HK' => __( 'Hong Kong SAR China', 'clanspress' ),
		'HU' => __( 'Hungary', 'clanspress' ),
		'IS' => __( 'Iceland', 'clanspress' ),
		'IN' => __( 'India', 'clanspress' ),
		'ID' => __( 'Indonesia', 'clanspress' ),
		'IR' => __( 'Iran', 'clanspress' ),
		'IQ' => __( 'Iraq', 'clanspress' ),
		'IE' => __( 'Ireland', 'clanspress' ),
		'IM' => __( 'Isle of Man', 'clanspress' ),
		'IL' => __( 'Israel', 'clanspress' ),
		'IT' => __( 'Italy', 'clanspress' ),
		'JM' => __( 'Jamaica', 'clanspress' ),
		'JP' => __( 'Japan', 'clanspress' ),
		'JE' => __( 'Jersey', 'clanspress' ),
		'JT' => __( 'Johnston Island', 'clanspress' ),
		'JO' => __( 'Jordan', 'clanspress' ),
		'KZ' => __( 'Kazakhstan', 'clanspress' ),
		'KE' => __( 'Kenya', 'clanspress' ),
		'KI' => __( 'Kiribati', 'clanspress' ),
		'KW' => __( 'Kuwait', 'clanspress' ),
		'KG' => __( 'Kyrgyzstan', 'clanspress' ),
		'LA' => __( 'Laos', 'clanspress' ),
		'LV' => __( 'Latvia', 'clanspress' ),
		'LB' => __( 'Lebanon', 'clanspress' ),
		'LS' => __( 'Lesotho', 'clanspress' ),
		'LR' => __( 'Liberia', 'clanspress' ),
		'LY' => __( 'Libya', 'clanspress' ),
		'LI' => __( 'Liechtenstein', 'clanspress' ),
		'LT' => __( 'Lithuania', 'clanspress' ),
		'LU' => __( 'Luxembourg', 'clanspress' ),
		'MO' => __( 'Macau SAR China', 'clanspress' ),
		'MK' => __( 'Macedonia', 'clanspress' ),
		'MG' => __( 'Madagascar', 'clanspress' ),
		'MW' => __( 'Malawi', 'clanspress' ),
		'MY' => __( 'Malaysia', 'clanspress' ),
		'MV' => __( 'Maldives', 'clanspress' ),
		'ML' => __( 'Mali', 'clanspress' ),
		'MT' => __( 'Malta', 'clanspress' ),
		'MH' => __( 'Marshall Islands', 'clanspress' ),
		'MQ' => __( 'Martinique', 'clanspress' ),
		'MR' => __( 'Mauritania', 'clanspress' ),
		'MU' => __( 'Mauritius', 'clanspress' ),
		'YT' => __( 'Mayotte', 'clanspress' ),
		'FX' => __( 'Metropolitan France', 'clanspress' ),
		'MX' => __( 'Mexico', 'clanspress' ),
		'FM' => __( 'Micronesia', 'clanspress' ),
		'MI' => __( 'Midway Islands', 'clanspress' ),
		'MD' => __( 'Moldova', 'clanspress' ),
		'MC' => __( 'Monaco', 'clanspress' ),
		'MN' => __( 'Mongolia', 'clanspress' ),
		'ME' => __( 'Montenegro', 'clanspress' ),
		'MS' => __( 'Montserrat', 'clanspress' ),
		'MA' => __( 'Morocco', 'clanspress' ),
		'MZ' => __( 'Mozambique', 'clanspress' ),
		'MM' => __( 'Myanmar [Burma]', 'clanspress' ),
		'NA' => __( 'Namibia', 'clanspress' ),
		'NR' => __( 'Nauru', 'clanspress' ),
		'NP' => __( 'Nepal', 'clanspress' ),
		'NL' => __( 'Netherlands', 'clanspress' ),
		'AN' => __( 'Netherlands Antilles', 'clanspress' ),
		'NT' => __( 'Neutral Zone', 'clanspress' ),
		'NC' => __( 'New Caledonia', 'clanspress' ),
		'NZ' => __( 'New Zealand', 'clanspress' ),
		'NI' => __( 'Nicaragua', 'clanspress' ),
		'NE' => __( 'Niger', 'clanspress' ),
		'NG' => __( 'Nigeria', 'clanspress' ),
		'NU' => __( 'Niue', 'clanspress' ),
		'NF' => __( 'Norfolk Island', 'clanspress' ),
		'KP' => __( 'North Korea', 'clanspress' ),
		'VD' => __( 'North Vietnam', 'clanspress' ),
		'MP' => __( 'Northern Mariana Islands', 'clanspress' ),
		'NO' => __( 'Norway', 'clanspress' ),
		'OM' => __( 'Oman', 'clanspress' ),
		'PC' => __( 'Pacific Islands Trust Territory', 'clanspress' ),
		'PK' => __( 'Pakistan', 'clanspress' ),
		'PW' => __( 'Palau', 'clanspress' ),
		'PS' => __( 'Palestinian Territories', 'clanspress' ),
		'PA' => __( 'Panama', 'clanspress' ),
		'PZ' => __( 'Panama Canal Zone', 'clanspress' ),
		'PG' => __( 'Papua New Guinea', 'clanspress' ),
		'PY' => __( 'Paraguay', 'clanspress' ),
		'YD' => __( 'People\'s Democratic Republic of Yemen', 'clanspress' ),
		'PE' => __( 'Peru', 'clanspress' ),
		'PH' => __( 'Philippines', 'clanspress' ),
		'PN' => __( 'Pitcairn Islands', 'clanspress' ),
		'PL' => __( 'Poland', 'clanspress' ),
		'PT' => __( 'Portugal', 'clanspress' ),
		'PR' => __( 'Puerto Rico', 'clanspress' ),
		'QA' => __( 'Qatar', 'clanspress' ),
		'RO' => __( 'Romania', 'clanspress' ),
		'RU' => __( 'Russia', 'clanspress' ),
		'RW' => __( 'Rwanda', 'clanspress' ),
		'BL' => __( 'Saint Barthélemy', 'clanspress' ),
		'SH' => __( 'Saint Helena', 'clanspress' ),
		'KN' => __( 'Saint Kitts and Nevis', 'clanspress' ),
		'LC' => __( 'Saint Lucia', 'clanspress' ),
		'MF' => __( 'Saint Martin', 'clanspress' ),
		'PM' => __( 'Saint Pierre and Miquelon', 'clanspress' ),
		'VC' => __( 'Saint Vincent and the Grenadines', 'clanspress' ),
		'WS' => __( 'Samoa', 'clanspress' ),
		'SM' => __( 'San Marino', 'clanspress' ),
		'SA' => __( 'Saudi Arabia', 'clanspress' ),
		'SN' => __( 'Senegal', 'clanspress' ),
		'RS' => __( 'Serbia', 'clanspress' ),
		'CS' => __( 'Serbia and Montenegro', 'clanspress' ),
		'SC' => __( 'Seychelles', 'clanspress' ),
		'SL' => __( 'Sierra Leone', 'clanspress' ),
		'SG' => __( 'Singapore', 'clanspress' ),
		'SK' => __( 'Slovakia', 'clanspress' ),
		'SI' => __( 'Slovenia', 'clanspress' ),
		'SB' => __( 'Solomon Islands', 'clanspress' ),
		'SO' => __( 'Somalia', 'clanspress' ),
		'ZA' => __( 'South Africa', 'clanspress' ),
		'GS' => __( 'South Georgia and the South Sandwich Islands', 'clanspress' ),
		'KR' => __( 'South Korea', 'clanspress' ),
		'ES' => __( 'Spain', 'clanspress' ),
		'LK' => __( 'Sri Lanka', 'clanspress' ),
		'SD' => __( 'Sudan', 'clanspress' ),
		'SR' => __( 'Suriname', 'clanspress' ),
		'SJ' => __( 'Svalbard and Jan Mayen', 'clanspress' ),
		'SZ' => __( 'Swaziland', 'clanspress' ),
		'SE' => __( 'Sweden', 'clanspress' ),
		'CH' => __( 'Switzerland', 'clanspress' ),
		'SY' => __( 'Syria', 'clanspress' ),
		'ST' => __( 'São Tomé and Príncipe', 'clanspress' ),
		'TW' => __( 'Taiwan', 'clanspress' ),
		'TJ' => __( 'Tajikistan', 'clanspress' ),
		'TZ' => __( 'Tanzania', 'clanspress' ),
		'TH' => __( 'Thailand', 'clanspress' ),
		'TL' => __( 'Timor-Leste', 'clanspress' ),
		'TG' => __( 'Togo', 'clanspress' ),
		'TK' => __( 'Tokelau', 'clanspress' ),
		'TO' => __( 'Tonga', 'clanspress' ),
		'TT' => __( 'Trinidad and Tobago', 'clanspress' ),
		'TN' => __( 'Tunisia', 'clanspress' ),
		'TR' => __( 'Turkey', 'clanspress' ),
		'TM' => __( 'Turkmenistan', 'clanspress' ),
		'TC' => __( 'Turks and Caicos Islands', 'clanspress' ),
		'TV' => __( 'Tuvalu', 'clanspress' ),
		'UM' => __( 'U.S. Minor Outlying Islands', 'clanspress' ),
		'PU' => __( 'U.S. Miscellaneous Pacific Islands', 'clanspress' ),
		'VI' => __( 'U.S. Virgin Islands', 'clanspress' ),
		'UG' => __( 'Uganda', 'clanspress' ),
		'UA' => __( 'Ukraine', 'clanspress' ),
		'SU' => __( 'Union of Soviet Socialist Republics', 'clanspress' ),
		'AE' => __( 'United Arab Emirates', 'clanspress' ),
		'GB' => __( 'United Kingdom', 'clanspress' ),
		'US' => __( 'United States', 'clanspress' ),
		'ZZ' => __( 'Unknown or Invalid Region', 'clanspress' ),
		'UY' => __( 'Uruguay', 'clanspress' ),
		'UZ' => __( 'Uzbekistan', 'clanspress' ),
		'VU' => __( 'Vanuatu', 'clanspress' ),
		'VA' => __( 'Vatican City', 'clanspress' ),
		'VE' => __( 'Venezuela', 'clanspress' ),
		'VN' => __( 'Vietnam', 'clanspress' ),
		'WK' => __( 'Wake Island', 'clanspress' ),
		'WF' => __( 'Wallis and Futuna', 'clanspress' ),
		'EH' => __( 'Western Sahara', 'clanspress' ),
		'YE' => __( 'Yemen', 'clanspress' ),
		'ZM' => __( 'Zambia', 'clanspress' ),
		'ZW' => __( 'Zimbabwe', 'clanspress' ),
		'AX' => __( 'Åland Islands', 'clanspress' ),
	);

	/**
	 * Filter the countries before returning
	 *
	 * @param array $countries countries array for filtering
	 */
	$countries = apply_filters( 'clanspress_players_countries_filters', $countries );

	/**
	 * Return the translated and filtered countries
	 */
	return $countries;
}

/**
 * URL of the plugin-bundled default player avatar (used when Players -> default avatar is unset).
 *
 * @return string Empty when `clanspress()` is unavailable.
 */
function clanspress_players_get_bundled_default_avatar_url(): string {
	if ( ! function_exists( 'clanspress' ) ) {
		return '';
	}

	return clanspress()->url . 'assets/img/avatars/default-player-avatar.png';
}

/**
 * URL of the plugin-bundled default player cover (used when Players → default cover is unset).
 *
 * @return string Empty when `clanspress()` is unavailable.
 */
function clanspress_players_get_bundled_default_cover_url(): string {
	if ( ! function_exists( 'clanspress' ) ) {
		return '';
	}

	return clanspress()->url . 'assets/img/covers/default-player-cover-image.png';
}

function clanspress_players_get_default_avatar( $player_id ) {
	$loader = clanspress()->extensions;
	if ( null === $loader ) {
		$ext = null;
	} else {
		$ext = $loader->get( 'cp_players' );
	}
	$default_avatar = $ext instanceof \Kernowdev\Clanspress\Extensions\Players
		? $ext->get_setting( 'default_avatar' )
		: '';

	if ( empty( $default_avatar ) ) {
		$default_avatar = clanspress_players_get_bundled_default_avatar_url();
	}

	return apply_filters( 'clanspress_players_get_default_avatar', $default_avatar, $player_id );
}

function clanspress_players_get_default_cover( $player_id ) {
	$loader = clanspress()->extensions;
	if ( null === $loader ) {
		$ext = null;
	} else {
		$ext = $loader->get( 'cp_players' );
	}
	$default_cover = $ext instanceof \Kernowdev\Clanspress\Extensions\Players
		? $ext->get_setting( 'default_cover' )
		: '';

	if ( empty( $default_cover ) ) {
		$default_cover = clanspress_players_get_bundled_default_cover_url();
	}

	return apply_filters( 'clanspress_players_get_default_cover', $default_cover, $player_id );
}

/**
 * Register a player profile subpage (tab) for the front-end player profile.
 *
 * Third-party plugins should call this on `init`.
 *
 * @param string $slug Unique slug (used in the URL).
 * @param array  $args {
 *     @type string $label        Human-readable label for navigation.
 *     @type string $template_id  FSE template identifier (defaults to "clanspress-player-{$slug}").
 *     @type string $default_blocks Optional default block markup to seed the template.
 *     @type string $capability   Capability required to view (default "read").
 *     @type int    $position     Sort order in navigation (lower first).
 * }
 * @return void
 */
function clanspress_register_player_subpage( string $slug, array $args = array() ): void {
	if ( function_exists( 'clanspress_register_profile_subpage' ) ) {
		clanspress_register_profile_subpage( 'player', $slug, $args );
	}
}

/**
 * All registered player profile subpages.
 *
 * @return array<string,array>
 */
function clanspress_get_player_subpages(): array {
	return function_exists( 'clanspress_get_profile_subpages' ) ? clanspress_get_profile_subpages( 'player' ) : array();
}

/**
 * Resolve a single player subpage config by slug.
 *
 * @param string $slug Subpage slug.
 * @return array<string,mixed>|null
 */
function clanspress_get_player_subpage( string $slug ): ?array {
	return function_exists( 'clanspress_get_profile_subpage' ) ? clanspress_get_profile_subpage( 'player', $slug ) : null;
}

/**
 * Resolve a player user ID from the canonical `/players/{nicename}/…` path (see rewrite rules).
 *
 * Uses {@see clanspress_get_canonical_request_path()} so subdirectory installs and `home_path` prefixes
 * match the same routes as {@see \Kernowdev\Clanspress\Extensions\Players::filter_request_for_players_virtual_routes()}.
 *
 * @return int User ID or 0 when the path is not a member profile (directory, settings, etc.).
 */
function clanspress_player_user_id_from_canonical_request_path(): int {
	if ( ! function_exists( 'clanspress_get_canonical_request_path' ) ) {
		return 0;
	}

	$path = clanspress_get_canonical_request_path();
	if ( '' === $path ) {
		return 0;
	}

	// Players directory — not a single profile.
	if ( 'players' === $path || preg_match( '#^players/page/[0-9]+/?$#', $path ) ) {
		return 0;
	}

	if ( str_starts_with( $path, 'players/settings' ) || 'players/settings' === $path ) {
		return 0;
	}

	$nicename = '';
	if ( preg_match( '#^players/(?!settings(?:/|$))([^/]+)/page/([0-9]+)/?$#', $path, $m ) ) {
		$nicename = $m[1];
	} elseif ( preg_match( '#^players/(?!settings(?:/|$))([^/]+)/([^/]+)/?$#', $path, $m ) ) {
		$nicename = $m[1];
	} elseif ( preg_match( '#^players/(?!settings(?:/|$))([^/]+)/?$#', $path, $m ) ) {
		$nicename = $m[1];
	}

	if ( '' === $nicename ) {
		return 0;
	}

	$user = get_user_by( 'slug', $nicename );

	return ( $user instanceof \WP_User ) ? (int) $user->ID : 0;
}

/**
 * User ID for player profile header/nav (author archive, `/players/settings/`, or canonical `/players/{nicename}/`).
 *
 * @return int
 */
function clanspress_player_profile_context_user_id(): int {
	if ( (int) get_query_var( 'players_settings' ) ) {
		$uid = get_current_user_id();

		return $uid > 0 ? $uid : 0;
	}

	if ( get_queried_object() instanceof \WP_User ) {
		return (int) get_queried_object()->ID;
	}

	$from_path = clanspress_player_user_id_from_canonical_request_path();

	return $from_path > 0 ? $from_path : 0;
}

/**
 * Resolves the subject user ID for Clanspress player blocks on the front end.
 *
 * Uses {@see clanspress_player_profile_context_user_id()} first (author archive, player settings),
 * then the block `postId` context author, then the logged-in user.
 *
 * @param \WP_Block|null $block Optional block instance for post context.
 * @return int User ID or 0 if none resolved.
 */
function clanspress_player_blocks_resolve_subject_user_id( $block = null ): int {
	if ( $block instanceof \WP_Block && ! empty( $block->context['clanspress/playerId'] ) ) {
		$loop_uid = (int) $block->context['clanspress/playerId'];
		if ( $loop_uid > 0 ) {
			return $loop_uid;
		}
	}

	$uid = (int) clanspress_player_profile_context_user_id();
	if ( $uid > 0 ) {
		return $uid;
	}

	if ( $block instanceof \WP_Block && ! empty( $block->context['postId'] ) ) {
		$author_id = (int) get_post_field( 'post_author', (int) $block->context['postId'] );
		if ( $author_id > 0 ) {
			return $author_id;
		}
	}

	if ( is_user_logged_in() ) {
		return (int) get_current_user_id();
	}

	return 0;
}

/**
 * Active player profile sub-route (`settings` or `cp_player_subpage`).
 *
 * @return string
 */
function clanspress_player_profile_route_current_slug(): string {
	if ( (int) get_query_var( 'players_settings' ) ) {
		return 'settings';
	}

	return sanitize_key( (string) get_query_var( 'cp_player_subpage' ) );
}

/**
 * Label map for image size slugs in Players / Teams settings UI.
 *
 * @return array<string, string> Slug => translated label.
 */
function clanspress_players_get_image_size_choices_for_settings(): array {
	$slugs = array_values(
		array_unique(
			array_merge(
				array( 'full' ),
				get_intermediate_image_sizes()
			)
		)
	);
	sort( $slugs );

	$labels = array(
		'full'                          => __( 'Full size', 'clanspress' ),
		'thumbnail'                     => __( 'Thumbnail', 'clanspress' ),
		'medium'                        => __( 'Medium', 'clanspress' ),
		'medium_large'                  => __( 'Medium large', 'clanspress' ),
		'large'                         => __( 'Large (WordPress)', 'clanspress' ),
		'clanspress-avatar-large'       => __( 'Clanspress player avatar — large (default preset)', 'clanspress' ),
		'clanspress-avatar-medium'      => __( 'Clanspress player avatar — medium (default preset)', 'clanspress' ),
		'clanspress-avatar-small'       => __( 'Clanspress player avatar — small (default preset)', 'clanspress' ),
		'clanspress-team-avatar-large'  => __( 'Clanspress team avatar — large (default preset)', 'clanspress' ),
		'clanspress-team-avatar-medium' => __( 'Clanspress team avatar — medium (default preset)', 'clanspress' ),
		'clanspress-team-avatar-small'  => __( 'Clanspress team avatar — small (default preset)', 'clanspress' ),
	);

	$out = array();
	foreach ( $slugs as $slug ) {
		$key           = (string) $slug;
		$out[ $key ] = $labels[ $key ] ?? $key;
	}

	/**
	 * Filters selectable image size slugs for Clanspress avatar settings dropdowns.
	 *
	 * @param array<string, string> $out Slug => label.
	 */
	return (array) apply_filters( 'clanspress_players_image_size_choices_for_settings', $out );
}

/**
 * Ensures an image size slug is registered (or `full`) before saving settings.
 *
 * @param string $value    Raw value.
 * @param string $fallback Slug to use when invalid.
 * @return string
 */
function clanspress_players_sanitize_image_size_setting_value( string $value, string $fallback ): string {
	$value    = sanitize_key( $value );
	$fallback = sanitize_key( $fallback );
	$allowed  = array_keys( clanspress_players_get_image_size_choices_for_settings() );

	if ( in_array( $value, $allowed, true ) ) {
		return $value;
	}

	if ( in_array( $fallback, $allowed, true ) ) {
		return $fallback;
	}

	return 'thumbnail';
}

/**
 * Maps a semantic avatar preset to the image size slug from Players settings.
 *
 * @param string $preset One of `large`, `medium`, `small`.
 * @return string Registered size name or `full`.
 */
function clanspress_players_resolve_player_avatar_image_size( string $preset ): string {
	$preset = sanitize_key( $preset );
	$keys   = array(
		'large'  => 'player_avatar_image_size_large',
		'medium' => 'player_avatar_image_size_medium',
		'small'  => 'player_avatar_image_size_small',
	);
	$defaults = array(
		'large'  => 'clanspress-avatar-large',
		'medium' => 'clanspress-avatar-medium',
		'small'  => 'clanspress-avatar-small',
	);

	$setting_key = $keys[ $preset ] ?? $keys['large'];
	$fallback    = $defaults[ $preset ] ?? $defaults['large'];

	$raw = '';
	$loader = clanspress()->extensions;
	if ( null !== $loader ) {
		$ext = $loader->get( 'cp_players' );
		if ( $ext instanceof \Kernowdev\Clanspress\Extensions\Players && method_exists( $ext, 'get_setting' ) ) {
			$raw = (string) $ext->get_setting( $setting_key, $fallback );
		}
	}

	$sanitized = clanspress_players_sanitize_image_size_setting_value( $raw, $fallback );

	/**
	 * Filters the resolved image size slug for a player avatar preset.
	 *
	 * @param string $size     Sanitized size slug.
	 * @param string $preset   `large`, `medium`, or `small`.
	 * @param string $raw      Value from settings before sanitization.
	 * @param string $fallback Default slug for this preset.
	 */
	return (string) apply_filters( 'clanspress_players_resolve_player_avatar_image_size', $sanitized, $preset, $raw, $fallback );
}

/**
 * Returns the players display avatar URL.
 *
 * All Clanspress player avatar surfaces should resolve the image URL through this function so third-party
 * code can override output once (XP overlays, rank badges as image, CDN URLs, etc.). Use the optional
 * {@see $context} argument to vary behaviour by surface; it is passed to filters and avatar markup helpers.
 *
 * Pass {@see $avatar_preset} as `large`, `medium`, or `small` to use the image sizes configured under
 * Clanspress → Players (profiles, forums/social feeds, comments/replies). When set, it overrides {@see $size}.
 *
 * @param int          $player_id        The Player/User unique identifier.
 * @param bool         $suppress_filters Disallows filtering of the value.
 * @param string|array $size             Registered size name or array of width and height (ignored when preset is set).
 * @param string       $context          Logical surface key (e.g. `player_avatar_block`, `user_nav`, `notifications`).
 * @param string       $avatar_preset    Optional. `large`, `medium`, or `small` for settings-driven sizes.
 *
 * @return string
 */
function clanspress_players_get_display_avatar( int $player_id = 0, bool $suppress_filters = false, string|array $size = '', string $context = '', string $avatar_preset = '' ): string {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$preset_key = sanitize_key( $avatar_preset );
	$preset_for_filter = '';

	if ( in_array( $preset_key, array( 'large', 'medium', 'small' ), true ) ) {
		$size              = clanspress_players_resolve_player_avatar_image_size( $preset_key );
		$preset_for_filter = $preset_key;
	} elseif ( '' === $size || ( is_array( $size ) && empty( $size ) ) ) {
		$size              = clanspress_players_resolve_player_avatar_image_size( 'large' );
		$preset_for_filter = 'large';
	}

	$user_avatar = wp_get_attachment_image_url( clanspress_players_get_display_avatar_id( $player_id, $suppress_filters ), $size );

	if ( false === $user_avatar || '' === $user_avatar ) {
		$user_avatar = clanspress_players_get_default_avatar( $player_id );
	}

	if ( $suppress_filters ) {
		return $user_avatar;
	}

	/**
	 * Filters the resolved player avatar image URL after attachment/default resolution.
	 *
	 * Return a URL string; consumers (REST, img[src], notifications) expect a URL, not HTML. Use
	 * {@see clanspress_players_get_player_avatar_img_html()} for markup.
	 *
	 * @param string       $user_avatar      URL.
	 * @param int          $player_id        User ID.
	 * @param string|array $size             Size used for attachment resolution.
	 * @param string       $context          Surface key (empty string when not passed by caller).
	 * @param string       $avatar_preset  `large`, `medium`, `small`, or empty when an explicit size was used.
	 */
	return apply_filters( 'clanspress_players_get_display_avatar', $user_avatar, $player_id, $size, $context, $preset_for_filter );
}

/**
 * Returns the players display avatar attachment identifier.
 *
 * @param int  $player_id The Player/User unique identifier.
 * @param bool $suppress_filters Disallows filtering of the value.
 *
 * @return int The players display avatar attachment identifier.
 */
function clanspress_players_get_display_avatar_id( int $player_id = 0, bool $suppress_filters = false ): int {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$user_avatar_id = get_user_meta( $player_id, 'cp_player_avatar_id', true );

	if ( $suppress_filters ) {
		return $user_avatar_id;
	}

	/**
	 * Allows filtering of the players display avatar attachment ID, this can be bypassed by setting $suppress_filters param
	 * to true.
	 */
	return apply_filters( 'clanspress_players_get_display_avatar_id', $user_avatar_id, $player_id );
}

/**
 * Builds a single &lt;img&gt; tag for a player avatar using {@see clanspress_players_get_display_avatar()}.
 *
 * Prefer this over hand-built &lt;img&gt; markup in templates and blocks so attribute and inner markup
 * hooks run consistently. Wrap or extend output with {@see clanspress_players_apply_player_avatar_display_markup()}.
 *
 * @param int   $user_id WordPress user ID.
 * @param array $args {
 *     Optional. Arguments.
 *
 *     @type string       $preset           Optional. `large`, `medium`, or `small` (Players settings). Overrides `size` when set.
 *     @type string|array $size             Image size when `preset` is empty. Default resolves to large preset.
 *     @type string       $context          Surface key for URL/img hooks. Default ''.
 *     @type string       $class            Class attribute (full string). Default `clanspress-player-avatar__img`.
 *     @type string       $alt              Alt text. Default from display name + translated “player avatar”.
 *     @type bool         $suppress_filters Same as {@see clanspress_players_get_display_avatar()}. Default false.
 *     @type string       $loading          `loading` attribute. Default `lazy`. Use empty string to omit.
 *     @type string       $decoding         `decoding` attribute. Default `async`. Use empty string to omit.
 *     @type int          $width            If positive, output width attribute.
 *     @type int          $height           If positive, output height attribute.
 * }
 * @return string HTML fragment or empty string when no URL resolves.
 */
function clanspress_players_get_player_avatar_img_html( int $user_id, array $args = array() ): string {
	if ( $user_id <= 0 ) {
		return '';
	}

	$defaults = array(
		'preset'           => '',
		'size'             => '',
		'context'          => '',
		'class'            => 'clanspress-player-avatar__img',
		'alt'              => '',
		'suppress_filters' => false,
		'loading'          => 'lazy',
		'decoding'         => 'async',
		'width'            => 0,
		'height'           => 0,
	);

	$args = wp_parse_args( $args, $defaults );

	$preset_arg = sanitize_key( (string) $args['preset'] );
	if ( in_array( $preset_arg, array( 'large', 'medium', 'small' ), true ) ) {
		$url = clanspress_players_get_display_avatar( $user_id, (bool) $args['suppress_filters'], '', (string) $args['context'], $preset_arg );
	} else {
		$size_arg = $args['size'];
		if ( '' === $size_arg || ( is_array( $size_arg ) && empty( $size_arg ) ) ) {
			$size_arg = clanspress_players_resolve_player_avatar_image_size( 'large' );
		}
		$url = clanspress_players_get_display_avatar( $user_id, (bool) $args['suppress_filters'], $size_arg, (string) $args['context'], '' );
	}

	if ( '' === $url ) {
		return '';
	}

	if ( '' === $args['alt'] && function_exists( 'clanspress_players_get_display_name' ) ) {
		$args['alt'] = sprintf(
			/* translators: %s: Player display name. */
			__( '%s player avatar', 'clanspress' ),
			clanspress_players_get_display_name( $user_id )
		);
	}

	/**
	 * Fires before the player avatar &lt;img&gt; is built (URL is resolved).
	 *
	 * @param int    $user_id User ID.
	 * @param array  $args    Arguments passed to {@see clanspress_players_get_player_avatar_img_html()}.
	 * @param string $url     Avatar URL.
	 */
	do_action( 'clanspress_player_avatar_img_before', $user_id, $args, $url );

	$attr = array(
		'class'    => (string) $args['class'],
		'src'      => $url,
		'alt'      => (string) $args['alt'],
		'loading'  => (string) $args['loading'],
		'decoding' => (string) $args['decoding'],
	);

	$w = (int) $args['width'];
	$h = (int) $args['height'];
	if ( $w > 0 ) {
		$attr['width'] = (string) $w;
	}
	if ( $h > 0 ) {
		$attr['height'] = (string) $h;
	}

	/**
	 * Filters attributes for the player avatar &lt;img&gt; element before the tag is built.
	 *
	 * @param array  $attr    Attribute name => value. Omitted or empty values are skipped (except alt may be empty).
	 * @param int    $user_id User ID.
	 * @param array  $args    Arguments from {@see clanspress_players_get_player_avatar_img_html()}.
	 * @param string $url     Avatar URL.
	 */
	$attr = apply_filters( 'clanspress_players_player_avatar_img_attributes', $attr, $user_id, $args, $url );

	$parts = array();
	foreach ( $attr as $name => $value ) {
		$name = sanitize_key( (string) $name );
		if ( '' === $name ) {
			continue;
		}
		if ( '' === $value && 'alt' !== $name ) {
			continue;
		}
		if ( 'src' === $name ) {
			$parts[] = sprintf( 'src="%s"', esc_url( (string) $value ) );
			continue;
		}
		$parts[] = sprintf( '%s="%s"', esc_attr( $name ), esc_attr( (string) $value ) );
	}

	$html = '<img ' . implode( ' ', $parts ) . ' />';

	/**
	 * Fires after the player avatar &lt;img&gt; HTML is built.
	 *
	 * @param int    $user_id User ID.
	 * @param array  $args    Arguments from {@see clanspress_players_get_player_avatar_img_html()}.
	 * @param string $url     Avatar URL.
	 * @param string $html    Built &lt;img&gt; markup.
	 */
	do_action( 'clanspress_player_avatar_img_after', $user_id, $args, $url, $html );

	/**
	 * Filters the player avatar &lt;img&gt; HTML fragment.
	 *
	 * @param string $html    Markup.
	 * @param int    $user_id User ID.
	 * @param array  $args    Arguments from {@see clanspress_players_get_player_avatar_img_html()}.
	 * @param string $url     Avatar URL.
	 */
	return (string) apply_filters( 'clanspress_players_player_avatar_img_html', $html, $user_id, $args, $url );
}

/**
 * Filters inner avatar markup (image, placeholder, or empty-state image) for display.
 *
 * Use this to wrap the avatar with rank badges, XP bars, or extra layout. Applies
 * {@see 'clanspress_players_player_avatar_display_markup'}.
 *
 * @param string $inner   HTML inside the avatar area (before profile link wrapping, if any).
 * @param int    $user_id User ID.
 * @param array  $args    Optional. Same `context` / `size` keys as passed to image helpers; extra keys allowed.
 * @return string
 */
function clanspress_players_apply_player_avatar_display_markup( string $inner, int $user_id, array $args = array() ): string {
	/**
	 * Filters inner player avatar markup after core builds the image or empty-state fragment.
	 *
	 * @param string $inner   HTML.
	 * @param int    $user_id User ID.
	 * @param array  $args    Context from the caller (e.g. `context`, `size`).
	 */
	return (string) apply_filters( 'clanspress_players_player_avatar_display_markup', $inner, $user_id, $args );
}

/**
 * Splits large player-avatar block output so markup can sit beside the circular clip (not inside it).
 *
 * @param string               $display_inner Full HTML from {@see clanspress_players_apply_player_avatar_display_markup()}.
 * @param int                  $user_id       User ID.
 * @param array<string, mixed> $args          Avatar args (`context`, `preset`, etc.).
 * @return array{
 *     clip_inner: string,
 *     after_clip: string,
 *     avatar_extra_class: string,
 *     rank_overlay_html: string
 * }
 */
function clanspress_players_apply_player_avatar_block_parts( string $display_inner, int $user_id, array $args ): array {
	$defaults = array(
		'clip_inner'          => $display_inner,
		'after_clip'          => '',
		'avatar_extra_class'  => '',
		'rank_overlay_html'   => '',
	);
	/**
	 * Filters layout parts for the player avatar block when preset is `large`.
	 *
	 * @param array<string, string> $parts {
	 *     @type string $clip_inner           Markup inside `.clanspress-player-avatar__clip`.
	 *     @type string $after_clip           Sibling markup after the clip (e.g. rank progress).
	 *     @type string $avatar_extra_class   Extra classes on `.clanspress-player-avatar` (space-separated).
	 *     @type string $rank_overlay_html    Rank icons outside the circular clip (sibling in `.clanspress-player-avatar__media`).
	 * }
	 * @param int                  $user_id       User ID.
	 * @param array<string, mixed> $args          Avatar args from the block.
	 * @param string               $display_inner Markup before splitting (same as default `clip_inner`).
	 */
	$filtered = apply_filters( 'clanspress_players_player_avatar_block_parts', $defaults, $user_id, $args, $display_inner );
	if ( ! is_array( $filtered ) ) {
		return $defaults;
	}
	return array(
		'clip_inner'          => (string) ( $filtered['clip_inner'] ?? $display_inner ),
		'after_clip'          => (string) ( $filtered['after_clip'] ?? '' ),
		'avatar_extra_class'  => trim( (string) ( $filtered['avatar_extra_class'] ?? '' ) ),
		'rank_overlay_html'   => (string) ( $filtered['rank_overlay_html'] ?? '' ),
	);
}

/**
 * Returns the players display cover.
 *
 * @param int          $player_id The Player/User unique identifier.
 * @param bool         $suppress_filters Disallows filtering of the value.
 * @param string|array $size The image size, either a registered size, or an array of width and height.
 *
 * @return string
 */
function clanspress_players_get_display_cover( int $player_id = 0, bool $suppress_filters = false, string|array $size = 'clanspress-cover' ): string {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$user_cover = wp_get_attachment_image_url( clanspress_players_get_display_cover_id( $player_id, $suppress_filters ), $size );

	if ( false === $user_cover || '' === $user_cover ) {
		$user_cover = clanspress_players_get_default_cover( $player_id );
	}

	if ( $suppress_filters ) {
		return $user_cover;
	}

	/**
	 * Allows filtering of the players display cover, this can be bypassed by setting $suppress_filters param
	 * to true.
	 */
	return apply_filters( 'clanspress_players_get_display_cover', $user_cover, $player_id );
}

/**
 * Returns the players display cover attachment identifier.
 *
 * @param int  $player_id The Player/User unique identifier.
 * @param bool $suppress_filters Disallows filtering of the value.
 *
 * @return int The players display cover attachment identifier.
 */
function clanspress_players_get_display_cover_id( int $player_id = 0, bool $suppress_filters = false ): int {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$user_cover_id = get_user_meta( $player_id, 'cp_player_cover_id', true );

	if ( $suppress_filters ) {
		return $user_cover_id;
	}

	/**
	 * Allows filtering of the players display cover attachment ID, this can be bypassed by setting $suppress_filters param
	 * to true.
	 */
	return apply_filters( 'clanspress_players_get_display_cover_id', $user_cover_id, $player_id );
}

/**
 * Returns the players display cover position X axis.
 *
 * @param int  $player_id The Player/User unique identifier.
 * @param bool $suppress_filters Disallows filtering of the value.
 *
 * @return float The players display cover attachment identifier.
 */
function clanspress_players_get_display_cover_position_x( int $player_id = 0, bool $suppress_filters = false ): float {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$user_cover_position_x = get_user_meta( $player_id, 'cp_player_cover_position_x', true );

	if ( $suppress_filters ) {
		return $user_cover_position_x;
	}

	/**
	 * Allows filtering of the players display cover position on the X axis, this can be bypassed by setting $suppress_filters param
	 * to true.
	 */
	return apply_filters( 'clanspress_players_get_display_cover_position_x', $user_cover_position_x, $player_id );
}

/**
 * Returns the players display cover position Y axis.
 *
 * @param int  $player_id The Player/User unique identifier.
 * @param bool $suppress_filters Disallows filtering of the value.
 *
 * @return float The players display cover attachment identifier.
 */
function clanspress_players_get_display_cover_position_y( int $player_id = 0, bool $suppress_filters = false ): float {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$user_cover_position_y = get_user_meta( $player_id, 'cp_player_cover_position_y', true );

	if ( $suppress_filters ) {
		return $user_cover_position_y;
	}

	/**
	 * Allows filtering of the players display cover position on the Y axis, this can be bypassed by setting $suppress_filters param
	 * to true.
	 */
	return apply_filters( 'clanspress_players_get_display_cover_position_y', $user_cover_position_y, $player_id );
}

/**
 * Returns the players display name.
 *
 * @param int  $player_id The Player/User unique identifier.
 * @param bool $suppress_filters Disallows filtering of the value.
 *
 * @return string
 */
function clanspress_players_get_display_name( int $player_id = 0, bool $suppress_filters = false ): string {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$player = get_userdata( $player_id );

	if ( ! $player ) {
		return '';
	}

	if ( $suppress_filters ) {
		return $player->display_name;
	}

	/**
	 * Allows filtering of the players display name, this can be bypassed by setting $suppress_filters param
	 * to true.
	 */
	return apply_filters( 'clanspress_players_get_display_name', $player->display_name, $player_id, $player );
}

/**
 * Returns the players display tagline.
 *
 * @param int  $player_id The Player/User unique identifier.
 * @param bool $suppress_filters Disallows filtering of the value.
 *
 * @return string
 */
function clanspress_players_get_display_tagline( int $player_id = 0, bool $suppress_filters = false ): string {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$tagline = get_user_meta( $player_id, 'cp_player_tagline', true );

	if ( $suppress_filters ) {
		return $tagline;
	}

	/**
	 * Allows filtering of the players display tagline, this can be bypassed by setting $suppress_filters param
	 * to true.
	 */
	return apply_filters( 'clanspress_players_get_display_tagline', $tagline, $player_id );
}

/**
 * Returns the players display bio/description.
 *
 * @param int  $player_id The Player/User unique identifier.
 * @param bool $suppress_filters Disallows filtering of the value.
 *
 * @return string
 */
function clanspress_players_get_display_bio( int $player_id = 0, bool $suppress_filters = false ): string {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$bio = get_user_meta( $player_id, 'cp_player_bio', true );

	if ( $suppress_filters ) {
		return $bio;
	}

	/**
	 * Allows filtering of the players display bio, this can be bypassed by setting $suppress_filters param
	 * to true.
	 */
	return apply_filters( 'clanspress_players_get_display_bio', $bio, $player_id );
}

/**
 * Returns the players display website.
 *
 * @param int  $player_id The Player/User unique identifier.
 * @param bool $suppress_filters Disallows filtering of the value.
 *
 * @return string
 */
function clanspress_players_get_display_website( int $player_id = 0, bool $suppress_filters = false ): string {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$website = get_user_meta( $player_id, 'cp_player_website', true );

	if ( $suppress_filters ) {
		return $website;
	}

	/**
	 * Allows filtering of the players display website, this can be bypassed by setting $suppress_filters param
	 * to true.
	 */
	return apply_filters( 'clanspress_players_get_display_website', $website, $player_id );
}

/**
 * Meta key for a player social profile field (user meta).
 *
 * @param string $slug Sanitized key from {@see clanspress_players_get_social_profile_field_definitions()}.
 * @return string
 */
function clanspress_players_social_profile_meta_key( string $slug ): string {
	return 'cp_player_social_' . sanitize_key( $slug );
}

/**
 * Registered social profile fields for player settings and user meta.
 *
 * Keys are stable slugs (`facebook`, `x`, …). Extend or replace fields via
 * {@see 'clanspress_players_social_profile_field_definitions'}.
 *
 * @return array<string, array{label: string, placeholder: string}>
 */
function clanspress_players_get_social_profile_field_definitions(): array {
	$definitions = array(
		'facebook'  => array(
			'label'       => __( 'Facebook', 'clanspress' ),
			'placeholder' => __( 'Profile URL or username', 'clanspress' ),
		),
		'x'         => array(
			'label'       => __( 'X', 'clanspress' ),
			'placeholder' => __( 'Profile URL or @handle', 'clanspress' ),
		),
		'instagram' => array(
			'label'       => __( 'Instagram', 'clanspress' ),
			'placeholder' => __( 'Profile URL or @handle', 'clanspress' ),
		),
		'youtube'   => array(
			'label'       => __( 'YouTube', 'clanspress' ),
			'placeholder' => __( 'Channel URL or @handle', 'clanspress' ),
		),
		'twitch'    => array(
			'label'       => __( 'Twitch', 'clanspress' ),
			'placeholder' => __( 'Channel URL or username', 'clanspress' ),
		),
		'discord'   => array(
			'label'       => __( 'Discord', 'clanspress' ),
			'placeholder' => __( 'Username, invite, or server link', 'clanspress' ),
		),
		'steam'     => array(
			'label'       => __( 'Steam', 'clanspress' ),
			'placeholder' => __( 'Profile URL or custom ID', 'clanspress' ),
		),
		'linkedin'  => array(
			'label'       => __( 'LinkedIn', 'clanspress' ),
			'placeholder' => __( 'Profile URL', 'clanspress' ),
		),
		'tiktok'    => array(
			'label'       => __( 'TikTok', 'clanspress' ),
			'placeholder' => __( 'Profile URL or @handle', 'clanspress' ),
		),
		'bluesky'   => array(
			'label'       => __( 'Bluesky', 'clanspress' ),
			'placeholder' => __( 'Profile URL or handle', 'clanspress' ),
		),
		'reddit'    => array(
			'label'       => __( 'Reddit', 'clanspress' ),
			'placeholder' => __( 'Profile URL or u/username', 'clanspress' ),
		),
		'github'    => array(
			'label'       => __( 'GitHub', 'clanspress' ),
			'placeholder' => __( 'Profile URL or username', 'clanspress' ),
		),
		'mastodon'  => array(
			'label'       => __( 'Mastodon', 'clanspress' ),
			'placeholder' => __( 'Full profile URL (@user@instance)', 'clanspress' ),
		),
	);

	/**
	 * Filters social profile fields shown in player settings and registered as user meta.
	 *
	 * @param array<string, array{label: string, placeholder: string}> $definitions Slug => label and placeholder.
	 */
	return (array) apply_filters( 'clanspress_players_social_profile_field_definitions', $definitions );
}

/**
 * Sanitizes a stored social profile field (URL, @handle, or plain text).
 *
 * @param mixed $value Raw input (stringable).
 * @return string
 */
function clanspress_players_sanitize_social_profile_value( $value ): string {
	$value = wp_strip_all_tags( (string) $value );
	$value = sanitize_text_field( $value );

	if ( function_exists( 'mb_substr' ) ) {
		$value = mb_substr( $value, 0, 500 );
	} else {
		$value = substr( $value, 0, 500 );
	}

	return $value;
}

/**
 * Returns a single social profile value for display or editing.
 *
 * @param string $slug            Key from {@see clanspress_players_get_social_profile_field_definitions()}.
 * @param int    $player_id       User ID.
 * @param bool   $suppress_filters When true, skip {@see 'clanspress_players_get_display_social'}.
 * @return string
 */
function clanspress_players_get_display_social( string $slug, int $player_id = 0, bool $suppress_filters = false ): string {
	$slug = sanitize_key( $slug );
	if ( '' === $slug ) {
		return '';
	}

	$definitions = clanspress_players_get_social_profile_field_definitions();
	if ( ! isset( $definitions[ $slug ] ) ) {
		return '';
	}

	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$value = (string) get_user_meta( $player_id, clanspress_players_social_profile_meta_key( $slug ), true );

	if ( $suppress_filters ) {
		return $value;
	}

	/**
	 * Filters a player’s social profile field after reading user meta.
	 *
	 * @param string $value     Stored value.
	 * @param string $slug      Field slug (e.g. `facebook`, `x`).
	 * @param int    $player_id User ID.
	 */
	return (string) apply_filters( 'clanspress_players_get_display_social', $value, $slug, $player_id );
}

/**
 * Turns a stored social profile value (URL, @handle, username, etc.) into an absolute https URL.
 *
 * Used by the Player Social Links block and available to extensions. Unknown or invalid input
 * returns an empty string.
 *
 * @param string $slug Field slug from {@see clanspress_players_get_social_profile_field_definitions()}.
 * @param string $raw  Stored value (trimmed internally).
 * @return string Escaped-safe absolute URL, or empty string.
 */
function clanspress_players_normalize_social_profile_link_url( string $slug, string $raw ): string {
	$raw = trim( $raw );
	if ( '' === $raw ) {
		return '';
	}

	$slug = sanitize_key( $slug );
	if ( '' === $slug ) {
		return '';
	}

	if ( preg_match( '#^https?://#i', $raw ) ) {
		$u = esc_url_raw( $raw );
		return is_string( $u ) && '' !== $u ? $u : '';
	}

	if ( str_starts_with( $raw, '//' ) ) {
		$u = esc_url_raw( 'https:' . $raw );
		return is_string( $u ) && '' !== $u ? $u : '';
	}

	if ( preg_match( '#^www\.#i', $raw ) ) {
		$u = esc_url_raw( 'https://' . $raw );
		return is_string( $u ) && '' !== $u ? $u : '';
	}

	$handle = preg_replace( '/^@+/u', '', $raw );
	$handle = trim( $handle );
	if ( '' === $handle ) {
		return '';
	}

	$u = '';

	switch ( $slug ) {
		case 'facebook':
			$u = esc_url_raw( 'https://www.facebook.com/' . rawurlencode( $handle ) );
			break;
		case 'x':
			$u = esc_url_raw( 'https://x.com/' . rawurlencode( ltrim( $handle, '@' ) ) );
			break;
		case 'instagram':
			$u = esc_url_raw( 'https://www.instagram.com/' . rawurlencode( ltrim( $handle, '@' ) ) . '/' );
			break;
		case 'youtube':
			if ( preg_match( '/^UC[\w-]{10,}$/', $handle ) ) {
				$u = esc_url_raw( 'https://www.youtube.com/channel/' . rawurlencode( $handle ) );
			} else {
				$h = ltrim( $handle, '@' );
				$u = esc_url_raw( 'https://www.youtube.com/@' . rawurlencode( $h ) );
			}
			break;
		case 'twitch':
			$u = esc_url_raw( 'https://www.twitch.tv/' . rawurlencode( $handle ) );
			break;
		case 'discord':
			if ( preg_match( '#discord\.gg/#i', $raw ) || preg_match( '#discord(?:app)?\.com/invite/#i', $raw ) ) {
				$pref = preg_match( '#^https?://#i', $raw ) ? $raw : 'https://' . $raw;
				$u    = esc_url_raw( $pref );
			} else {
				$u = esc_url_raw( 'https://discord.gg/' . rawurlencode( $handle ) );
			}
			break;
		case 'steam':
			if ( preg_match( '/^\d+$/', $handle ) ) {
				$u = esc_url_raw( 'https://steamcommunity.com/profiles/' . $handle );
			} else {
				$u = esc_url_raw( 'https://steamcommunity.com/id/' . rawurlencode( $handle ) );
			}
			break;
		case 'linkedin':
			$u = esc_url_raw( 'https://www.linkedin.com/in/' . rawurlencode( $handle ) . '/' );
			break;
		case 'tiktok':
			$h = ltrim( $handle, '@' );
			$u = esc_url_raw( 'https://www.tiktok.com/@' . rawurlencode( $h ) );
			break;
		case 'bluesky':
			if ( str_contains( $handle, '/' ) ) {
				return '';
			}
			$u = esc_url_raw( 'https://bsky.app/profile/' . rawurlencode( $handle ) );
			break;
		case 'reddit':
			$h = preg_replace( '#^/?u/#i', '', $handle );
			$h = ltrim( $h, '/' );
			$u = esc_url_raw( 'https://www.reddit.com/user/' . rawurlencode( $h ) . '/' );
			break;
		case 'github':
			$u = esc_url_raw( 'https://github.com/' . rawurlencode( $handle ) );
			break;
		case 'mastodon':
			if ( preg_match( '/^@?([^@\s]+)@([^@\s/]+)$/', $handle, $m ) ) {
				$u = esc_url_raw( 'https://' . $m[2] . '/@' . rawurlencode( $m[1] ) );
			} else {
				$u = '';
			}
			break;
		default:
			$u = '';
			break;
	}

	return is_string( $u ) && '' !== $u ? $u : '';
}

/**
 * Resolved public link URL for a player social field (reads meta + normalizes).
 *
 * @param string $slug      Field slug.
 * @param int    $player_id WordPress user ID.
 * @return string Absolute URL or empty when unset / invalid.
 */
function clanspress_players_get_social_profile_link_url( string $slug, int $player_id ): string {
	if ( $player_id < 1 ) {
		return '';
	}

	$raw = clanspress_players_get_display_social( $slug, $player_id, true );
	$url = clanspress_players_normalize_social_profile_link_url( $slug, $raw );

	/**
	 * Filters the final social profile link URL for a player.
	 *
	 * @param string $url       URL or empty.
	 * @param string $slug      Field slug.
	 * @param int    $player_id User ID.
	 * @param string $raw       Raw meta value.
	 */
	return (string) apply_filters( 'clanspress_players_social_profile_link_url', $url, $slug, $player_id, $raw );
}

/**
 * Inline SVG icon markup for a social profile slug (for the Player Social Links block).
 *
 * @param string $slug Field slug.
 * @return string SVG element HTML (safe, no user input).
 */
function clanspress_players_get_social_profile_svg_icon( string $slug ): string {
	$slug = sanitize_key( $slug );

	$wrap = static function ( string $path_d ): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="currentColor" class="clanspress-player-social-links__svg" aria-hidden="true" focusable="false"><path d="' . $path_d . '"/></svg>';
	};

	$paths = array(
		'facebook'  => 'M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2v-2.3C10 7.93 11.47 6 14 6h2v3h-2c-.97 0-1 .68-1 1.5V12h3v3h-3v6.95c5.05-.5 9-4.76 9-9.95z',
		'x'         => 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z',
		'instagram' => 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z',
		'youtube'   => 'M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z',
		'twitch'    => 'M11.571 4.714h1.715v5.143H11.57zm4.715 0H18v5.143h-1.714zM6 0L1.714 4.286v15.428h5.143V24l4.286-4.286h3.428L22.286 12V0zm14.571 11.143l-3.428 3.428h-3.429l-3 3v-3H6.857V1.714h13.714Z',
		'discord'   => 'M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z',
		'steam'     => 'M11.979 0C7.786 0 4.205 3.17 3.74 7.21l4.094 1.676c.545-.371 1.203-.59 1.908-.59.063 0 .125.002.187.006l2.43-3.51A7.957 7.957 0 0 0 11.979 0zM7.833 8.892l-2.612 3.778a4.005 4.005 0 0 0 3.973 4.81c1.47 0 2.76-.79 3.46-1.97l-2.295-3.94a2.016 2.016 0 0 1-2.526-2.678zm8.302 4.87l-3.526-2.03a2.016 2.016 0 0 1-2.748.748 2.016 2.016 0 0 1-.748-2.748l-2.03-3.526A7.96 7.96 0 0 0 12 20c2.17 0 4.14-.87 5.58-2.28l-1.445-2.858z',
		'linkedin'  => 'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z',
		'tiktok'    => 'M12.525.02c1.31-.02 2.61-.01 3.918-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z',
		'bluesky'   => 'M12 10.8c-1.087-2.114-4.046-6.053-6.798-7.995C2.566.944 1.561 1.266.902 1.565.139 1.908 0 3.08 0 3.768c0 .69.378 5.65.624 6.479.815 2.736 3.713 3.66 6.383 3.036.437-.1.883-.228 1.342-.396-.604 1.088-1.75 2.548-3.316 3.528-1.454.93-3.01 1.29-4.59 1.18-.98-.07-1.85-.42-2.55-.98C.78 18.92 0 20.34 0 21.72c0 .5.09.98.27 1.42.6 1.38 1.84 2.42 3.39 2.85 1.62.46 3.44.35 5.02-.28 2.53-1.04 4.55-3.17 5.8-5.67 1.25 2.5 3.27 4.63 5.8 5.67 1.58.63 3.4.74 5.02.28 1.55-.43 2.79-1.47 3.39-2.85.18-.44.27-.92.27-1.42 0-1.38-.78-2.8-2.07-3.81-.7-.56-1.57-.91-2.55-.98-1.58-.11-3.14.25-4.59 1.18-1.57 1-2.7 2.45-3.32 3.54.46.17.9.3 1.34.4 2.67.62 5.57-.3 6.38-3.04.25-.83.62-5.72.62-6.48 0-.69-.14-1.86-.9-2.23-.66-.3-1.67-.62-4.3 1.36C16.046 4.748 13.087 8.687 12 10.8z',
		'reddit'    => 'M12 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0zm5.01 4.744c.688 0 1.25.561 1.25 1.249a1.25 1.25 0 0 1-2.498.056l-2.597-.547-.8 3.747c1.824.07 3.48.632 4.674 1.488.308-.309.73-.481 1.207-.481.968 0 1.754.786 1.754 1.754 0 .716-.435 1.333-1.01 1.614a3.111 3.111 0 0 1 .042.52c0 2.694-3.13 4.87-7.004 4.87-3.874 0-7.004-2.176-7.004-4.87 0-.183.015-.366.043-.534A1.748 1.748 0 0 1 4.028 12c0-.968.786-1.754 1.754-1.754.463 0 .898.196 1.207.49 1.207-.883 2.878-1.43 4.744-1.493l.885-4.182a.342.342 0 0 1 .14-.197.35.35 0 0 1 .238-.042l2.906.617a1.214 1.214 0 0 1 1.108-.701zM9.25 12C8.561 12 8 12.562 8 13.25c0 .687.561 1.248 1.25 1.248.687 0 1.248-.561 1.248-1.249 0-.688-.561-1.249-1.249-1.249zm5.5 0c-.687 0-1.248.561-1.248 1.25 0 .687.561 1.248 1.249 1.248.688 0 1.249-.561 1.249-1.249 0-.687-.562-1.249-1.25-1.249zm-5.466 3.99a.327.327 0 0 0-.231.094.33.33 0 0 0 0 .463c.842.842 2.484.913 2.961.913.477 0 2.105-.056 2.961-.913a.361.361 0 0 0 .029-.463.33.33 0 0 0-.464 0c-.547.533-1.684.73-2.512.73-.828 0-1.979-.196-2.512-.73a.326.326 0 0 0-.232-.095z',
		'github'    => 'M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12',
		'mastodon'  => 'M23.268 5.313c-.35-2.578-2.616-4.51-5.304-4.627C17.55.273 15.792 0 11.813 0h-.03c-3.98 0-4.835.273-5.288.286C2.789.891.45 2.621.12 5.23.003 6.854 0 8.644 0 11.801v.097c0 3.157.003 4.947.118 6.57.33 2.607 2.65 4.338 5.365 4.944 2.664.126 5.47.14 8.392.14 3.922 0 6.896-.13 9.19-.402 2.86-.33 4.957-2.351 5.15-5.055.09-1.21.1-2.423.1-3.647v-.71c0-3.158-.003-4.952-.12-6.575zm-4.41 7.875c-1.708 1.313-4.247 1.403-5.073.35-.13-.16-.19-.36-.19-.6V8.844c0-.655.47-1.156 1.076-1.156.607 0 1.077.5 1.077 1.156v3.095l2.84-2.84c.31-.31.73-.48 1.17-.48h.01c.44 0 .86.17 1.17.48.31.31.48.73.48 1.17s-.17.86-.48 1.17l-2.84 2.84 2.84 2.84c.31.31.48.73.48 1.17s-.17.86-.48 1.17c-.31.31-.73.48-1.17.48-.44 0-.86-.17-1.17-.48l-2.84-2.84v3.095c0 .655-.47 1.156-1.077 1.156-.606 0-1.076-.5-1.076-1.156V12.9c0-.24-.06-.44-.19-.6-.826-1.053-3.365-.963-5.073.35-1.37 1.055-2.11 2.635-2.11 4.25v.097c0 1.615.74 3.195 2.11 4.25 1.708 1.313 4.247 1.403 5.073.35.13-.16.19-.36.19-.6v-1.9c0-2.07 1.35-3.87 3.35-4.87l-3.35-2.58V8.844c0-.655.47-1.156 1.076-1.156.607 0 1.077.5 1.077 1.156v1.9l3.35 2.58c2-1 3.35-2.8 3.35-4.87V8.844c0-1.615-.74-3.195-2.11-4.25z',
	);

	$path = $paths[ $slug ] ?? 'M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z';

	$markup = $wrap( $path );

	/**
	 * Filters the inline SVG markup for a social profile icon.
	 *
	 * @param string $markup SVG HTML.
	 * @param string $slug   Field slug.
	 */
	return (string) apply_filters( 'clanspress_players_social_profile_svg_icon', $markup, $slug );
}

/**
 * Returns the players display country.
 *
 * @param int    $player_id The Player/User unique identifier.
 * @param string $return_type The country format to return, either code or name.
 * @param bool   $suppress_filters Disallows filtering of the value.
 *
 * @return string
 */
function clanspress_players_get_display_country( int $player_id = 0, string $return_type = 'name', bool $suppress_filters = false ): string {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$country = get_user_meta( $player_id, 'cp_player_country', true );

	if ( $return_type === 'name' ) {
		$countries = clanspress_players_get_countries();

		foreach ( $countries as $country_code => $country_name ) {
			if ( $country_code === $country ) {
				$country = $country_name;
				break;
			}
		}
	}

	if ( $suppress_filters ) {
		return $country;
	}

	/**
	 * Allows filtering of the players display country, this can be bypassed by setting $suppress_filters param
	 * to true.
	 */
	return apply_filters( 'clanspress_players_get_display_country', $country, $player_id, $return_type );
}

/**
 * Returns the players display city.
 *
 * @param int  $player_id The Player/User unique identifier.
 * @param bool $suppress_filters Disallows filtering of the value.
 *
 * @return string
 */
function clanspress_players_get_display_city( int $player_id = 0, bool $suppress_filters = false ): string {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$city = get_user_meta( $player_id, 'cp_player_city', true );

	if ( $suppress_filters ) {
		return $city;
	}

	/**
	 * Allows filtering of the players display city, this can be bypassed by setting $suppress_filters param
	 * to true.
	 */
	return apply_filters( 'clanspress_players_get_display_city', $city, $player_id );
}

/**
 * Returns the players display birthday.
 *
 * @param int  $player_id The Player/User unique identifier.
 * @param bool $suppress_filters Disallows filtering of the value.
 *
 * @return string
 */
function clanspress_players_get_display_birthday( int $player_id = 0, bool $suppress_filters = false ): string {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$birthday = get_user_meta( $player_id, 'cp_player_birthday', true );

	if ( $suppress_filters ) {
		return $birthday;
	}

	/**
	 * Allows filtering of the players display birthday, this can be bypassed by setting $suppress_filters param
	 * to true.
	 */
	return apply_filters( 'clanspress_players_get_display_birthday', $birthday, $player_id );
}

/**
 * Returns the players display age.
 *
 * @param int  $player_id The Player/User unique identifier.
 * @param bool $suppress_filters Disallows filtering of the value.
 *
 * @return string
 */
function clanspress_players_get_display_age( int $player_id = 0, bool $suppress_filters = false ): string {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$birthday = clanspress_players_get_display_birthday( $player_id, true );

	// Create DateTime objects.
	$birth = new DateTime( $birthday );
	$today = new DateTime();

	// Calculate the difference.
	$age = $today->diff( $birth )->y;

	if ( $suppress_filters ) {
		return (string) $age;
	}

	/**
	 * Allows filtering of the players display age, this can be bypassed by setting $suppress_filters param
	 * to true.
	 */
	return (string) apply_filters( 'clanspress_players_get_display_age', $age, $player_id, $birthday );
}

/**
 * Returns the players account first name.
 *
 * @param int  $player_id The Player/User unique identifier.
 * @param bool $suppress_filters Disallows filtering of the value.
 *
 * @return string
 */
function clanspress_players_get_account_firstname( int $player_id = 0, bool $suppress_filters = false ): string {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$first_name = get_user_meta( $player_id, 'cp_player_first_name', true );

	if ( $suppress_filters ) {
		return $first_name;
	}

	/**
	 * Allows filtering of the players account first name, this can be bypassed by setting $suppress_filters param
	 * to true.
	 */
	return apply_filters( 'clanspress_players_get_account_firstname', $first_name, $player_id );
}

/**
 * Returns the players account last name.
 *
 * @param int  $player_id The Player/User unique identifier.
 * @param bool $suppress_filters Disallows filtering of the value.
 *
 * @return string
 */
function clanspress_players_get_account_lastname( int $player_id = 0, bool $suppress_filters = false ): string {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$last_name = get_user_meta( $player_id, 'cp_player_last_name', true );

	if ( $suppress_filters ) {
		return $last_name;
	}

	/**
	 * Allows filtering of the players account last name, this can be bypassed by setting $suppress_filters param
	 * to true.
	 */
	return apply_filters( 'clanspress_players_get_account_lastname', $last_name, $player_id );
}

/**
 * Returns the players account full name.
 *
 * @param int  $player_id The Player/User unique identifier.
 * @param bool $suppress_filters Disallows filtering of the value.
 *
 * @return string
 */
function clanspress_players_get_account_fullname( int $player_id = 0, bool $suppress_filters = false ): string {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$first_name = clanspress_players_get_account_firstname( $player_id, $suppress_filters );
	$last_name  = clanspress_players_get_account_lastname( $player_id, $suppress_filters );
	$full_name  = $first_name . ' ' . $last_name;

	if ( $suppress_filters ) {
		return $full_name;
	}

	/**
	 * Allows filtering of the players account full name, this can be bypassed by setting $suppress_filters param
	 * to true.
	 */
	return apply_filters( 'clanspress_players_get_account_fullname', $full_name, $player_id, $first_name, $last_name );
}

/**
 * Get user navigation menu items for logged-in users.
 *
 * Returns an array of menu items that appear in the user nav dropdown.
 * Third-party plugins can filter this to add, remove, or modify items.
 *
 * @param int $user_id User ID.
 * @return array<int, array{
 *     id: string,
 *     label: string,
 *     url: string,
 *     icon?: string,
 *     group?: string,
 *     class?: string,
 *     target?: string,
 *     priority?: int
 * }> Array of menu items sorted by group and priority.
 */
function clanspress_get_user_nav_menu_items( int $user_id = 0 ): array {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}

	if ( ! $user_id ) {
		return array();
	}

	$profile_url  = function_exists( 'clanspress_get_player_profile_url' )
		? clanspress_get_player_profile_url( $user_id )
		: get_author_posts_url( $user_id );
	$settings_url = home_url( '/players/settings/' );

	$items = array(
		array(
			'id'       => 'profile',
			'label'    => __( 'My Profile', 'clanspress' ),
			'url'      => $profile_url,
			'icon'     => 'admin-users',
			'group'    => 'profile',
			'priority' => 10,
		),
		array(
			'id'       => 'settings',
			'label'    => __( 'Settings', 'clanspress' ),
			'url'      => $settings_url,
			'icon'     => 'admin-generic',
			'group'    => 'profile',
			'priority' => 20,
		),
	);

	if ( function_exists( 'clanspress_get_notifications_url' ) ) {
		$items[] = array(
			'id'       => 'notifications',
			'label'    => __( 'Notifications', 'clanspress' ),
			'url'      => clanspress_get_notifications_url( $user_id ),
			'icon'     => 'bell',
			'group'    => 'profile',
			'priority' => 30,
		);
	}

	if ( current_user_can( 'edit_posts' ) ) {
		$items[] = array(
			'id'       => 'dashboard',
			'label'    => __( 'Dashboard', 'clanspress' ),
			'url'      => admin_url(),
			'icon'     => 'dashboard',
			'group'    => 'admin',
			'priority' => 10,
		);
	}

	$items[] = array(
		'id'       => 'logout',
		'label'    => __( 'Log Out', 'clanspress' ),
		'url'      => wp_logout_url( home_url() ),
		'icon'     => 'exit',
		'group'    => 'logout',
		'class'    => 'clanspress-user-nav__menu-item--danger',
		'priority' => 100,
	);

	/**
	 * Filter user navigation menu items.
	 *
	 * Allows third-party plugins to add, remove, or modify menu items
	 * in the user navigation dropdown.
	 *
	 * @param array $items   Array of menu items.
	 * @param int   $user_id Current user ID.
	 */
	$items = (array) apply_filters( 'clanspress_user_nav_menu_items', $items, $user_id );

	usort(
		$items,
		static function ( $a, $b ) {
			$group_order = array(
				'profile' => 1,
				'teams'   => 2,
				'groups'  => 3,
				'admin'   => 4,
				'logout'  => 99,
			);

			$group_a = $a['group'] ?? 'other';
			$group_b = $b['group'] ?? 'other';

			$order_a = $group_order[ $group_a ] ?? 50;
			$order_b = $group_order[ $group_b ] ?? 50;

			if ( $order_a !== $order_b ) {
				return $order_a <=> $order_b;
			}

			$priority_a = $a['priority'] ?? 10;
			$priority_b = $b['priority'] ?? 10;

			return $priority_a <=> $priority_b;
		}
	);

	return $items;
}

/**
 * Get guest links for the user navigation block.
 *
 * Returns an array of links shown to non-logged-in users (typically Login and Register).
 *
 * @return array<int, array{
 *     id: string,
 *     label: string,
 *     url: string,
 *     style?: string,
 *     target?: string,
 *     priority?: int
 * }> Array of guest links sorted by priority.
 */
function clanspress_get_user_nav_guest_links(): array {
	$links = array(
		array(
			'id'       => 'login',
			'label'    => __( 'Log In', 'clanspress' ),
			'url'      => wp_login_url( get_permalink() ),
			'style'    => 'secondary',
			'priority' => 10,
		),
	);

	if ( get_option( 'users_can_register' ) ) {
		$links[] = array(
			'id'       => 'register',
			'label'    => __( 'Register', 'clanspress' ),
			'url'      => wp_registration_url(),
			'style'    => 'primary',
			'priority' => 20,
		);
	}

	/**
	 * Filter guest navigation links.
	 *
	 * Allows third-party plugins to add, remove, or modify links
	 * shown to non-logged-in users.
	 *
	 * @param array $links Array of guest links.
	 */
	$links = (array) apply_filters( 'clanspress_user_nav_guest_links', $links );

	usort(
		$links,
		static function ( $a, $b ) {
			$priority_a = $a['priority'] ?? 10;
			$priority_b = $b['priority'] ?? 10;
			return $priority_a <=> $priority_b;
		}
	);

	return $links;
}

/**
 * Add a menu item to the user navigation.
 *
 * Helper function for third-party plugins to easily add items.
 *
 * @param array $item Menu item configuration.
 * @return void
 */
function clanspress_add_user_nav_menu_item( array $item ): void {
	add_filter(
		'clanspress_user_nav_menu_items',
		static function ( $items ) use ( $item ) {
			$items[] = $item;
			return $items;
		}
	);
}

/**
 * Remove a menu item from the user navigation by ID.
 *
 * Helper function for third-party plugins to easily remove items.
 *
 * @param string $item_id The ID of the item to remove.
 * @return void
 */
function clanspress_remove_user_nav_menu_item( string $item_id ): void {
	add_filter(
		'clanspress_user_nav_menu_items',
		static function ( $items ) use ( $item_id ) {
			return array_filter(
				$items,
				static function ( $item ) use ( $item_id ) {
					return ( $item['id'] ?? '' ) !== $item_id;
				}
			);
		}
	);
}
