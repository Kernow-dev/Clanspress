<?php
/**
 * Persistence layer for Team entities (CPT-backed by default).
 *
 * @package clanspress
 */

namespace Kernowdev\Clanspress\Extensions\Teams;

/**
 * CRUD for a single team (`cp_team` by default).
 */
interface Team_Data_Store {

	/**
	 * Load a team by post ID, or null if the post is missing or not a `cp_team`.
	 *
	 * @param int $id Post ID.
	 * @return Team|null
	 */
	public function read( int $id ): ?Team;

	/**
	 * Create a new team post and set `$team->set_id()` to the new ID.
	 *
	 * @param Team $team In-memory team; ID must be 0.
	 * @return void
	 */
	public function create( Team $team ): void;

	/**
	 * Persist post fields and meta for an existing team.
	 *
	 * @param Team $team Team with positive ID.
	 * @return void
	 */
	public function update( Team $team ): void;

	/**
	 * Trash or permanently delete the team post.
	 *
	 * @param Team $team  Team to remove.
	 * @param bool $force When true, skip trash.
	 * @return bool True on success.
	 */
	public function delete( Team $team, bool $force = false ): bool;
}
