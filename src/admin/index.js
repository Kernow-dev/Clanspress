/**
 * Unified Clanbite wp-admin (tabs: General, Extensions, per-extension settings).
 * Active tab is synced to `?tab=<id>` for deep links and refresh.
 */
import {
	render,
	useState,
	useEffect,
	useCallback,
	useRef,
	createContext,
	useContext,
} from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import {
	BaseControl,
	TabPanel,
	Spinner,
	Button,
	Notice,
	Modal,
	ToggleControl,
	SelectControl,
	TextControl,
	TextareaControl,
	VisuallyHidden,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

import './style.css';

const TAB_QUERY_KEY = 'tab';

/**
 * @param {unknown} raw Bootstrap or localized `iconPacks` value.
 * @return {Object[]} List usable as icon pack rows.
 */
function normalizeIconPackList( raw ) {
	if ( Array.isArray( raw ) ) {
		return raw;
	}
	if ( raw && typeof raw === 'object' ) {
		return Object.values( raw ).filter(
			( p ) => p && typeof p === 'object'
		);
	}
	return [];
}

/**
 * @param {Object}   field Field schema (`iconPackScope` optional).
 * @param {Object[]} packs Full bootstrap icon packs (each may have `scope`).
 * @return {Object[]} Packs to show for this field.
 */
function filterIconPacksByFieldScope( field, packs ) {
	const scope = field?.iconPackScope;
	if ( ! scope || scope === 'all' ) {
		return packs;
	}
	return packs.filter( ( p ) => {
		const packScope = String( p.scope || '' );
		return packScope === scope || packScope === 'all';
	} );
}

const IconPickerBootstrapContext = createContext( {
	iconPacks: [],
	iconPickerI18n: {},
} );

/**
 * Icon URL stored via pack picker and/or media library (no raw URL field; `icon_picker` + type url).
 *
 * @param {Object}        props
 * @param {Object}        props.field   Field schema from REST.
 * @param {unknown}       props.value   Current value.
 * @param {Function}      props.onChange ( fieldId, next ) => void
 * @return {import('react').ReactNode} Rendered controls.
 */
function IconUrlFieldControl( { field, value, onChange } ) {
	const id = field.id;
	const { iconPacks: contextPacks, iconPickerI18n: contextI18n } = useContext(
		IconPickerBootstrapContext
	);
	const [ pickerOpen, setPickerOpen ] = useState( false );
	const packs = ( () => {
		const fromCtx = normalizeIconPackList( contextPacks );
		const allPacks =
			fromCtx.length > 0
				? fromCtx
				: normalizeIconPackList(
						typeof window !== 'undefined'
							? window.clanbiteAdmin?.iconPacks
							: undefined
				  );
		return filterIconPacksByFieldScope( field, allPacks );
	} )();
	const i18n =
		contextI18n &&
		typeof contextI18n === 'object' &&
		Object.keys( contextI18n ).length > 0
			? contextI18n
			: typeof window !== 'undefined' &&
			  window.clanbiteAdmin?.iconPickerI18n &&
			  typeof window.clanbiteAdmin.iconPickerI18n === 'object'
			? window.clanbiteAdmin.iconPickerI18n
			: {};

	const openMediaFrame = () => {
		if ( ! window.wp || ! window.wp.media ) {
			return;
		}
		const frame = window.wp.media( {
			title: field.mediaTitle || __( 'Select image', 'clanbite' ),
			button: {
				text: field.mediaButtonText || __( 'Use image', 'clanbite' ),
			},
			library: { type: 'image' },
			multiple: false,
		} );
		frame.on( 'select', () => {
			const attachment = frame
				.state()
				.get( 'selection' )
				.first()
				?.toJSON();
			onChange( id, attachment?.url || '' );
		} );
		frame.open();
	};

	const pickIcon = ( url ) => {
		onChange( id, url );
		setPickerOpen( false );
	};

	const [ hasMedia, setHasMedia ] = useState( () => {
		if ( typeof window === 'undefined' ) {
			return false;
		}
		return !! ( window.wp && window.wp.media );
	} );

	useEffect( () => {
		if ( hasMedia || typeof window === 'undefined' ) {
			return;
		}
		const ready = () =>
			typeof window !== 'undefined' &&
			!! ( window.wp && window.wp.media );
		if ( ready() ) {
			setHasMedia( true );
			return;
		}
		const intervalId = window.setInterval( () => {
			if ( ready() ) {
				setHasMedia( true );
				window.clearInterval( intervalId );
			}
		}, 100 );
		const timeoutId = window.setTimeout( () => {
			window.clearInterval( intervalId );
		}, 8000 );
		return () => {
			window.clearInterval( intervalId );
			window.clearTimeout( timeoutId );
		};
	}, [ hasMedia ] );

	const previewUrl = String( value ?? '' ).trim();
	let helpCombined = field.description || '';
	if ( packs.length === 0 && ! hasMedia ) {
		const extra =
			i18n.noIcons || __( 'No icon packs registered yet.', 'clanbite' );
		helpCombined = helpCombined ? `${ helpCombined } ${ extra }` : extra;
	}

	return (
		<div className="clanbite-field-icon-url">
			<BaseControl
				id={ `clanbite-icon-url-${ id }` }
				label={ field.label }
				help={ helpCombined || undefined }
				__nextHasNoMarginBottom
			>
				{ previewUrl ? (
					<div className="clanbite-field-icon-url__preview">
						<img src={ previewUrl } alt="" />
					</div>
				) : null }
				<div className="clanbite-field-icon-url__actions">
					{ packs.length > 0 ? (
						<Button
							variant="secondary"
							onClick={ () => setPickerOpen( true ) }
						>
							{ i18n.chooseIcon ||
								__( 'Choose icon', 'clanbite' ) }
						</Button>
					) : null }
					{ hasMedia ? (
						<Button variant="secondary" onClick={ openMediaFrame }>
							{ i18n.mediaLibrary ||
								__( 'Media Library…', 'clanbite' ) }
						</Button>
					) : null }
					{ previewUrl ? (
						<Button
							variant="tertiary"
							isDestructive
							onClick={ () => onChange( id, '' ) }
						>
							{ i18n.clear || __( 'Clear', 'clanbite' ) }
						</Button>
					) : null }
				</div>
			</BaseControl>
			{ pickerOpen ? (
				<Modal
					title={ i18n.title || __( 'Choose icon', 'clanbite' ) }
					onRequestClose={ () => setPickerOpen( false ) }
					className="clanbite-icon-picker-modal"
				>
					<div className="clanbite-icon-picker-modal__inner">
						<p>
							<Button
								variant="secondary"
								onClick={ () => pickIcon( '' ) }
							>
								{ i18n.none || __( 'No icon', 'clanbite' ) }
							</Button>
						</p>
						{ packs.length === 0 ? (
							<p className="description">
								{ i18n.noIcons ||
									__(
										'No icon packs registered yet.',
										'clanbite'
									) }
							</p>
						) : (
							packs.map( ( pack ) => (
								<section
									key={ String( pack.id ) }
									className="clanbite-icon-picker-pack"
								>
									<h4 className="clanbite-icon-picker-pack__title">
										{ pack.label }
									</h4>
									<div className="clanbite-icon-picker-grid">
										{ ( pack.icons || [] ).map(
											( icon ) => (
												<button
													key={ String( icon.id ) }
													type="button"
													className="clanbite-icon-picker-grid__item"
													onClick={ () =>
														pickIcon(
															String(
																icon.url || ''
															)
														)
													}
													title={ String(
														icon.label ||
															icon.id ||
															''
													) }
												>
													<img
														src={ String(
															icon.url || ''
														) }
														alt={ String(
															icon.label || ''
														) }
													/>
													<span>
														{ String(
															icon.label ||
																icon.id ||
																''
														) }
													</span>
												</button>
											)
										) }
									</div>
								</section>
							) )
						) }
					</div>
				</Modal>
			) : null }
		</div>
	);
}

/**
 * Read the tab slug from the current admin URL query string.
 *
 * @return {string} Raw value or empty string.
 */
function getTabFromLocation() {
	try {
		return (
			new URLSearchParams( window.location.search ).get(
				TAB_QUERY_KEY
			) || ''
		);
	} catch {
		return '';
	}
}

/**
 * Apply `?tab=` to the address bar without reloading (replaceState).
 *
 * @param {string} tabName Valid tab id.
 */
function replaceTabInUrl( tabName ) {
	try {
		const url = new URL( window.location.href );
		if ( ! tabName ) {
			url.searchParams.delete( TAB_QUERY_KEY );
		} else {
			url.searchParams.set( TAB_QUERY_KEY, tabName );
		}
		const next = url.pathname + url.search + url.hash;
		if (
			next !==
			window.location.pathname +
				window.location.search +
				window.location.hash
		) {
			window.history.replaceState( null, '', next );
		}
	} catch {
		// Ignore invalid URLs (e.g. very old browsers).
	}
}

/**
 * Pick the initial tab from the URL if valid, otherwise the first tab.
 *
 * @param {Array<{ id: string }>} tabMetas Bootstrap tab definitions.
 * @return {string} Tab id.
 */
function resolveInitialTabId( tabMetas ) {
	if ( ! tabMetas?.length ) {
		return '';
	}
	const allowed = new Set( tabMetas.map( ( t ) => t.id ) );
	const fromUrl = getTabFromLocation();
	if ( fromUrl && allowed.has( fromUrl ) ) {
		return fromUrl;
	}
	return tabMetas[ 0 ].id;
}

function setupApiFetch() {
	const root =
		typeof wpApiSettings !== 'undefined' && wpApiSettings?.root
			? wpApiSettings.root
			: ( clanbiteAdmin?.restUrl || '' ).replace(
					/clanbite\/v1\/$/,
					''
			  ) || '/wp-json/';
	const nonce =
		typeof wpApiSettings !== 'undefined' && wpApiSettings?.nonce
			? wpApiSettings.nonce
			: clanbiteAdmin?.nonce || '';
	apiFetch.use( apiFetch.createNonceMiddleware( nonce ) );
	apiFetch.use( apiFetch.createRootURLMiddleware( root ) );
}

/**
 * Repeater-style user IDs with search (REST) and removable rows (avatar + name).
 *
 * @param {Object}   props
 * @param {Object}   props.field   Field schema (`user_search_path`, `label`, `description`, `id`).
 * @param {unknown}  props.value   Expected number[] from settings.
 * @param {Function} props.onChange ( fieldId, nextValue ) => void
 * @return {import('react').ReactNode}
 */
function UserIdListControl( { field, value, onChange } ) {
	const fid = field.id;
	const searchPathRaw =
		field.userSearchPath || field.user_search_path || 'wp/v2/users';
	const searchPath = String( searchPathRaw ).replace( /\/$/, '' );

	const ids = Array.isArray( value )
		? [
				...new Set(
					value
						.map( ( v ) => parseInt( v, 10 ) )
						.filter( ( n ) => n > 0 )
				),
		  ]
		: [];

	const [ query, setQuery ] = useState( '' );
	const [ suggestions, setSuggestions ] = useState( [] );
	const [ loading, setLoading ] = useState( false );
	const [ details, setDetails ] = useState( {} );
	const searchRequestIdRef = useRef( 0 );

	const idsKey = ids
		.slice()
		.sort( ( a, b ) => a - b )
		.join( ',' );

	useEffect( () => {
		if ( ! idsKey ) {
			return;
		}
		let cancelled = false;
		( async () => {
			try {
				const includeQs = ids
					.map(
						( uid ) =>
							`include=${ encodeURIComponent( String( uid ) ) }`
					)
					.join( '&' );
				const users = await apiFetch( {
					path: `${ searchPath }?${ includeQs }&per_page=100&_fields=id,name,slug,avatar_urls`,
				} );
				if ( cancelled || ! Array.isArray( users ) ) {
					return;
				}
				setDetails( ( prev ) => {
					const next = { ...prev };
					for ( const u of users ) {
						const uid = parseInt( u.id, 10 );
						if ( ! uid ) {
							continue;
						}
						const av =
							u.avatar_urls?.[ '96' ] ||
							u.avatar_urls?.[ '48' ] ||
							u.avatar_urls?.[ '24' ] ||
							'';
						next[ uid ] = {
							name:
								u.name ||
								u.slug ||
								/* translators: %d: user ID */
								sprintf( __( 'User %d', 'clanbite' ), uid ),
							avatar: av,
						};
					}
					return next;
				} );
			} catch {
				// Leave placeholders for display names.
			}
		} )();
		return () => {
			cancelled = true;
		};
	}, [ idsKey, searchPath ] );

	useEffect( () => {
		const q = query.trim();
		if ( q.length < 2 ) {
			setSuggestions( [] );
			return;
		}
		const handle = setTimeout( async () => {
			const reqId = ++searchRequestIdRef.current;
			setLoading( true );
			try {
				const users = await apiFetch( {
					path: `${ searchPath }?search=${ encodeURIComponent(
						q
					) }&per_page=10&_fields=id,name,slug,avatar_urls`,
				} );
				if ( reqId !== searchRequestIdRef.current ) {
					return;
				}
				setSuggestions( Array.isArray( users ) ? users : [] );
			} catch {
				if ( reqId !== searchRequestIdRef.current ) {
					return;
				}
				setSuggestions( [] );
			} finally {
				if ( reqId === searchRequestIdRef.current ) {
					setLoading( false );
				}
			}
		}, 300 );
		return () => {
			clearTimeout( handle );
			searchRequestIdRef.current += 1;
		};
	}, [ query, searchPath ] );

	const addUser = ( u ) => {
		const uid = parseInt( u.id, 10 );
		if ( ! uid || ids.includes( uid ) ) {
			return;
		}
		const av =
			u.avatar_urls?.[ '96' ] ||
			u.avatar_urls?.[ '48' ] ||
			u.avatar_urls?.[ '24' ] ||
			'';
		setDetails( ( d ) => ( {
			...d,
			[ uid ]: {
				name:
					u.name ||
					u.slug ||
					sprintf( __( 'User %d', 'clanbite' ), uid ),
				avatar: av,
			},
		} ) );
		onChange( fid, [ ...ids, uid ] );
		setQuery( '' );
		setSuggestions( [] );
	};

	const removeUser = ( uid ) => {
		onChange(
			fid,
			ids.filter( ( x ) => x !== uid )
		);
	};

	return (
		<div className="clanbite-field-user-id-list">
			{ field.label ? (
				<p className="clanbite-field-user-id-list__label">
					<strong>{ field.label }</strong>
				</p>
			) : null }
			{ ids.length ? (
				<ul
					className="clanbite-field-user-id-list__rows"
					aria-label={ __( 'Selected users', 'clanbite' ) }
				>
					{ ids.map( ( uid ) => {
						const row = details[ uid ];
						const label = row?.name
							? row.name
							: sprintf( __( 'User %d', 'clanbite' ), uid );
						return (
							<li
								key={ uid }
								className="clanbite-field-user-id-list__row"
							>
								<span className="clanbite-field-user-id-list__avatar-wrap">
									{ row?.avatar ? (
										<img
											src={ row.avatar }
											alt=""
											width={ 32 }
											height={ 32 }
											className="clanbite-field-user-id-list__avatar"
										/>
									) : (
										<span
											className="clanbite-field-user-id-list__avatar clanbite-field-user-id-list__avatar--placeholder"
											aria-hidden="true"
										/>
									) }
								</span>
								<span className="clanbite-field-user-id-list__name">
									{ label }
								</span>
								<Button
									variant="tertiary"
									isDestructive
									onClick={ () => removeUser( uid ) }
									className="clanbite-field-user-id-list__remove"
								>
									{ __( 'Remove', 'clanbite' ) }
								</Button>
							</li>
						);
					} ) }
				</ul>
			) : (
				<p className="description clanbite-field-user-id-list__empty">
					{ __( 'No users added yet.', 'clanbite' ) }
				</p>
			) }
			<div className="clanbite-field-user-id-list__search">
				<TextControl
					label={ __( 'Add user', 'clanbite' ) }
					value={ query }
					onChange={ setQuery }
					placeholder={ __(
						'Search by name or username…',
						'clanbite'
					) }
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					autoComplete="off"
				/>
				{ loading ? (
					<p className="description">
						{ __( 'Searching…', 'clanbite' ) }
					</p>
				) : null }
				{ suggestions.length ? (
					<ul
						className="clanbite-field-user-id-list__suggestions"
						role="listbox"
						aria-label={ __( 'Matching users', 'clanbite' ) }
					>
						{ suggestions.map( ( u ) => {
							const uid = parseInt( u.id, 10 );
							const disabled = ! uid || ids.includes( uid );
							const av =
								u.avatar_urls?.[ '48' ] ||
								u.avatar_urls?.[ '96' ] ||
								u.avatar_urls?.[ '24' ] ||
								'';
							const name =
								u.name ||
								u.slug ||
								sprintf( __( 'User %d', 'clanbite' ), uid );
							return (
								<li key={ uid || u.slug } role="option">
									<button
										type="button"
										className="clanbite-field-user-id-list__suggestion"
										disabled={ disabled }
										onClick={ () => addUser( u ) }
									>
										{ av ? (
											<img
												src={ av }
												alt=""
												width={ 24 }
												height={ 24 }
												className="clanbite-field-user-id-list__suggestion-avatar"
											/>
										) : null }
										<span>{ name }</span>
										{ disabled ? (
											<span className="description">
												{ __(
													'(already added)',
													'clanbite'
												) }
											</span>
										) : null }
									</button>
								</li>
							);
						} ) }
					</ul>
				) : null }
			</div>
			{ field.description ? (
				<p className="description">{ field.description }</p>
			) : null }
		</div>
	);
}

/**
 * Plain-text label from a `wp/v2/*` post in the REST API.
 *
 * @param {{ title?: string|{ rendered?: string, raw?: string }, slug?: string }} p Post payload.
 * @return {string} Title text, slug, or empty string.
 */
function restPostTitleText( p ) {
	if ( ! p ) {
		return '';
	}
	const t = p.title;
	if ( typeof t === 'string' ) {
		return t;
	}
	if ( t && typeof t === 'object' ) {
		const raw = t.rendered || t.raw || '';
		return String( raw )
			.replace( /<[^>]+>/g, '' )
			.trim();
	}
	return p.slug ? String( p.slug ) : '';
}

/**
 * Multi-select list of post IDs (teams, groups, etc.) for extension settings.
 *
 * @param {Object}             props          Props.
 * @param {Object}             props.field    Field schema from PHP (`post_id_list`).
 * @param {number[]|undefined} props.value   Selected post IDs.
 * @param {Function}           props.onChange `( fieldId, nextIds ) => void`.
 * @return {import('react').ReactNode} Field UI.
 */
function PostIdListControl( { field, value, onChange } ) {
	const fid = field.id;
	const searchPathRaw =
		field.postSearchPath || field.post_search_path || 'wp/v2/posts';
	const searchPath = String( searchPathRaw ).replace( /\/$/, '' );

	const ids = Array.isArray( value )
		? [
				...new Set(
					value
						.map( ( v ) => parseInt( v, 10 ) )
						.filter( ( n ) => n > 0 )
				),
		  ]
		: [];

	const [ query, setQuery ] = useState( '' );
	const [ suggestions, setSuggestions ] = useState( [] );
	const [ loading, setLoading ] = useState( false );
	const [ details, setDetails ] = useState( {} );
	const searchRequestIdRef = useRef( 0 );

	const idsKey = ids
		.slice()
		.sort( ( a, b ) => a - b )
		.join( ',' );

	useEffect( () => {
		if ( ! idsKey ) {
			return;
		}
		let cancelled = false;
		( async () => {
			try {
				const path = `${ searchPath }?include=${ encodeURIComponent(
					ids.join( ',' )
				) }&per_page=100&_fields=id,title,slug`;
				const posts = await apiFetch( { path } );
				if ( cancelled || ! Array.isArray( posts ) ) {
					return;
				}
				setDetails( ( prev ) => {
					const next = { ...prev };
					for ( const p of posts ) {
						const pid = parseInt( p.id, 10 );
						if ( ! pid ) {
							continue;
						}
						const title = restPostTitleText( p );
						next[ pid ] = {
							name:
								title ||
								p.slug ||
								/* translators: %d: post ID */
								sprintf( __( 'Item %d', 'clanbite' ), pid ),
						};
					}
					return next;
				} );
			} catch {
				// Leave placeholders for display names.
			}
		} )();
		return () => {
			cancelled = true;
		};
	}, [ idsKey, searchPath ] );

	useEffect( () => {
		const q = query.trim();
		if ( q.length < 2 ) {
			setSuggestions( [] );
			return;
		}
		const handle = setTimeout( async () => {
			const reqId = ++searchRequestIdRef.current;
			setLoading( true );
			try {
				const posts = await apiFetch( {
					path: `${ searchPath }?search=${ encodeURIComponent(
						q
					) }&per_page=10&_fields=id,title,slug`,
				} );
				if ( reqId !== searchRequestIdRef.current ) {
					return;
				}
				setSuggestions( Array.isArray( posts ) ? posts : [] );
			} catch {
				if ( reqId !== searchRequestIdRef.current ) {
					return;
				}
				setSuggestions( [] );
			} finally {
				if ( reqId === searchRequestIdRef.current ) {
					setLoading( false );
				}
			}
		}, 300 );
		return () => {
			clearTimeout( handle );
			searchRequestIdRef.current += 1;
		};
	}, [ query, searchPath ] );

	const addPost = ( p ) => {
		const pid = parseInt( p.id, 10 );
		if ( ! pid || ids.includes( pid ) ) {
			return;
		}
		const title = restPostTitleText( p );
		setDetails( ( d ) => ( {
			...d,
			[ pid ]: {
				name:
					title ||
					p.slug ||
					/* translators: %d: post ID */
					sprintf( __( 'Item %d', 'clanbite' ), pid ),
			},
		} ) );
		onChange( fid, [ ...ids, pid ] );
		setQuery( '' );
		setSuggestions( [] );
	};

	const removePost = ( pid ) => {
		onChange(
			fid,
			ids.filter( ( x ) => x !== pid )
		);
	};

	return (
		<div className="clanbite-field-post-id-list">
			{ field.label ? (
				<p className="clanbite-field-post-id-list__label">
					<strong>{ field.label }</strong>
				</p>
			) : null }
			{ ids.length ? (
				<ul
					className="clanbite-field-post-id-list__rows"
					aria-label={ __( 'Selected items', 'clanbite' ) }
				>
					{ ids.map( ( pid ) => {
						const row = details[ pid ];
						let label;
						if ( row?.name ) {
							label = row.name;
						} else {
							/* translators: %d: post ID */
							label = sprintf(
								__( 'Item %d', 'clanbite' ),
								pid
							);
						}
						return (
							<li
								key={ pid }
								className="clanbite-field-post-id-list__row"
							>
								<span className="clanbite-field-post-id-list__name">
									{ label }
								</span>
								<Button
									variant="tertiary"
									isDestructive
									onClick={ () => removePost( pid ) }
									className="clanbite-field-post-id-list__remove"
								>
									{ __( 'Remove', 'clanbite' ) }
								</Button>
							</li>
						);
					} ) }
				</ul>
			) : (
				<p className="description clanbite-field-post-id-list__empty">
					{ __( 'No items added yet.', 'clanbite' ) }
				</p>
			) }
			<div className="clanbite-field-post-id-list__search">
				<TextControl
					label={ __( 'Add item', 'clanbite' ) }
					value={ query }
					onChange={ setQuery }
					placeholder={ __(
						'Search by title or slug…',
						'clanbite'
					) }
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					autoComplete="off"
				/>
				{ loading ? (
					<p className="description">
						{ __( 'Searching…', 'clanbite' ) }
					</p>
				) : null }
				{ suggestions.length ? (
					<ul
						className="clanbite-field-post-id-list__suggestions"
						role="listbox"
						aria-label={ __( 'Matching items', 'clanbite' ) }
					>
						{ suggestions.map( ( p ) => {
							const pid = parseInt( p.id, 10 );
							const disabled = ! pid || ids.includes( pid );
							const title = restPostTitleText( p );
							const name =
								title ||
								p.slug ||
								/* translators: %d: post ID */
								sprintf( __( 'Item %d', 'clanbite' ), pid );
							return (
								<li key={ pid || p.slug } role="option">
									<button
										type="button"
										className="clanbite-field-post-id-list__suggestion"
										disabled={ disabled }
										onClick={ () => addPost( p ) }
									>
										<span>{ name }</span>
										{ disabled ? (
											<span className="description">
												{ __(
													'(already added)',
													'clanbite'
												) }
											</span>
										) : null }
									</button>
								</li>
							);
						} ) }
					</ul>
				) : null }
			</div>
			{ field.description ? (
				<p className="description">{ field.description }</p>
			) : null }
		</div>
	);
}

/**
 * Nested repeaters for extension settings (e.g. Points types + rules).
 *
 * @param {Object}   props
 * @param {Object}   props.field   Field schema (`fields`, `add_label`, `default_row`).
 * @param {unknown}  props.value   Expected array of row objects.
 * @param {Function} props.onChange ( fieldId, nextRows ) => void
 * @return {import('react').ReactNode}
 */
function RepeaterControl( { field, value, onChange } ) {
	const id = field.id;
	const rows = Array.isArray( value ) ? value : [];
	const subFields = field.fields || [];
	const [ editingIndex, setEditingIndex ] = useState( -1 );
	const [ draftRow, setDraftRow ] = useState( null );
	const addLabel =
		field.addLabel || field.add_label || __( 'Add row', 'clanbite' );
	const inferEntityName = () => {
		const key = String( id || '' ).toLowerCase();
		if ( key === 'point_types' ) return __( 'point type', 'clanbite' );
		if ( key === 'rank_types' ) return __( 'rank type', 'clanbite' );
		if ( key === 'rules' ) return __( 'rule', 'clanbite' );
		if ( key === 'levels' ) return __( 'level', 'clanbite' );
		return __( 'row', 'clanbite' );
	};
	const entityName = inferEntityName();

	const defaultRowFromSchema = () => {
		const dr = field.defaultRow || field.default_row;
		if ( dr && typeof dr === 'object' && ! Array.isArray( dr ) ) {
			return { ...dr };
		}
		/** @type {Record<string, unknown>} */
		const row = {};
		for ( const sub of subFields ) {
			if ( ! sub?.id ) {
				continue;
			}
			if ( sub.type === 'repeater' ) {
				row[ sub.id ] = Array.isArray( sub.default )
					? [ ...sub.default ]
					: [];
			} else if ( sub.default !== undefined && sub.default !== null ) {
				row[ sub.id ] = sub.default;
			} else if ( sub.type === 'checkbox' ) {
				row[ sub.id ] = false;
			} else if ( sub.type === 'number' ) {
				row[ sub.id ] = 0;
			} else {
				row[ sub.id ] = '';
			}
		}
		return row;
	};

	const setRows = ( next ) => onChange( id, next );

	const cloneRow = ( row ) => JSON.parse( JSON.stringify( row || {} ) );

	const openRowEditor = ( index ) => {
		if ( index >= 0 && rows[ index ] ) {
			setEditingIndex( index );
			setDraftRow( cloneRow( rows[ index ] ) );
			return;
		}
		setEditingIndex( -1 );
		setDraftRow( defaultRowFromSchema() );
	};

	const removeRow = ( index ) => {
		if ( ! window.confirm( __( 'Delete this row?', 'clanbite' ) ) ) {
			return;
		}
		setRows( rows.filter( ( _, i ) => i !== index ) );
	};

	const updateDraftCell = ( subId, subValue ) => {
		if ( ! draftRow || typeof draftRow !== 'object' ) {
			return;
		}
		setDraftRow( { ...draftRow, [ subId ]: subValue } );
	};

	const saveDraftRow = () => {
		if ( ! draftRow || typeof draftRow !== 'object' ) {
			setDraftRow( null );
			setEditingIndex( -1 );
			return;
		}
		if ( editingIndex >= 0 ) {
			setRows(
				rows.map( ( r, i ) =>
					i === editingIndex ? cloneRow( draftRow ) : r
				)
			);
		} else {
			setRows( [ ...rows, cloneRow( draftRow ) ] );
		}
		setDraftRow( null );
		setEditingIndex( -1 );
	};

	const closeDraftRow = () => {
		setDraftRow( null );
		setEditingIndex( -1 );
	};

	const summarizeCell = ( row, sub ) => {
		const cell = row?.[ sub.id ];
		if ( sub.type === 'repeater' ) {
			const count = Array.isArray( cell ) ? cell.length : 0;
			return sprintf(
				/* translators: %d: number of nested rows. */
				__( '%d rows', 'clanbite' ),
				count
			);
		}
		if ( sub.type === 'checkbox' ) {
			return cell ? __( 'Yes', 'clanbite' ) : __( 'No', 'clanbite' );
		}
		return String( cell ?? '' );
	};

	const renderModal = () => {
		if ( null === draftRow ) {
			return null;
		}
		return (
			<Modal
				title={
					editingIndex >= 0
						? sprintf(
								/* translators: 1: entity label (e.g. rule), 2: 1-based row index. */
								__( 'Edit %1$s %2$d', 'clanbite' ),
								entityName,
								editingIndex + 1
						  )
						: sprintf(
								/* translators: %s: entity label (e.g. rule). */
								__( 'Add %s', 'clanbite' ),
								entityName
						  )
				}
				onRequestClose={ closeDraftRow }
			>
				<div className="clanbite-field-repeater__row-edit">
					{ subFields.map( ( sub ) => (
						<div
							key={ sub.id }
							className="clanbite-field-repeater__cell"
						>
							{ sub.type === 'repeater' ? (
								<RepeaterControl
									field={ sub }
									value={ draftRow[ sub.id ] }
									onChange={ ( fid, v ) =>
										updateDraftCell( fid, v )
									}
								/>
							) : (
								<FieldControl
									field={ sub }
									value={ draftRow[ sub.id ] }
									onChange={ ( fid, v ) =>
										updateDraftCell( fid, v )
									}
								/>
							) }
						</div>
					) ) }
					<div className="clanbite-field-repeater__modal-actions">
						<Button variant="primary" onClick={ saveDraftRow }>
							{ sprintf(
								/* translators: %s: entity label (e.g. rule). */
								__( 'Save %s', 'clanbite' ),
								entityName
							) }
						</Button>{ ' ' }
						<Button variant="secondary" onClick={ closeDraftRow }>
							{ __( 'Cancel', 'clanbite' ) }
						</Button>
					</div>
				</div>
			</Modal>
		);
	};

	return (
		<div className="clanbite-field-repeater">
			<fieldset>
				<legend className="clanbite-field-repeater__legend">
					{ field.label }
				</legend>
				{ field.description ? (
					<p className="description">{ field.description }</p>
				) : null }
				<div className="clanbite-extensions-table-wrap">
					<table className="widefat striped clanbite-extensions-table clanbite-field-repeater__table">
						<thead>
							<tr>
								{ subFields.map( ( sub ) => (
									<th key={ sub.id }>
										{ sub.label || sub.id }
									</th>
								) ) }
								<th>{ __( 'Actions', 'clanbite' ) }</th>
							</tr>
						</thead>
						<tbody>
							{ rows.map( ( row, rowIndex ) => (
								<tr key={ rowIndex }>
									{ subFields.map( ( sub ) => (
										<td key={ sub.id }>
											{ summarizeCell( row, sub ) }
										</td>
									) ) }
									<td className="clanbite-field-repeater__actions-cell">
										<Button
											variant="secondary"
											onClick={ () =>
												openRowEditor( rowIndex )
											}
										>
											{ __( 'Edit', 'clanbite' ) }
										</Button>{ ' ' }
										<Button
											isDestructive
											variant="tertiary"
											onClick={ () =>
												removeRow( rowIndex )
											}
										>
											{ __( 'Delete', 'clanbite' ) }
										</Button>
									</td>
								</tr>
							) ) }
						</tbody>
					</table>
				</div>
				<div style={ { marginTop: '10px' } }>
					<Button
						variant="primary"
						onClick={ () => openRowEditor( -1 ) }
					>
						{ addLabel }
					</Button>
				</div>
			</fieldset>
			{ renderModal() }
		</div>
	);
}

function FieldControl( { field, value, onChange } ) {
	const id = field.id;
	const common = {
		label: field.label,
		help: field.description || undefined,
	};

	switch ( field.type ) {
		case 'repeater':
			return (
				<RepeaterControl
					field={ field }
					value={ value }
					onChange={ onChange }
				/>
			);
		case 'user_id_list':
			return (
				<UserIdListControl
					field={ field }
					value={ value }
					onChange={ onChange }
				/>
			);
		case 'post_id_list':
			return (
				<PostIdListControl
					field={ field }
					value={ value }
					onChange={ onChange }
				/>
			);
		case 'image': {
			const previewUrl =
				value || field.fallbackUrl || field.fallback_url || '';

			const openMediaFrame = () => {
				if ( ! window.wp || ! window.wp.media ) {
					return;
				}

				const frame = window.wp.media( {
					title:
						field.mediaTitle || __( 'Select image', 'clanbite' ),
					button: {
						text:
							field.mediaButtonText ||
							__( 'Use image', 'clanbite' ),
					},
					library: { type: 'image' },
					multiple: false,
				} );

				frame.on( 'select', () => {
					const attachment = frame
						.state()
						.get( 'selection' )
						.first()
						?.toJSON();
					onChange( id, attachment?.url || '' );
				} );

				frame.open();
			};

			return (
				<div className="clanbite-field-image">
					{ previewUrl ? (
						<div className="clanbite-field-image-preview">
							<img src={ previewUrl } alt="" />
						</div>
					) : null }
					<div className="clanbite-field-image-actions">
						<Button variant="secondary" onClick={ openMediaFrame }>
							{ __( 'Upload / choose image', 'clanbite' ) }
						</Button>
						<Button
							variant="tertiary"
							isDestructive
							onClick={ () => onChange( id, '' ) }
						>
							{ __( 'Use plugin default', 'clanbite' ) }
						</Button>
					</div>
					{ common.help ? (
						<p className="description">{ common.help }</p>
					) : null }
				</div>
			);
		}
		case 'checkbox':
			return (
				<ToggleControl
					{ ...common }
					checked={ !! value }
					onChange={ ( v ) => onChange( id, v ) }
					__nextHasNoMarginBottom
				/>
			);
		case 'select':
			return (
				<SelectControl
					{ ...common }
					value={ String( value ?? '' ) }
					options={ ( field.options || [] ).map( ( o ) => ( {
						label: o.label,
						value: o.value,
					} ) ) }
					onChange={ ( v ) => onChange( id, v ) }
				/>
			);
		case 'avatar_shape_radio':
			return (
				<BaseControl
					{ ...common }
					className="clanbite-admin-avatar-shape-radio"
				>
					<div className="clanbite-admin-avatar-shape-radio__options">
						{ ( field.options || [] ).map( ( option ) => {
							const shapeValue = option.value;
							const isChecked = String( value ?? 'circle' ) === shapeValue;
							return (
								<label
									key={ shapeValue }
									className={ `clanbite-admin-avatar-shape-radio__option ${
										isChecked ? 'is-checked' : ''
									}` }
								>
									<input
										type="radio"
										name={ `avatar-shape-${ id }` }
										value={ shapeValue }
										checked={ isChecked }
										onChange={ () => onChange( id, shapeValue ) }
										className="clanbite-admin-avatar-shape-radio__input"
									/>
									<div className="clanbite-admin-avatar-shape-radio__preview">
										<div
											className={ `clanbite-admin-avatar-shape-radio__shape clanbite-admin-avatar-shape-radio__shape--${ shapeValue }` }
											aria-hidden="true"
										/>
									</div>
									<span className="clanbite-admin-avatar-shape-radio__label">
										{ option.label }
									</span>
								</label>
							);
						} ) }
					</div>
				</BaseControl>
			);
		case 'textarea':
			return (
				<TextareaControl
					{ ...common }
					value={ String( value ?? '' ) }
					onChange={ ( v ) => onChange( id, v ) }
					rows={ 4 }
					__nextHasNoMarginBottom
				/>
			);
		case 'number': {
			const n =
				value === '' || value === null || value === undefined
					? ''
					: String( value );
			return (
				<TextControl
					{ ...common }
					type="number"
					value={ n }
					onChange={ ( v ) => {
						if ( v === '' || v === null ) {
							onChange( id, 0 );
							return;
						}
						const parsed = parseInt( String( v ), 10 );
						onChange( id, Number.isFinite( parsed ) ? parsed : 0 );
					} }
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
			);
		}
		case 'url':
			if ( field.iconPicker ) {
				return (
					<IconUrlFieldControl
						field={ field }
						value={ value }
						onChange={ onChange }
					/>
				);
			}
			return (
				<TextControl
					{ ...common }
					type="url"
					value={ String( value ?? '' ) }
					onChange={ ( v ) => onChange( id, v ) }
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
			);
		default:
			return (
				<TextControl
					{ ...common }
					value={ String( value ?? '' ) }
					onChange={ ( v ) => onChange( id, v ) }
					__next40pxDefaultSize
					__nextHasNoMarginBottom
				/>
			);
	}
}

/**
 * Check if a field's dependencies are satisfied.
 *
 * @param {Object}              field Field config with optional `depends_on`.
 * @param {Object}              data  Current values for the option key.
 * @return {boolean} True if field should be visible.
 */
function isFieldVisible( field, data ) {
	if ( ! field.depends_on ) {
		return true;
	}
	const { field: depField, value: depValue } = field.depends_on;
	if ( ! depField ) {
		return true;
	}
	const currentValue = data[ depField ];
	if ( depValue === true ) {
		return !! currentValue;
	}
	if ( depValue === false ) {
		return ! currentValue;
	}
	return currentValue === depValue;
}

function SettingsSections( { sections, optionKey, values, onFieldChange } ) {
	const data = values[ optionKey ] || {};

	if ( ! sections?.length ) {
		return (
			<p className="description">
				{ __(
					'No configurable fields for this section.',
					'clanbite'
				) }
			</p>
		);
	}

	return sections.map( ( section ) => {
		const visibleFields = ( section.fields || [] ).filter( ( field ) =>
			isFieldVisible( field, data )
		);

		if ( ! visibleFields.length ) {
			return null;
		}

		return (
			<div key={ section.id } className="clanbite-settings-section">
				{ section.title ? <h3>{ section.title }</h3> : null }
				<div className="clanbite-settings-fields">
					{ visibleFields.map( ( field ) => (
						<div
							key={ field.id }
							className="clanbite-settings-field-row"
						>
							<FieldControl
								field={ field }
								value={ data[ field.id ] }
								onChange={ ( fid, v ) =>
									onFieldChange( optionKey, fid, v )
								}
							/>
						</div>
					) ) }
				</div>
			</div>
		);
	} );
}

/**
 * Lists required extension names; flags dependencies that are not currently installed.
 *
 * @param {Object}   props
 * @param {string[]} props.requires                 Slugs from the server (`ext.requires`).
 * @param {Object[]} props.allExtensions          Full `bootstrap.extensions` list.
 * @param {string[]} props.installedSlugs         Currently toggled-on slugs in the UI.
 * @param {string}   props.requiresClanbite     Minimum core version (`x.y.z`) or empty.
 * @param {boolean}  props.meetsClanbiteVersion Whether core satisfies `requiresClanbite`.
 * @return {import('react').ReactNode}
 */
function ExtensionRequiresCell( {
	requires,
	allExtensions,
	installedSlugs,
	requiresClanbite,
	meetsClanbiteVersion,
} ) {
	const hasExtReqs = requires?.length > 0;
	const hasCoreReq = Boolean( requiresClanbite );

	if ( ! hasExtReqs && ! hasCoreReq ) {
		return (
			<span
				className="description"
				aria-label={ __( 'None', 'clanbite' ) }
			>
				—
			</span>
		);
	}

	return (
		<div className="clanbite-extension-requires-cell">
			{ hasExtReqs ? (
				<ul className="clanbite-extension-requires">
					{ requires.map( ( reqSlug ) => {
						const reqExt = allExtensions.find(
							( e ) => e.slug === reqSlug
						);
						const label = reqExt?.name || reqSlug;
						const isInstalled = installedSlugs.includes( reqSlug );
						return (
							<li key={ reqSlug }>
								{ label }
								{ ! isInstalled ? (
									<span className="description">
										({ __( 'not installed', 'clanbite' ) })
									</span>
								) : null }
							</li>
						);
					} ) }
				</ul>
			) : null }
			{ hasCoreReq ? (
				<p
					className={ `description${
						meetsClanbiteVersion
							? ''
							: ' clanbite-extension-requires-core-miss'
					}` }
				>
					{ sprintf(
						/* translators: %s: minimum Clanbite version (x.y.z). */
						__( 'Requires Clanbite %s or newer.', 'clanbite' ),
						requiresClanbite
					) }
					{ ! meetsClanbiteVersion ? (
						<>
							{ ' ' }
							<span className="clanbite-extension-requires-core-miss-label">
								{ __(
									'(current Clanbite is too old)',
									'clanbite'
								) }
							</span>
						</>
					) : null }
				</p>
			) : null }
		</div>
	);
}

function extensionCanBeTurnedOn( ext, pendingInstalledSlugs ) {
	const depsOk =
		! ext.requires?.length ||
		ext.requires.every( ( slug ) =>
			pendingInstalledSlugs.includes( slug )
		);
	return depsOk && ext.canInstall;
}

/**
 * Integration tab mount point: fires `clanbite:admin-integration-mount` / `unmount` on this element.
 *
 * Depends on `bootstrapRevision` (not `bootstrap` identity) so re-renders that reuse state do not re-dispatch DOM events.
 */
function IntegrationSlot( { slotId, bootstrap, bootstrapRevision } ) {
	const ref = useRef( null );
	const bootstrapRef = useRef( bootstrap );
	bootstrapRef.current = bootstrap;

	useEffect( () => {
		const el = ref.current;
		if ( ! slotId || ! el ) {
			return;
		}
		const payload = bootstrapRef.current;
		el.dispatchEvent(
			new CustomEvent( 'clanbite:admin-integration-mount', {
				bubbles: true,
				detail: { slotId, bootstrap: payload },
			} )
		);
		return () => {
			el.dispatchEvent(
				new CustomEvent( 'clanbite:admin-integration-unmount', {
					bubbles: true,
					detail: { slotId },
				} )
			);
		};
	}, [ slotId, bootstrapRevision ] );

	return (
		<div
			ref={ ref }
			className="clanbite-admin-integration-slot"
			data-clanbite-admin-slot={ slotId }
		/>
	);
}

function getExtensionToggleDisabledMessage(
	ext,
	isRequired,
	versionBlocksInstall
) {
	if ( isRequired ) {
		return __(
			'This extension is required and cannot be disabled.',
			'clanbite'
		);
	}
	if ( versionBlocksInstall ) {
		return sprintf(
			/* translators: %s: minimum Clanbite version (x.y.z). */
			__( 'Requires Clanbite %s or newer.', 'clanbite' ),
			ext.requiresClanbite
		);
	}
	if ( ext.requires?.length ) {
		return __(
			'Turn on all required extensions first (you can save everything in one step).',
			'clanbite'
		);
	}
	return __( 'This extension cannot be enabled.', 'clanbite' );
}

function App() {
	const [ pluginMeta, setPluginMeta ] = useState( {
		version: '',
		isBeta: false,
	} );

	const [ bootstrap, setBootstrap ] = useState( null );
	/** Bumped only after `load()` replaces bootstrap so integration tabs avoid redundant mount/unmount on unrelated renders. */
	const [ bootstrapRevision, setBootstrapRevision ] = useState( 0 );
	const [ values, setValues ] = useState( {} );
	const [ installed, setInstalled ] = useState( [] );
	const [ error, setError ] = useState( null );
	const [ saving, setSaving ] = useState( false );
	const [ saveNotice, setSaveNotice ] = useState( null );
	const [ activeTabId, setActiveTabId ] = useState( '' );
	const [ tabPanelEpoch, setTabPanelEpoch ] = useState( 0 );
	const capturedAdminTitleRef = useRef( '' );

	const load = useCallback( async () => {
		setError( null );
		try {
			const data = await apiFetch( {
				path: 'clanbite/v1/admin/bootstrap',
			} );
			setBootstrap( data );
			setBootstrapRevision( ( r ) => r + 1 );
			setValues( { ...data.values } );
			const slugs = ( data.extensions || [] )
				.filter( ( e ) => e.isInstalled )
				.map( ( e ) => e.slug );
			setInstalled( slugs );

			if ( data?.plugin ) {
				const v = String( data.plugin.version || '' );
				const isBeta = v && Number.parseFloat( v ) < 1 ? true : false;
				setPluginMeta( {
					version: v,
					isBeta,
				} );
			}
		} catch ( e ) {
			setError(
				e?.message || __( 'Failed to load settings.', 'clanbite' )
			);
		}
	}, [] );

	useEffect( () => {
		setupApiFetch();
		load();
	}, [ load ] );

	useEffect( () => {
		if ( ! bootstrap || typeof window === 'undefined' ) {
			return;
		}
		if (
			window.clanbiteAdmin &&
			typeof window.clanbiteAdmin === 'object'
		) {
			// Optional; scripts on `toplevel_page_clanbite` may read bootstrap without REST.
			window.clanbiteAdmin.bootstrap = bootstrap;
		}
	}, [ bootstrap ] );

	// Capture WP admin title once, resolve tab from URL, canonicalize invalid ?tab=.
	useEffect( () => {
		if ( ! bootstrap?.tabs?.length ) {
			return;
		}
		if (
			typeof document !== 'undefined' &&
			! capturedAdminTitleRef.current
		) {
			capturedAdminTitleRef.current = document.title;
		}
		const initial = resolveInitialTabId( bootstrap.tabs );
		setActiveTabId( initial );
		const fromUrl = getTabFromLocation();
		if ( fromUrl && fromUrl !== initial ) {
			replaceTabInUrl( initial );
		}
	}, [ bootstrap ] );

	// Reflect active tab in the document title (restore base title only on full unmount).
	useEffect( () => {
		if (
			typeof document === 'undefined' ||
			! bootstrap?.tabs?.length ||
			! activeTabId ||
			! capturedAdminTitleRef.current
		) {
			return;
		}
		const tab = bootstrap.tabs.find( ( t ) => t.id === activeTabId );
		if ( ! tab ) {
			return;
		}
		document.title = sprintf(
			/* translators: 1: original admin screen title, 2: settings tab label */
			__( '%1$s ‹ %2$s', 'clanbite' ),
			capturedAdminTitleRef.current,
			tab.label
		);
	}, [ bootstrap, activeTabId ] );

	useEffect( () => {
		return () => {
			if (
				typeof document !== 'undefined' &&
				capturedAdminTitleRef.current
			) {
				document.title = capturedAdminTitleRef.current;
			}
		};
	}, [] );

	const onTabSelect = useCallback( ( tabName ) => {
		setActiveTabId( tabName );
		replaceTabInUrl( tabName );
	}, [] );

	// Browser back/forward: `replaceState` does not emit popstate; other navigations can.
	useEffect( () => {
		if ( ! bootstrap?.tabs?.length ) {
			return;
		}
		const onPopState = () => {
			const next = resolveInitialTabId( bootstrap.tabs );
			setActiveTabId( next );
			setTabPanelEpoch( ( e ) => e + 1 );
		};
		window.addEventListener( 'popstate', onPopState );
		return () => window.removeEventListener( 'popstate', onPopState );
	}, [ bootstrap ] );

	const onFieldChange = useCallback( ( optionKey, fieldId, v ) => {
		setValues( ( prev ) => ( {
			...prev,
			[ optionKey ]: {
				...prev[ optionKey ],
				[ fieldId ]: v,
			},
		} ) );
	}, [] );

	const saveOption = async ( optionKey ) => {
		setSaving( true );
		setSaveNotice( null );
		try {
			const res = await apiFetch( {
				path: `clanbite/v1/admin/settings/${ optionKey }`,
				method: 'PUT',
				data: values[ optionKey ] || {},
			} );
			setValues( ( prev ) => ( { ...prev, [ optionKey ]: res.values } ) );
			setSaveNotice(
				<Notice
					status="success"
					isDismissible
					onRemove={ () => setSaveNotice( null ) }
				>
					{ __( 'Settings saved.', 'clanbite' ) }
				</Notice>
			);
		} catch ( e ) {
			setSaveNotice(
				<Notice
					status="error"
					isDismissible
					onRemove={ () => setSaveNotice( null ) }
				>
					{ e?.message || __( 'Save failed.', 'clanbite' ) }
				</Notice>
			);
		} finally {
			setSaving( false );
		}
	};

	const saveExtensions = async () => {
		setSaving( true );
		setSaveNotice( null );
		try {
			await apiFetch( {
				path: 'clanbite/v1/admin/extensions',
				method: 'PUT',
				data: { installed },
			} );
			setSaveNotice(
				<Notice
					status="success"
					isDismissible
					onRemove={ () => setSaveNotice( null ) }
				>
					{ __( 'Extensions updated. Reloading…', 'clanbite' ) }
				</Notice>
			);
			setTimeout( () => window.location.reload(), 800 );
		} catch ( e ) {
			setSaveNotice(
				<Notice
					status="error"
					isDismissible
					onRemove={ () => setSaveNotice( null ) }
				>
					{ e?.message ||
						__( 'Could not update extensions.', 'clanbite' ) }
				</Notice>
			);
		} finally {
			setSaving( false );
		}
	};

	if ( error ) {
		return (
			<Notice status="error" isDismissible={ false }>
				{ error }
			</Notice>
		);
	}

	if ( ! bootstrap?.tabs ) {
		return <Spinner />;
	}

	const generalKey = bootstrap.generalOptionKey;
	const generalSections = bootstrap.optionSchemas?.[ generalKey ] || [];

	const tabs = bootstrap.tabs.map( ( t ) => ( {
		name: t.id,
		title: t.label,
	} ) );

	const initialTabName = resolveInitialTabId( bootstrap.tabs );

	const versionLabel = pluginMeta.version
		? pluginMeta.isBeta
			? sprintf(
					/* translators: 1: version, 2: "beta" label */
					__( '%1$s beta', 'clanbite' ),
					pluginMeta.version
			  )
			: pluginMeta.version
		: '';

	const fromBootstrap = normalizeIconPackList(
		bootstrap.iconPacks ?? bootstrap.icon_packs
	);
	const fromLocalized = normalizeIconPackList(
		typeof window !== 'undefined'
			? window.clanbiteAdmin?.iconPacks
			: undefined
	);
	const iconPacksForContext =
		fromBootstrap.length > 0 ? fromBootstrap : fromLocalized;

	const iconPickerI18n =
		( bootstrap.iconPickerI18n &&
		typeof bootstrap.iconPickerI18n === 'object'
			? bootstrap.iconPickerI18n
			: null ) ||
		( bootstrap.icon_picker_i18n &&
		typeof bootstrap.icon_picker_i18n === 'object'
			? bootstrap.icon_picker_i18n
			: null ) ||
		( typeof window !== 'undefined' &&
		window.clanbiteAdmin?.iconPickerI18n &&
		typeof window.clanbiteAdmin.iconPickerI18n === 'object'
			? window.clanbiteAdmin.iconPickerI18n
			: {} );

	return (
		<IconPickerBootstrapContext.Provider
			value={ {
				iconPacks: iconPacksForContext,
				iconPickerI18n,
			} }
		>
			<div className="clanbite-admin-app clanbite-admin-shell">
				<header className="clanbite-admin-header">
					<div className="clanbite-admin-header-inner">
						<div className="clanbite-admin-header-brand">
							<span className="clanbite-admin-logo-wrap">
								<img
									src={
										window.clanbiteAdmin?.logoUrl || ''
									}
									alt=""
									className="clanbite-admin-logo"
								/>
								<span className="clanbite-admin-brand-title">
									{ __(
										'Clanbite: Team Management Software',
										'clanbite'
									) }
								</span>
							</span>
							{ versionLabel ? (
								<div className="clanbite-admin-version">
									{ versionLabel }
								</div>
							) : null }
						</div>
						<div className="clanbite-admin-header-actions">
							<a
								href="https://clanbite.com"
								className="clanbite-admin-header-btn"
								target="_blank"
								rel="noopener noreferrer"
							>
								{ __( 'Developers', 'clanbite' ) }
							</a>
							<a
								href="https://github.com/Kernow-dev/Clanbite"
								className="clanbite-admin-header-btn"
								target="_blank"
								rel="noopener noreferrer"
							>
								{ __( 'GitHub', 'clanbite' ) }
							</a>
							<a
								href="https://discord.gg/vCaA8JyGWh"
								className="clanbite-admin-header-btn"
								target="_blank"
								rel="noopener noreferrer"
							>
								{ __( 'Discord', 'clanbite' ) }
							</a>
						</div>
					</div>
				</header>
				<div className="clanbite-admin-main">
					{ saveNotice ? (
						<div
							className="clanbite-admin-global-notice"
							role="status"
						>
							{ saveNotice }
						</div>
					) : null }
					<TabPanel
						key={ `clanbite-admin-tabpanel-${ tabPanelEpoch }` }
						className="clanbite-admin-tabs"
						activeClass="is-active"
						tabs={ tabs }
						initialTabName={ initialTabName }
						onSelect={ onTabSelect }
					>
						{ ( tab ) => {
							const meta = bootstrap.tabs.find(
								( x ) => x.id === tab.name
							);
							if ( ! meta ) {
								return null;
							}
							if ( meta.type === 'general' ) {
								return (
									<div className="clanbite-admin-section-inner">
										<SettingsSections
											sections={ generalSections }
											optionKey={ generalKey }
											values={ values }
											onFieldChange={ onFieldChange }
										/>
										<Button
											variant="primary"
											onClick={ () =>
												saveOption( generalKey )
											}
											isBusy={ saving }
										>
											{ __(
												'Save settings',
												'clanbite'
											) }
										</Button>
									</div>
								);
							}
							if ( meta.type === 'extensions' ) {
								return (
									<div className="clanbite-admin-section-inner">
										<p className="description">
											{ __(
												'Enable or disable extensions. You can turn on dependencies and dependents in any order; save once when ready. Saving reloads this screen.',
												'clanbite'
											) }
										</p>
										<div className="clanbite-extensions-table-wrap">
											<table className="widefat striped clanbite-extensions-table">
												<thead>
													<tr>
														<th>
															{ __(
																'Name',
																'clanbite'
															) }
														</th>
														<th
															scope="col"
															className="clanbite-extensions-table__badges-col"
														>
															<VisuallyHidden as="span">
																{ __(
																	'Official and core labels',
																	'clanbite'
																) }
															</VisuallyHidden>
														</th>
														<th>
															{ __(
																'Description',
																'clanbite'
															) }
														</th>
														<th>
															{ __(
																'Requires',
																'clanbite'
															) }
														</th>
														<th>
															{ __(
																'Active',
																'clanbite'
															) }
														</th>
													</tr>
												</thead>
												<tbody>
													{ bootstrap.extensions.map(
														( ext ) => {
															const isOn =
																installed.includes(
																	ext.slug
																);
															const canTurnOn =
																extensionCanBeTurnedOn(
																	ext,
																	installed
																);
															const isRequired =
																ext.isRequired ===
																true;
															const toggleDisabled =
																isRequired ||
																( ! isOn &&
																	! canTurnOn );
															const versionBlocksInstall =
																Boolean(
																	ext.requiresClanbite
																) &&
																ext.meetsClanbiteVersion ===
																	false;
															return (
																<tr
																	key={
																		ext.slug
																	}
																>
																	<td>
																		<span
																			className={
																				'clanbite-extension-name' +
																				( ext.parentSlug
																					? ' clanbite-extension-name--child'
																					: '' )
																			}
																		>
																			{ ext.parentSlug
																				? '— '
																				: '' }
																			<strong>
																				{
																					ext.name
																				}
																			</strong>
																		</span>
																	</td>
																	<td className="clanbite-extensions-table__badges-col">
																		<div
																			className="clanbite-extension-badges"
																			role="group"
																			aria-label={ sprintf(
																				/* translators: %s: extension display name. */
																				__(
																					'Distribution labels for %s',
																					'clanbite'
																				),
																				ext.name
																			) }
																		>
																			{ ext.isOfficial ||
																			ext.isCoreBundled ? (
																				<>
																					{ ext.isOfficial ? (
																						<span className="clanbite-extension-badge">
																							{ __(
																								'Official',
																								'clanbite'
																							) }
																						</span>
																					) : null }
																					{ ext.isCoreBundled ? (
																						<span className="clanbite-extension-badge clanbite-extension-badge--core">
																							{ __(
																								'Core',
																								'clanbite'
																							) }
																						</span>
																					) : null }
																				</>
																			) : (
																				<>
																					<span
																						className="clanbite-extension-badge-placeholder"
																						aria-hidden="true"
																					>
																						—
																					</span>
																					<VisuallyHidden as="span">
																						{ __(
																							'No official or core label',
																							'clanbite'
																						) }
																					</VisuallyHidden>
																				</>
																			) }
																		</div>
																	</td>
																	<td>
																		{
																			ext.description
																		}
																	</td>
																	<td>
																		<ExtensionRequiresCell
																			requires={
																				ext.requires ||
																				[]
																			}
																			allExtensions={
																				bootstrap.extensions
																			}
																			installedSlugs={
																				installed
																			}
																			requiresClanbite={
																				ext.requiresClanbite ||
																				''
																			}
																			meetsClanbiteVersion={
																				ext.meetsClanbiteVersion !==
																				false
																			}
																		/>
																	</td>
																	<td>
																		<ToggleControl
																			label={ __(
																				'Installed',
																				'clanbite'
																			) }
																			checked={
																				isOn
																			}
																			disabled={
																				toggleDisabled
																			}
																			onChange={ (
																				on
																			) => {
																				if (
																					isRequired &&
																					! on
																				) {
																					return;
																				}
																				setInstalled(
																					(
																						prev
																					) => {
																						if (
																							on
																						) {
																							return [
																								...new Set(
																									[
																										...prev,
																										ext.slug,
																									]
																								),
																							];
																						}
																						return prev.filter(
																							(
																								s
																							) =>
																								s !==
																								ext.slug
																						);
																					}
																				);
																			} }
																			__nextHasNoMarginBottom
																		/>
																		{ toggleDisabled ? (
																			<p className="description">
																				{ getExtensionToggleDisabledMessage(
																					ext,
																					isRequired,
																					versionBlocksInstall
																				) }
																			</p>
																		) : null }
																	</td>
																</tr>
															);
														}
													) }
												</tbody>
											</table>
										</div>
										<Button
											variant="primary"
											className="clanbite-extensions-save"
											onClick={ saveExtensions }
											isBusy={ saving }
										>
											{ __(
												'Save extensions',
												'clanbite'
											) }
										</Button>
									</div>
								);
							}
							if ( meta.type === 'integration' ) {
								const slotId =
									meta.slotId || meta.slot_id || '';
								if ( ! slotId ) {
									return (
										<div className="clanbite-admin-section-inner">
											<p className="description">
												{ __(
													'This integration tab is misconfigured (missing slot identifier).',
													'clanbite'
												) }
											</p>
										</div>
									);
								}
								return (
									<div className="clanbite-admin-section-inner">
										<IntegrationSlot
											slotId={ slotId }
											bootstrap={ bootstrap }
											bootstrapRevision={
												bootstrapRevision
											}
										/>
									</div>
								);
							}
							if ( meta.type === 'extension' ) {
								return (
									<div className="clanbite-admin-section-inner">
										{ ( meta.sectionGroups || [] ).map(
											( group ) => (
												<div
													key={ `${ group.kind }-${ group.slug }` }
													className="clanbite-settings-section"
												>
													{ group.kind === 'child' ? (
														<h3>{ group.name }</h3>
													) : null }
													<SettingsSections
														sections={
															group.sections
														}
														optionKey={
															group.optionKey
														}
														values={ values }
														onFieldChange={
															onFieldChange
														}
													/>
													<Button
														variant="primary"
														onClick={ () =>
															saveOption(
																group.optionKey
															)
														}
														isBusy={ saving }
														className="clanbite-extension-save"
													>
														{ __(
															'Save',
															'clanbite'
														) }
													</Button>
												</div>
											)
										) }
									</div>
								);
							}
							return null;
						} }
					</TabPanel>
				</div>
			</div>
		</IconPickerBootstrapContext.Provider>
	);
}

const root = document.getElementById( 'clanbite-admin-root' );
if ( root ) {
	render( <App />, root );
}
