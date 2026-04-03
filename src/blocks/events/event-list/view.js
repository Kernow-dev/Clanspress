/**
 * Event list: REST-backed time filter and pagination (no full page reload).
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

function esc( s ) {
	const d = document.createElement( 'div' );
	d.textContent = s;
	return d.innerHTML;
}

function formatStart( startsAt ) {
	if ( ! startsAt ) {
		return '';
	}
	const d = new Date( String( startsAt ).replace( ' ', 'T' ) + 'Z' );
	if ( Number.isNaN( d.getTime() ) ) {
		return '';
	}
	return d.toLocaleString( undefined, {
		dateStyle: 'medium',
		timeStyle: 'short',
	} );
}

function renderItems( root, items, i18n ) {
	const ul = root.querySelector( '.clanspress-event-list' );
	if ( ! ul ) {
		return;
	}
	ul.innerHTML = '';
	const untitled = i18n.untitled || '';
	if ( ! items || ! items.length ) {
		const li = document.createElement( 'li' );
		li.className = 'clanspress-event-list__empty';
		li.textContent = i18n.noEvents || '';
		ul.appendChild( li );
		return;
	}
	items.forEach( ( row ) => {
		const li = document.createElement( 'li' );
		li.className = 'clanspress-event-list__item';
		const title = row.title || untitled;
		const label = formatStart( row.startsAt );
		const url = row.permalink || '';
		if ( url ) {
			li.innerHTML = `<a class="clanspress-event-list__title" href="${ esc(
				url
			) }">${ esc( title ) }</a>`;
		} else {
			li.innerHTML = `<span class="clanspress-event-list__title">${ esc(
				title
			) }</span>`;
		}
		if ( label ) {
			const p = document.createElement( 'p' );
			p.className = 'clanspress-event-list__meta';
			p.textContent = label;
			li.appendChild( p );
		}
		ul.appendChild( li );
	} );
}

async function apiList( ctx, query ) {
	const base = ctx.restUrl || '';
	const url = base.startsWith( 'http' )
		? new URL( base )
		: new URL( base, window.location.origin );
	Object.entries( query ).forEach( ( [ k, v ] ) => {
		if ( v !== undefined && v !== null && v !== '' ) {
			url.searchParams.set( k, String( v ) );
		}
	} );
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

const { state, actions } = store( 'clanspress-event-list', {
	state: {
		root: null,
		loading: false,
		errorMessage: '',
		page: 1,
		total: 0,
		perPage: 20,
		timeScope: 'all',
		pageLabel: '',
		isLoading() {
			return Boolean( state.loading );
		},
		totalPages() {
			const pp = Number( state.perPage ) || 20;
			const t = Number( state.total ) || 0;
			return Math.max( 1, Math.ceil( t / pp ) );
		},
		showPagination() {
			if ( state.loading ) {
				return false;
			}
			return state.totalPages() > 1;
		},
		isFirstPage() {
			return state.page <= 1;
		},
		isLastPage() {
			return state.page >= state.totalPages();
		},
	},
	callbacks: {
		init() {
			const { ref } = getElement();
			const root = ref?.closest( '.clanspress-event-list-wrap' );
			if ( ! root ) {
				return;
			}
			state.root = root;
			const ctx = getContext();
			state.perPage = Math.min(
				50,
				Math.max( 1, Number( ctx.perPage ) || 20 )
			);
			state.timeScope = 'all';
			state.page = 1;
			const timeSel = root.querySelector(
				'.clanspress-event-list__time'
			);
			if ( timeSel ) {
				timeSel.value = 'all';
			}
			actions.fetchList();
		},
	},
	actions: {
		onTimeScopeChange( event ) {
			const el = event?.target;
			const v = el && 'value' in el ? String( el.value ) : 'all';
			state.timeScope = [ 'all', 'upcoming', 'past' ].includes( v )
				? v
				: 'all';
			state.page = 1;
			actions.fetchList();
		},
		prevPage() {
			if ( state.page <= 1 ) {
				return;
			}
			state.page -= 1;
			actions.fetchList();
		},
		nextPage() {
			if ( state.page >= state.totalPages() ) {
				return;
			}
			state.page += 1;
			actions.fetchList();
		},
		async fetchList() {
			const root = state.root;
			if ( ! root ) {
				return;
			}
			const ctx = getContext();
			const i18n = ctx.i18n || {};
			state.loading = true;
			state.errorMessage = '';
			const order = state.timeScope === 'past' ? 'desc' : 'asc';
			const teamId = ctx.scope === 'team' ? Number( ctx.teamId ) || 0 : 0;
			const groupId =
				ctx.scope === 'group' ? Number( ctx.groupId ) || 0 : 0;
			const query = {
				page: state.page,
				per_page: state.perPage,
				time_scope: state.timeScope,
				order,
			};
			if ( teamId > 0 ) {
				query.team_id = teamId;
			}
			if ( groupId > 0 ) {
				query.group_id = groupId;
			}
			try {
				const data = await apiList( ctx, query );
				state.total = Number( data.total ) || 0;
				const items = Array.isArray( data.items ) ? data.items : [];
				renderItems( root, items, i18n );
				const tp = state.totalPages();
				const pfx = i18n.pageLabel || '';
				state.pageLabel = `${ pfx } ${ state.page } / ${ tp }`;
			} catch ( e ) {
				state.errorMessage = e?.message || i18n.error || '';
			} finally {
				state.loading = false;
			}
		},
	},
} );
