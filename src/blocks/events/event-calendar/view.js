/**
 * Event calendar: REST range queries + month / week / day layouts.
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

function esc( s ) {
	const d = document.createElement( 'div' );
	d.textContent = s;
	return d.innerHTML;
}

function formatYmdLocal( d ) {
	const y = d.getFullYear();
	const m = String( d.getMonth() + 1 ).padStart( 2, '0' );
	const day = String( d.getDate() ).padStart( 2, '0' );
	return `${ y }-${ m }-${ day }`;
}

function startOfWeek( d ) {
	const x = new Date( d );
	const day = x.getDay();
	x.setDate( x.getDate() - day );
	x.setHours( 0, 0, 0, 0 );
	return x;
}

function endOfWeek( d ) {
	const s = startOfWeek( d );
	const e = new Date( s );
	e.setDate( e.getDate() + 6 );
	e.setHours( 23, 59, 59, 999 );
	return e;
}

function startOfMonth( d ) {
	return new Date( d.getFullYear(), d.getMonth(), 1, 0, 0, 0, 0 );
}

function endOfMonth( d ) {
	return new Date( d.getFullYear(), d.getMonth() + 1, 0, 23, 59, 59, 999 );
}

function rangeForView( view, anchorDate ) {
	const a = new Date( anchorDate );
	if ( view === 'day' ) {
		const s = new Date(
			a.getFullYear(),
			a.getMonth(),
			a.getDate(),
			0,
			0,
			0,
			0
		);
		const e = new Date(
			a.getFullYear(),
			a.getMonth(),
			a.getDate(),
			23,
			59,
			59,
			999
		);
		return { start: s, end: e };
	}
	if ( view === 'week' ) {
		return { start: startOfWeek( a ), end: endOfWeek( a ) };
	}
	/* month + list: anchored month (must match PHP `clanspress_events_calendar_range_iso_for_view`). */
	return { start: startOfMonth( a ), end: endOfMonth( a ) };
}

function shiftAnchor( view, anchorDate, delta ) {
	const d = new Date( anchorDate );
	if ( view === 'day' ) {
		d.setDate( d.getDate() + delta );
		return d;
	}
	if ( view === 'week' ) {
		d.setDate( d.getDate() + 7 * delta );
		return d;
	}
	if ( view === 'list' ) {
		d.setMonth( d.getMonth() + delta );
		return d;
	}
	d.setMonth( d.getMonth() + delta );
	return d;
}

function headingFor( view, anchorDate ) {
	const a = new Date( anchorDate );
	if ( view === 'month' ) {
		return a.toLocaleDateString( undefined, {
			month: 'long',
			year: 'numeric',
		} );
	}
	if ( view === 'week' ) {
		const s = startOfWeek( a );
		const e = endOfWeek( a );
		return `${ s.toLocaleDateString( undefined, {
			month: 'short',
			day: 'numeric',
		} ) } – ${ e.toLocaleDateString( undefined, {
			month: 'short',
			day: 'numeric',
			year: 'numeric',
		} ) }`;
	}
	if ( view === 'list' ) {
		return a.toLocaleDateString( undefined, {
			month: 'long',
			year: 'numeric',
		} );
	}
	return a.toLocaleDateString( undefined, {
		weekday: 'long',
		month: 'long',
		day: 'numeric',
		year: 'numeric',
	} );
}

function formatTime( startsAt ) {
	if ( ! startsAt ) {
		return '';
	}
	const d = new Date( String( startsAt ).replace( ' ', 'T' ) + 'Z' );
	if ( Number.isNaN( d.getTime() ) ) {
		return '';
	}
	return d.toLocaleTimeString( undefined, {
		hour: 'numeric',
		minute: '2-digit',
	} );
}

function parseEventUtc( raw ) {
	if ( ! raw ) {
		return null;
	}
	const d = new Date( String( raw ).replace( ' ', 'T' ) + 'Z' );
	return Number.isNaN( d.getTime() ) ? null : d;
}

function eventYmdRange( ev ) {
	const s = parseEventUtc( ev.startsAt );
	if ( ! s ) {
		return null;
	}
	const startYmd = formatYmdLocal( s );
	const endD = parseEventUtc( ev.endsAt );
	let endYmd = endD ? formatYmdLocal( endD ) : startYmd;
	if ( endYmd < startYmd ) {
		endYmd = startYmd;
	}
	return { startYmd, endYmd, ev };
}

function segmentKind( range, ymd ) {
	if ( range.startYmd === range.endYmd ) {
		return 'single';
	}
	if ( ymd === range.startYmd ) {
		return 'start';
	}
	if ( ymd === range.endYmd ) {
		return 'end';
	}
	return 'mid';
}

function eventsTouchingYmd( items, ymd ) {
	const out = [];
	( items || [] ).forEach( ( row ) => {
		const r = eventYmdRange( row );
		if ( ! r || ymd < r.startYmd || ymd > r.endYmd ) {
			return;
		}
		out.push( r );
	} );
	out.sort( ( a, b ) => {
		const ta = parseEventUtc( a.ev.startsAt )?.getTime() ?? 0;
		const tb = parseEventUtc( b.ev.startsAt )?.getTime() ?? 0;
		return ta - tb;
	} );
	return out;
}

function renderSkeleton( surface, view ) {
	if ( ! surface ) {
		return;
	}
	const v = view || 'month';
	if ( v === 'list' ) {
		let rows = '';
		for ( let i = 0; i < 8; i++ ) {
			rows +=
				'<div class="clanspress-event-calendar__skel-list-row"><span class="clanspress-event-calendar__skel-pill"></span><span class="clanspress-event-calendar__skel-line"></span></div>';
		}
		surface.innerHTML = `<div class="clanspress-event-calendar__skeleton clanspress-event-calendar__skeleton--list">${ rows }</div>`;
		return;
	}
	if ( v === 'day' ) {
		let rows = '';
		for ( let i = 0; i < 5; i++ ) {
			rows += '<div class="clanspress-event-calendar__skel-line"></div>';
		}
		surface.innerHTML = `<div class="clanspress-event-calendar__skeleton clanspress-event-calendar__skeleton--day">${ rows }</div>`;
		return;
	}
	if ( v === 'week' ) {
		let cols = '';
		for ( let i = 0; i < 7; i++ ) {
			cols +=
				'<div class="clanspress-event-calendar__skeleton-week-col"><div class="clanspress-event-calendar__skel-line"></div><div class="clanspress-event-calendar__skel-line"></div><div class="clanspress-event-calendar__skel-line clanspress-event-calendar__skel-line--short"></div></div>';
		}
		surface.innerHTML = `<div class="clanspress-event-calendar__skeleton clanspress-event-calendar__skeleton--week">${ cols }</div>`;
		return;
	}
	let dow = '';
	for ( let i = 0; i < 7; i++ ) {
		dow += '<div class="clanspress-event-calendar__skel-dow"></div>';
	}
	let grid = '';
	for ( let c = 0; c < 42; c++ ) {
		grid +=
			'<div class="clanspress-event-calendar__skel-cell"><span class="clanspress-event-calendar__skel-num"></span><span class="clanspress-event-calendar__skel-line"></span><span class="clanspress-event-calendar__skel-line clanspress-event-calendar__skel-line--short"></span></div>';
	}
	surface.innerHTML = `<div class="clanspress-event-calendar__skeleton clanspress-event-calendar__skeleton--month"><div class="clanspress-event-calendar__skel-dow-row">${ dow }</div><div class="clanspress-event-calendar__skel-grid">${ grid }</div></div>`;
}

function renderMonthCellEvents( ranges, cellYmd, i18n, maxItems ) {
	let html = '';
	let n = 0;
	for ( const r of ranges ) {
		if ( n >= maxItems ) {
			break;
		}
		const kind = segmentKind( r, cellYmd );
		const ev = r.ev;
		const t = ev.title || i18n.untitled || '';
		const u = ev.permalink || '';
		const timeLbl =
			kind === 'start' || kind === 'single'
				? formatTime( ev.startsAt )
				: '';
		if ( kind === 'mid' || kind === 'end' ) {
			html += `<li class="clanspress-event-calendar__ev-seg clanspress-event-calendar__ev-seg--span" title="${ esc(
				t
			) }"><span class="clanspress-event-calendar__ev-bar" aria-hidden="true"></span><span class="clanspress-event-calendar__sr-only">${ esc(
				t
			) }</span></li>`;
		} else if ( u ) {
			html += `<li class="clanspress-event-calendar__ev-seg"><a href="${ esc(
				u
			) }"><span class="clanspress-event-calendar__ev-time">${ esc(
				timeLbl
			) }</span> ${ esc( t ) }</a></li>`;
		} else {
			html += `<li class="clanspress-event-calendar__ev-seg"><span class="clanspress-event-calendar__ev-time">${ esc(
				timeLbl
			) }</span> ${ esc( t ) }</li>`;
		}
		n++;
	}
	if ( ranges.length > maxItems ) {
		html += `<li class="clanspress-event-calendar__more">+${
			ranges.length - maxItems
		}</li>`;
	}
	return html;
}

async function fetchEvents( ctx, start, end ) {
	const raw = ctx.restUrl || '';
	const url = raw.startsWith( 'http' )
		? new URL( raw )
		: new URL( raw.replace( /^\/+/, '' ), window.location.origin );
	url.searchParams.set( 'per_page', String( ctx.rangePerPage || 200 ) );
	url.searchParams.set( 'order', 'asc' );
	url.searchParams.set( 'time_scope', 'all' );
	url.searchParams.set( 'starts_after', start.toISOString() );
	url.searchParams.set( 'starts_before', end.toISOString() );
	if ( ctx.scope === 'team' ) {
		url.searchParams.set( 'team_id', String( ctx.teamId || 0 ) );
	} else if ( ctx.scope === 'group' ) {
		url.searchParams.set( 'group_id', String( ctx.groupId || 0 ) );
	} else if ( ctx.scope === 'player' ) {
		url.searchParams.set( 'player_user_id', String( ctx.playerId || 0 ) );
	}
	const res = await fetch( url.toString(), {
		credentials: 'same-origin',
		headers: { 'X-WP-Nonce': ctx.nonce },
	} );
	if ( ! res.ok ) {
		const err = await res.json().catch( () => ( {} ) );
		throw new Error( err?.message || res.statusText );
	}
	return res.json();
}

function syncViewButtons( root, view ) {
	root.querySelectorAll( '[data-cal-view]' ).forEach( ( btn ) => {
		const v = btn.getAttribute( 'data-cal-view' );
		btn.classList.toggle( 'is-active', v === view );
	} );
}

function renderSurface( root, ctx, items, i18n ) {
	const surface = root.querySelector( '.clanspress-event-calendar__surface' );
	const heading = root.querySelector( '.clanspress-event-calendar__heading' );
	if ( heading ) {
		heading.textContent = headingFor( ctx.view, ctx.anchor );
	}
	if ( ! surface ) {
		return;
	}

	const anchor = new Date( ctx.anchor + 'T12:00:00' );

	if ( ctx.view === 'month' ) {
		const a = new Date( anchor );
		const first = new Date( a.getFullYear(), a.getMonth(), 1 );
		const startGrid = new Date( first );
		startGrid.setDate( startGrid.getDate() - startGrid.getDay() );
		const weekdays = i18n.weekdays || [];

		let html = '<div class="clanspress-event-calendar__month">';
		html += '<div class="clanspress-event-calendar__dow">';
		for ( let i = 0; i < 7; i++ ) {
			html += `<div class="clanspress-event-calendar__dow-cell">${ esc(
				weekdays[ i ] || ''
			) }</div>`;
		}
		html += '</div><div class="clanspress-event-calendar__grid">';
		const cursor = new Date( startGrid );
		for ( let w = 0; w < 6; w++ ) {
			for ( let col = 0; col < 7; col++ ) {
				const ymd = formatYmdLocal( cursor );
				const inMonth = cursor.getMonth() === a.getMonth();
				const ranges = eventsTouchingYmd( items, ymd );
				const isToday = ymd === formatYmdLocal( new Date() );
				let cellCls = 'clanspress-event-calendar__cell';
				if ( ! inMonth ) {
					cellCls += ' is-muted';
				}
				if ( isToday ) {
					cellCls += ' is-today';
				}
				html += `<div class="${ cellCls }"><div class="clanspress-event-calendar__cell-num">${ cursor.getDate() }</div><ul class="clanspress-event-calendar__cell-events">`;
				html += renderMonthCellEvents( ranges, ymd, i18n, 3 );
				html += '</ul></div>';
				cursor.setDate( cursor.getDate() + 1 );
			}
		}
		html += '</div></div>';
		surface.innerHTML = html;
		return;
	}

	if ( ctx.view === 'week' ) {
		const s = startOfWeek( anchor );
		let html = '<div class="clanspress-event-calendar__week">';
		for ( let i = 0; i < 7; i++ ) {
			const day = new Date( s );
			day.setDate( s.getDate() + i );
			const ymd = formatYmdLocal( day );
			const ranges = eventsTouchingYmd( items, ymd );
			html += '<div class="clanspress-event-calendar__week-day">';
			html += `<div class="clanspress-event-calendar__week-day-head">${ esc(
				day.toLocaleDateString( undefined, {
					weekday: 'short',
					month: 'short',
					day: 'numeric',
				} )
			) }</div><ul>`;
			if ( ranges.length === 0 ) {
				html += `<li class="clanspress-event-calendar__empty">${ esc(
					i18n.noEvents || ''
				) }</li>`;
			} else {
				ranges.forEach( ( r ) => {
					const ev = r.ev;
					const kind = segmentKind( r, ymd );
					const t = ev.title || i18n.untitled || '';
					const u = ev.permalink || '';
					const timeLbl =
						kind === 'start' || kind === 'single'
							? formatTime( ev.startsAt )
							: '···';
					if ( u ) {
						html += `<li><a href="${ esc( u ) }">${ esc(
							timeLbl
						) } — ${ esc( t ) }</a></li>`;
					} else {
						html += `<li>${ esc( timeLbl ) } — ${ esc( t ) }</li>`;
					}
				} );
			}
			html += '</ul></div>';
		}
		html += '</div>';
		surface.innerHTML = html;
		return;
	}

	if ( ctx.view === 'list' ) {
		const sorted = ( items || [] )
			.filter( ( row ) => row.startsAt )
			.sort( ( a, b ) => {
				const ta = parseEventUtc( a.startsAt )?.getTime() ?? 0;
				const tb = parseEventUtc( b.startsAt )?.getTime() ?? 0;
				return ta - tb;
			} );
		let html =
			'<div class="clanspress-event-calendar__list-view"><ul class="clanspress-event-calendar__list">';
		if ( sorted.length === 0 ) {
			html += `<li class="clanspress-event-calendar__empty">${ esc(
				i18n.noEvents || ''
			) }</li>`;
		} else {
			sorted.forEach( ( ev ) => {
				const t = ev.title || i18n.untitled || '';
				const u = ev.permalink || '';
				const s = parseEventUtc( ev.startsAt );
				const e = parseEventUtc( ev.endsAt );
				let when = s
					? s.toLocaleString( undefined, {
							dateStyle: 'medium',
							timeStyle: 'short',
					  } )
					: '';
				if (
					e &&
					ev.endsAt &&
					s &&
					formatYmdLocal( s ) !== formatYmdLocal( e )
				) {
					when +=
						' – ' +
						e.toLocaleString( undefined, {
							dateStyle: 'medium',
							timeStyle: 'short',
						} );
				}
				html += '<li class="clanspress-event-calendar__list-item">';
				html += `<span class="clanspress-event-calendar__list-when">${ esc(
					when
				) }</span>`;
				if ( u ) {
					html += `<a class="clanspress-event-calendar__list-title" href="${ esc(
						u
					) }">${ esc( t ) }</a>`;
				} else {
					html += `<span class="clanspress-event-calendar__list-title">${ esc(
						t
					) }</span>`;
				}
				html += '</li>';
			} );
		}
		html += '</ul></div>';
		surface.innerHTML = html;
		return;
	}

	/* day */
	const ymd = formatYmdLocal( anchor );
	const ranges = eventsTouchingYmd( items, ymd );
	let html = '<div class="clanspress-event-calendar__day"><ul>';
	if ( ranges.length === 0 ) {
		html += `<li class="clanspress-event-calendar__empty">${ esc(
			i18n.noEvents || ''
		) }</li>`;
	} else {
		ranges.forEach( ( r ) => {
			const ev = r.ev;
			const kind = segmentKind( r, ymd );
			const t = ev.title || i18n.untitled || '';
			const u = ev.permalink || '';
			const timeLbl =
				kind === 'start' || kind === 'single'
					? formatTime( ev.startsAt )
					: '···';
			if ( u ) {
				html += `<li><a href="${ esc( u ) }">${ esc(
					timeLbl
				) } — ${ esc( t ) }</a></li>`;
			} else {
				html += `<li>${ esc( timeLbl ) } — ${ esc( t ) }</li>`;
			}
		} );
	}
	html += '</ul></div>';
	surface.innerHTML = html;
}

const { actions } = store( 'clanspress-event-calendar', {
	callbacks: {
		init() {
			const { ref } = getElement();
			const root = ref?.closest( '.clanspress-event-calendar-wrap' );
			if ( ! root ) {
				return;
			}
			const ctx = getContext();
			ctx._cpCalRoot = root;
			if ( ! ctx.view ) {
				ctx.view = 'month';
			}
			syncViewButtons( root, ctx.view );
			if ( ctx.calSsrHydrated ) {
				ctx.calLoading = false;
				ctx.fetchError = '';
				return;
			}
			void actions.load();
		},
	},
	actions: {
		setView( event ) {
			const btn = event?.target?.closest?.( '[data-cal-view]' );
			const v = btn?.getAttribute( 'data-cal-view' );
			if ( ! v || ! [ 'month', 'week', 'day', 'list' ].includes( v ) ) {
				return;
			}
			const ctx = getContext();
			ctx.view = v;
			const root = ctx._cpCalRoot;
			if ( root ) {
				syncViewButtons( root, v );
			}
			void actions.load();
		},

		prevPeriod() {
			const ctx = getContext();
			const next = shiftAnchor( ctx.view, ctx.anchor + 'T12:00:00', -1 );
			ctx.anchor = formatYmdLocal( next );
			void actions.load();
		},

		nextPeriod() {
			const ctx = getContext();
			const next = shiftAnchor( ctx.view, ctx.anchor + 'T12:00:00', 1 );
			ctx.anchor = formatYmdLocal( next );
			void actions.load();
		},

		goToday() {
			const ctx = getContext();
			const t = new Date();
			ctx.anchor = formatYmdLocal( t );
			void actions.load();
		},

		async load() {
			const ctx = getContext();
			const root = ctx._cpCalRoot;
			if ( ! root ) {
				return;
			}
			const i18n = ctx.i18n || {};

			ctx.calLoading = true;
			ctx.fetchError = '';

			const anchorDate = new Date( ctx.anchor + 'T12:00:00' );
			const range = rangeForView( ctx.view, anchorDate );

			const calHeading = root.querySelector(
				'.clanspress-event-calendar__heading'
			);
			if ( calHeading ) {
				calHeading.textContent = headingFor( ctx.view, ctx.anchor );
			}

			const surface = root.querySelector(
				'.clanspress-event-calendar__surface'
			);
			if ( surface ) {
				renderSkeleton( surface, ctx.view );
			}

			try {
				const data = await fetchEvents( ctx, range.start, range.end );
				const items = Array.isArray( data.items ) ? data.items : [];
				renderSurface( root, ctx, items, i18n );
			} catch ( e ) {
				ctx.fetchError =
					e?.message || i18n.error || 'Could not load events.';
				const surface = root.querySelector(
					'.clanspress-event-calendar__surface'
				);
				if ( surface ) {
					surface.innerHTML = '';
				}
			} finally {
				ctx.calLoading = false;
			}
		},
	},
} );
