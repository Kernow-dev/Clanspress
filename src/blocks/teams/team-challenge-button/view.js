/**
 * Front-end interactivity: challenge modal, remote team preview, REST submit.
 */
import { store, getContext } from '@wordpress/interactivity';

function joinRestPath( base, segment ) {
	const b = String( base || '' ).replace( /\/+$/, '' );
	const s = String( segment || '' ).replace( /^\/+/, '' );
	return `${ b }/${ s }`;
}

store( 'clanspress-team-challenge-button', {
	actions: {
		open() {
			const ctx = getContext();
			ctx.open = true;
			ctx.formError = '';
			ctx.formSuccess = '';
			ctx.remoteError = '';
			ctx.logoAttachmentId = 0;
			ctx.logoUploadMessage = '';
		},
		close() {
			const ctx = getContext();
			ctx.open = false;
		},
		closeBackdrop( event ) {
			if ( event.target === event.currentTarget ) {
				const ctx = getContext();
				ctx.open = false;
			}
		},
		stop( event ) {
			event.stopPropagation();
		},
		async lookupRemote( event ) {
			const ctx = getContext();
			const url = String( event.target.value || '' ).trim();
			ctx.previewTitle = '';
			ctx.previewLogo = '';
			ctx.previewPermalink = '';
			ctx.remoteError = '';
			if ( ! url ) {
				return;
			}
			ctx.lookupLoading = true;
			try {
				const endpoint = new URL(
					joinRestPath( ctx.restUrl, 'challenge-remote-team' )
				);
				endpoint.searchParams.set( 'team_id', String( ctx.teamId ) );
				endpoint.searchParams.set( 'url', url );
				endpoint.searchParams.set(
					'challenge_nonce',
					String( ctx.challengeNonce )
				);
				const res = await fetch( endpoint.toString(), {
					credentials: 'same-origin',
					headers: {
						'X-WP-Nonce': String( ctx.restNonce ),
					},
				} );
				const data = await res.json().catch( () => ( {} ) );
				if ( ! res.ok ) {
					ctx.remoteError =
						data?.message ||
						data?.data?.message ||
						'Could not load remote team.';
					return;
				}
				if ( data.team && data.team.title ) {
					ctx.previewTitle = String( data.team.title );
					ctx.previewLogo = data.team.logoUrl
						? String( data.team.logoUrl )
						: '';
					ctx.previewPermalink = data.team.permalink
						? String( data.team.permalink )
						: '';
					ctx.remoteError = '';
				} else if ( data.clanspress ) {
					ctx.remoteError = '';
				}
			} catch {
				ctx.remoteError = 'Network error.';
			} finally {
				ctx.lookupLoading = false;
			}
		},
		async uploadLogo( event ) {
			const ctx = getContext();
			const input = event.target;
			if ( ! ( input instanceof HTMLInputElement ) ) {
				return;
			}
			const file = input.files && input.files[ 0 ];
			ctx.logoAttachmentId = 0;
			ctx.logoUploadMessage = '';
			if ( ! file ) {
				return;
			}
			const fd = new FormData();
			fd.append( 'team_id', String( ctx.teamId ) );
			fd.append( 'challenge_nonce', String( ctx.challengeNonce ) );
			fd.append( 'file', file, file.name );
			try {
				const res = await fetch(
					joinRestPath( ctx.restUrl, 'team-challenge-media' ),
					{
						method: 'POST',
						credentials: 'same-origin',
						headers: {
							'X-WP-Nonce': String( ctx.restNonce ),
						},
						body: fd,
					}
				);
				const data = await res.json().catch( () => ( {} ) );
				if ( ! res.ok ) {
					ctx.logoUploadMessage =
						data?.message ||
						data?.data?.message ||
						'Upload failed.';
					return;
				}
				ctx.logoAttachmentId = data?.id
					? parseInt( String( data.id ), 10 )
					: 0;
				ctx.logoUploadMessage =
					ctx.logoAttachmentId > 0 ? 'Logo uploaded.' : '';
			} catch {
				ctx.logoUploadMessage = 'Network error.';
			}
			input.value = '';
		},
		async submit( event ) {
			event.preventDefault();
			const ctx = getContext();
			const form = event.target;
			if ( ! ( form instanceof HTMLFormElement ) ) {
				return;
			}
			ctx.loading = true;
			ctx.formError = '';
			ctx.formSuccess = '';
			const fd = new FormData( form );
			const body = {
				team_id: ctx.teamId,
				challenge_nonce: ctx.challengeNonce,
				challenger_name: String( fd.get( 'challenger_name' ) || '' ),
				challenger_email: String( fd.get( 'challenger_email' ) || '' ),
				message: String( fd.get( 'message' ) || '' ),
				opponent_team_url: String(
					fd.get( 'opponent_team_url' ) || ''
				),
				proposed_scheduled_at: String(
					fd.get( 'proposed_scheduled_at' ) || ''
				),
				challenger_team_id: parseInt(
					String( fd.get( 'challenger_team_id' ) || '0' ),
					10
				),
				challenger_team_name: String(
					fd.get( 'challenger_team_name' ) || ''
				),
				challenger_team_logo_id: ctx.logoAttachmentId || 0,
			};
			try {
				const res = await fetch(
					joinRestPath( ctx.restUrl, 'team-challenges' ),
					{
						method: 'POST',
						credentials: 'same-origin',
						headers: {
							'Content-Type': 'application/json',
							'X-WP-Nonce': String( ctx.restNonce ),
						},
						body: JSON.stringify( body ),
					}
				);
				const data = await res.json().catch( () => ( {} ) );
				if ( ! res.ok ) {
					ctx.formError =
						data?.message ||
						data?.data?.message ||
						'Request failed.';
					return;
				}
				ctx.formSuccess = data?.message || 'Challenge sent.';
				form.reset();
				ctx.previewTitle = '';
				ctx.previewLogo = '';
				ctx.previewPermalink = '';
				ctx.remoteError = '';
				ctx.logoAttachmentId = 0;
				ctx.logoUploadMessage = '';
			} catch {
				ctx.formError = 'Network error.';
			} finally {
				ctx.loading = false;
			}
		},
	},
} );
