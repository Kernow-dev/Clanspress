import { store, getContext, getElement } from '@wordpress/interactivity';
import { syncTeamFormTabs } from '../shared/sync-team-form-tabs.js';

function clearDomNode( node ) {
	while ( node.firstChild ) {
		node.removeChild( node.firstChild );
	}
}

const { state, actions } = store( 'clanspress-team-create-form', {
	state: {
		root: null,
		step: 1,
		/** Total wizard steps (set in init from server context; do not use getContext() in derived state). */
		stepCount: 1,
		inviteQuery: '',
		inviteSuggestions: [],
		inviteActiveIndex: -1,
		invites: [],
		inviteSearchTimeout: null,
		maxStep() {
			const total = Number( state.stepCount );
			return total > 0 ? total : 1;
		},
		canGoNext() {
			return state.step < state.maxStep();
		},
		canGoBack() {
			return state.step > 1;
		},
	},
	actions: {
		onSubmit( event ) {
			if ( state.step < state.maxStep() ) {
				event.preventDefault();
				event.stopPropagation();
			}
		},
		nextStep( event ) {
			event.preventDefault();

			if ( state.step >= state.maxStep() ) {
				return;
			}

			const { ref } = getElement();
			const root = ref?.closest( '.clanspress-team-create-form' );
			if ( ! root ) {
				state.step += 1;
				syncTeamFormTabs( state.root, state.step, { wizard: true } );
				return;
			}

			const stepScope = root.querySelector(
				`[data-team-step="${ state.step }"]`
			);
			if ( ! stepScope ) {
				state.step += 1;
				syncTeamFormTabs( root, state.step, { wizard: true } );
				return;
			}

			const requiredFields = stepScope.querySelectorAll( '[required]' );
			let isValid = true;

			requiredFields.forEach( ( field ) => {
				if ( ! field.checkValidity() ) {
					field.reportValidity();
					isValid = false;
				}
			} );

			if ( ! isValid ) {
				return;
			}

			state.step += 1;
			syncTeamFormTabs( root, state.step, { wizard: true } );
		},
		previousStep( event ) {
			event.preventDefault();

			if ( state.step > 1 ) {
				state.step -= 1;
				const { ref } = getElement();
				const root = ref?.closest( '.clanspress-team-create-form' );
				syncTeamFormTabs( root, state.step, { wizard: true } );
			}
		},
		goToStepTab( event ) {
			event.preventDefault();
			const btn = event?.currentTarget;
			const n = Number( btn?.getAttribute( 'data-team-tab' ) );
			if ( ! n || n > state.step || n === state.step ) {
				return;
			}

			const { ref } = getElement();
			const root = ref?.closest( '.clanspress-team-create-form' );
			state.step = n;
			syncTeamFormTabs( root, state.step, { wizard: true } );
		},
		selectTeamAvatar() {
			const input = state.root?.querySelector(
				'input[name="team_avatar"]'
			);
			input?.click();
		},
		updateTeamAvatar( event ) {
			const file = event.target.files[ 0 ];
			if ( ! file ) {
				return;
			}

			if ( ! [ 'image/png', 'image/jpeg' ].includes( file.type ) ) {
				// eslint-disable-next-line no-alert -- synchronous validation before submit
				window.alert( 'Only PNG or JPEG allowed.' );
				event.target.value = '';
				return;
			}

			const preview = state.root?.querySelector(
				'.clanspress-team-create-form__avatar-preview'
			);
			if ( ! preview ) {
				return;
			}

			preview.style.backgroundImage = `url(${ URL.createObjectURL(
				file
			) })`;
		},
		selectTeamCover() {
			const input = state.root?.querySelector(
				'input[name="team_cover"]'
			);
			input?.click();
		},
		updateTeamCover( event ) {
			const file = event.target.files[ 0 ];
			if ( ! file ) {
				return;
			}

			if ( ! [ 'image/png', 'image/jpeg' ].includes( file.type ) ) {
				// eslint-disable-next-line no-alert -- synchronous validation before submit
				window.alert( 'Only PNG or JPEG allowed.' );
				event.target.value = '';
				return;
			}

			const preview = state.root?.querySelector(
				'.clanspress-team-create-form__cover-preview'
			);
			if ( ! preview ) {
				return;
			}

			preview.style.backgroundImage = `url(${ URL.createObjectURL(
				file
			) })`;
		},
		onInviteInput( event ) {
			const query = event?.target?.value || '';
			state.inviteQuery = query.trim();
			state.inviteActiveIndex = -1;

			if ( state.inviteSearchTimeout ) {
				clearTimeout( state.inviteSearchTimeout );
			}

			if ( state.inviteQuery.length < 2 ) {
				state.inviteSuggestions = [];
				actions.renderInviteSuggestions();
				return;
			}

			state.inviteSearchTimeout = setTimeout( async () => {
				await actions.fetchInviteSuggestions();
			}, 250 );
		},
		async fetchInviteSuggestions() {
			const context = getContext();
			if ( ! context.inviteSearchUrl || ! context.inviteSearchNonce ) {
				return;
			}

			const params = new URLSearchParams( {
				action: 'clanspress_team_invite_search',
				nonce: context.inviteSearchNonce,
				q: state.inviteQuery,
			} );

			try {
				const response = await fetch(
					`${ context.inviteSearchUrl }?${ params.toString() }`,
					{
						credentials: 'same-origin',
					}
				);
				const json = await response.json();

				if ( ! json.success || ! Array.isArray( json.data ) ) {
					state.inviteSuggestions = [];
					state.inviteActiveIndex = -1;
					actions.renderInviteSuggestions();
					return;
				}

				state.inviteSuggestions = json.data.filter(
					( user ) =>
						! state.invites.some(
							( invitedUser ) => invitedUser.id === user.id
						)
				);
				state.inviteActiveIndex = -1;
				actions.renderInviteSuggestions();
			} catch ( error ) {
				state.inviteSuggestions = [];
				state.inviteActiveIndex = -1;
				actions.renderInviteSuggestions();
			}
		},
		onInviteKeydown( event ) {
			const key = event?.key;
			const len = state.inviteSuggestions.length;

			if ( key === 'ArrowDown' ) {
				if ( len < 1 ) {
					return;
				}
				event.preventDefault();
				state.inviteActiveIndex =
					state.inviteActiveIndex < 0
						? 0
						: Math.min( state.inviteActiveIndex + 1, len - 1 );
				actions.renderInviteSuggestions();
				return;
			}

			if ( key === 'ArrowUp' ) {
				if ( len < 1 ) {
					return;
				}
				event.preventDefault();
				if ( state.inviteActiveIndex <= 0 ) {
					state.inviteActiveIndex = -1;
				} else {
					state.inviteActiveIndex -= 1;
				}
				actions.renderInviteSuggestions();
				return;
			}

			if ( key === 'Enter' ) {
				if ( len < 1 || state.inviteActiveIndex < 0 ) {
					return;
				}
				event.preventDefault();
				const user = state.inviteSuggestions[ state.inviteActiveIndex ];
				if ( user ) {
					actions.selectInviteUser( user );
				}
				return;
			}

			if ( key === 'Escape' ) {
				if ( len < 1 && state.inviteActiveIndex < 0 ) {
					return;
				}
				event.preventDefault();
				state.inviteSuggestions = [];
				state.inviteActiveIndex = -1;
				actions.renderInviteSuggestions();
			}
		},
		selectInviteUser( user ) {
			if ( ! user || ! user.id ) {
				return;
			}

			state.invites = [ ...state.invites, user ];
			state.inviteSuggestions = [];
			state.inviteActiveIndex = -1;
			actions.renderInviteSuggestions();
			actions.renderInviteList();

			const input = state.root?.querySelector(
				'#clanspress-team-invite-search'
			);
			if ( input ) {
				input.value = '';
			}
			state.inviteQuery = '';
		},
		onSuggestionClick( event ) {
			const button = event?.target?.closest?.(
				'button[data-invite-user-id]'
			);
			if ( ! button ) {
				return;
			}

			event.preventDefault();

			const id = Number(
				button.getAttribute( 'data-invite-user-id' ) || 0
			);
			if ( ! id ) {
				return;
			}

			const user = state.inviteSuggestions.find(
				( item ) => item.id === id
			);
			if ( ! user ) {
				return;
			}

			actions.selectInviteUser( user );
		},
		onInviteListClick( event ) {
			const button = event?.target?.closest?.(
				'button[data-remove-invite-id]'
			);
			if ( ! button ) {
				return;
			}

			event.preventDefault();
			const id = Number(
				button.getAttribute( 'data-remove-invite-id' ) || 0
			);
			if ( ! id ) {
				return;
			}

			state.invites = state.invites.filter( ( user ) => user.id !== id );
			actions.renderInviteList();
		},
		renderInviteSuggestions() {
			const list = state.root?.querySelector(
				'[data-team-invite-suggestions]'
			);
			const input = state.root?.querySelector(
				'#clanspress-team-invite-search'
			);
			if ( ! list ) {
				return;
			}

			clearDomNode( list );

			if ( state.inviteSuggestions.length < 1 ) {
				if ( input ) {
					input.setAttribute( 'aria-expanded', 'false' );
					input.removeAttribute( 'aria-activedescendant' );
				}
				return;
			}

			if (
				state.inviteActiveIndex >= state.inviteSuggestions.length ||
				state.inviteActiveIndex < -1
			) {
				state.inviteActiveIndex = -1;
			}

			const frag = document.createDocumentFragment();
			state.inviteSuggestions.forEach( ( user, index ) => {
				const id = Number( user?.id );
				if ( ! Number.isFinite( id ) || id < 1 ) {
					return;
				}

				const li = document.createElement( 'li' );
				li.setAttribute( 'role', 'presentation' );

				const btn = document.createElement( 'button' );
				btn.type = 'button';
				btn.setAttribute( 'role', 'option' );
				btn.id = `clanspress-invite-option-${ index }`;
				btn.className =
					'clanspress-team-create-form__invite-option' +
					( index === state.inviteActiveIndex ? ' is-active' : '' );
				btn.setAttribute( 'data-invite-user-id', String( id ) );
				btn.setAttribute(
					'data-invite-suggestion-index',
					String( index )
				);
				btn.setAttribute(
					'aria-selected',
					index === state.inviteActiveIndex ? 'true' : 'false'
				);
				btn.textContent = String( user?.label ?? '' );

				li.appendChild( btn );
				frag.appendChild( li );
			} );
			list.appendChild( frag );

			if ( input ) {
				input.setAttribute( 'aria-expanded', 'true' );
				if ( state.inviteActiveIndex >= 0 ) {
					input.setAttribute(
						'aria-activedescendant',
						`clanspress-invite-option-${ state.inviteActiveIndex }`
					);
					const activeBtn = list.querySelector(
						`#clanspress-invite-option-${ state.inviteActiveIndex }`
					);
					activeBtn?.scrollIntoView( { block: 'nearest' } );
				} else {
					input.removeAttribute( 'aria-activedescendant' );
				}
			}
		},
		renderInviteList() {
			const list = state.root?.querySelector( '[data-team-invite-list]' );
			const hidden = state.root?.querySelector(
				'[data-team-invite-hidden]'
			);
			if ( ! list || ! hidden ) {
				return;
			}

			clearDomNode( list );

			if ( state.invites.length < 1 ) {
				hidden.value = '';
				return;
			}

			const frag = document.createDocumentFragment();
			const ids = [];
			state.invites.forEach( ( user ) => {
				const id = Number( user?.id );
				if ( ! Number.isFinite( id ) || id < 1 ) {
					return;
				}
				ids.push( id );

				const wrap = document.createElement( 'div' );
				wrap.className = 'clanspress-team-create-form__invite-chip';

				const span = document.createElement( 'span' );
				span.textContent = String( user?.label ?? '' );

				const btn = document.createElement( 'button' );
				btn.type = 'button';
				btn.setAttribute( 'aria-label', 'Remove' );
				btn.setAttribute( 'data-remove-invite-id', String( id ) );
				btn.appendChild( document.createTextNode( '\u00d7' ) );

				wrap.appendChild( span );
				wrap.appendChild( btn );
				frag.appendChild( wrap );
			} );
			list.appendChild( frag );
			hidden.value = ids.join( ',' );
		},
	},
	callbacks: {
		init() {
			const { ref } = getElement();
			if ( ! ref ) {
				return;
			}

			const ctx = getContext();
			const fromServer = Number( ctx?.stepCount );
			state.stepCount =
				Number.isFinite( fromServer ) && fromServer > 0
					? fromServer
					: 1;

			state.root = ref;
			syncTeamFormTabs( ref, state.step, { wizard: true } );
			actions.renderInviteSuggestions();
			actions.renderInviteList();
		},
	},
} );
