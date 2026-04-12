import { store, getElement } from '@wordpress/interactivity';

const parseArg = ( value ) => {
	try {
		return JSON.parse( value );
	} catch ( e ) {
		return value;
	}
};

const parseMaybeJson = ( value, fallback = {} ) => {
	if ( ! value || typeof value !== 'string' ) {
		return fallback;
	}
	try {
		return JSON.parse( value );
	} catch ( e ) {
		return fallback;
	}
};

const getPlayerSettingsConfig = () =>
	typeof window !== 'undefined' ? window.CLANSPRESSPLAYERSETTINGS || {} : {};

/**
 * @param {HTMLElement|null} root Block root.
 * @param {string}           nav   Parent nav slug (e.g. profile).
 * @param {string}           panel Panel slug (e.g. profile-info).
 * @return {boolean} Whether both controls exist in the DOM.
 */
const playerSettingsRouteExistsInDom = ( root, nav, panel ) => {
	if ( ! root || ! nav || ! panel ) {
		return false;
	}
	const navHeader = root.querySelector(
		`.nav-item-header[aria-controls="${ nav }"]`
	);
	const panelBtn = root.querySelector(
		`.nav-sub-item[aria-controls="panel-${ panel }"]`
	);
	return Boolean( navHeader && panelBtn );
};

const parsePlayerSettingsRouteFromWindow = () => {
	const cfg = getPlayerSettingsConfig();
	const base = ( cfg.settings_url_base || '' ).replace( /\/$/, '' );
	if ( ! base || typeof window === 'undefined' ) {
		return { nav: null, panel: null };
	}
	try {
		const baseUrl = new URL( base, window.location.origin );
		const prefix = baseUrl.pathname.replace( /\/$/, '' );
		const path = window.location.pathname.replace( /\/$/, '' );
		if ( ! path.startsWith( prefix ) ) {
			return { nav: null, panel: null };
		}
		const rest = path.slice( prefix.length ).replace( /^\//, '' );
		if ( ! rest ) {
			return { nav: null, panel: null };
		}
		const parts = rest.split( '/' ).filter( Boolean );
		return { nav: parts[ 0 ] || null, panel: parts[ 1 ] || null };
	} catch {
		return { nav: null, panel: null };
	}
};

let playerSettingsPopstateBound = false;

const { state, actions } = store( 'clanspress-player-settings', {
	state: {
		root: null,
		activeNav: null,
		activePanel: null,
		isSaving: false,
		errors: {},
		toast: {
			visible: false,
			type: 'success',
			heading: '',
			message: '',
			timeout: null,
		},

		/**
		 * Is the current parent nav element expanded.
		 *
		 * @return {boolean} True if current active nav is this element.
		 */
		isThisNavExpanded() {
			const { attributes } = getElement();
			return this.activeNav === parseArg( attributes[ 'data-wp-args' ] );
		},

		/**
		 * Is the current child nav element expanded.
		 *
		 * @return {boolean} True if current active nav is this child element.
		 */
		isNavExpanded() {
			const { attributes } = getElement();
			const isActive = this.activeNav === attributes.id;

			if ( state.root ) {
				const element = state.root.querySelector(
					`#${ attributes.id }`
				);
				if ( element ) {
					if ( isActive ) {
						// Open: set max-height to scrollHeight for smooth transition
						element.style.maxHeight = `${ element.scrollHeight }px`;
					} else {
						element.style.maxHeight = '0';
					}
				}
			}

			return isActive;
		},

		isThisPanelActive() {
			const { attributes } = getElement();
			return (
				this.activePanel === parseArg( attributes[ 'data-wp-args' ] )
			);
		},

		isPanelActive() {
			const { attributes } = getElement();
			return `panel-${ this.activePanel }` === attributes.id;
		},

		isError() {
			const { attributes } = getElement();
			const name = attributes.name;

			if ( ! name ) {
				return false;
			}

			return name in this.errors;
		},

		showError() {
			const { attributes } = getElement();
			const name = attributes[ 'data-wp-args' ];

			if ( ! name ) {
				return false;
			}

			return ! ( name in this.errors );
		},

		errorMessage() {
			const { attributes } = getElement();
			const name = attributes[ 'data-wp-args' ];

			if ( ! name ) {
				return null;
			}

			if ( this.errors && name in this.errors ) {
				return this.errors[ name ];
			}

			return null;
		},

		isToastSuccess() {
			return this.toast.type === 'success';
		},

		isToastError() {
			return this.toast.type === 'error';
		},
	},

	actions: {
		toggleNav() {
			const { ref } = getElement();
			const navId = parseArg(
				ref?.attributes?.[ 'data-wp-args' ]?.value ?? ''
			);

			if ( ! navId ) {
				return;
			}

			if ( state.activeNav === navId ) {
				state.activeNav = null;
				return;
			}

			state.activeNav = navId;
			const navItem = ref.closest( '.nav-item' );
			const firstSub = navItem?.querySelector( '.nav-sub-item' );
			const firstPanel = firstSub
				?.getAttribute( 'aria-controls' )
				?.replace( /^panel-/, '' );
			if ( firstPanel ) {
				state.activePanel = firstPanel;
			}
			pushPlayerSettingsUrl();
		},

		showPanel() {
			const { ref } = getElement();
			const panelId = parseArg(
				ref?.attributes?.[ 'data-wp-args' ]?.value ?? ''
			);

			if ( ! panelId ) {
				return;
			}

			const navItem = ref.closest( '.nav-item' );
			const navHeader = navItem?.querySelector( '.nav-item-header' );
			const navId = navHeader?.getAttribute( 'aria-controls' );
			if ( navId ) {
				state.activeNav = navId;
			}
			state.activePanel = panelId;
			pushPlayerSettingsUrl();
		},

		selectAvatar() {
			const avatarInput = state.root.querySelector(
				'input[name="profile_avatar"]'
			);

			if ( avatarInput ) {
				avatarInput.click();
			}
		},

		updateAvatar( event ) {
			const file = event.target.files[ 0 ];
			if ( ! file ) {
				return;
			}

			// Only allow image files
			if ( ! [ 'image/png', 'image/jpeg' ].includes( file.type ) ) {
				alert( 'Only PNG or JPEG allowed.' );
				return;
			}

			// Find the avatar preview inside this block
			const preview = state.root.querySelector( '.avatar-preview' );
			if ( ! preview ) {
				return;
			}

			// Update preview using object URL
			preview.style.backgroundImage = `url(${ URL.createObjectURL(
				file
			) })`;
		},

		selectCover() {
			const coverInput = state.root.querySelector(
				'input[name="profile_cover"]'
			);

			if ( coverInput ) {
				coverInput.click();
			}
		},

		updateCover( event ) {
			const file = event.target.files[ 0 ];
			if ( ! file ) {
				return;
			}

			// Only allow image files
			if ( ! [ 'image/png', 'image/jpeg' ].includes( file.type ) ) {
				alert( 'Only PNG or JPEG allowed.' );
				return;
			}

			// Find the cover preview inside this block
			const preview = state.root.querySelector( '.cover-preview' );
			if ( ! preview ) {
				return;
			}

			// Update preview using object URL
			preview.style.backgroundImage = `url(${ URL.createObjectURL(
				file
			) })`;
		},

		showToast( {
			type = 'success',
			heading = '',
			message = '',
			duration = 6000,
		} ) {
			// Clear existing timeout
			if ( state.toast.timeout ) {
				clearTimeout( state.toast.timeout );
			}

			state.toast.type = type;
			state.toast.heading = heading;
			state.toast.message = message;
			state.toast.visible = true;

			// Auto-hide
			if ( duration ) {
				state.toast.timeout = setTimeout( () => {
					state.toast.visible = false;
				}, duration );
			}
		},

		hideToast() {
			if ( state.toast.timeout ) {
				clearTimeout( state.toast.timeout );
			}

			state.toast.visible = false;
		},

		async save() {
			const { ref } = getElement();

			if ( ! state.root ) {
				return;
			}

			// Grab the nonce from the hidden field
			const nonceInput = state.root.querySelector(
				'input[name="_clanspress_profile_settings_save_nonce"]'
			);

			if ( ! nonceInput || ! window.CLANSPRESSPLAYERSETTINGS?.ajax_url ) {
				ref.classList.remove( 'saving' );
				ref.classList.add( 'error' );
				return;
			}

			/*
			 * Read all field values before touching reactive store state (`state.isSaving`, etc.).
			 * Updating the Interactivity store first can trigger a Preact pass that reconciles
			 * the hydrated tree back toward the server snapshot and resets form controls, so
			 * values edited on other tabs (e.g. Social Networks) would be lost on save.
			 */
			const fields = state.root.querySelectorAll(
				'input, select, textarea'
			);

			const data = {};

			fields.forEach( ( field ) => {
				if ( ! field.name || field.disabled ) {
					return;
				}

				if ( field.type === 'checkbox' ) {
					data[ field.name ] = field.checked ? '1' : '0';
				} else if ( field.type === 'radio' ) {
					if ( field.checked ) {
						data[ field.name ] = field.value;
					}
				} else if ( field.type !== 'file' ) {
					data[ field.name ] = field.value;
				}
			} );

			data.nonce = nonceInput.value;

			data.action = 'clanspress_save_player_settings';

			const formData = new FormData();
			for ( const key in data ) {
				formData.append( key, data[ key ] );
			}

			// Add files directly from inputs
			const avatarInput = state.root.querySelector(
				'input[name="profile_avatar"]'
			);
			const coverInput = state.root.querySelector(
				'input[name="profile_cover"]'
			);

			if ( avatarInput && avatarInput.files[ 0 ] ) {
				formData.append( 'profile_avatar', avatarInput.files[ 0 ] );
			}

			if ( coverInput && coverInput.files[ 0 ] ) {
				formData.append( 'profile_cover', coverInput.files[ 0 ] );
			}

			state.isSaving = true;
			ref.classList.remove( 'saved' );
			ref.classList.remove( 'error' );
			ref.classList.add( 'saving' );

			try {
				const res = await fetch( CLANSPRESSPLAYERSETTINGS.ajax_url, {
					method: 'POST',
					credentials: 'same-origin',
					body: formData,
				} );

				const json = await res.json();

				ref.classList.remove( 'saving' );

				if ( json.success ) {
					ref.classList.add( 'saved' );
					state.errors = {};
					actions.showToast( {
						type: 'success',
						heading: 'Success',
						message: 'Your changes were saved successfully.',
					} );
				} else {
					ref.classList.add( 'error' );
					state.errors = json?.data?.errors;
					actions.showToast( {
						type: 'error',
						heading: 'Error',
						message: 'There was an error while saving changes.',
					} );
				}
			} catch ( err ) {
				ref.classList.remove( 'saving' );
				ref.classList.add( 'error' );
				actions.showToast( {
					type: 'error',
					heading: 'Error',
					message: 'Network error while saving changes.',
				} );
				state.errors = {};
				console.error( 'Save failed', err );
			} finally {
				state.isSaving = false;
			}
		},

		/**
		 * Generic action endpoint for plugin-provided controls inside player settings.
		 *
		 * Supported data attributes on the clicked element:
		 * - data-cp-action-url (required)
		 * - data-cp-action-method (default POST)
		 * - data-cp-action-body (JSON string, optional)
		 * - data-cp-action-confirm (optional confirmation text)
		 * - data-cp-action-remove-closest (CSS selector to remove on success)
		 * - data-cp-action-success-message / data-cp-action-error-message
		 */
		async runPluginAction( event ) {
			event.preventDefault();
			const { ref } = getElement();
			if ( ! ref ) {
				return;
			}

			const actionUrl = ref.getAttribute( 'data-cp-action-url' );
			if ( ! actionUrl ) {
				return;
			}

			const confirmMsg = ref.getAttribute( 'data-cp-action-confirm' );
			if ( confirmMsg && ! window.confirm( confirmMsg ) ) {
				return;
			}

			const method =
				( ref.getAttribute( 'data-cp-action-method' ) || 'POST' )
					.toUpperCase()
					.trim() || 'POST';
			const bodyRaw = ref.getAttribute( 'data-cp-action-body' ) || '';
			const bodyObj = parseMaybeJson( bodyRaw, {} );
			const successMsg =
				ref.getAttribute( 'data-cp-action-success-message' ) ||
				'Action completed.';
			const errorMsg =
				ref.getAttribute( 'data-cp-action-error-message' ) ||
				'Could not complete this action.';
			const removeSelector =
				ref.getAttribute( 'data-cp-action-remove-closest' ) || '';

			const restNonce = window.CLANSPRESSPLAYERSETTINGS?.rest_nonce || '';
			ref.disabled = true;
			try {
				const response = await fetch( actionUrl, {
					method,
					credentials: 'same-origin',
					headers: {
						'Content-Type': 'application/json',
						...( restNonce ? { 'X-WP-Nonce': restNonce } : {} ),
					},
					body:
						method === 'GET' || method === 'HEAD'
							? undefined
							: JSON.stringify( bodyObj ),
				} );
				if ( ! response.ok ) {
					actions.showToast( {
						type: 'error',
						heading: 'Error',
						message: errorMsg,
					} );
					ref.disabled = false;
					return;
				}

				if ( removeSelector ) {
					const row = ref.closest( removeSelector );
					if ( row ) {
						row.remove();
					}
				}
				actions.showToast( {
					type: 'success',
					heading: 'Success',
					message: successMsg,
				} );
			} catch ( err ) {
				actions.showToast( {
					type: 'error',
					heading: 'Error',
					message: errorMsg,
				} );
				ref.disabled = false;
			}
		},
	},

	callbacks: {
		init() {
			const { ref } = getElement();

			if ( ! ref ) {
				return;
			}

			state.root = ref;

			if ( ! playerSettingsPopstateBound ) {
				playerSettingsPopstateBound = true;
				window.addEventListener( 'popstate', () => {
					if ( ! state.root ) {
						return;
					}
					const cfg = getPlayerSettingsConfig();
					if ( ! cfg.settings_url_base ) {
						return;
					}
					const { nav, panel } = parsePlayerSettingsRouteFromWindow();
					if (
						nav &&
						panel &&
						playerSettingsRouteExistsInDom( state.root, nav, panel )
					) {
						state.activeNav = nav;
						state.activePanel = panel;
						return;
					}
					const firstNav = state.root.querySelector( '.nav-item' );
					const navButton =
						firstNav?.querySelector( '.nav-item-header' );
					const subItem = firstNav?.querySelector( '.nav-sub-item' );
					const navId = navButton?.getAttribute( 'aria-controls' );
					const panelId = subItem
						?.getAttribute( 'aria-controls' )
						?.replace( /^panel-/, '' );
					if ( navId && panelId ) {
						state.activeNav = navId;
						state.activePanel = panelId;
					}
				} );
			}

			const cfg = getPlayerSettingsConfig();
			let nav = cfg.settings_initial_nav || '';
			let panel = cfg.settings_initial_panel || '';

			if ( ! nav || ! panel ) {
				const parsed = parsePlayerSettingsRouteFromWindow();
				if ( parsed.nav && parsed.panel ) {
					nav = parsed.nav;
					panel = parsed.panel;
				}
			}

			if (
				nav &&
				panel &&
				playerSettingsRouteExistsInDom( ref, nav, panel )
			) {
				state.activeNav = nav;
				state.activePanel = panel;
				replacePlayerSettingsUrl();
				return;
			}

			const firstNav = ref.querySelector( '.nav-item' );

			if ( ! firstNav ) {
				return;
			}

			const navButton = firstNav.querySelector( '.nav-item-header' );
			const subItem = firstNav.querySelector( '.nav-sub-item' );

			if ( ! navButton || ! subItem ) {
				return;
			}

			const navId = navButton.getAttribute( 'aria-controls' );
			const panelId = subItem
				.getAttribute( 'aria-controls' )
				?.replace( 'panel-', '' );

			if ( navId && panelId ) {
				state.activeNav = navId;
				state.activePanel = panelId;
				replacePlayerSettingsUrl();
			}
		},
	},
} );

const replacePlayerSettingsUrl = () => {
	const cfg = getPlayerSettingsConfig();
	const base = ( cfg.settings_url_base || '' ).replace( /\/$/, '' );
	if ( ! base || ! state.activeNav || ! state.activePanel ) {
		return;
	}
	const path = `${ base }/${ state.activeNav }/${ state.activePanel }/`;
	const url = new URL( path, window.location.origin );
	if (
		window.location.pathname.replace( /\/$/, '' ) !==
		url.pathname.replace( /\/$/, '' )
	) {
		window.history.replaceState(
			{ clanspressPlayerSettings: true },
			'',
			url
		);
	}
};

const pushPlayerSettingsUrl = () => {
	const cfg = getPlayerSettingsConfig();
	const base = ( cfg.settings_url_base || '' ).replace( /\/$/, '' );
	if ( ! base || ! state.activeNav || ! state.activePanel ) {
		return;
	}
	const path = `${ base }/${ state.activeNav }/${ state.activePanel }/`;
	const url = new URL( path, window.location.origin );
	if (
		window.location.pathname.replace( /\/$/, '' ) !==
		url.pathname.replace( /\/$/, '' )
	) {
		window.history.pushState( { clanspressPlayerSettings: true }, '', url );
	}
};
