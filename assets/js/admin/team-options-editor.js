( function ( wp ) {
	const { registerPlugin } = wp.plugins;
	const { PluginDocumentSettingPanel } = wp.editPost;
	const { createElement: el, Fragment } = wp.element;
	const {
		SelectControl,
		ToggleControl,
		TextControl,
		TextareaControl,
		PanelRow,
		Button,
		Spinner,
	} = wp.components;
	const { useSelect } = wp.data;
	const { useEntityProp } = wp.coreData;
	const { MediaUpload, MediaUploadCheck } = wp.blockEditor;
	const { __ } = wp.i18n;
	const { applyFilters } = wp.hooks;

	function getCountryOptions() {
		const w =
			typeof window !== 'undefined' && window.clanspressTeamEditor
				? window.clanspressTeamEditor
				: null;
		if ( w && Array.isArray( w.countries ) && w.countries.length ) {
			return w.countries;
		}
		return [ { value: '', label: __( '— Select —', 'clanspress' ) } ];
	}

	function useTeamMeta() {
		const [ rawMeta, setMeta ] = useEntityProp(
			'postType',
			'cp_team',
			'meta'
		);
		const meta = rawMeta && typeof rawMeta === 'object' ? rawMeta : {};
		const patch = function ( key, value ) {
			setMeta( Object.assign( {}, meta, { [ key ]: value } ) );
		};
		return { meta, patchMeta: patch };
	}

	function boolMeta( meta, key, defaultTrue ) {
		if ( ! Object.prototype.hasOwnProperty.call( meta, key ) ) {
			return defaultTrue;
		}
		const v = meta[ key ];
		if ( v === '' || v === null || v === undefined ) {
			return defaultTrue;
		}
		return !! v;
	}

	function statString( meta, key ) {
		const v = meta[ key ];
		if ( v === undefined || v === null || v === '' ) {
			return '0';
		}
		return String( v );
	}

	function TeamBasicFields() {
		const _useTeamMeta = useTeamMeta();
		const meta = _useTeamMeta.meta;
		const patch = _useTeamMeta.patchMeta;

		return el(
			Fragment,
			null,
			el(
				PanelRow,
				{ key: 'cp_team_code' },
				el( TextControl, {
					label: __( 'Team code', 'clanspress' ),
					value: meta.cp_team_code || '',
					onChange( value ) {
						patch( 'cp_team_code', value );
					},
				} )
			),
			el(
				PanelRow,
				{ key: 'cp_team_motto' },
				el( TextareaControl, {
					label: __( 'Motto', 'clanspress' ),
					value: meta.cp_team_motto || '',
					onChange( value ) {
						patch( 'cp_team_motto', value );
					},
					rows: 3,
				} )
			),
			el(
				PanelRow,
				{ key: 'cp_team_country' },
				el( SelectControl, {
					label: __( 'Country', 'clanspress' ),
					value:
						meta.cp_team_country != null
							? String( meta.cp_team_country )
							: '',
					options: getCountryOptions(),
					onChange( value ) {
						patch( 'cp_team_country', value );
					},
				} )
			),
			el(
				PanelRow,
				{ key: 'cp_team_wins' },
				el( TextControl, {
					label: __( 'Wins', 'clanspress' ),
					type: 'number',
					min: 0,
					value: statString( meta, 'cp_team_wins' ),
					onChange( value ) {
						patch( 'cp_team_wins', parseInt( value, 10 ) || 0 );
					},
				} )
			),
			el(
				PanelRow,
				{ key: 'cp_team_losses' },
				el( TextControl, {
					label: __( 'Losses', 'clanspress' ),
					type: 'number',
					min: 0,
					value: statString( meta, 'cp_team_losses' ),
					onChange( value ) {
						patch( 'cp_team_losses', parseInt( value, 10 ) || 0 );
					},
				} )
			),
			el(
				PanelRow,
				{ key: 'cp_team_draws' },
				el( TextControl, {
					label: __( 'Draws', 'clanspress' ),
					type: 'number',
					min: 0,
					value: statString( meta, 'cp_team_draws' ),
					onChange( value ) {
						patch( 'cp_team_draws', parseInt( value, 10 ) || 0 );
					},
				} )
			)
		);
	}

	function getDefaultUrls() {
		const w =
			typeof window !== 'undefined' && window.clanspressTeamEditor
				? window.clanspressTeamEditor
				: null;
		const d = w && w.defaults ? w.defaults : {};
		return {
			avatar: d.avatarUrl || '',
			cover: d.coverUrl || '',
		};
	}

	function TeamBrandingImageRow( props ) {
		const metaKey = props.metaKey;
		const label = props.label;
		const help = props.help;
		const patch = props.patchMeta;
		const meta = props.meta;
		const defaultUrl = props.defaultUrl || '';
		const variant = props.variant || 'avatar';

		const id = parseInt( meta[ metaKey ], 10 ) || 0;

		const media = useSelect(
			function ( select ) {
				if ( ! id ) {
					return { state: 'none' };
				}
				const rec = select( 'core' ).getEntityRecord(
					'postType',
					'attachment',
					id
				);
				if ( rec === undefined ) {
					return { state: 'loading' };
				}
				if ( ! rec || ! rec.source_url ) {
					return { state: 'missing' };
				}
				return { state: 'ok', url: rec.source_url };
			},
			[ id ]
		);

		let previewSrc = '';
		if ( id ) {
			if ( media.state === 'ok' && media.url ) {
				previewSrc = media.url;
			}
		} else if ( defaultUrl ) {
			previewSrc = defaultUrl;
		}

		const previewWrapStyle =
			variant === 'cover'
				? {
						position: 'relative',
						width: '100%',
						minHeight: '120px',
						maxHeight: '160px',
						borderRadius: '6px',
						overflow: 'hidden',
						background: '#f0f0f1',
						border: '1px solid #c3c4c7',
				  }
				: {
						position: 'relative',
						width: '120px',
						height: '120px',
						borderRadius: '6px',
						overflow: 'hidden',
						background: '#f0f0f1',
						border: '1px solid #c3c4c7',
				  };

		const imgStyle =
			variant === 'cover'
				? {
						width: '100%',
						height: '100%',
						minHeight: '120px',
						objectFit: 'cover',
						display: 'block',
				  }
				: {
						width: '100%',
						height: '100%',
						objectFit: 'cover',
						display: 'block',
				  };

		return el(
			PanelRow,
			{ key: metaKey + '-row' },
			el(
				'div',
				{
					className: 'clanspress-team-editor-branding-field',
					style: { width: '100%' },
				},
				el(
					'p',
					{ className: 'components-base-control__label' },
					label
				),
				help
					? el(
							'p',
							{
								className: 'components-base-control__help',
								style: { marginTop: 0 },
							},
							help
					  )
					: null,
				el(
					'div',
					{ style: previewWrapStyle },
					media.state === 'loading' && id
						? el(
								'div',
								{
									style: {
										display: 'flex',
										alignItems: 'center',
										justifyContent: 'center',
										height: '100%',
										minHeight: '120px',
									},
								},
								el( Spinner, null )
						  )
						: null,
					previewSrc && ! ( media.state === 'loading' && id )
						? el( 'img', {
								src: previewSrc,
								alt: '',
								style: imgStyle,
						  } )
						: null,
					! previewSrc &&
						! ( media.state === 'loading' && id ) &&
						media.state === 'missing' &&
						id
						? el(
								'div',
								{
									style: {
										padding: '12px',
										fontSize: '12px',
										color: '#646970',
									},
								},
								__(
									'Image unavailable. Replace it or use the site default.',
									'clanspress'
								)
						  )
						: null
				),
				el(
					'div',
					{
						style: {
							marginTop: '10px',
							display: 'flex',
							flexWrap: 'wrap',
							gap: '8px',
							alignItems: 'center',
						},
					},
					el(
						MediaUploadCheck,
						{},
						el( MediaUpload, {
							onSelect( image ) {
								patch( metaKey, image.id );
							},
							allowedTypes: [ 'image' ],
							value: id || undefined,
							render( obj ) {
								return el(
									Button,
									{
										variant: 'secondary',
										onClick: obj.open,
									},
									id
										? __( 'Replace image', 'clanspress' )
										: __( 'Choose image', 'clanspress' )
								);
							},
						} )
					),
					id
						? el(
								Button,
								{
									variant: 'tertiary',
									onClick() {
										patch( metaKey, 0 );
									},
								},
								__( 'Use site default', 'clanspress' )
						  )
						: null
				),
				el(
					'p',
					{
						className: 'components-base-control__help',
						style: { marginTop: '8px', marginBottom: 0 },
					},
					id
						? __(
								'Custom image — replace or restore the site default above.',
								'clanspress'
						  )
						: __(
								'Using site default image on the front end.',
								'clanspress'
						  )
				)
			)
		);
	}

	function TeamBrandingFields() {
		const _useTeamMeta2 = useTeamMeta();
		const meta = _useTeamMeta2.meta;
		const patch = _useTeamMeta2.patchMeta;
		const def = getDefaultUrls();

		return el(
			Fragment,
			null,
			el( TeamBrandingImageRow, {
				metaKey: 'cp_team_avatar_id',
				label: __( 'Team avatar', 'clanspress' ),
				help: __(
					'Shown on team templates. Leave as site default or pick a custom image.',
					'clanspress'
				),
				meta,
				patchMeta: patch,
				defaultUrl: def.avatar,
				variant: 'avatar',
			} ),
			el( TeamBrandingImageRow, {
				metaKey: 'cp_team_cover_id',
				label: __( 'Team cover', 'clanspress' ),
				help: __(
					'Wide image behind the team header. Leave as site default or pick a custom image.',
					'clanspress'
				),
				meta,
				patchMeta: patch,
				defaultUrl: def.cover,
				variant: 'cover',
			} )
		);
	}

	function TeamMembershipFields() {
		const _useTeamMeta3 = useTeamMeta();
		const meta = _useTeamMeta3.meta;
		const patch = _useTeamMeta3.patchMeta;

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

		const membershipControls = [
			el(
				PanelRow,
				{ key: 'cp_team_join_mode' },
				el( SelectControl, {
					label: __( 'Join mode', 'clanspress' ),
					value: meta.cp_team_join_mode || 'open_join',
					options: joinModes,
					onChange( value ) {
						patch( 'cp_team_join_mode', value );
					},
				} )
			),
			el(
				PanelRow,
				{ key: 'cp_team_allow_invites' },
				el( ToggleControl, {
					label: __( 'Allow player invites', 'clanspress' ),
					checked: boolMeta( meta, 'cp_team_allow_invites', true ),
					onChange( value ) {
						patch( 'cp_team_allow_invites', !! value );
					},
				} )
			),
			el(
				PanelRow,
				{ key: 'cp_team_allow_frontend_edit' },
				el( ToggleControl, {
					label: __( 'Allow front-end team editing', 'clanspress' ),
					checked: boolMeta(
						meta,
						'cp_team_allow_frontend_edit',
						true
					),
					onChange( value ) {
						patch( 'cp_team_allow_frontend_edit', !! value );
					},
				} )
			),
			el(
				PanelRow,
				{ key: 'cp_team_allow_ban_players' },
				el( ToggleControl, {
					label: __( 'Allow banning players', 'clanspress' ),
					checked: boolMeta(
						meta,
						'cp_team_allow_ban_players',
						true
					),
					onChange( value ) {
						patch( 'cp_team_allow_ban_players', !! value );
					},
				} )
			),
			el(
				PanelRow,
				{ key: 'cp_team_accept_challenges' },
				el( ToggleControl, {
					label: __( 'Accept match challenges', 'clanspress' ),
					checked: boolMeta(
						meta,
						'cp_team_accept_challenges',
						true
					),
					onChange( value ) {
						patch( 'cp_team_accept_challenges', !! value );
					},
				} )
			),
		];

		const filtered = applyFilters(
			'clanspress.teams.optionControls',
			membershipControls,
			{
				meta,
				setMetaValue: patch,
				patchMeta: patch,
				components: wp.components,
				element: wp.element,
			}
		);

		const list = Array.isArray( filtered )
			? filtered.filter( function ( node ) {
					return node != null;
			  } )
			: [];

		return wp.element.createElement.apply(
			wp.element,
			[ Fragment, null ].concat( list )
		);
	}

	function TeamOptionsDocumentPanels() {
		return el(
			Fragment,
			null,
			el(
				PluginDocumentSettingPanel,
				{
					name: 'clanspress-team-basic',
					title: __( 'Team — Basic', 'clanspress' ),
					className: 'clanspress-team-panel-basic',
				},
				el( TeamBasicFields, null )
			),
			el(
				PluginDocumentSettingPanel,
				{
					name: 'clanspress-team-branding',
					title: __( 'Team — Branding', 'clanspress' ),
					className: 'clanspress-team-panel-branding',
				},
				el( TeamBrandingFields, null )
			),
			el(
				PluginDocumentSettingPanel,
				{
					name: 'clanspress-team-membership',
					title: __( 'Team — Membership & rules', 'clanspress' ),
					className: 'clanspress-team-panel-membership',
				},
				el( TeamMembershipFields, null )
			)
		);
	}

	function TeamOptionsPanel() {
		const postType = useSelect( function ( select ) {
			return select( 'core/editor' ).getCurrentPostType();
		}, [] );

		if ( 'cp_team' !== postType ) {
			return null;
		}

		return el( TeamOptionsDocumentPanels, null );
	}

	registerPlugin( 'clanspress-team-options', {
		render: TeamOptionsPanel,
		icon: null,
	} );
} )( window.wp );
