/**
 * Front-end: team avatar upload when the block enables inline editing.
 */
import { store, getContext, getElement } from '@wordpress/interactivity';
import {
	applyClanspressInlineMediaSavePayload,
	createClanspressShowToast,
	createClanspressToolbarPanelToggler,
	getClanspressInteractivityStateGetter,
	rejectClanspressInvalidImageFile,
	setClanspressPreviewObjectUrlFromFile,
} from '../../shared/front-media-interactivity.js';

const getTeamAvatarState = getClanspressInteractivityStateGetter(
	'clanspress-team-avatar'
);

const { state, actions } = store( 'clanspress-team-avatar', {
	state: {
		root: null,
		activePanel: null,
		isSaving: false,
		previewObjectUrl: null,
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
		togglePanel: createClanspressToolbarPanelToggler( getTeamAvatarState, {
			panelSelectorPrefix: '.clanspress-team-avatar__panel--',
			allPanelsSelector: '.clanspress-team-avatar__panel',
		} ),

		selectFile() {
			state.root?.querySelector( 'input[name="team_avatar"]' )?.click();
		},

		updateImage( event ) {
			const file = event.target.files[ 0 ];
			if ( ! file ) {
				return;
			}
			const { strings } = getContext();
			if (
				rejectClanspressInvalidImageFile( file, event.target, strings, {
					showToast: actions.showToast,
				} )
			) {
				return;
			}
			const url = setClanspressPreviewObjectUrlFromFile( state, file );
			const preview = state.root?.querySelector(
				'.clanspress-team-avatar__img'
			);
			if ( preview && preview.tagName === 'IMG' ) {
				preview.classList.remove(
					'clanspress-team-avatar__img--empty'
				);
				preview.src = url;
			}
		},

		showToast: createClanspressShowToast( getTeamAvatarState ),

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
			formData.append( '_clanspress_team_media_nonce', nonceInput.value );
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
					applyClanspressInlineMediaSavePayload( state, json.data, {
						items: [
							{
								urlKey: 'avatarUrl',
								mediaSelector: '.clanspress-team-avatar__img',
								requireImg: true,
								emptyClass:
									'clanspress-team-avatar__img--empty',
								clearInputName: 'team_avatar',
							},
						],
					} );
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
