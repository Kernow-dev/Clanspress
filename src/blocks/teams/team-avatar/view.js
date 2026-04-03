/**
 * Front-end: team avatar upload when the block enables inline editing.
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

const { state, actions } = store( 'clanspress-team-avatar', {
	state: {
		root: null,
		activePanel: null,
		isSaving: false,
		toast: {
			visible: false,
			type: 'success',
			message: '',
			timeout: null,
		},

		isThisPanelActive() {
			const { attributes } = getElement();
			return this.activePanel === attributes[ 'data-wp-args' ];
		},

		isToastSuccess() {
			return this.toast.type === 'success';
		},

		isToastError() {
			return this.toast.type === 'error';
		},
	},

	actions: {
		togglePanel() {
			const { ref, attributes } = getElement();
			if ( ! ref || ! attributes || ! ref.parentNode ) {
				return;
			}
			const panelName = attributes[ 'data-wp-args' ];
			if ( ! panelName ) {
				return;
			}
			const panel = ref.parentNode.querySelector(
				`.clanspress-team-avatar__panel--${ panelName }`
			);
			if ( ! panel ) {
				return;
			}
			const willOpen = ! panel.classList.contains( 'is-open' );
			ref.parentNode
				.querySelectorAll( '.clanspress-team-avatar__panel' )
				.forEach( ( p ) => p.classList.remove( 'is-open' ) );
			if ( willOpen ) {
				panel.classList.add( 'is-open' );
				state.activePanel = panelName;
			} else {
				state.activePanel = null;
			}
		},

		selectFile() {
			state.root?.querySelector( 'input[name="team_avatar"]' )?.click();
		},

		updateImage( event ) {
			const file = event.target.files[ 0 ];
			if ( ! file ) {
				return;
			}
			const { strings } = getContext();
			const badType = strings?.invalidFileType || 'Invalid file type.';
			if ( ! [ 'image/png', 'image/jpeg' ].includes( file.type ) ) {
				if ( window.wp?.a11y?.speak ) {
					window.wp.a11y.speak( badType, 'assertive' );
				} else {
					window.alert( badType );
				}
				event.target.value = '';
				return;
			}
			const preview = state.root?.querySelector(
				'.clanspress-team-avatar__img'
			);
			if ( preview && preview.tagName === 'IMG' ) {
				preview.classList.remove( 'clanspress-team-avatar__img--empty' );
				preview.src = URL.createObjectURL( file );
			}
		},

		showToast( { type = 'success', message = '', duration = 6000 } ) {
			if ( state.toast.timeout ) {
				clearTimeout( state.toast.timeout );
			}
			state.toast.type = type;
			state.toast.message = message;
			state.toast.visible = true;
			if ( duration ) {
				state.toast.timeout = setTimeout( () => {
					state.toast.visible = false;
				}, duration );
			}
		},

		async save() {
			const { ref } = getElement();
			const { ajaxUrl, teamId, strings } = getContext();

			if ( ! state.root || ! ref || ! ajaxUrl || ! teamId ) {
				return;
			}

			state.isSaving = true;
			ref.classList.remove( 'saved', 'error' );
			ref.classList.add( 'saving' );

			const nonceInput = state.root.querySelector(
				'input[name="_clanspress_team_media_nonce"]'
			);
			const fileInput = state.root.querySelector(
				'input[name="team_avatar"]'
			);

			if ( ! nonceInput || ! fileInput?.files?.[ 0 ] ) {
				state.isSaving = false;
				ref.classList.remove( 'saving' );
				ref.classList.add( 'error' );
				actions.showToast( {
					type: 'error',
					message:
						strings?.saveError ||
						'There was an error while saving changes.',
				} );
				return;
			}

			const formData = new FormData();
			formData.append( 'action', 'clanspress_save_team_media' );
			formData.append( 'clanspress_team_id', String( teamId ) );
			formData.append(
				'_clanspress_team_media_nonce',
				nonceInput.value
			);
			formData.append( 'team_avatar', fileInput.files[ 0 ] );

			try {
				const res = await fetch( ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					body: formData,
				} );

				const json = await res.json();
				ref.classList.remove( 'saving' );

				if ( json.success && json.data?.avatarUrl ) {
					ref.classList.add( 'saved' );
					const img = state.root.querySelector(
						'.clanspress-team-avatar__img'
					);
					if ( img && img.tagName === 'IMG' ) {
						img.src = json.data.avatarUrl;
						img.classList.remove(
							'clanspress-team-avatar__img--empty'
						);
					}
					fileInput.value = '';
					actions.showToast( {
						type: 'success',
						message:
							strings?.saveSuccess ||
							'Your changes were saved successfully.',
					} );
					state.root
						.querySelectorAll( '.clanspress-team-avatar__panel' )
						.forEach( ( p ) => p.classList.remove( 'is-open' ) );
					state.activePanel = null;
				} else {
					ref.classList.add( 'error' );
					actions.showToast( {
						type: 'error',
						message:
							json?.data?.message ||
							strings?.saveError ||
							'There was an error while saving changes.',
					} );
				}
			} catch ( err ) {
				// eslint-disable-next-line no-console
				console.error( 'Save failed', err );
				ref.classList.remove( 'saving' );
				ref.classList.add( 'error' );
			} finally {
				state.isSaving = false;
			}
		},
	},

	callbacks: {
		init() {
			const { ref } = getElement();
			if ( ref ) {
				state.root = ref;
			}
		},
	},
} );
