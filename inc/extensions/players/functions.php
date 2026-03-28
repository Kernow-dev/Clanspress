<?php

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
		$default_avatar = clanspress()->url . 'assets/img/avatars/default-avatar.png';
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
		$default_cover = clanspress()->url . 'assets/img/covers/default-cover.png';
	}

	return apply_filters( 'clanspress_players_get_default_cover', $default_cover, $player_id );
}

/**
 * Returns the players display avatar.
 *
 * @param int          $player_id The Player/User unique identifier.
 * @param bool         $suppress_filters Disallows filtering of the value.
 * @param string|array $size The image size, either a registered size, or an array of width and height.
 *
 * @return string
 */
function clanspress_players_get_display_avatar( int $player_id = 0, bool $suppress_filters = false, string|array $size = 'clanspress-avatar-large' ): string {
	if ( ! $player_id ) {
		$player_id = get_current_user_id();
	}

	$user_avatar = wp_get_attachment_image_url( clanspress_players_get_display_avatar_id( $player_id, $suppress_filters ), $size );

	if ( false === $user_avatar || '' === $user_avatar ) {
		$user_avatar = clanspress_players_get_default_avatar( $player_id );
	}

	if ( $suppress_filters ) {
		return $user_avatar;
	}

	/**
	 * Allows filtering of the players display avatar, this can be bypassed by setting $suppress_filters param
	 * to true.
	 */
	return apply_filters( 'clanspress_players_get_display_avatar', $user_avatar, $player_id );
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
