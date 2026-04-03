/**
 * Unified Clanspress wp-admin (tabs: General, Extensions, per-extension settings).
 * Active tab is synced to `?tab=<id>` for deep links and refresh.
 */
import {
	render,
	useState,
	useEffect,
	useCallback,
	useRef,
} from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import {
	TabPanel,
	Spinner,
	Button,
	Notice,
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
			: ( clanspressAdmin?.restUrl || '' ).replace(
					/clanspress\/v1\/$/,
					''
			  ) || '/wp-json/';
	const nonce =
		typeof wpApiSettings !== 'undefined' && wpApiSettings?.nonce
			? wpApiSettings.nonce
			: clanspressAdmin?.nonce || '';
	apiFetch.use( apiFetch.createNonceMiddleware( nonce ) );
	apiFetch.use( apiFetch.createRootURLMiddleware( root ) );
}

function FieldControl( { field, value, onChange } ) {
	const id = field.id;
	const common = {
		label: field.label,
		help: field.description || undefined,
	};

	switch ( field.type ) {
		case 'image': {
			const previewUrl =
				value || field.fallbackUrl || field.fallback_url || '';

			const openMediaFrame = () => {
				if ( ! window.wp || ! window.wp.media ) {
					return;
				}

				const frame = window.wp.media( {
					title:
						field.mediaTitle || __( 'Select image', 'clanspress' ),
					button: {
						text:
							field.mediaButtonText ||
							__( 'Use image', 'clanspress' ),
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
				<div className="clanspress-field-image">
					{ previewUrl ? (
						<div className="clanspress-field-image-preview">
							<img src={ previewUrl } alt="" />
						</div>
					) : null }
					<div className="clanspress-field-image-actions">
						<Button variant="secondary" onClick={ openMediaFrame }>
							{ __( 'Upload / choose image', 'clanspress' ) }
						</Button>
						<Button
							variant="tertiary"
							isDestructive
							onClick={ () => onChange( id, '' ) }
						>
							{ __( 'Use plugin default', 'clanspress' ) }
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
		case 'textarea':
			return (
				<TextareaControl
					{ ...common }
					value={ String( value ?? '' ) }
					onChange={ ( v ) => onChange( id, v ) }
					rows={ 4 }
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
					'clanspress'
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
			<div key={ section.id } className="clanspress-settings-section">
				{ section.title ? <h3>{ section.title }</h3> : null }
				<div className="clanspress-settings-fields">
					{ visibleFields.map( ( field ) => (
						<div
							key={ field.id }
							className="clanspress-settings-field-row"
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
 * @param {string[]} props.requires       Slugs from the server (`ext.requires`).
 * @param {Object[]} props.allExtensions  Full `bootstrap.extensions` list.
 * @param {string[]} props.installedSlugs Currently toggled-on slugs in the UI.
 * @return {import('react').ReactNode}
 */
function ExtensionRequiresCell( { requires, allExtensions, installedSlugs } ) {
	if ( ! requires?.length ) {
		return (
			<span
				className="description"
				aria-label={ __( 'None', 'clanspress' ) }
			>
				—
			</span>
		);
	}

	return (
		<ul className="clanspress-extension-requires">
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
								({ __( 'not installed', 'clanspress' ) })
							</span>
						) : null }
					</li>
				);
			} ) }
		</ul>
	);
}

/**
 * Whether an extension may be switched on given the current checkbox/toggle state (not yet saved).
 * Extensions with no `requires` still respect server `canInstall` (e.g. custom filters).
 *
 * @param {Object}   ext                   Extension row from bootstrap.
 * @param {string[]} pendingInstalledSlugs Slugs currently toggled on in the UI.
 * @return {boolean}
 */
function extensionCanBeTurnedOn( ext, pendingInstalledSlugs ) {
	if ( ext.requires?.length ) {
		return ext.requires.every( ( slug ) =>
			pendingInstalledSlugs.includes( slug )
		);
	}
	return ext.canInstall;
}

function App() {
	const [ pluginMeta, setPluginMeta ] = useState( {
		version: '',
		isBeta: false,
	} );

	const [ bootstrap, setBootstrap ] = useState( null );
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
				path: 'clanspress/v1/admin/bootstrap',
			} );
			setBootstrap( data );
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
				e?.message || __( 'Failed to load settings.', 'clanspress' )
			);
		}
	}, [] );

	useEffect( () => {
		setupApiFetch();
		load();
	}, [ load ] );

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
			__( '%1$s ‹ %2$s', 'clanspress' ),
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
				path: `clanspress/v1/admin/settings/${ optionKey }`,
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
					{ __( 'Settings saved.', 'clanspress' ) }
				</Notice>
			);
		} catch ( e ) {
			setSaveNotice(
				<Notice
					status="error"
					isDismissible
					onRemove={ () => setSaveNotice( null ) }
				>
					{ e?.message || __( 'Save failed.', 'clanspress' ) }
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
				path: 'clanspress/v1/admin/extensions',
				method: 'PUT',
				data: { installed },
			} );
			setSaveNotice(
				<Notice
					status="success"
					isDismissible
					onRemove={ () => setSaveNotice( null ) }
				>
					{ __( 'Extensions updated. Reloading…', 'clanspress' ) }
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
						__( 'Could not update extensions.', 'clanspress' ) }
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
					__( '%1$s beta', 'clanspress' ),
					pluginMeta.version
			  )
			: pluginMeta.version
		: '';

	return (
		<div className="clanspress-admin-app clanspress-admin-shell">
			<header className="clanspress-admin-header">
				<div className="clanspress-admin-header-inner">
					<div className="clanspress-admin-header-brand">
						<span className="clanspress-admin-logo-wrap">
							<img
								src={ window.clanspressAdmin?.logoUrl || '' }
								alt=""
								className="clanspress-admin-logo"
							/>
						</span>
						{ versionLabel ? (
							<div className="clanspress-admin-version">
								{ versionLabel }
							</div>
						) : null }
					</div>
					<div className="clanspress-admin-header-actions">
						<a
							href="https://clanspress.com"
							className="clanspress-admin-header-btn"
							target="_blank"
							rel="noopener noreferrer"
						>
							{ __( 'Developers', 'clanspress' ) }
						</a>
						<a
							href="https://github.com/Kernow-dev/Clanspress"
							className="clanspress-admin-header-btn"
							target="_blank"
							rel="noopener noreferrer"
						>
							{ __( 'GitHub', 'clanspress' ) }
						</a>
						<a
							href="https://discord.gg/vCaA8JyGWh"
							className="clanspress-admin-header-btn"
							target="_blank"
							rel="noopener noreferrer"
						>
							{ __( 'Discord', 'clanspress' ) }
						</a>
					</div>
				</div>
			</header>
			<div className="clanspress-admin-main">
				{ saveNotice ? (
					<div
						className="clanspress-admin-global-notice"
						role="status"
					>
						{ saveNotice }
					</div>
				) : null }
				<TabPanel
					key={ `clanspress-admin-tabpanel-${ tabPanelEpoch }` }
					className="clanspress-admin-tabs"
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
								<div className="clanspress-admin-section-inner">
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
										{ __( 'Save settings', 'clanspress' ) }
									</Button>
								</div>
							);
						}
						if ( meta.type === 'extensions' ) {
							return (
								<div className="clanspress-admin-section-inner">
									<p className="description">
										{ __(
											'Enable or disable extensions. You can turn on dependencies and dependents in any order; save once when ready. Saving reloads this screen.',
											'clanspress'
										) }
									</p>
									<div className="clanspress-extensions-table-wrap">
										<table className="widefat striped clanspress-extensions-table">
											<thead>
												<tr>
													<th>
														{ __(
															'Name',
															'clanspress'
														) }
													</th>
													<th
														scope="col"
														className="clanspress-extensions-table__badges-col"
													>
														<VisuallyHidden as="span">
															{ __(
																'Official and core labels',
																'clanspress'
															) }
														</VisuallyHidden>
													</th>
													<th>
														{ __(
															'Description',
															'clanspress'
														) }
													</th>
													<th>
														{ __(
															'Requires',
															'clanspress'
														) }
													</th>
													<th>
														{ __(
															'Active',
															'clanspress'
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
														return (
															<tr
																key={ ext.slug }
															>
																<td>
																	<span
																		className={
																			'clanspress-extension-name' +
																			( ext.parentSlug
																				? ' clanspress-extension-name--child'
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
																<td className="clanspress-extensions-table__badges-col">
																	<div
																		className="clanspress-extension-badges"
																		role="group"
																		aria-label={ sprintf(
																			/* translators: %s: extension display name. */
																			__(
																				'Distribution labels for %s',
																				'clanspress'
																			),
																			ext.name
																		) }
																	>
																		{ ext.isOfficial ||
																		ext.isCoreBundled ? (
																			<>
																				{ ext.isOfficial ? (
																					<span className="clanspress-extension-badge">
																						{ __(
																							'Official',
																							'clanspress'
																						) }
																					</span>
																				) : null }
																				{ ext.isCoreBundled ? (
																					<span className="clanspress-extension-badge clanspress-extension-badge--core">
																						{ __(
																							'Core',
																							'clanspress'
																						) }
																					</span>
																				) : null }
																			</>
																		) : (
																			<>
																				<span
																					className="clanspress-extension-badge-placeholder"
																					aria-hidden="true"
																				>
																					—
																				</span>
																				<VisuallyHidden
																					as="span"
																				>
																					{ __(
																						'No official or core label',
																						'clanspress'
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
																	/>
																</td>
																<td>
																	<ToggleControl
																		label={ __(
																			'Installed',
																			'clanspress'
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
																			{ isRequired
																				? __(
																						'This extension is required and cannot be disabled.',
																						'clanspress'
																				  )
																				: ext
																						.requires
																						?.length
																				? __(
																						'Turn on all required extensions first (you can save everything in one step).',
																						'clanspress'
																				  )
																				: __(
																						'This extension cannot be enabled.',
																						'clanspress'
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
										className="clanspress-extensions-save"
										onClick={ saveExtensions }
										isBusy={ saving }
									>
										{ __(
											'Save extensions',
											'clanspress'
										) }
									</Button>
								</div>
							);
						}
						if ( meta.type === 'extension' ) {
							return (
								<div className="clanspress-admin-section-inner">
									{ ( meta.sectionGroups || [] ).map(
										( group ) => (
											<div
												key={ `${ group.kind }-${ group.slug }` }
												className="clanspress-settings-section"
											>
												{ group.kind === 'child' ? (
													<h3>{ group.name }</h3>
												) : null }
												<SettingsSections
													sections={ group.sections }
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
													className="clanspress-extension-save"
												>
													{ __(
														'Save',
														'clanspress'
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
	);
}

const root = document.getElementById( 'clanspress-admin-root' );
if ( root ) {
	render( <App />, root );
}
