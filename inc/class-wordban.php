<?php
/**
 * Site-wide word filter (General settings): strict blocking for short identity fields, masking elsewhere.
 *
 * @package clanspress
 */

namespace Kernowdev\Clanspress;

defined( 'ABSPATH' ) || exit;

use WP_Error;
use Kernowdev\Clanspress\Admin\General_Settings;

/**
 * Registers hooks and implements matching/masking for the Clanspress word filter.
 */
final class Wordban {

	/**
	 * @var bool
	 */
	private static bool $hooks_registered = false;

	/**
	 * @var array<string, true>|null
	 */
	private static ?array $single_token_cache = null;

	/**
	 * @var array<int, array<int, string>>|null
	 */
	private static ?array $phrase_cache = null;

	/**
	 * @var array<int, string>|null
	 */
	private static ?array $canonical_words_cache = null;

	/**
	 * Whether the word filter is enabled (General → Moderation).
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		$settings = get_option( General_Settings::OPTION_KEY, array() );
		return ! empty( $settings['wordban_enabled'] );
	}

	/**
	 * Register WordPress hooks (idempotent).
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		if ( self::$hooks_registered ) {
			return;
		}
		self::$hooks_registered = true;

		add_filter( 'registration_errors', array( self::class, 'filter_registration_errors' ), 10, 3 );
		add_action( 'user_profile_update_errors', array( self::class, 'action_user_profile_update_errors' ), 10, 3 );
		add_filter( 'rest_pre_insert_user', array( self::class, 'filter_rest_pre_insert_user' ), 10, 2 );
		add_filter( 'rest_pre_update_user', array( self::class, 'filter_rest_pre_insert_user' ), 10, 2 );

		add_filter( 'rest_pre_insert_cp_team', array( self::class, 'filter_rest_pre_insert_team' ), 10, 2 );

		add_action( 'init', array( self::class, 'maybe_register_group_rest_filter' ), 20 );
	}

	/**
	 * Late-register group REST filter when Social Kit registers the CPT after priority 0.
	 *
	 * @return void
	 */
	public static function maybe_register_group_rest_filter(): void {
		if ( ! post_type_exists( 'cp_group' ) ) {
			return;
		}
		if ( has_filter( 'rest_pre_insert_cp_group', array( self::class, 'filter_rest_pre_insert_group' ) ) ) {
			return;
		}
		add_filter( 'rest_pre_insert_cp_group', array( self::class, 'filter_rest_pre_insert_group' ), 10, 2 );
	}

	/**
	 * Block banned material in a new username at registration.
	 *
	 * @param \WP_Error $errors Errors object.
	 * @param string    $sanitized_user_login Login.
	 * @param string    $user_email Email (unused).
	 * @return \WP_Error
	 */
	public static function filter_registration_errors( $errors, $sanitized_user_login, $user_email ) {
		unset( $user_email );
		if ( ! self::is_enabled() || ! ( $errors instanceof \WP_Error ) ) {
			return $errors;
		}
		$err = self::validate_strict_text( (string) $sanitized_user_login );
		if ( $err instanceof WP_Error ) {
			$errors->add( $err->get_error_code(), $err->get_error_message() );
		}
		return $errors;
	}

	/**
	 * Block banned material in profile fields (classic profile / admin).
	 *
	 * @param \WP_Error $errors Errors.
	 * @param bool      $update Whether updating existing user.
	 * @param \stdClass $user User object.
	 * @return void
	 */
	public static function action_user_profile_update_errors( $errors, $update, $user ): void {
		unset( $user );
		if ( ! self::is_enabled() || ! $update || ! ( $errors instanceof \WP_Error ) ) {
			return;
		}
		$fields = array( 'first_name', 'last_name', 'nickname', 'display_name' );
		foreach ( $fields as $field ) {
			if ( empty( $_POST[ $field ] ) || ! is_string( $_POST[ $field ] ) ) {
				continue;
			}
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below.
			$raw = wp_unslash( $_POST[ $field ] );
			$val = sanitize_text_field( (string) $raw );
			$err = self::validate_strict_text( $val );
			if ( $err instanceof WP_Error ) {
				$errors->add( $err->get_error_code(), $err->get_error_message() );
			}
		}
	}

	/**
	 * REST user create/update: validate login, slug, and display-related fields (array or object payloads).
	 *
	 * @param array|object|\WP_Error $prepared_user Prepared user data.
	 * @param \WP_REST_Request       $request Request.
	 * @return array|object|\WP_Error
	 */
	public static function filter_rest_pre_insert_user( $prepared_user, $request ) {
		unset( $request );
		if ( ! self::is_enabled() || $prepared_user instanceof WP_Error ) {
			return $prepared_user;
		}
		$parts = array();
		if ( is_array( $prepared_user ) ) {
			$parts[] = isset( $prepared_user['user_login'] ) ? (string) $prepared_user['user_login'] : '';
			$parts[] = isset( $prepared_user['name'] ) ? (string) $prepared_user['name'] : '';
			$parts[] = isset( $prepared_user['nickname'] ) ? (string) $prepared_user['nickname'] : '';
			$parts[] = isset( $prepared_user['slug'] ) ? (string) $prepared_user['slug'] : '';
		} elseif ( is_object( $prepared_user ) ) {
			$parts[] = isset( $prepared_user->user_login ) ? (string) $prepared_user->user_login : '';
			$parts[] = isset( $prepared_user->display_name ) ? (string) $prepared_user->display_name : '';
			$parts[] = isset( $prepared_user->nickname ) ? (string) $prepared_user->nickname : '';
			$parts[] = isset( $prepared_user->user_nicename ) ? (string) $prepared_user->user_nicename : '';
		} else {
			return $prepared_user;
		}
		$err = self::validate_user_payload_fields( $parts );
		return $err instanceof WP_Error ? $err : $prepared_user;
	}

	/**
	 * @param array<int, string> $parts Strings to validate.
	 * @return \WP_Error|null
	 */
	private static function validate_user_payload_fields( array $parts ): ?WP_Error {
		foreach ( $parts as $p ) {
			$p = trim( $p );
			if ( '' === $p ) {
				continue;
			}
			$err = self::validate_strict_text( $p );
			if ( $err instanceof WP_Error ) {
				return $err;
			}
		}
		return null;
	}

	/**
	 * Block banned words in team titles via REST.
	 *
	 * @param \stdClass|\WP_Post|\WP_Error $prepared_post Prepared post.
	 * @param \WP_REST_Request            $request Request.
	 * @return \stdClass|\WP_Post|\WP_Error
	 */
	public static function filter_rest_pre_insert_team( $prepared_post, $request ) {
		unset( $request );
		if ( ! self::is_enabled() || $prepared_post instanceof WP_Error || ! is_object( $prepared_post ) ) {
			return $prepared_post;
		}
		$title = isset( $prepared_post->post_title ) ? (string) $prepared_post->post_title : '';
		$err   = self::validate_strict_text( $title );
		return $err instanceof WP_Error ? $err : $prepared_post;
	}

	/**
	 * Block banned words in group titles via REST.
	 *
	 * @param \stdClass|\WP_Post|\WP_Error $prepared_post Prepared post.
	 * @param \WP_REST_Request            $request Request.
	 * @return \stdClass|\WP_Post|\WP_Error
	 */
	public static function filter_rest_pre_insert_group( $prepared_post, $request ) {
		unset( $request );
		if ( ! self::is_enabled() || $prepared_post instanceof WP_Error || ! is_object( $prepared_post ) ) {
			return $prepared_post;
		}
		$title = isset( $prepared_post->post_title ) ? (string) $prepared_post->post_title : '';
		$err   = self::validate_strict_text( $title );
		return $err instanceof WP_Error ? $err : $prepared_post;
	}

	/**
	 * Reject value if any banned whole-token (or phrase) matches.
	 *
	 * @param string $text Text to check.
	 * @return \WP_Error|null Null when allowed.
	 */
	public static function validate_strict_text( string $text ): ?WP_Error {
		if ( ! self::is_enabled() || '' === trim( $text ) ) {
			return null;
		}

		$tokens = self::tokenize_for_strict( $text );
		$banned = self::get_single_token_map();

		foreach ( $tokens as $t ) {
			if ( '' === $t || strlen( $t ) < 2 ) {
				continue;
			}
			if ( isset( $banned[ $t ] ) ) {
				return self::blocked_error();
			}
		}

		foreach ( self::get_phrases() as $phrase ) {
			if ( self::tokens_contain_phrase( $tokens, $phrase ) ) {
				return self::blocked_error();
			}
		}

		return null;
	}

	/**
	 * Mask banned words in plain text (no HTML).
	 *
	 * @param string $text Input.
	 * @return string
	 */
	public static function mask_plain_text( string $text ): string {
		if ( ! self::is_enabled() || '' === $text ) {
			return $text;
		}
		$out = $text;
		foreach ( self::get_canonical_words_longest_first() as $word ) {
			if ( strlen( $word ) < 2 ) {
				continue;
			}
			$pattern = self::build_flexible_word_pattern( $word );
			if ( '' === $pattern ) {
				continue;
			}
			// phpcs:ignore WordPress.PHP.PregReplaceCallback.eMod -- Unicode word boundaries need `u`.
			$out = (string) preg_replace_callback(
				$pattern,
				static function ( array $m ): string {
					return self::asterisk_mask_match( $m[0] );
				},
				$out
			);
		}

		/**
		 * Filter plain text after the word mask runs.
		 *
		 * @param string $out    Masked text.
		 * @param string $text   Original text.
		 */
		return (string) apply_filters( 'clanspress_wordban_masked_plain_text', $out, $text );
	}

	/**
	 * Mask banned words only outside HTML tags.
	 *
	 * @param string $html HTML fragment.
	 * @return string
	 */
	public static function mask_html_content( string $html ): string {
		if ( ! self::is_enabled() || '' === $html ) {
			return $html;
		}

		// Preserve entire `<script>` / `<style>` blocks so we never mutate JS/CSS or misparse `>` inside them.
		$placeholders = array();
		$stripped     = (string) preg_replace_callback(
			'#<(?:script|style)\b[^>]*>.*?</(?:script|style)>#is',
			static function ( array $m ) use ( &$placeholders ): string {
				$key                  = '<!--clanspress-wb-' . \wp_generate_password( 12, false, false ) . '-->';
				$placeholders[ $key ] = $m[0];
				return $key;
			},
			$html
		);

		$parts = preg_split( '/(<[^>]*>)/', $stripped, -1, PREG_SPLIT_DELIM_CAPTURE );
		if ( ! is_array( $parts ) ) {
			$out = self::mask_plain_text( $stripped );
		} else {
			$buf = '';
			foreach ( $parts as $part ) {
				if ( '' === $part ) {
					continue;
				}
				if ( 1 === preg_match( '/^<[^>]+>$/', $part ) ) {
					$buf .= $part;
				} else {
					$buf .= self::mask_plain_text( $part );
				}
			}
			$out = $buf;
		}

		if ( array() !== $placeholders ) {
			$out = strtr( $out, $placeholders );
		}

		/**
		 * Filter HTML after the word mask runs on text nodes.
		 *
		 * @param string $out  Masked HTML.
		 * @param string $html Original HTML.
		 */
		return (string) apply_filters( 'clanspress_wordban_masked_html', $out, $html );
	}

	/**
	 * Clear in-request caches (e.g. after tests or option updates).
	 *
	 * @return void
	 */
	public static function clear_caches(): void {
		self::$single_token_cache = null;
		self::$phrase_cache       = null;
		self::$canonical_words_cache = null;
	}

	/**
	 * @return \WP_Error
	 */
	private static function blocked_error(): WP_Error {
		return new WP_Error(
			'clanspress_wordban_blocked',
			__( 'That text is not allowed.', 'clanspress' ),
			array( 'status' => 400 )
		);
	}

	/**
	 * @param string $matched Full regex match.
	 * @return string
	 */
	private static function asterisk_mask_match( string $matched ): string {
		$len = function_exists( 'mb_strlen' ) ? mb_strlen( $matched, 'UTF-8' ) : strlen( $matched );
		if ( $len <= 1 ) {
			return '*';
		}
		$first = function_exists( 'mb_substr' )
			? mb_substr( $matched, 0, 1, 'UTF-8' )
			: $matched[0];
		$rest  = function_exists( 'mb_strlen' )
			? max( 1, $len - 1 )
			: max( 1, strlen( $matched ) - 1 );

		return $first . str_repeat( '*', $rest );
	}

	/**
	 * @param string $text Raw text.
	 * @return array<int, string> Normalized tokens.
	 */
	private static function tokenize_for_strict( string $text ): array {
		$text = strtolower( (string) wp_strip_all_tags( $text ) );
		$text = remove_accents( $text );
		$parts = preg_split( '/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY );
		if ( ! is_array( $parts ) ) {
			return array();
		}
		$out = array();
		foreach ( $parts as $p ) {
			$canon = self::canonicalize_alnum_token( (string) $p );
			if ( '' !== $canon ) {
				$out[] = $canon;
			}
		}
		return $out;
	}

	/**
	 * @param string $token Letters/digits only chunk.
	 * @return string
	 */
	private static function canonicalize_alnum_token( string $token ): string {
		$token = strtolower( $token );
		$chars = preg_split( '//u', $token, -1, PREG_SPLIT_NO_EMPTY );
		if ( ! is_array( $chars ) ) {
			return '';
		}
		$buf = '';
		foreach ( $chars as $ch ) {
			if ( 1 === strlen( $ch ) && ord( $ch ) < 128 ) {
				$buf .= self::map_leet_char( $ch );
			} else {
				$buf .= $ch;
			}
		}
		return $buf;
	}

	/**
	 * @param string $ch Single byte (ASCII) or multibyte — we only map ASCII leet.
	 * @return string
	 */
	private static function map_leet_char( string $ch ): string {
		static $map = array(
			'0' => 'o',
			'1' => 'i',
			'3' => 'e',
			'4' => 'a',
			'5' => 's',
			'7' => 't',
			'8' => 'b',
			'9' => 'g',
			'@' => 'a',
			'$' => 's',
			'!' => 'i',
			'|' => 'l',
			'+' => 't',
		);
		return $map[ $ch ] ?? $ch;
	}

	/**
	 * @param array<int, string> $tokens Normalized tokens.
	 * @param array<int, string> $phrase Phrase tokens.
	 * @return bool
	 */
	private static function tokens_contain_phrase( array $tokens, array $phrase ): bool {
		$plen = count( $phrase );
		if ( $plen < 1 ) {
			return false;
		}
		$tlen = count( $tokens );
		for ( $i = 0; $i <= $tlen - $plen; $i++ ) {
			$slice = array_slice( $tokens, $i, $plen );
			if ( $slice === $phrase ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @return array<string, true>
	 */
	private static function get_single_token_map(): array {
		if ( null !== self::$single_token_cache ) {
			return self::$single_token_cache;
		}
		$words = self::get_merged_canonical_words();
		$map   = array();
		foreach ( $words as $w ) {
			if ( strlen( $w ) < 2 ) {
				continue;
			}
			if ( str_contains( $w, ' ' ) ) {
				continue;
			}
			$map[ $w ] = true;
		}
		self::$single_token_cache = $map;
		return self::$single_token_cache;
	}

	/**
	 * @return array<int, array<int, string>>
	 */
	private static function get_phrases(): array {
		if ( null !== self::$phrase_cache ) {
			return self::$phrase_cache;
		}
		$out = array();
		foreach ( self::get_merged_canonical_words() as $w ) {
			if ( ! str_contains( $w, ' ' ) ) {
				continue;
			}
			$parts = preg_split( '/\s+/', $w, -1, PREG_SPLIT_NO_EMPTY );
			if ( ! is_array( $parts ) || count( $parts ) < 2 ) {
				continue;
			}
			$norm = array();
			foreach ( $parts as $p ) {
				$norm[] = self::canonicalize_alnum_token( (string) $p );
			}
			$norm = array_values( array_filter( $norm ) );
			if ( count( $norm ) > 1 ) {
				$out[] = $norm;
			}
		}
		self::$phrase_cache = $out;
		return self::$phrase_cache;
	}

	/**
	 * Tokens used for flexible masking (single words only; phrases contribute each token), longest first.
	 *
	 * @return array<int, string>
	 */
	private static function get_canonical_words_longest_first(): array {
		if ( null !== self::$canonical_words_cache ) {
			return self::$canonical_words_cache;
		}
		$tokens = array();
		foreach ( self::get_merged_canonical_words() as $w ) {
			if ( str_contains( $w, ' ' ) ) {
				foreach ( preg_split( '/\s+/', $w, -1, PREG_SPLIT_NO_EMPTY ) as $p ) {
					$p = (string) $p;
					if ( strlen( $p ) >= 2 ) {
						$tokens[] = $p;
					}
				}
			} elseif ( strlen( $w ) >= 2 ) {
				$tokens[] = $w;
			}
		}
		$tokens = array_values( array_unique( $tokens ) );
		usort(
			$tokens,
			static function ( string $a, string $b ): int {
				return strlen( $b ) <=> strlen( $a );
			}
		);
		self::$canonical_words_cache = $tokens;
		return self::$canonical_words_cache;
	}

	/**
	 * Default + custom list, normalized (lowercase, leet-collapsed; phrases keep spaces).
	 *
	 * @return array<int, string>
	 */
	private static function get_merged_canonical_words(): array {
		$defaults = self::default_banned_canonical();
		$custom   = self::parse_custom_list();
		$merged   = array_merge( $defaults, $custom );
		$merged   = array_values( array_unique( array_filter( array_map( 'trim', $merged ) ) ) );

		/**
		 * Filter merged canonical banned tokens/phrases after defaults and the option list are combined.
		 *
		 * Each entry is either a single normalized token (letters only) or a space-separated phrase of tokens.
		 *
		 * @param array<int, string> $merged Merged list.
		 */
		return (array) apply_filters( 'clanspress_wordban_merged_tokens', $merged );
	}

	/**
	 * Built-in list: common profanity roots (ASCII), lowercase. Leet variants are detected via normalization / flexible patterns.
	 *
	 * @return array<int, string>
	 */
	private static function default_banned_canonical(): array {
		return array(
			'anal',
			'arse',
			'ass',
			'ballsack',
			'bastard',
			'bitch',
			'blowjob',
			'bollock',
			'boner',
			'boob',
			'bugger',
			'bullshit',
			'clitoris',
			'cock',
			'crap',
			'cum',
			'cunt',
			'damn',
			'dick',
			'dildo',
			'fellatio',
			'fuck',
			'hell',
			'homo',
			'jizz',
			'labia',
			'motherfucker',
			'penis',
			'piss',
			'porn',
			'pussy',
			'rape',
			'rapist',
			'scrotum',
			'shit',
			'slut',
			'spunk',
			'testicle',
			'tit',
			'tits',
			'twat',
			'vagina',
			'wank',
			'whore',
		);
	}

	/**
	 * @return array<int, string>
	 */
	private static function parse_custom_list(): array {
		$settings = get_option( General_Settings::OPTION_KEY, array() );
		$raw      = isset( $settings['wordban_custom_list'] ) ? (string) $settings['wordban_custom_list'] : '';
		$raw      = str_replace( array( "\r\n", "\r" ), "\n", $raw );
		$bits     = preg_split( '/[,\n]+/', $raw, -1, PREG_SPLIT_NO_EMPTY );
		if ( ! is_array( $bits ) ) {
			return array();
		}
		$out = array();
		foreach ( $bits as $bit ) {
			$bit = trim( (string) $bit );
			if ( '' === $bit ) {
				continue;
			}
			if ( preg_match( '/\s/', $bit ) ) {
				$parts = preg_split( '/\s+/', strtolower( remove_accents( $bit ) ), -1, PREG_SPLIT_NO_EMPTY );
				if ( ! is_array( $parts ) ) {
					continue;
				}
				$norm = array();
				foreach ( $parts as $p ) {
					$p    = preg_replace( '/[^\p{L}\p{N}]+/u', '', (string) $p );
					$norm[] = self::canonicalize_alnum_token( (string) $p );
				}
				$norm = array_values( array_filter( $norm ) );
				if ( count( $norm ) > 1 ) {
					$out[] = implode( ' ', $norm );
				} elseif ( 1 === count( $norm ) ) {
					$out[] = $norm[0];
				}
			} else {
				$bit = strtolower( remove_accents( $bit ) );
				$bit = preg_replace( '/[^\p{L}\p{N}]+/u', '', $bit );
				if ( '' !== $bit ) {
					$out[] = self::canonicalize_alnum_token( $bit );
				}
			}
		}
		return $out;
	}

	/**
	 * @param string $canonical Lowercase letters only.
	 * @return string Regex with `u` modifier recommended.
	 */
	private static function build_flexible_word_pattern( string $canonical ): string {
		$chars = preg_split( '//u', $canonical, -1, PREG_SPLIT_NO_EMPTY );
		if ( ! is_array( $chars ) || array() === $chars ) {
			return '';
		}
		$chunks = array();
		foreach ( $chars as $ch ) {
			$ch     = strtolower( $ch );
			$alts   = self::leet_class_alternatives( $ch );
			$quoted = array_map(
				static function ( string $a ): string {
					return preg_quote( $a, '/' );
				},
				$alts
			);
			$chunks[] = '(?:' . implode( '|', $quoted ) . ')';
		}
		return '/(?<![\p{L}\p{N}])' . implode( '[^\p{L}\p{N}]*', $chunks ) . '(?![\p{L}\p{N}])/iu';
	}

	/**
	 * @param string $letter Single character a-z.
	 * @return array<int, string>
	 */
	private static function leet_class_alternatives( string $letter ): array {
		$letter = strtolower( $letter );
		$map    = array(
			'a' => array( 'a', '4', '@' ),
			'b' => array( 'b', '8' ),
			'e' => array( 'e', '3' ),
			'g' => array( 'g', '9' ),
			'i' => array( 'i', '1', '!', '|', 'l' ),
			'l' => array( 'l', '1', '|' ),
			'o' => array( 'o', '0' ),
			's' => array( 's', '5', '$' ),
			't' => array( 't', '7', '+' ),
			'z' => array( 'z', '2' ),
		);
		if ( isset( $map[ $letter ] ) ) {
			return array_values( array_unique( $map[ $letter ] ) );
		}
		return array( $letter );
	}
}
