import { store, getContext, getElement } from '@wordpress/interactivity';

const { state, actions } = store( 'clanspress-player-avatar', {
	state: {
		root: null,
		isEditing: false,
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
		 * @returns {boolean} True if current active nav is this element.
		 */
		isThisNavExpanded() {
			const { attributes } = getElement();
			return (
				this.activeNav === JSON.parse( attributes[ 'data-wp-args' ] )
			);
		},

		/**
		 * Is the current child nav element expanded.
		 *
		 * @returns {boolean} True if current active nav is this child element.
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
						element.style.maxHeight = element.scrollHeight + 'px';
					} else {
						element.style.maxHeight = '0';
					}
				}
			}

			return isActive;
		},

		isThisControlActive() {

		},

		isThisPanelActive() {
			const { attributes } = getElement();
			return (
				this.activePanel === attributes[ 'data-wp-args' ]
			);
		},

		isPanelActive() {
			const { attributes } = getElement();
			return 'panel-' + this.activePanel === attributes.id;
		},

		isError() {
			const { attributes } = getElement();
			const name = attributes[ 'name' ];

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
		enableControls() {
			const { canEdit } = getContext();
			if ( ! canEdit ) {
				return;
			}

			state.isEditing = true;
		},

		disableControls() {
			const { canEdit } = getContext();
			if ( ! canEdit ) {
				return;
			}

			const openControls = state.root.querySelectorAll( '.player-avatar__controls-container .control-panel.active' );

			openControls.forEach( ( control ) => {
				control.parentElement.querySelector( 'button' ).click();
			} );

			state.isEditing = false;
		},

		toggleControl() {
			const { ref, attributes } = getElement();

			if ( ! ref || ! attributes ) {
				return;
			}

			const panelName = attributes[ 'data-wp-args' ];
			if ( ! panelName ) {
				return;
			}

			// Find the panel inside the current control
			const panel = ref.parentNode.querySelector(
				`.control-panel.${ panelName }`
			);
			if ( ! panel ) {
				return;
			}

			//state.activePanel = panel.id;

			// Toggle the 'active' class
			panel.classList.toggle( 'active' );
		},

		showPanel() {
			const { ref } = getElement();
			const panelId = ref?.attributes?.[ 'data-wp-args' ]?.value ?? null;

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
			const preview = state.root.querySelector(
				'.player-avatar__image-background'
			);
			if ( preview ) {
				// Update preview using object URL
				preview.src = `${ URL.createObjectURL( file ) }`;
			}

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
					if ( field.checked ) data[ field.name ] = field.value;
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

			if ( avatarInput && avatarInput.files[ 0 ] ) {
				formData.append( 'profile_avatar', avatarInput.files[ 0 ] );
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
		},
	},
} );
