/**
 * Merge theme.json-oriented `supports` and `selectors` into each block.json under src/blocks.
 * Run: node scripts/add-block-theme-support.mjs
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname( fileURLToPath( import.meta.url ) );
const root = path.resolve( __dirname, '..' );
const blocksRoot = path.join( root, 'src', 'blocks' );

function wpBlockClass( blockName ) {
	return '.wp-block-' + blockName.replace( '/', '-' );
}

/** Second object wins (theme author / existing block.json overrides defaults). */
function deepMergePreferSecond( base, over ) {
	if ( ! over || typeof over !== 'object' || Array.isArray( over ) ) {
		return base;
	}
	const out = { ...( base && typeof base === 'object' ? base : {} ) };
	for ( const key of Object.keys( over ) ) {
		const bv = out[ key ];
		const ov = over[ key ];
		if (
			ov &&
			typeof ov === 'object' &&
			! Array.isArray( ov ) &&
			bv &&
			typeof bv === 'object' &&
			! Array.isArray( bv )
		) {
			out[ key ] = deepMergePreferSecond( bv, ov );
		} else {
			out[ key ] = ov;
		}
	}
	return out;
}

const DEFAULT_SUPPORTS_BASE = {
	spacing: {
		margin: true,
		padding: true,
		blockGap: true,
	},
	border: {
		color: true,
		radius: true,
		style: true,
		width: true,
	},
	shadow: true,
	color: {
		text: true,
		background: true,
	},
};

const NO_BACKGROUND = new Set( [
	'clanspress/player-cover',
	'clanspress/team-cover',
] );

const COLOR_LINK = new Set( [
	'clanspress/team-name',
	'clanspress/player-display-name',
	'clanspress/team-profile-nav',
	'clanspress/player-profile-nav',
	'clanspress/user-nav',
	'clanspress/match-card',
	'clanspress/match-list',
] );

/**
 * @param {string} name
 * @returns {Record<string, unknown>}
 */
function buildSelectors( name ) {
	const wp = wpBlockClass( name );
	const table = {
		'clanspress/team-motto': {
			root: wp,
			color: {
				text: `${ wp } .clanspress-team-motto__text`,
				background: wp,
			},
			typography: `${ wp } .clanspress-team-motto__text`,
			border: wp,
		},
		'clanspress/team-name': {
			root: wp,
			color: {
				text: `${ wp } .clanspress-team-name__heading, ${ wp } .clanspress-team-name__link`,
				background: wp,
				link: `${ wp } .clanspress-team-name__link`,
			},
			typography: `${ wp } .clanspress-team-name__heading`,
			border: wp,
		},
		'clanspress/player-display-name': {
			root: wp,
			color: {
				text: `${ wp } .clanspress-player-display-name__text, ${ wp } .clanspress-player-display-name__link`,
				background: wp,
				link: `${ wp } .clanspress-player-display-name__link`,
			},
			typography: `${ wp } .clanspress-player-display-name__text`,
			border: wp,
		},
		'clanspress/team-description': {
			root: wp,
			color: {
				text: `${ wp } .clanspress-team-description__content`,
				background: wp,
			},
			typography: `${ wp } .clanspress-team-description__content`,
			border: wp,
		},
		'clanspress/team-code': {
			root: wp,
			color: {
				text: `${ wp } .clanspress-team-code__value`,
				background: wp,
			},
			typography: `${ wp } .clanspress-team-code__value`,
			border: wp,
		},
		'clanspress/team-country': {
			root: wp,
			color: {
				text: `${ wp } .clanspress-country-display__label, ${ wp } .clanspress-country-display__flag`,
				background: wp,
			},
			typography: `${ wp } .clanspress-country-display__label`,
			border: wp,
		},
		'clanspress/player-country': {
			root: wp,
			color: {
				text: `${ wp } .clanspress-country-display__label, ${ wp } .clanspress-country-display__flag`,
				background: wp,
			},
			typography: `${ wp } .clanspress-country-display__label`,
			border: wp,
		},
		'clanspress/team-profile-nav': {
			root: wp,
			color: {
				text: `${ wp } .clanspress-team-profile-nav__link`,
				background: wp,
				link: `${ wp } .clanspress-team-profile-nav__link`,
			},
			typography: `${ wp } .clanspress-team-profile-nav__link`,
			border: wp,
		},
		'clanspress/player-profile-nav': {
			root: wp,
			color: {
				text: `${ wp } .clanspress-player-profile-nav__link`,
				background: wp,
				link: `${ wp } .clanspress-player-profile-nav__link`,
			},
			typography: `${ wp } .clanspress-player-profile-nav__link`,
			border: wp,
		},
		'clanspress/user-nav': {
			root: wp,
			color: {
				text: `${ wp } .clanspress-user-nav__link`,
				background: wp,
				link: `${ wp } .clanspress-user-nav__link`,
			},
			typography: `${ wp } .clanspress-user-nav__link`,
			border: wp,
		},
		'clanspress/team-manage-link': {
			root: wp,
			color: {
				text: `${ wp } .wp-block-button__link`,
				background: `${ wp } .wp-block-button__link`,
			},
			typography: `${ wp } .wp-block-button__link`,
			border: wp,
		},
		'clanspress/player-settings-link': {
			root: wp,
			color: {
				text: `${ wp } .wp-block-button__link`,
				background: `${ wp } .wp-block-button__link`,
			},
			typography: `${ wp } .wp-block-button__link`,
			border: wp,
		},
		'clanspress/team-challenge-button': {
			root: wp,
			color: {
				text: `${ wp } .wp-block-button__link, ${ wp } button`,
				background: `${ wp } .wp-block-button__link, ${ wp } button`,
			},
			typography: `${ wp } .wp-block-button__link`,
			border: wp,
		},
		'clanspress/match-card': {
			root: wp,
			color: {
				text: `${ wp } .clanspress-match-card, ${ wp } .clanspress-match-card a`,
				background: wp,
				link: `${ wp } .clanspress-match-card a`,
			},
			typography: `${ wp } .clanspress-match-card`,
			border: wp,
		},
		'clanspress/match-list': {
			root: wp,
			color: {
				text: `${ wp } .clanspress-match-list, ${ wp } .clanspress-match-list__link`,
				background: wp,
				link: `${ wp } .clanspress-match-list__link`,
			},
			typography: `${ wp } .clanspress-match-list`,
			border: wp,
		},
		'clanspress/player-template': {
			root: wp,
			color: {
				text: wp,
				background: wp,
				link: `${ wp } a`,
			},
			typography: wp,
			border: wp,
		},
		'clanspress/player-cover': {
			root: wp,
			color: {
				text: `${ wp } .player-cover__content-container`,
				background: wp,
			},
			typography: `${ wp } .player-cover__content-container`,
			border: wp,
		},
		'clanspress/team-cover': {
			root: wp,
			color: {
				text: `${ wp } .team-cover__content-container`,
				background: wp,
			},
			typography: `${ wp } .team-cover__content-container`,
			border: wp,
		},
		'clanspress/player-avatar': {
			root: wp,
			color: { background: wp },
			border: `${ wp } .clanspress-player-avatar`,
		},
		'clanspress/team-avatar': {
			root: wp,
			color: { background: wp },
			border: `${ wp } .clanspress-team-avatar`,
		},
		'clanspress/notification-bell': {
			root: wp,
			color: {
				text: `${ wp } .clanspress-notification-bell__trigger, ${ wp } .clanspress-notification-bell__dropdown`,
				background: wp,
			},
			typography: `${ wp } .clanspress-notification-bell__trigger`,
			border: wp,
		},
	};
	if ( table[ name ] ) {
		return structuredClone( table[ name ] );
	}
	return {
		root: wp,
		color: wp,
		typography: wp,
		border: wp,
	};
}

function walkBlockJson( dir ) {
	const out = [];
	if ( ! fs.existsSync( dir ) ) {
		return out;
	}
	for ( const n of fs.readdirSync( dir ) ) {
		const p = path.join( dir, n );
		if ( fs.statSync( p ).isDirectory() ) {
			out.push( ...walkBlockJson( p ) );
		} else if ( n === 'block.json' ) {
			out.push( p );
		}
	}
	return out;
}

for ( const file of walkBlockJson( blocksRoot ) ) {
	const raw = fs.readFileSync( file, 'utf8' );
	const original = JSON.parse( raw );
	const name = original.name;
	if ( ! name || typeof name !== 'string' ) {
		continue;
	}

	let pack = structuredClone( DEFAULT_SUPPORTS_BASE );
	if ( NO_BACKGROUND.has( name ) && pack.color ) {
		pack.color = { ...pack.color, background: false };
	}
	if ( COLOR_LINK.has( name ) && pack.color ) {
		pack.color = { ...pack.color, link: true };
	}

	original.supports = deepMergePreferSecond( pack, original.supports || {} );

	const gen = buildSelectors( name );
	const prevSel = original.selectors || {};
	// Regenerate layout selectors each run; preserve only `filter` (e.g. duotone) from block.json.
	const preserve = prevSel.filter ? { filter: prevSel.filter } : {};
	original.selectors = deepMergePreferSecond( gen, preserve );

	fs.writeFileSync(
		file,
		JSON.stringify( original, null, '\t' ) + '\n',
		'utf8'
	);
	process.stdout.write( 'updated ' + path.relative( root, file ) + '\n' );
}
