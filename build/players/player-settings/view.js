import { store, getElement } from '@wordpress/interactivity';

const parseArg = ( value ) => {
	try {
		return JSON.parse( value );
	} catch ( e ) {
		return value;
	}
};

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

			state.activeNav = state.activeNav === navId ? null : navId;
		},

		showPanel() {
			const { ref } = getElement();
			const panelId = parseArg(
				ref?.attributes?.[ 'data-wp-args' ]?.value ?? ''
			);

			if ( ! panelId ) {
				return;
			}

			state.activePanel = panelId;
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

			state.isSaving = true;
			ref.classList.remove( 'saved' );
			ref.classList.remove( 'error' );
			ref.classList.add( 'saving' );

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

			// Grab the nonce from the hidden field
			const nonceInput = state.root.querySelector(
				'input[name="_clanspress_profile_settings_save_nonce"]'
			);

			if ( ! nonceInput || ! window.CLANSPRESSPLAYERSETTINGS?.ajax_url ) {
				state.isSaving = false;
				ref.classList.remove( 'saving' );
				ref.classList.add( 'error' );
				return;
			}
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
	},

	callbacks: {
		init() {
			const { ref } = getElement();

			if ( ! ref ) {
				return;
			}

			state.root = ref;

			// First nav item
			const firstNav = ref.querySelector( '.nav-item' );

			if ( ! firstNav ) {
				return;
			}

			const navButton = firstNav.querySelector( '.nav-item-header' );
			const subItem = firstNav.querySelector( '.nav-sub-item' );

			if ( ! navButton || ! subItem ) {
				console.log( 'no nav item/sub item' );
				return;
			}

			const navId = navButton.getAttribute( 'aria-controls' );
			const panelId = subItem
				.getAttribute( 'aria-controls' )
				?.replace( 'panel-', '' );

			if ( navId && panelId ) {
				state.activeNav = navId;
				state.activePanel = panelId;
			}
		},
	},
} );
