( function ( wp ) {
	const { registerPlugin } = wp.plugins;
	const { PluginDocumentSettingPanel } = wp.editPost;
	const { createElement: el } = wp.element;
	const { SelectControl, ToggleControl } = wp.components;
	const { useSelect, useDispatch } = wp.data;
	const { __ } = wp.i18n;
	const { applyFilters } = wp.hooks;

	const PANEL_NAME = 'clanspress-team-options-panel';

	const TeamOptionsPanel = function () {
		const postType = useSelect( function ( select ) {
			return select( 'core/editor' ).getCurrentPostType();
		}, [] );

		if ( 'cp_team' !== postType ) {
			return null;
		}

		const meta = useSelect( function ( select ) {
			return select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {};
		}, [] );

		const editPost = useDispatch( 'core/editor' ).editPost;

		const baseJoinModes = [
			{ label: __( 'Open join', 'clanspress' ), value: 'open_join' },
			{
				label: __( 'Join with permission', 'clanspress' ),
				value: 'join_with_permission',
			},
			{ label: __( 'Invite only', 'clanspress' ), value: 'invite_only' },
		];

		const joinModes = applyFilters(
			'clanspress.teams.joinModes',
			baseJoinModes,
			meta
		);

		const setMetaValue = function ( key, value ) {
			editPost( {
				meta: Object.assign( {}, meta, { [ key ]: value } ),
			} );
		};

		const baseControls = [
			el( SelectControl, {
				key: 'cp_team_join_mode',
				label: __( 'Join mode', 'clanspress' ),
				value: meta.cp_team_join_mode || 'open_join',
				options: joinModes,
				onChange: function ( value ) {
					setMetaValue( 'cp_team_join_mode', value );
				},
			} ),
			el( ToggleControl, {
				key: 'cp_team_allow_invites',
				label: __( 'Allow player invites', 'clanspress' ),
				checked: !! meta.cp_team_allow_invites,
				onChange: function ( value ) {
					setMetaValue( 'cp_team_allow_invites', !! value );
				},
			} ),
			el( ToggleControl, {
				key: 'cp_team_allow_frontend_edit',
				label: __( 'Allow front-end team editing', 'clanspress' ),
				checked: !! meta.cp_team_allow_frontend_edit,
				onChange: function ( value ) {
					setMetaValue( 'cp_team_allow_frontend_edit', !! value );
				},
			} ),
			el( ToggleControl, {
				key: 'cp_team_allow_ban_players',
				label: __( 'Allow banning players', 'clanspress' ),
				checked: !! meta.cp_team_allow_ban_players,
				onChange: function ( value ) {
					setMetaValue( 'cp_team_allow_ban_players', !! value );
				},
			} ),
		];

		const controls = applyFilters(
			'clanspress.teams.optionControls',
			baseControls,
			{
				meta: meta,
				setMetaValue: setMetaValue,
				components: wp.components,
				element: wp.element,
			}
		);

		return el(
			PluginDocumentSettingPanel,
			{
				name: PANEL_NAME,
				title: __( 'Team Options', 'clanspress' ),
			},
			controls
		);
	};

	registerPlugin( 'clanspress-team-options', {
		render: TeamOptionsPanel,
		icon: null,
	} );
} )( window.wp );
