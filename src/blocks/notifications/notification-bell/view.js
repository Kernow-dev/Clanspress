/**
 * Notification Bell block - Interactivity API view script.
 *
 * Polls `/notifications/poll` on a server-driven interval (`next_poll`). The server defaults to
 * non-blocking polls (one DB read per request); enable long-polling via
 * `clanspress_notification_poll_blocking_wait` if desired. Third-party plugins can provide
 * WebSocket transport via the 'sync.providers' filter (same pattern as WP 7.0 RTC).
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

/**
 * Simple fetch wrapper for REST API calls.
 * Uses native fetch since @wordpress/api-fetch isn't available in module scripts.
 *
 * @param {string} restUrl        Base REST URL from context.
 * @param {string} nonce          WP REST nonce from context.
 * @param {Object} options        Fetch options.
 * @param {string} options.path   REST API path (relative to namespace, e.g., 'notifications').
 * @param {string} options.method HTTP method (default GET).
 * @param {Object} options.data   Request body data.
 * @return {Promise<Object>} Response JSON.
 */
async function restFetch( restUrl, nonce, { path, method = 'GET', data } ) {
	// Remove leading slash from path to avoid double slashes.
	const cleanPath = path.replace( /^\/+/, '' );
	const url = `${ restUrl }${ cleanPath }`;

	const options = {
		method,
		credentials: 'same-origin',
		headers: {
			'Content-Type': 'application/json',
		},
	};

	if ( nonce ) {
		options.headers[ 'X-WP-Nonce' ] = nonce;
	}

	if ( data && method !== 'GET' && method !== 'HEAD' ) {
		options.body = JSON.stringify( data );
	}

	const response = await fetch( url, options );

	if ( ! response.ok ) {
		const error = new Error( `REST request failed: ${ response.status }` );
		error.response = response;
		throw error;
	}

	return response.json();
}

/**
 * Allow only http(s) URLs for href/src built from REST JSON.
 *
 * @param {string} href Raw URL.
 * @return {string} Safe absolute URL or empty string.
 */
function safeHttpUrl( href ) {
	if ( ! href || typeof href !== 'string' ) {
		return '';
	}
	try {
		const u = new URL( href, window.location.origin );
		if ( u.protocol !== 'http:' && u.protocol !== 'https:' ) {
			return '';
		}
		return u.href;
	} catch {
		return '';
	}
}

/**
 * Sanitize CSS class suffix from API (e.g. status slug).
 *
 * @param {string} raw Raw slug.
 * @return {string}
 */
function safeClassSuffix( raw ) {
	return String( raw || '' ).replace( /[^a-z0-9-]/gi, '' );
}

/**
 * Build one notification row using DOM APIs (avoid innerHTML with API-sourced strings).
 *
 * @param {Object} notification Notification data.
 * @param {Object} i18n         Internationalization strings.
 * @return {HTMLElement|null}
 */
function createNotificationItemElement( notification, i18n ) {
	const id = parseInt( notification?.id, 10 );
	if ( ! Number.isFinite( id ) || id < 1 ) {
		return null;
	}

	const root = document.createElement( 'div' );
	let classes = 'clanspress-notification is-compact';
	if ( ! notification.is_read ) {
		classes += ' is-unread';
	}
	if ( notification.is_actionable ) {
		classes += ' is-actionable';
	}
	const statusSlug = safeClassSuffix( notification.status );
	if ( statusSlug && statusSlug !== 'pending' ) {
		classes += ` is-${ statusSlug }`;
	}
	root.className = classes;
	root.dataset.notificationId = String( id );

	const avatarWrap = document.createElement( 'div' );
	const avatarSrc =
		notification.actor && notification.actor.avatar_url
			? safeHttpUrl( notification.actor.avatar_url )
			: '';
	if ( notification.actor && avatarSrc ) {
		avatarWrap.className = 'clanspress-notification__avatar';
		const img = document.createElement( 'img' );
		img.src = avatarSrc;
		img.alt = notification.actor.name
			? String( notification.actor.name )
			: '';
		img.loading = 'lazy';
		img.decoding = 'async';
		avatarWrap.appendChild( img );
	} else {
		avatarWrap.className = 'clanspress-notification__icon';
		const span = document.createElement( 'span' );
		span.className = 'dashicons dashicons-bell';
		avatarWrap.appendChild( span );
	}

	const content = document.createElement( 'div' );
	content.className = 'clanspress-notification__content';

	const header = document.createElement( 'div' );
	header.className = 'clanspress-notification__header';

	const linkUrl =
		notification.url && ! notification.is_actionable
			? safeHttpUrl( notification.url )
			: '';
	if ( linkUrl ) {
		const a = document.createElement( 'a' );
		a.href = linkUrl;
		a.className = 'clanspress-notification__link';
		a.dataset.notificationId = String( id );
		const titleSpan = document.createElement( 'span' );
		titleSpan.className = 'clanspress-notification__title';
		titleSpan.textContent = notification.title
			? String( notification.title )
			: '';
		a.appendChild( titleSpan );
		header.appendChild( a );
	} else {
		const titleSpan = document.createElement( 'span' );
		titleSpan.className = 'clanspress-notification__title';
		titleSpan.textContent = notification.title
			? String( notification.title )
			: '';
		header.appendChild( titleSpan );
	}

	const timeSpan = document.createElement( 'span' );
	timeSpan.className = 'clanspress-notification__time';
	timeSpan.textContent = notification.time_ago
		? String( notification.time_ago )
		: '';
	header.appendChild( timeSpan );

	content.appendChild( header );

	if ( notification.message ) {
		const p = document.createElement( 'p' );
		p.className = 'clanspress-notification__message';
		p.textContent = String( notification.message );
		content.appendChild( p );
	}

	if (
		notification.is_actionable &&
		notification.actions &&
		notification.actions.length > 0
	) {
		const actionsRow = document.createElement( 'div' );
		actionsRow.className = 'clanspress-notification__actions';
		notification.actions.forEach( ( action ) => {
			const btn = document.createElement( 'button' );
			btn.type = 'button';
			const rawStyle = String( action.style || 'secondary' );
			const safeStyle = safeClassSuffix( rawStyle ) || 'secondary';
			btn.className = `clanspress-notification__action clanspress-notification__action--${ safeStyle }`;
			btn.dataset.action = action.key != null ? String( action.key ) : '';
			btn.dataset.notificationId = String( id );
			if ( action.confirm ) {
				btn.dataset.confirm = String( action.confirm );
			}
			btn.textContent =
				action.label != null ? String( action.label ) : '';
			actionsRow.appendChild( btn );
		} );
		content.appendChild( actionsRow );
	} else if ( notification.status && notification.status !== 'pending' ) {
		const statusLabels = i18n?.statusLabels || {
			accepted: 'Accepted',
			declined: 'Declined',
			dismissed: 'Dismissed',
			expired: 'Expired',
		};
		const st = String( notification.status );
		const statusEl = document.createElement( 'div' );
		statusEl.className = 'clanspress-notification__status';
		const label = statusLabels[ st ] ?? st;
		statusEl.textContent = String( label );
		content.appendChild( statusEl );
	}

	root.appendChild( avatarWrap );
	root.appendChild( content );

	if ( ! notification.is_read && ! notification.is_actionable ) {
		const dot = document.createElement( 'div' );
		dot.className = 'clanspress-notification__unread-dot';
		root.appendChild( dot );
	}

	return root;
}

/**
 * Render the notifications list into the DOM (no innerHTML for API-sourced text).
 *
 * @param {Object}      ctx Context object with notifications and i18n.
 * @param {HTMLElement} ref Block root element reference.
 */
function renderNotificationsList( ctx, ref ) {
	if ( ! ref ) {
		return;
	}

	const listEl = ref.querySelector( '.clanspress-notification-bell__list' );
	if ( ! listEl ) {
		return;
	}

	if ( ! ctx.notifications || ctx.notifications.length === 0 ) {
		const empty = document.createElement( 'p' );
		empty.className = 'clanspress-notification-bell__empty';
		empty.textContent =
			ctx.i18n?.noNotifications || 'No notifications yet.';
		listEl.replaceChildren( empty );
		return;
	}

	const frag = document.createDocumentFragment();
	ctx.notifications.forEach( ( n ) => {
		const el = createNotificationItemElement( n, ctx.i18n );
		if ( el ) {
			frag.appendChild( el );
		}
	} );
	listEl.replaceChildren( frag );
}

/**
 * Accessible confirmation dialog (replaces window.confirm for notification actions).
 *
 * @param {string}           message            Plain-text message from the server.
 * @param {HTMLElement|null} restoreFocusTarget Control to restore focus after close.
 * @return {Promise<boolean>} Resolves true when the user confirms.
 */
function accessibleConfirm( message, restoreFocusTarget ) {
	return new Promise( ( resolve ) => {
		const custom = window.wp?.hooks?.applyFilters?.(
			'clanspress.notifications.accessibleConfirm',
			null,
			message
		);
		if ( custom instanceof Promise ) {
			custom.then( resolve );
			return;
		}

		const uid = `clanspress-nb-confirm-${ Date.now() }`;

		const overlay = document.createElement( 'div' );
		overlay.className = 'clanspress-notification-bell__confirm-overlay';

		const dialog = document.createElement( 'div' );
		dialog.className = 'clanspress-notification-bell__confirm';
		dialog.setAttribute( 'role', 'alertdialog' );
		dialog.setAttribute( 'aria-modal', 'true' );
		dialog.setAttribute( 'aria-labelledby', `${ uid }-title` );
		dialog.setAttribute( 'aria-describedby', `${ uid }-desc` );

		const title = document.createElement( 'h2' );
		title.className = 'clanspress-notification-bell__confirm-title';
		title.id = `${ uid }-title`;
		title.textContent = 'Confirm action';

		const body = document.createElement( 'p' );
		body.className = 'clanspress-notification-bell__confirm-message';
		body.id = `${ uid }-desc`;
		body.textContent = message;

		const actionsRow = document.createElement( 'div' );
		actionsRow.className = 'clanspress-notification-bell__confirm-actions';

		const cancelBtn = document.createElement( 'button' );
		cancelBtn.type = 'button';
		cancelBtn.className =
			'clanspress-notification-bell__confirm-btn clanspress-notification-bell__confirm-btn--cancel';
		cancelBtn.textContent = 'Cancel';

		const okBtn = document.createElement( 'button' );
		okBtn.type = 'button';
		okBtn.className =
			'clanspress-notification-bell__confirm-btn clanspress-notification-bell__confirm-btn--ok';
		okBtn.textContent = 'OK';

		const focusables = [ cancelBtn, okBtn ];

		function cleanup() {
			document.removeEventListener( 'keydown', onKeyDown, true );
			overlay.remove();
			if (
				restoreFocusTarget &&
				typeof restoreFocusTarget.focus === 'function'
			) {
				restoreFocusTarget.focus();
			}
		}

		function finish( value ) {
			cleanup();
			resolve( value );
		}

		function onKeyDown( event ) {
			if ( event.key === 'Escape' ) {
				event.preventDefault();
				event.stopPropagation();
				finish( false );
				return;
			}
			if ( event.key === 'Tab' ) {
				const active = overlay.ownerDocument.activeElement;
				const i = focusables.indexOf( active );
				if ( event.shiftKey ) {
					if ( i <= 0 ) {
						event.preventDefault();
						okBtn.focus();
					}
				} else if ( i === focusables.length - 1 ) {
					event.preventDefault();
					cancelBtn.focus();
				}
			}
		}

		cancelBtn.addEventListener( 'click', () => finish( false ) );
		okBtn.addEventListener( 'click', () => finish( true ) );
		overlay.addEventListener( 'click', ( event ) => {
			if ( event.target === overlay ) {
				finish( false );
			}
		} );

		actionsRow.append( cancelBtn, okBtn );
		dialog.append( title, body, actionsRow );
		overlay.append( dialog );
		document.body.append( overlay );

		document.addEventListener( 'keydown', onKeyDown, true );

		if ( window.wp?.a11y?.speak ) {
			window.wp.a11y.speak( message, 'assertive' );
		}

		window.requestAnimationFrame( () => {
			cancelBtn.focus();
		} );
	} );
}

/**
 * Show a toast notification.
 *
 * @param {string} message Toast message.
 * @param {string} type    Toast type ('info', 'success', 'error').
 */
function showToast( message, type = 'info' ) {
	/**
	 * Filter to customize toast notification display.
	 */
	const handled = window.wp?.hooks?.applyFilters?.(
		'clanspress.notifications.showToast',
		false,
		message,
		type
	);

	if ( handled ) {
		return;
	}

	// Simple fallback toast.
	const toast = document.createElement( 'div' );
	toast.className = `clanspress-toast clanspress-toast--${ type }`;
	toast.textContent = message;
	toast.style.cssText = `
		position: fixed;
		bottom: 20px;
		right: 20px;
		padding: 12px 20px;
		background: ${ type === 'error' ? '#d63638' : '#00a32a' };
		color: white;
		border-radius: 4px;
		z-index: 100000;
		animation: fadeIn 0.3s ease;
	`;

	document.body.appendChild( toast );

	setTimeout( () => {
		toast.style.opacity = '0';
		toast.style.transition = 'opacity 0.3s ease';
		setTimeout( () => toast.remove(), 300 );
	}, 3000 );
}

const { state, actions, callbacks } = store( 'clanspress/notification-bell', {
	state: {
		get hasUnread() {
			const ctx = getContext();
			return ctx.unreadCount > 0;
		},
	},

	actions: {
		toggleDropdown( event ) {
			// Do not stopPropagation: document click handlers on other header UI (e.g. user nav)
			// must see the bubble so they can close their popovers when this one opens.
			const ctx = getContext();
			ctx.isOpen = ! ctx.isOpen;

			if ( ctx.isOpen ) {
				actions.loadNotifications();
			}
		},

		handleOutsideClick( event ) {
			const ctx = getContext();
			if ( ! ctx.isOpen ) {
				return;
			}

			const { ref } = getElement();
			if ( ref && ! ref.contains( event.target ) ) {
				ctx.isOpen = false;
			}
		},

		async loadNotifications() {
			const ctx = getContext();
			const { ref } = getElement();
			ctx.isLoading = true;

			try {
				const response = await restFetch( ctx.restUrl, ctx.nonce, {
					path: `notifications?per_page=${ ctx.dropdownCount }`,
				} );

				ctx.notifications = response.notifications || [];
				ctx.unreadCount = response.unread_count || 0;

				if ( ctx.notifications.length > 0 ) {
					ctx.lastId = ctx.notifications[ 0 ].id;
				}

				renderNotificationsList( ctx, ref );
			} catch ( error ) {
				console.error( 'Failed to load notifications:', error );
			} finally {
				ctx.isLoading = false;
			}
		},

		async markAllRead() {
			const ctx = getContext();
			const { ref } = getElement();

			try {
				await restFetch( ctx.restUrl, ctx.nonce, {
					path: 'notifications/read-all',
					method: 'POST',
				} );

				ctx.unreadCount = 0;
				ctx.notifications = ctx.notifications.map( ( n ) => ( {
					...n,
					is_read: true,
				} ) );

				renderNotificationsList( ctx, ref );
			} catch ( error ) {
				console.error( 'Failed to mark all as read:', error );
			}
		},

		async markRead( event ) {
			const notificationId = event.target.closest(
				'[data-notification-id]'
			)?.dataset?.notificationId;
			if ( ! notificationId ) {
				return;
			}

			const ctx = getContext();
			const { ref } = getElement();

			try {
				const response = await restFetch( ctx.restUrl, ctx.nonce, {
					path: `notifications/${ notificationId }/read`,
					method: 'POST',
				} );

				ctx.unreadCount = response.unread_count || 0;

				const idx = ctx.notifications.findIndex(
					( n ) => n.id === parseInt( notificationId, 10 )
				);
				if ( idx !== -1 ) {
					ctx.notifications[ idx ].is_read = true;
				}

				renderNotificationsList( ctx, ref );
			} catch ( error ) {
				console.error( 'Failed to mark as read:', error );
			}
		},

		async executeAction( event ) {
			const button = event.target.closest( '[data-action]' );
			if ( ! button ) {
				return;
			}

			const notificationId = button.dataset.notificationId;
			const actionKey = button.dataset.action;
			const confirmMsg = button.dataset.confirm;

			if ( confirmMsg ) {
				const confirmed = await accessibleConfirm( confirmMsg, button );
				if ( ! confirmed ) {
					return;
				}
			}

			const ctx = getContext();
			const { ref } = getElement();
			button.disabled = true;

			try {
				const response = await restFetch( ctx.restUrl, ctx.nonce, {
					path: `notifications/${ notificationId }/action`,
					method: 'POST',
					data: { action: actionKey },
				} );

				ctx.unreadCount = response.unread_count || 0;

				// Remove or update the notification.
				const idx = ctx.notifications.findIndex(
					( n ) => n.id === parseInt( notificationId, 10 )
				);
				if ( idx !== -1 ) {
					ctx.notifications[ idx ].is_actionable = false;
					ctx.notifications[ idx ].is_read = true;
					ctx.notifications[ idx ].status =
						response.status || 'dismissed';
				}

				renderNotificationsList( ctx, ref );

				// Show success message.
				if ( response.message ) {
					showToast( response.message, 'success' );
				}

				// Handle redirect if provided.
				if ( response.redirect ) {
					const next = safeHttpUrl( response.redirect );
					if ( next ) {
						window.location.assign( next );
					}
				}
			} catch ( error ) {
				console.error( 'Failed to execute action:', error );
				showToast( error.message || 'Action failed', 'error' );
			} finally {
				button.disabled = false;
			}
		},
	},

	callbacks: {
		init() {
			const ctx = getContext();
			const { ref } = getElement();

			// Initialize notifications array if not set.
			if ( ! ctx.notifications ) {
				ctx.notifications = [];
			}

			// Store element reference for use in async callbacks.
			ctx._ref = ref;

			// Fetch initial count immediately.
			fetchInitialCount( ctx );

			/**
			 * Filter to provide alternative sync providers (e.g., WebSocket).
			 *
			 * Uses the same 'sync.providers' pattern as WordPress 7.0 RTC.
			 * Providers should implement: { subscribe( channel, callback ), unsubscribe( channel ) }
			 *
			 * @param {Array}  providers Array of sync provider objects.
			 * @param {string} channel   The channel name ('clanspress.notifications').
			 */
			const providers =
				window.wp?.hooks?.applyFilters?.(
					'sync.providers',
					[],
					'clanspress.notifications'
				) || [];
			const wsProvider = providers.find(
				( p ) => p && typeof p.subscribe === 'function'
			);

			if ( wsProvider ) {
				// Use WebSocket provider.
				ctx.syncProvider = wsProvider;
				initSyncProvider( ctx );
			} else {
				// Fall back to HTTP long polling.
				startPolling( ctx );
			}
		},

		/**
		 * Sync list markup when context.notifications changes (e.g. long poll) while dropdown is open.
		 */
		renderNotifications() {
			const ctx = getContext();
			if ( ! ctx.isOpen ) {
				return;
			}
			const { ref } = getElement();
			const root =
				ref?.closest?.( '.clanspress-notification-bell' ) || ctx._ref;
			renderNotificationsList( ctx, root );
		},
	},
} );

/**
 * Fetch initial notification count.
 *
 * @param {Object} ctx Context object.
 */
async function fetchInitialCount( ctx ) {
	try {
		const response = await restFetch( ctx.restUrl, ctx.nonce, {
			path: 'notifications/count',
		} );

		ctx.unreadCount = response.unread_count || 0;
		ctx.lastTimestamp = response.timestamp;
	} catch ( error ) {
		// Silently fail - the badge will just show the server-rendered count.
		console.error( 'Failed to fetch notification count:', error );
	}
}

/**
 * Start HTTP long polling for notifications.
 *
 * @param {Object} ctx Context object.
 */
function startPolling( ctx ) {
	const poll = async () => {
		// Don't poll if sync provider is active.
		if ( ctx.syncProviderActive ) {
			return;
		}

		try {
			const params = new URLSearchParams( {
				last_id: ctx.lastId || 0,
				timeout: 25,
			} );

			if ( ctx.lastTimestamp && ! ctx.lastId ) {
				params.set( 'since', ctx.lastTimestamp );
			}

			const response = await restFetch( ctx.restUrl, ctx.nonce, {
				path: `notifications/poll?${ params.toString() }`,
			} );

			// Update state with new notifications.
			if ( response.notifications && response.notifications.length > 0 ) {
				ctx.notifications = [
					...response.notifications,
					...( ctx.notifications || [] ),
				].slice( 0, ctx.dropdownCount );

				ctx.lastId = response.notifications[ 0 ].id;

				// Re-render if dropdown is open.
				if ( ctx.isOpen && ctx._ref ) {
					renderNotificationsList( ctx, ctx._ref );
				}

				// Fire event for other components.
				window.wp?.hooks?.doAction?.(
					'clanspress.notifications.received',
					response.notifications
				);
			}

			ctx.unreadCount = response.unread_count ?? ctx.unreadCount;
			ctx.lastTimestamp = response.timestamp;
			ctx.pollInterval = response.next_poll || 4000;
		} catch ( error ) {
			// Increase interval on error.
			ctx.pollInterval = Math.min( ctx.pollInterval * 2, 30000 );
			console.error( 'Notification poll failed:', error );
		}

		// Schedule next poll.
		setTimeout( poll, ctx.pollInterval );
	};

	// Start polling immediately.
	poll();
}

/**
 * Initialize WebSocket sync provider.
 *
 * @param {Object} ctx Context object.
 */
function initSyncProvider( ctx ) {
	const provider = ctx.syncProvider;

	if ( ! provider || typeof provider.subscribe !== 'function' ) {
		startPolling( ctx );
		return;
	}

	try {
		provider.subscribe( 'clanspress.notifications', ( data ) => {
			if ( data.type === 'notification' && data.notification ) {
				ctx.notifications = [
					data.notification,
					...( ctx.notifications || [] ),
				].slice( 0, ctx.dropdownCount );

				ctx.unreadCount = data.unread_count || ctx.unreadCount + 1;
				ctx.lastId = data.notification.id;

				if ( ctx.isOpen && ctx._ref ) {
					renderNotificationsList( ctx, ctx._ref );
				}

				window.wp?.hooks?.doAction?.(
					'clanspress.notifications.received',
					[ data.notification ]
				);
			} else if ( data.type === 'count' ) {
				ctx.unreadCount = data.unread_count || 0;
			}
		} );

		ctx.syncProviderActive = true;
	} catch ( error ) {
		console.error( 'Failed to initialize sync provider:', error );
		startPolling( ctx );
	}
}
