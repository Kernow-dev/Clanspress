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
