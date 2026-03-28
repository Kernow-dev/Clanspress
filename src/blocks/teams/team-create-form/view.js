import { store, getContext, getElement } from '@wordpress/interactivity';

function escapeInviteLabel( text ) {
	return String( text )
		.replace( /&/g, '&amp;' )
		.replace( /</g, '&lt;' )
		.replace( />/g, '&gt;' )
		.replace( /"/g, '&quot;' );
}

const { state, actions } = store( 'clanspress-team-create-form', {
	state: {
		root: null,
		step: 1,
		inviteQuery: '',
		inviteSuggestions: [],
		inviteActiveIndex: -1,
		invites: [],
		inviteSearchTimeout: null,
		maxStep() {
			const total = Number( getContext().stepCount || 1 );
			return total > 0 ? total : 1;
		},
		isCurrentStep() {
			const { attributes } = getElement();
			const stepAttr = Number( attributes[ 'data-team-step' ] || 0 );
			return stepAttr === state.step;
		},
		canGoNext() {
			return state.step < state.maxStep();
		},
		canGoBack() {
			return state.step > 1;
		},
	},
	actions: {
		nextStep( event ) {
			event.preventDefault();

			if ( state.step >= state.maxStep() ) {
				return;
			}

			const { ref } = getElement();
			const root = ref?.closest( '.clanspress-team-create-form' );
			if ( ! root ) {
				state.step += 1;
				return;
			}

			const stepScope = root.querySelector( `[data-team-step="${ state.step }"]` );
			if ( ! stepScope ) {
				state.step += 1;
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
		},
		previousStep( event ) {
			event.preventDefault();

			if ( state.step > 1 ) {
				state.step -= 1;
			}
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
				alert( 'Only PNG or JPEG allowed.' );
				event.target.value = '';
				return;
			}

			const preview = state.root?.querySelector(
				'.clanspress-team-create-form__avatar-preview'
			);
			if ( ! preview ) {
				return;
			}

			preview.style.backgroundImage = `url(${ URL.createObjectURL( file ) })`;
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
				alert( 'Only PNG or JPEG allowed.' );
				event.target.value = '';
				return;
			}

			const preview = state.root?.querySelector(
				'.clanspress-team-create-form__cover-preview'
			);
			if ( ! preview ) {
				return;
			}

			preview.style.backgroundImage = `url(${ URL.createObjectURL( file ) })`;
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

			const input = state.root?.querySelector( '#clanspress-team-invite-search' );
			if ( input ) {
				input.value = '';
			}
			state.inviteQuery = '';
		},
		onSuggestionClick( event ) {
			const button = event?.target?.closest?.( 'button[data-invite-user-id]' );
			if ( ! button ) {
				return;
			}

			event.preventDefault();

			const id = Number( button.getAttribute( 'data-invite-user-id' ) || 0 );
			if ( ! id ) {
				return;
			}

			const user = state.inviteSuggestions.find( ( item ) => item.id === id );
			if ( ! user ) {
				return;
			}

			actions.selectInviteUser( user );
		},
		onInviteListClick( event ) {
			const button = event?.target?.closest?.( 'button[data-remove-invite-id]' );
			if ( ! button ) {
				return;
			}

			event.preventDefault();
			const id = Number( button.getAttribute( 'data-remove-invite-id' ) || 0 );
			if ( ! id ) {
				return;
			}

			state.invites = state.invites.filter( ( user ) => user.id !== id );
			actions.renderInviteList();
		},
		renderInviteSuggestions() {
			const list = state.root?.querySelector( '[data-team-invite-suggestions]' );
			const input = state.root?.querySelector( '#clanspress-team-invite-search' );
			if ( ! list ) {
				return;
			}

			if ( state.inviteSuggestions.length < 1 ) {
				list.innerHTML = '';
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

			list.innerHTML = state.inviteSuggestions
				.map( ( user, index ) => {
					const active =
						index === state.inviteActiveIndex ? ' is-active' : '';
					const label = escapeInviteLabel( user.label );
					return `<li role="presentation"><button type="button" role="option" id="clanspress-invite-option-${ index }" class="clanspress-team-create-form__invite-option${ active }" data-invite-user-id="${ user.id }" data-invite-suggestion-index="${ index }" aria-selected="${ index === state.inviteActiveIndex ? 'true' : 'false' }">${ label }</button></li>`;
				} )
				.join( '' );

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
			const hidden = state.root?.querySelector( '[data-team-invite-hidden]' );
			if ( ! list || ! hidden ) {
				return;
			}

			if ( state.invites.length < 1 ) {
				list.innerHTML = '';
				hidden.value = '';
				return;
			}

			list.innerHTML = state.invites
				.map(
					( user ) =>
						`<div class="clanspress-team-create-form__invite-chip"><span>${ escapeInviteLabel( user.label ) }</span><button type="button" aria-label="Remove" data-remove-invite-id="${ user.id }">×</button></div>`
				)
				.join( '' );
			hidden.value = state.invites.map( ( user ) => user.id ).join( ',' );
		},
	},
	callbacks: {
		init() {
			const { ref } = getElement();
			if ( ! ref ) {
				return;
			}

			state.root = ref;
			actions.renderInviteSuggestions();
			actions.renderInviteList();
		},
	},
} );
