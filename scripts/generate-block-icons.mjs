/**
 * Generates per-block icon modules (Heroicons solid 24) and patches block index.js imports.
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const root = path.join( path.dirname( fileURLToPath( import.meta.url ) ), '..' );
const iconsDir = path.join( root, 'src', 'blocks', 'shared', 'block-icons', 'icons' );
const blocksRoot = path.join( root, 'src', 'blocks' );

/** Heroicons solid export name per block slug. */
const MAP = {
	default: 'CubeIcon',
	'visibility-container': 'EyeIcon',
	'event-calendar': 'CalendarDaysIcon',
	'event-create-form': 'PlusCircleIcon',
	'event-detail': 'InformationCircleIcon',
	'event-list': 'ListBulletIcon',
	'event-rsvp': 'CheckBadgeIcon',
	'match-card': 'TrophyIcon',
	'match-list': 'QueueListIcon',
	'notification-bell': 'BellAlertIcon',
	'player-avatar': 'UserCircleIcon',
	'player-birthday': 'CakeIcon',
	'player-city': 'MapPinIcon',
	'player-country': 'GlobeAltIcon',
	'player-cover': 'PhotoIcon',
	'player-description': 'DocumentTextIcon',
	'player-display-name': 'IdentificationIcon',
	'player-handle': 'AtSymbolIcon',
	'player-query': 'MagnifyingGlassIcon',
	'player-settings-link': 'LinkIcon',
	'player-settings': 'Cog6ToothIcon',
	'player-social-links': 'ShareIcon',
	'player-tagline': 'ChatBubbleBottomCenterTextIcon',
	'player-template': 'Squares2X2Icon',
	'player-website': 'GlobeAmericasIcon',
	'profile-nav': 'Bars3Icon',
	'user-nav': 'UserGroupIcon',
	'team-avatar': 'ShieldCheckIcon',
	'team-card': 'RectangleStackIcon',
	'team-challenge-button': 'ViewfinderCircleIcon',
	'team-code': 'HashtagIcon',
	'team-country': 'FlagIcon',
	'team-cover': 'PhotoIcon',
	'team-create-form': 'PlusIcon',
	'team-description': 'DocumentTextIcon',
	'team-draws': 'ArrowsRightLeftIcon',
	'team-losses': 'XCircleIcon',
	'team-manage-link': 'WrenchScrewdriverIcon',
	'team-members-count': 'UsersIcon',
	'team-motto': 'ChatBubbleLeftRightIcon',
	'team-name': 'TagIcon',
	'team-wins': 'TrophyIcon',
};

function iconModule( slug, exportName ) {
	return `/**
 * Block inserter icon: ${ slug }
 */
import { ${ exportName } } from '@heroicons/react/24/solid';
import { createClanbiteBlockIcon } from '../create-icon.js';

export const clanbiteBlockIcon = createClanbiteBlockIcon( ${ exportName } );
`;
}

function findBlockIndexFiles() {
	const out = [];
	function walk( dir ) {
		for ( const entry of fs.readdirSync( dir, { withFileTypes: true } ) ) {
			const full = path.join( dir, entry.name );
			if ( entry.isDirectory() ) {
				walk( full );
			} else if (
				entry.name === 'index.js' &&
				full.includes( `${ path.sep }blocks${ path.sep }` ) &&
				! full.includes( `${ path.sep }shared${ path.sep }block-icons${ path.sep }` )
			) {
				out.push( full );
			}
		}
	}
	walk( blocksRoot );
	return out;
}

function slugFromIndexPath( indexPath ) {
	return path.basename( path.dirname( indexPath ) );
}

fs.mkdirSync( iconsDir, { recursive: true } );

for ( const [ slug, exportName ] of Object.entries( MAP ) ) {
	const file = path.join( iconsDir, `${ slug }.js` );
	fs.writeFileSync( file, iconModule( slug, exportName ) );
}

for ( const indexPath of findBlockIndexFiles() ) {
	const slug = slugFromIndexPath( indexPath );
	if ( ! MAP[ slug ] ) {
		console.warn( 'no icon map for', slug );
		continue;
	}

	let src = fs.readFileSync( indexPath, 'utf8' );
	const relImport = `../../shared/block-icons/icons/${ slug }`;

	const newImport = `import { clanbiteBlockIcon } from '${ relImport }';`;

	src = src.replace(
		/import\s*\{[^}]*\}\s*from\s*['"]\.\.\/\.\.\/shared\/block-icons['"];?\n?/,
		''
	);
	src = src.replace(
		/icon:\s*clanbiteBlockIconFromMetadata\(\s*metadata\s*\),?/,
		'icon: clanbiteBlockIcon(),'
	);
	src = src.replace(
		/icon=\{\s*<ClanbiteBlockIcon[^/]*\/>\s*\}/,
		'icon={ clanbiteBlockIcon() }'
	);

	if ( ! src.includes( newImport ) ) {
		const blockJsonImport = src.match( /import metadata from ['"]\.\/block\.json['"];?\n/ );
		if ( blockJsonImport ) {
			src = src.replace(
				blockJsonImport[ 0 ],
				`${ blockJsonImport[ 0 ] }${ newImport }\n`
			);
		} else {
			src = `${ newImport }\n${ src }`;
		}
	}

	fs.writeFileSync( indexPath, src );
	console.log( 'patched', path.relative( root, indexPath ) );
}

// Shared index for optional use.
fs.writeFileSync(
	path.join( root, 'src', 'blocks', 'shared', 'block-icons', 'index.js' ),
	`/**
 * Per-block icons live in ./icons/{slug}.js (Heroicons solid, 24px).
 */
export { clanbiteBlockIcon as clanbiteBlockIconFromMetadata } from './icons/default';
`
);

console.log( 'Generated', Object.keys( MAP ).length, 'icon modules.' );
