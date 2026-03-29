<?php
/**
 * Team domain object (`cp_team` post type).
 *
 * Holds mutable state for a team. Reading and writing the database is done through
 * {@see Team_Data_Store} implementations so third parties can swap storage.
 *
 * @package clanspress
 */

namespace Kernowdev\Clanspress\Extensions\Teams;

/**
 * Team entity: mirrors core post fields plus team meta (code, roster, flags, etc.).
 */
class Team {

	/**
	 * WordPress post ID; `0` means not yet saved.
	 *
	 * @var int
	 */
	protected int $id = 0;

	/** @var string Post title. */
	protected string $name = '';

	/** @var string Post name (slug). */
	protected string $slug = '';

	/** @var string Post content. */
	protected string $description = '';

	/** @var string Post status. */
	protected string $status = 'publish';

	/** @var int Post author (team owner). */
	protected int $author_id = 0;

	/** @var string Short team code meta. */
	protected string $code = '';

	/** @var string Team motto meta. */
	protected string $motto = '';

	/** @var string Join mode meta slug. */
	protected string $join_mode = 'open_join';

	/** @var bool Whether invites are allowed. */
	protected bool $allow_invites = true;

	/** @var bool Whether front-end editing is allowed. */
	protected bool $allow_frontend_edit = true;

	/** @var bool Whether banning members is allowed. */
	protected bool $allow_ban_players = true;

	/** @var bool Whether other teams may challenge this team (Matches). */
	protected bool $accept_challenges = true;

	/** @var string ISO country code for display (optional). */
	protected string $country = '';

	/** @var int Win count (record). */
	protected int $wins = 0;

	/** @var int Loss count (record). */
	protected int $losses = 0;

	/** @var int Draw count (record). */
	protected int $draws = 0;

	/** @var int Featured image attachment ID. */
	protected int $avatar_id = 0;

	/** @var int Cover image attachment ID. */
	protected int $cover_id = 0;

	/**
	 * Roster map from post meta: user ID => role slug.
	 *
	 * @var array<int, string>
	 */
	protected array $member_roles = array();

	/**
	 * WordPress post ID.
	 *
	 * @return int Zero when unsaved.
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Set post ID (normally only used by a data store after insert).
	 *
	 * @param int $id Post ID; negative values are clamped to 0.
	 * @return void
	 */
	public function set_id( int $id ): void {
		$this->id = max( 0, $id );
	}

	/**
	 * Whether this team corresponds to a saved post.
	 *
	 * @return bool
	 */
	public function exists(): bool {
		return $this->id > 0;
	}

	/**
	 * Post title (team name).
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 *
	 * @param string $name Post title.
	 * @return void
	 */
	public function set_name( string $name ): void {
		$this->name = $name;
	}

	/**
	 * Post slug (`post_name`).
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return $this->slug;
	}

	/**
	 *
	 * @param string $slug Sanitized slug.
	 * @return void
	 */
	public function set_slug( string $slug ): void {
		$this->slug = $slug;
	}

	/**
	 * Post content (description / bio HTML).
	 *
	 * @return string
	 */
	public function get_description(): string {
		return $this->description;
	}

	/**
	 *
	 * @param string $description Post content.
	 * @return void
	 */
	public function set_description( string $description ): void {
		$this->description = $description;
	}

	/**
	 * Post status (e.g. `publish`, `draft`).
	 *
	 * @return string
	 */
	public function get_status(): string {
		return $this->status;
	}

	/**
	 *
	 * @param string $status Post status.
	 * @return void
	 */
	public function set_status( string $status ): void {
		$this->status = $status;
	}

	/**
	 * Owner user ID (`post_author`).
	 *
	 * @return int
	 */
	public function get_author_id(): int {
		return $this->author_id;
	}

	/**
	 *
	 * @param int $author_id User ID.
	 * @return void
	 */
	public function set_author_id( int $author_id ): void {
		$this->author_id = max( 0, $author_id );
	}

	/**
	 * Team code meta.
	 *
	 * @return string
	 */
	public function get_code(): string {
		return $this->code;
	}

	/**
	 *
	 * @param string $code Team code.
	 * @return void
	 */
	public function set_code( string $code ): void {
		$this->code = $code;
	}

	/**
	 * Team motto meta.
	 *
	 * @return string
	 */
	public function get_motto(): string {
		return $this->motto;
	}

	/**
	 *
	 * @param string $motto Motto text.
	 * @return void
	 */
	public function set_motto( string $motto ): void {
		$this->motto = $motto;
	}

	/**
	 * Join mode option (validated elsewhere when persisting from admin/extension).
	 *
	 * @return string
	 */
	public function get_join_mode(): string {
		return $this->join_mode;
	}

	/**
	 *
	 * @param string $join_mode Join mode slug.
	 * @return void
	 */
	public function set_join_mode( string $join_mode ): void {
		$this->join_mode = $join_mode;
	}

	/**
	 *
	 * @return bool
	 */
	public function get_allow_invites(): bool {
		return $this->allow_invites;
	}

	/**
	 *
	 * @param bool $allow_invites Whether invites are enabled.
	 * @return void
	 */
	public function set_allow_invites( bool $allow_invites ): void {
		$this->allow_invites = $allow_invites;
	}

	/**
	 *
	 * @return bool
	 */
	public function get_allow_frontend_edit(): bool {
		return $this->allow_frontend_edit;
	}

	/**
	 *
	 * @param bool $allow_frontend_edit Whether front-end manage is enabled.
	 * @return void
	 */
	public function set_allow_frontend_edit( bool $allow_frontend_edit ): void {
		$this->allow_frontend_edit = $allow_frontend_edit;
	}

	/**
	 *
	 * @return bool
	 */
	public function get_allow_ban_players(): bool {
		return $this->allow_ban_players;
	}

	/**
	 *
	 * @param bool $allow_ban_players Whether banning is enabled.
	 * @return void
	 */
	public function set_allow_ban_players( bool $allow_ban_players ): void {
		$this->allow_ban_players = $allow_ban_players;
	}

	/**
	 * Whether this team accepts match challenges from other teams.
	 *
	 * @return bool
	 */
	public function get_accept_challenges(): bool {
		return $this->accept_challenges;
	}

	/**
	 * @param bool $accept_challenges Whether challenges are allowed.
	 * @return void
	 */
	public function set_accept_challenges( bool $accept_challenges ): void {
		$this->accept_challenges = $accept_challenges;
	}

	/**
	 * ISO country code (optional).
	 *
	 * @return string
	 */
	public function get_country(): string {
		return $this->country;
	}

	/**
	 * @param string $country ISO 3166-1 alpha-2 code or empty.
	 * @return void
	 */
	public function set_country( string $country ): void {
		$this->country = sanitize_text_field( $country );
	}

	/**
	 * @return int
	 */
	public function get_wins(): int {
		return $this->wins;
	}

	/**
	 * @param int $wins Wins.
	 * @return void
	 */
	public function set_wins( int $wins ): void {
		$this->wins = max( 0, $wins );
	}

	/**
	 * @return int
	 */
	public function get_losses(): int {
		return $this->losses;
	}

	/**
	 * @param int $losses Losses.
	 * @return void
	 */
	public function set_losses( int $losses ): void {
		$this->losses = max( 0, $losses );
	}

	/**
	 * @return int
	 */
	public function get_draws(): int {
		return $this->draws;
	}

	/**
	 * @param int $draws Draws.
	 * @return void
	 */
	public function set_draws( int $draws ): void {
		$this->draws = max( 0, $draws );
	}

	/**
	 * Team avatar attachment ID.
	 *
	 * @return int
	 */
	public function get_avatar_id(): int {
		return $this->avatar_id;
	}

	/**
	 *
	 * @param int $avatar_id Attachment ID.
	 * @return void
	 */
	public function set_avatar_id( int $avatar_id ): void {
		$this->avatar_id = max( 0, $avatar_id );
	}

	/**
	 * Team cover attachment ID.
	 *
	 * @return int
	 */
	public function get_cover_id(): int {
		return $this->cover_id;
	}

	/**
	 *
	 * @param int $cover_id Attachment ID.
	 * @return void
	 */
	public function set_cover_id( int $cover_id ): void {
		$this->cover_id = max( 0, $cover_id );
	}

	/**
	 * Raw roster map from storage (user ID => role slug).
	 *
	 * @return array<int, string>
	 */
	public function get_member_roles(): array {
		return $this->member_roles;
	}

	/**
	 * Replace the in-memory roster map (IDs coerced to positive integers).
	 *
	 * @param array<int|string, string> $roles User ID => role slug.
	 * @return void
	 */
	public function set_member_roles( array $roles ): void {
		$clean = array();
		foreach ( $roles as $uid => $role ) {
			$uid = (int) $uid;
			if ( $uid < 1 ) {
				continue;
			}
			$clean[ $uid ] = (string) $role;
		}
		$this->member_roles = $clean;
	}

	/**
	 * Create or update via the given store (`create` when ID is 0, else `update`).
	 *
	 * @param Team_Data_Store $store Active store.
	 * @return void
	 */
	public function save( Team_Data_Store $store ): void {
		if ( $this->id > 0 ) {
			$store->update( $this );
		} else {
			$store->create( $this );
		}
	}

	/**
	 * Trash or delete via the store.
	 *
	 * @param Team_Data_Store $store Active store.
	 * @param bool            $force When true, skip trash.
	 * @return bool True on success.
	 */
	public function delete( Team_Data_Store $store, bool $force = false ): bool {
		return $store->delete( $this, $force );
	}

	/**
	 * Public permalink for this team, or empty string if unsaved.
	 *
	 * @return string
	 */
	public function get_permalink(): string {
		if ( $this->id < 1 ) {
			return '';
		}
		$url = get_permalink( $this->id );

		return is_string( $url ) ? $url : '';
	}
}
