/**
 * Copy block view.js (viewScriptModule) from src/blocks into build/ — wp-scripts does not emit these.
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname( fileURLToPath( import.meta.url ) );
const root = path.join( __dirname, '..' );
const srcBlocks = path.join( root, 'src/blocks' );

function walk( dir ) {
	if ( ! fs.existsSync( dir ) ) {
		return;
	}
	for ( const ent of fs.readdirSync( dir, { withFileTypes: true } ) ) {
		const p = path.join( dir, ent.name );
		if ( ent.isDirectory() ) {
			walk( p );
		} else if ( ent.name === 'view.js' ) {
			const rel = path.relative( srcBlocks, p );
			const out = path.join( root, 'build', rel );
			fs.mkdirSync( path.dirname( out ), { recursive: true } );
			fs.copyFileSync( p, out );
			// eslint-disable-next-line no-console
			console.log( 'Copied', rel, '-> build/' + rel );
		}
	}
}

walk( srcBlocks );

/**
 * Team block view modules import shared helpers (e.g. sync-team-form-tabs.js); copy alongside build output.
 */
const teamsSharedSrc = path.join( srcBlocks, 'teams', 'shared' );
const teamsSharedDest = path.join( root, 'build', 'teams', 'shared' );
if ( fs.existsSync( teamsSharedSrc ) ) {
	fs.mkdirSync( teamsSharedDest, { recursive: true } );
	for ( const ent of fs.readdirSync( teamsSharedSrc, { withFileTypes: true } ) ) {
		if ( ! ent.isFile() || ! ent.name.endsWith( '.js' ) ) {
			continue;
		}
		const from = path.join( teamsSharedSrc, ent.name );
		const to = path.join( teamsSharedDest, ent.name );
		fs.copyFileSync( from, to );
		// eslint-disable-next-line no-console
		console.log( 'Copied', `teams/shared/${ ent.name }`, '->', `build/teams/shared/${ ent.name }` );
	}
}
