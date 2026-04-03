/**
 * Event RSVP: REST-backed status, attendee list, and collapsible responses (Interactivity API).
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

function esc( s ) {
	const d = document.createElement( 'div' );
	d.textContent = s;
	return d.innerHTML;
}

function apiFetchJson( url, opts ) {
	return fetch( url, opts ).then( ( r ) => {
		if ( ! r.ok ) {
			return r.json().then(
				( err ) => {
					throw new Error( err?.message || r.statusText );
				},
				() => {
					throw new Error( r.statusText );
				}
			);
		}
		return r.json();
	} );
}

function renderAttendees( root, data, i18n ) {
	const listEl = root.querySelector( '.clanspress-event-rsvp__list' );
	if ( ! listEl ) {
		return;
	}
	listEl.innerHTML = '';
	const rows = data.attendees || [];
	if ( ! rows.length ) {
		listEl.innerHTML = `<li class="clanspress-event-rsvp__empty">${ esc(
			i18n.noAttendees || ''
		) }</li>`;
		return;
	}
	rows.forEach( ( row ) => {
		const li = document.createElement( 'li' );
		li.className = 'clanspress-event-rsvp__attendee';
		const name = row.name || `#${ row.user_id }`;
		const st = row.status || '';
		li.innerHTML = `<span class="clanspress-event-rsvp__attendee-name">${ esc(
			name
		) }</span> <span class="clanspress-event-rsvp__attendee-status">${ esc(
			st
		) }</span>`;
		listEl.appendChild( li );
	} );
}

function restUrls( ctx ) {
	const base = String( ctx.restUrl || '' ).replace( /\/?$/, '/' );
	const eventType = encodeURIComponent( ctx.eventType || '' );
	const eventId = Number( ctx.eventId ) || 0;
	return {
		rsvpUrl: `${ base }events/${ eventType }/${ eventId }/rsvp`,
		attUrl: `${ base }events/${ eventType }/${ eventId }/attendees`,
	};
}

const { state } = store( 'clanspress-event-rsvp', {
	state: {
		root: null,
		panelOpen: true,
		attendeesOpen() {
			return state.panelOpen;
		},
	},
	actions: {
		toggleAttendees() {
			state.panelOpen = ! state.panelOpen;
		},
		async postRsvp() {
			const { ref } = getElement();
			const status = ref?.getAttribute( 'data-cp-rsvp-status' );
			if ( ! status ) {
				return;
			}
			const ctx = getContext();
			const root = state.root;
			if ( ! root || ! ctx.loggedIn ) {
				return;
			}
			const { rsvpUrl, attUrl } = restUrls( ctx );
			const i18n = ctx.i18n || {};
			const statusEl = root.querySelector(
				'.clanspress-event-rsvp__status'
			);
			const buttons = root.querySelectorAll( '[data-cp-rsvp-status]' );

			function setStatusText( msg ) {
				if ( statusEl ) {
					statusEl.textContent = msg;
				}
			}

			try {
				await apiFetchJson( rsvpUrl, {
					method: 'POST',
					credentials: 'same-origin',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': ctx.nonce,
					},
					body: JSON.stringify( { status } ),
				} );
				setStatusText(
					( i18n.currentPrefix || '' ) +
						( i18n.statusLabels?.[ status ] || status )
				);
				buttons.forEach( ( btn ) => {
					const s = btn.getAttribute( 'data-cp-rsvp-status' );
					btn.classList.toggle( 'is-active', s === status );
				} );
				if ( ctx.showAttendees ) {
					const listEl = root.querySelector(
						'.clanspress-event-rsvp__list'
					);
					if ( listEl ) {
						apiFetchJson( `${ attUrl }?limit=100&offset=0`, {
							credentials: 'same-origin',
							headers: { 'X-WP-Nonce': ctx.nonce },
						} )
							.then( ( data ) =>
								renderAttendees( root, data, i18n )
							)
							.catch( () => {} );
					}
				}
			} catch ( err ) {
				setStatusText( err?.message || 'Error' );
			}
		},
	},
	callbacks: {
		init() {
			const ctx = getContext();
			const { ref } = getElement();
			const root = ref?.closest( '.clanspress-event-rsvp' );
			if ( ! root || ! ctx.canView || ! ctx.eventId ) {
				return;
			}
			state.root = root;
			state.panelOpen = true;

			const i18n = ctx.i18n || {};
			const { rsvpUrl, attUrl } = restUrls( ctx );
			const statusEl = root.querySelector(
				'.clanspress-event-rsvp__status'
			);
			const attNote = root.querySelector(
				'.clanspress-event-rsvp__attendees-note'
			);
			const buttons = root.querySelectorAll( '[data-cp-rsvp-status]' );

			function loadAttendees() {
				if ( ! ctx.showAttendees ) {
					return;
				}
				const listEl = root.querySelector(
					'.clanspress-event-rsvp__list'
				);
				if ( ! listEl ) {
					return;
				}
				apiFetchJson( `${ attUrl }?limit=100&offset=0`, {
					credentials: 'same-origin',
					headers: { 'X-WP-Nonce': ctx.nonce },
				} )
					.then( ( data ) => renderAttendees( root, data, i18n ) )
					.catch( () => {
						if ( attNote ) {
							attNote.textContent = i18n.attendeesHidden || '';
							attNote.hidden = false;
						}
					} );
			}

			if ( ctx.loggedIn ) {
				apiFetchJson( rsvpUrl, {
					credentials: 'same-origin',
					headers: { 'X-WP-Nonce': ctx.nonce },
				} )
					.then( ( data ) => {
						const st = data.status || '';
						if ( st && statusEl ) {
							statusEl.textContent =
								( i18n.currentPrefix || '' ) +
								( i18n.statusLabels?.[ st ] || st );
						}
						buttons.forEach( ( btn ) => {
							const s = btn.getAttribute( 'data-cp-rsvp-status' );
							btn.classList.toggle( 'is-active', s === st );
						} );
					} )
					.catch( () => {} );
			}

			loadAttendees();
		},
	},
} );
