<?php
/**
 * Default {@see Team_Data_Store}: `cp_team` posts and post meta.
 *
 * @package clanspress
 */

namespace Kernowdev\Clanspress\Extensions\Teams;

use Kernowdev\Clanspress\Extensions\Data_Stores\WP_Post_Meta_Data_Store;

/**
 * Loads and persists {@see Team} objects using the posts table and postmeta.
 */
class Team_Data_Store_CPT extends WP_Post_Meta_Data_Store implements Team_Data_Store {

	/**
	 * Running Teams extension (sanitization, etc.).
	 *
	 * @var \Kernowdev\Clanspress\Extensions\Teams
	 */
	protected \Kernowdev\Clanspress\Extensions\Teams $extension;

	/**
	 * @param \Kernowdev\Clanspress\Extensions\Teams $extension Teams extension instance.
	 */
	public function __construct( \Kernowdev\Clanspress\Extensions\Teams $extension ) {
		$this->extension            = $extension;
		$this->meta_type            = 'post';
		$this->internal_meta_keys   = array(
			'cp_team_join_mode',
			'cp_team_allow_invites',
			'cp_team_allow_frontend_edit',
			'cp_team_allow_ban_players',
			'cp_team_code',
			'cp_team_motto',
			'cp_team_avatar_id',
			'cp_team_cover_id',
			'cp_team_member_roles',
			'cp_team_accept_challenges',
			'cp_team_country',
			'cp_team_wins',
			'cp_team_losses',
			'cp_team_draws',
		);
		$this->must_exist_meta_keys = array( 'cp_team_member_roles' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function read( int $id ): ?Team {
		if ( $id < 1 ) {
			return null;
		}

		$post = get_post( $id );
		if ( ! $post || 'cp_team' !== $post->post_type ) {
			return null;
		}

		$team = new Team();
		$team->set_id( (int) $post->ID );
		$team->set_name( (string) $post->post_title );
		$team->set_slug( (string) $post->post_name );
		$team->set_description( (string) $post->post_content );
		$team->set_status( (string) $post->post_status );
		$team->set_author_id( (int) $post->post_author );

		$team->set_join_mode(
			$this->extension->sanitize_team_join_mode( get_post_meta( $id, 'cp_team_join_mode', true ) )
		);
		$team->set_allow_invites( rest_sanitize_boolean( get_post_meta( $id, 'cp_team_allow_invites', true ) ) );
		$team->set_allow_frontend_edit( rest_sanitize_boolean( get_post_meta( $id, 'cp_team_allow_frontend_edit', true ) ) );
		$team->set_allow_ban_players( rest_sanitize_boolean( get_post_meta( $id, 'cp_team_allow_ban_players', true ) ) );

		$team->set_code( (string) get_post_meta( $id, 'cp_team_code', true ) );
		$team->set_motto( (string) get_post_meta( $id, 'cp_team_motto', true ) );
		$team->set_avatar_id( (int) get_post_meta( $id, 'cp_team_avatar_id', true ) );
		$team->set_cover_id( (int) get_post_meta( $id, 'cp_team_cover_id', true ) );

		$roles = get_post_meta( $id, 'cp_team_member_roles', true );
		$team->set_member_roles( is_array( $roles ) ? $roles : array() );

		$accept_raw = get_post_meta( $id, 'cp_team_accept_challenges', true );
		if ( '' === $accept_raw ) {
			$team->set_accept_challenges( true );
		} else {
			$team->set_accept_challenges( rest_sanitize_boolean( $accept_raw ) );
		}

		$team->set_country( (string) get_post_meta( $id, 'cp_team_country', true ) );
		$team->set_wins( max( 0, (int) get_post_meta( $id, 'cp_team_wins', true ) ) );
		$team->set_losses( max( 0, (int) get_post_meta( $id, 'cp_team_losses', true ) ) );
		$team->set_draws( max( 0, (int) get_post_meta( $id, 'cp_team_draws', true ) ) );

		return $team;
	}

	/**
	 * {@inheritDoc}
	 */
	public function create( Team $team ): void {
		$post_id = wp_insert_post(
			array(
				'post_type'    => 'cp_team',
				'post_title'   => $team->get_name(),
				'post_name'    => $team->get_slug(),
				'post_content' => $team->get_description(),
				'post_status'  => $team->get_status() ?: 'publish',
				'post_author'  => $team->get_author_id(),
			),
			true
		);

		if ( is_wp_error( $post_id ) || $post_id < 1 ) {
			return;
		}

		$team->set_id( (int) $post_id );
		$this->persist_team_meta( $team );
	}

	/**
	 * {@inheritDoc}
	 */
	public function update( Team $team ): void {
		$id = $team->get_id();
		if ( $id < 1 ) {
			return;
		}

		wp_update_post(
			array(
				'ID'           => $id,
				'post_title'   => $team->get_name(),
				'post_name'    => $team->get_slug(),
				'post_content' => $team->get_description(),
				'post_status'  => $team->get_status(),
				'post_author'  => $team->get_author_id(),
			)
		);

		$this->persist_team_meta( $team );
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete( Team $team, bool $force = false ): bool {
		$id = $team->get_id();
		if ( $id < 1 ) {
			return false;
		}

		$result = wp_delete_post( $id, $force );
		if ( false === $result || null === $result ) {
			return false;
		}

		$team->set_id( 0 );
		return true;
	}

	/**
	 * Write registered team meta keys from the object.
	 *
	 * @param Team $team Team with positive ID.
	 */
	protected function persist_team_meta( Team $team ): void {
		$id = $team->get_id();
		if ( $id < 1 ) {
			return;
		}

		$this->update_or_delete_post_meta( $id, 'cp_team_join_mode', $this->extension->sanitize_team_join_mode( $team->get_join_mode() ) );
		$this->update_or_delete_post_meta( $id, 'cp_team_allow_invites', $team->get_allow_invites() );
		$this->update_or_delete_post_meta( $id, 'cp_team_allow_frontend_edit', $team->get_allow_frontend_edit() );
		$this->update_or_delete_post_meta( $id, 'cp_team_allow_ban_players', $team->get_allow_ban_players() );
		$this->update_or_delete_post_meta( $id, 'cp_team_code', $team->get_code() );
		$this->update_or_delete_post_meta( $id, 'cp_team_motto', $team->get_motto() );
		$this->update_or_delete_post_meta( $id, 'cp_team_avatar_id', $team->get_avatar_id() );
		$this->update_or_delete_post_meta( $id, 'cp_team_cover_id', $team->get_cover_id() );
		$this->update_or_delete_post_meta( $id, 'cp_team_member_roles', $team->get_member_roles() );
		$this->update_or_delete_post_meta( $id, 'cp_team_accept_challenges', $team->get_accept_challenges() );
		$this->update_or_delete_post_meta( $id, 'cp_team_country', $team->get_country() );
		$this->update_or_delete_post_meta( $id, 'cp_team_wins', $team->get_wins() );
		$this->update_or_delete_post_meta( $id, 'cp_team_losses', $team->get_losses() );
		$this->update_or_delete_post_meta( $id, 'cp_team_draws', $team->get_draws() );
	}
}
