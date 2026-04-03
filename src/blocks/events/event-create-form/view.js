import { store, getContext, getElement } from '@wordpress/interactivity';

function toMysqlUtc( localDatetime ) {
	if ( ! localDatetime ) {
		return '';
	}
	const d = new Date( localDatetime );
	if ( Number.isNaN( d.getTime() ) ) {
		return '';
	}
	return d.toISOString().replace( 'T', ' ' ).slice( 0, 19 );
}

function syncWizardUi( root, step, stepCount ) {
	if ( ! root ) {
		return;
	}
	const form = root.querySelector( '.clanspress-event-create-form__form' );
	if ( form ) {
		form.dataset.activeStep = String( step );
	}

	const tabs = root.querySelectorAll(
		'.clanspress-event-create-form__tab[data-event-tab]'
	);
	tabs.forEach( ( tab ) => {
		const n = Number( tab.getAttribute( 'data-event-tab' ) );
		const isActive = n === step;
		const isComplete = n < step;
		const isUpcoming = n > step;
		tab.classList.toggle( 'is-active', isActive );
		tab.classList.toggle( 'is-complete', isComplete );
		tab.classList.toggle( 'is-upcoming', isUpcoming );
		tab.setAttribute( 'aria-selected', isActive ? 'true' : 'false' );
		tab.tabIndex = isActive ? 0 : -1;
		tab.disabled = n > step;
	} );

	for ( let n = 1; n <= stepCount; n++ ) {
		const panel = root.querySelector( `[data-event-step="${ n }"]` );
		if ( panel ) {
			panel.hidden = n !== step;
		}
	}
}

/**
 * Build REST URL with _wpnonce query arg. WordPress accepts this when cookie-auth POSTs
 * miss or strip the X-WP-Nonce header (some hosts/proxies); see rest_cookie_check_errors().
 *
 * @param {string} restUrl REST endpoint URL.
 * @param {string} nonce   Value from wp_create_nonce( 'wp_rest' ).
 * @return {string}
 */
function restUrlWithNonceQuery( restUrl, nonce ) {
	const raw = restUrl || '';
	const n = String( nonce );
	try {
		const u = raw.startsWith( 'http' )
			? new URL( raw )
			: new URL( raw.replace( /^\/+/, '' ), window.location.origin );
		u.searchParams.set( '_wpnonce', n );
		return u.toString();
	} catch {
		const sep = raw.includes( '?' ) ? '&' : '?';
		return `${ raw }${ sep }_wpnonce=${ encodeURIComponent( n ) }`;
	}
}

function syncModeUi( root, mode ) {
	if ( ! root ) {
		return;
	}
	const virtualFields = root.querySelectorAll(
		'.clanspress-event-create-form__field--virtual'
	);
	const addressFields = root.querySelectorAll(
		'.clanspress-event-create-form__field--address'
	);

	virtualFields.forEach( ( el ) => {
		el.hidden = mode !== 'virtual';
	} );
	addressFields.forEach( ( el ) => {
		el.hidden = mode === 'virtual';
	} );

	const vurl = root.querySelector(
		'.clanspress-event-create-form__input-vurl'
	);
	if ( vurl ) {
		if ( mode === 'virtual' ) {
			vurl.setAttribute( 'required', 'required' );
		} else {
			vurl.removeAttribute( 'required' );
		}
	}
}

const { state, actions } = store( 'clanspress-event-create-form', {
	callbacks: {
		init() {
			const { ref } = getElement();
			const root = ref?.closest( '.clanspress-event-create-form' );
			if ( ! root ) {
				return;
			}
			const context = getContext();
			context._cpFormRoot = root;
			state.root = root;
			state.stepCount = Number( context.stepCount ) || 4;

			const modeSelect = root.querySelector(
				'.clanspress-event-create-form__input-mode'
			);
			state.mode = modeSelect?.value || 'in_person';
			syncModeUi( root, state.mode );
			syncWizardUi( root, state.step, state.maxStep() );
		},
	},
	state: {
		root: null,
		step: 1,
		stepCount: 4,
		mode: 'in_person',
		isSubmitting: false,
		message: '',
		messageType: 'info',
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
		isVirtual() {
			return state.mode === 'virtual';
		},
		isSubmittingNow() {
			return Boolean( state.isSubmitting );
		},
		hasMessage() {
			return Boolean( state.message );
		},
		successScreen: false,
		successEventUrl: '',
		showSuccessScreen() {
			return Boolean( state.successScreen );
		},
		hasSuccessEventUrl() {
			return Boolean( state.successEventUrl );
		},
	},
	actions: {
		onModeChange( event ) {
			state.mode = event?.target?.value || 'in_person';
			const ctx = getContext();
			const root = ctx._cpFormRoot || state.root;
			syncModeUi( root, state.mode );
		},

		onSubmit( event ) {
			if ( state.step < state.maxStep() ) {
				event.preventDefault();
				event.stopPropagation();
				actions.nextStep( event );
				return;
			}
			event.preventDefault();
			event.stopPropagation();
			void actions.submitEvent();
		},

		nextStep( event ) {
			event?.preventDefault?.();
			if ( state.step >= state.maxStep() ) {
				return;
			}

			const ctx = getContext();
			const root = ctx._cpFormRoot || state.root;
			const stepScope = root?.querySelector(
				`[data-event-step="${ state.step }"]`
			);
			if ( stepScope ) {
				const requiredFields =
					stepScope.querySelectorAll( '[required]' );
				let valid = true;
				requiredFields.forEach( ( field ) => {
					if ( ! field.checkValidity() ) {
						field.reportValidity();
						valid = false;
					}
				} );
				if ( ! valid ) {
					return;
				}
			}

			state.step += 1;
			syncWizardUi( root, state.step, state.maxStep() );
		},

		previousStep( event ) {
			event?.preventDefault?.();
			if ( state.step <= 1 ) {
				return;
			}
			state.step -= 1;
			const ctx = getContext();
			const root = ctx._cpFormRoot || state.root;
			syncWizardUi( root, state.step, state.maxStep() );
		},

		goToStepTab( event ) {
			event.preventDefault();
			const btn = event?.currentTarget;
			const n = Number( btn?.getAttribute( 'data-event-tab' ) );
			if ( ! n || n >= state.step ) {
				return;
			}
			state.step = n;
			const ctx = getContext();
			const root = ctx._cpFormRoot || state.root;
			syncWizardUi( root, state.step, state.maxStep() );
		},

		resetAfterSuccess() {
			const ctx = getContext();
			const root = ctx._cpFormRoot || state.root;
			if ( ! root ) {
				return;
			}
			state.successScreen = false;
			state.successEventUrl = '';
			state.step = 1;
			const form = root.querySelector(
				'.clanspress-event-create-form__form'
			);
			if ( form && typeof form.reset === 'function' ) {
				form.reset();
			}
			const modeSelect = root.querySelector(
				'.clanspress-event-create-form__input-mode'
			);
			state.mode = modeSelect?.value || 'in_person';
			syncModeUi( root, state.mode );
			syncWizardUi( root, state.step, state.maxStep() );
		},

		async submitEvent() {
			if ( state.isSubmitting ) {
				return;
			}
			const context = getContext();
			const root = context._cpFormRoot || state.root;
			if ( ! root || ! context?.restUrl || ! context?.nonce ) {
				return;
			}

			const eventId = Number( context.eventId ) || 0;
			const isEdit = eventId > 0;

			const title =
				root
					.querySelector(
						'.clanspress-event-create-form__input-title'
					)
					?.value?.trim() || '';
			const content =
				root.querySelector(
					'.clanspress-event-create-form__input-content'
				)?.value || '';
			const mode =
				root.querySelector(
					'.clanspress-event-create-form__input-mode'
				)?.value || 'in_person';

			if ( ! title ) {
				state.message =
					context?.i18n?.titleRequired || 'Title required';
				state.messageType = 'error';
				return;
			}

			const payloadBody = {
				title,
				content,
				status: 'publish',
				mode,
				virtual_url:
					root.querySelector(
						'.clanspress-event-create-form__input-vurl'
					)?.value || '',
				address_line1:
					root.querySelector(
						'.clanspress-event-create-form__input-line1'
					)?.value || '',
				address_line2:
					root.querySelector(
						'.clanspress-event-create-form__input-line2'
					)?.value || '',
				locality:
					root.querySelector(
						'.clanspress-event-create-form__input-locality'
					)?.value || '',
				region:
					root.querySelector(
						'.clanspress-event-create-form__input-region'
					)?.value || '',
				postcode:
					root.querySelector(
						'.clanspress-event-create-form__input-postcode'
					)?.value || '',
				country:
					root.querySelector(
						'.clanspress-event-create-form__input-country'
					)?.value || '',
				starts_at: toMysqlUtc(
					root.querySelector(
						'.clanspress-event-create-form__input-starts'
					)?.value
				),
				ends_at: toMysqlUtc(
					root.querySelector(
						'.clanspress-event-create-form__input-ends'
					)?.value
				),
				visibility:
					root.querySelector(
						'.clanspress-event-create-form__input-vis'
					)?.value || 'public',
				attendees_visibility:
					root.querySelector(
						'.clanspress-event-create-form__input-attvis'
					)?.value || 'hidden',
			};

			const memberOutreach =
				root.querySelector(
					'.clanspress-event-create-form__input-member-outreach'
				)?.value || 'none';

			let payload;
			if ( isEdit ) {
				payload = { ...payloadBody };
				if ( memberOutreach !== 'none' ) {
					payload.member_outreach = memberOutreach;
				}
			} else {
				payload = {
					...payloadBody,
					scope: context.scope,
					team_id: context.teamId,
					group_id: context.groupId,
					member_outreach: memberOutreach,
				};
			}

			state.isSubmitting = true;
			state.message = '';
			state.messageType = 'info';

			try {
				const response = await fetch(
					restUrlWithNonceQuery( context.restUrl, context.nonce ),
					{
						method: isEdit ? 'PUT' : 'POST',
						credentials: 'same-origin',
						headers: {
							'Content-Type': 'application/json',
							'X-WP-Nonce': context.nonce,
						},
						body: JSON.stringify( payload ),
					}
				);
				const body = await response.json().catch( () => ( {} ) );

				if ( response.ok && body?.id ) {
					if ( isEdit ) {
						const permalink =
							typeof body.permalink === 'string'
								? body.permalink.trim()
								: '';
						if ( permalink ) {
							window.location.assign( permalink );
						} else {
							window.location.reload();
						}
						if ( window.wp?.a11y?.speak ) {
							window.wp.a11y.speak(
								context?.i18n?.success || 'Event updated.',
								'polite'
							);
						}
						return;
					}
					const permalink =
						typeof body.permalink === 'string'
							? body.permalink.trim()
							: '';
					if ( permalink ) {
						window.location.assign( permalink );
						return;
					}
					state.message = '';
					state.messageType = 'info';
					state.successScreen = true;
					state.successEventUrl = '';
					const successEl = root.querySelector(
						'.clanspress-event-create-form__success'
					);
					if ( successEl && typeof successEl.focus === 'function' ) {
						successEl.focus();
					}
					if ( window.wp?.a11y?.speak ) {
						window.wp.a11y.speak(
							context?.i18n?.success || 'Event created.',
							'polite'
						);
					}
					return;
				}

				state.message =
					body?.message ||
					context?.i18n?.error ||
					( isEdit
						? 'Could not update event.'
						: 'Could not create event.' );
				state.messageType = 'error';
			} catch {
				state.message =
					context?.i18n?.error ||
					( isEdit
						? 'Could not update event.'
						: 'Could not create event.' );
				state.messageType = 'error';
			} finally {
				state.isSubmitting = false;
			}
		},
	},
} );
