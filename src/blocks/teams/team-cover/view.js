/**
 * Front-end: team cover upload when the block enables inline editing.
 */
import { store, getContext, getElement } from '@wordpress/interactivity';
import {
	applyClanspressInlineMediaSavePayload,
	CLANSPRESS_MEDIA_ISLAND_ROOT_SELECTORS,
	createClanspressShowToast,
	createClanspressToolbarPanelToggler,
	getClanspressInteractivityStateGetter,
	getClanspressIslandRootFromRef,
	getClanspressToolbarPanelId,
	rejectClanspressInvalidImageFile,
	setClanspressPreviewObjectUrlFromFile,
} from '../../shared/front-media-interactivity.js';

const STORE_NAMESPACE = 'clanspress-team-cover';

const getTeamCoverState =
	getClanspressInteractivityStateGetter( STORE_NAMESPACE );

const { state, actions } = store( STORE_NAMESPACE, {
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
			const { ref, attributes } = getElement();
			return this.activePanel === getClanspressToolbarPanelId( attributes, ref );
		},

		isToastSuccess() {
			return this.toast.type === 'success';
		},

		isToastError() {
			return this.toast.type === 'error';
		},
	},

	actions: {
		togglePanel: createClanspressToolbarPanelToggler( getTeamCoverState, {
			panelSelectorPrefix: '.clanspress-team-cover__panel--',
			allPanelsSelector: '.clanspress-team-cover__panel',
			islandRootSelector: CLANSPRESS_MEDIA_ISLAND_ROOT_SELECTORS.teamCover,
		} ),

		selectFile() {
			const { ref } = getElement();
			const root = getClanspressIslandRootFromRef(
				ref,
				CLANSPRESS_MEDIA_ISLAND_ROOT_SELECTORS.teamCover
			);
			root?.querySelector( 'input[name="team_cover"]' )?.click();
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
			const { ref } = getElement();
			const root = getClanspressIslandRootFromRef(
				ref,
				CLANSPRESS_MEDIA_ISLAND_ROOT_SELECTORS.teamCover
			);
			const url = setClanspressPreviewObjectUrlFromFile( state, file );
			const preview = root?.querySelector(
				'.clanspress-team-cover__media'
			);
			if ( preview ) {
				preview.classList.remove(
					'clanspress-team-cover__media--empty'
				);
				preview.style.opacity = '';
				preview.style.pointerEvents = '';
				preview.src = url;
			}
		},

		showToast: createClanspressShowToast( getTeamCoverState ),

		async save() {
			const { ref } = getElement();
			const { ajaxUrl, teamId, strings } = getContext();
			const root = getClanspressIslandRootFromRef(
				ref,
				CLANSPRESS_MEDIA_ISLAND_ROOT_SELECTORS.teamCover
			);

			if ( ! root || ! ref || ! ajaxUrl || ! teamId ) {
				return;
			}

			state.isSaving = true;
			ref.classList.remove( 'saved', 'error' );
			ref.classList.add( 'saving' );

			const nonceInput = root.querySelector(
				'input[name="_clanspress_team_media_nonce"]'
			);
			const fileInput = root.querySelector(
				'input[name="team_cover"]'
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
			formData.append( 'team_cover', fileInput.files[ 0 ] );

			try {
				const res = await fetch( ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					body: formData,
				} );

				const json = await res.json();
				ref.classList.remove( 'saving' );

				if ( json.success && json.data?.coverUrl ) {
					ref.classList.add( 'saved' );
					applyClanspressInlineMediaSavePayload( state, json.data, {
						root,
						items: [
							{
								urlKey: 'coverUrl',
								mediaSelector: '.clanspress-team-cover__media',
								emptyClass:
									'clanspress-team-cover__media--empty',
								clearInputName: 'team_cover',
							},
						],
					} );
					actions.showToast( {
						type: 'success',
						message:
							strings?.saveSuccess ||
							'Your changes were saved successfully.',
					} );
					root
						.querySelectorAll( '.clanspress-team-cover__panel' )
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
				state.root =
					getClanspressIslandRootFromRef(
						ref,
						CLANSPRESS_MEDIA_ISLAND_ROOT_SELECTORS.teamCover
					) || ref;
			}
		},
	},
} );
