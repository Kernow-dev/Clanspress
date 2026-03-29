/**
 * Shared tab strip + step panel sync for team create (wizard) and manage (free navigation).
 *
 * @param {Element|null|undefined} root    Block root containing `.clanspress-team-create-form__form`.
 * @param {number}                 step    Active step (1-based).
 * @param {{ wizard?: boolean }}   options `wizard`: disable tabs ahead of current step (create flow).
 */
export function syncTeamFormTabs( root, step, options = {} ) {
	const { wizard = false } = options;

	if ( ! root ) {
		return;
	}

	const form = root.querySelector( '.clanspress-team-create-form__form' );
	if ( ! form ) {
		return;
	}

	form.setAttribute( 'data-active-step', String( step ) );

	form.querySelectorAll( '[data-team-tab]' ).forEach( ( btn ) => {
		const n = Number( btn.getAttribute( 'data-team-tab' ) );
		const isActive = n === step;
		btn.setAttribute( 'aria-selected', isActive ? 'true' : 'false' );
		btn.tabIndex = isActive ? 0 : -1;
		btn.disabled = wizard ? n > step : false;
		btn.classList.toggle( 'is-active', isActive );
		btn.classList.toggle( 'is-complete', n < step );
		btn.classList.toggle( 'is-upcoming', n > step );
	} );

	form.querySelectorAll(
		'.clanspress-team-create-form__step[data-team-step]'
	).forEach( ( panel ) => {
		const n = Number( panel.getAttribute( 'data-team-step' ) );
		if ( n === step ) {
			panel.removeAttribute( 'hidden' );
		} else {
			panel.setAttribute( 'hidden', '' );
		}
	} );
}

/**
 * Move keyboard focus to the tab button for the active step (after programmatic step change).
 *
 * @param {Element|null|undefined} root Block root.
 * @param {number}                 step Active step (1-based).
 */
export function focusActiveTabButton( root, step ) {
	if ( ! root || step < 1 ) {
		return;
	}
	const form = root.querySelector( '.clanspress-team-create-form__form' );
	const el = form?.querySelector( `[data-team-tab="${ step }"]` );
	if (
		el &&
		typeof el.focus === 'function' &&
		! ( 'disabled' in el && el.disabled )
	) {
		el.focus();
	}
}
