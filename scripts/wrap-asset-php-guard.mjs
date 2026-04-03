/**
 * Normalize generated PHP stubs for Plugin Check:
 * - *.asset.php: multi-line file with ABSPATH guard before `return`.
 * - build/{matches,players,teams,...}/blocks-manifest.php: same guard (wp-scripts does not emit it).
 *
 * Uses `if ( ! defined( 'ABSPATH' ) ) { exit; }` — preferred by Plugin Check over `defined() || exit`.
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname( fileURLToPath( import.meta.url ) );
const root = path.resolve( __dirname, '..' );

const LONG_GUARD = "if ( ! defined( 'ABSPATH' ) ) {\n\texit;\n}\n\n";

/**
 * Remove `<?php` and any known ABSPATH guard variants from the start of a file body.
 *
 * @param {string} raw
 * @returns {string}
 */
function stripPhpOpenAndGuards( raw ) {
	let s = raw.replace( /^\ufeff?<\?php\s*/u, '' );
	s = s.replace(
		/^\s*if\s*\(\s*!\s*defined\s*\(\s*['"]ABSPATH['"]\s*\)\s*\)\s*\{\s*exit\s*;\s*\}\s*/m,
		''
	);
	s = s.replace( /^\s*defined\s*\(\s*'ABSPATH'\s*\)\s*\|\|\s*exit\s*;\s*/m, '' );
	return s.trimStart();
}

/**
 * @param {string} dir
 * @param {string[]} acc
 * @returns {string[]}
 */
function collectAssetPhp( dir, acc = [] ) {
	if ( ! fs.existsSync( dir ) ) {
		return acc;
	}
	for ( const name of fs.readdirSync( dir ) ) {
		const full = path.join( dir, name );
		const st = fs.statSync( full );
		if ( st.isDirectory() ) {
			collectAssetPhp( full, acc );
		} else if ( name.endsWith( '.asset.php' ) ) {
			acc.push( full );
		}
	}
	return acc;
}

/**
 * @param {string} dir
 * @param {string[]} acc
 * @returns {string[]}
 */
function collectBlocksManifests( dir, acc = [] ) {
	if ( ! fs.existsSync( dir ) ) {
		return acc;
	}
	for ( const name of fs.readdirSync( dir ) ) {
		const full = path.join( dir, name );
		const st = fs.statSync( full );
		if ( st.isDirectory() ) {
			collectBlocksManifests( full, acc );
		} else if ( name === 'blocks-manifest.php' ) {
			acc.push( full );
		}
	}
	return acc;
}

/**
 * @param {string} filePath
 * @param {string} body
 * @param {string} kind
 */
function saveGuarded( filePath, body, kind ) {
	const out =
		'<?php\n\n' +
		LONG_GUARD +
		body +
		( body.endsWith( '\n' ) ? '' : '\n' );
	fs.writeFileSync( filePath, out );
	process.stdout.write(
		`wrap-asset-php-guard: ${ kind } ${ path.relative( root, filePath ) }\n`
	);
}

/**
 * @param {string} filePath
 */
function wrapAssetFile( filePath ) {
	const raw = fs.readFileSync( filePath, 'utf8' );
	if ( raw.includes( "if ( ! defined( 'ABSPATH' ) )" ) ) {
		return;
	}
	const body = stripPhpOpenAndGuards( raw );
	if ( ! body.startsWith( 'return ' ) ) {
		process.stderr.write(
			`wrap-asset-php-guard: skip asset (no return): ${ path.relative( root, filePath ) }\n`
		);
		return;
	}
	saveGuarded( filePath, body, 'asset' );
}

/**
 * @param {string} filePath
 */
function wrapBlocksManifest( filePath ) {
	const raw = fs.readFileSync( filePath, 'utf8' );
	if ( raw.includes( "if ( ! defined( 'ABSPATH' ) )" ) ) {
		return;
	}
	const body = stripPhpOpenAndGuards( raw );
	if ( ! body.startsWith( '//' ) && ! body.startsWith( 'return ' ) ) {
		process.stderr.write(
			`wrap-asset-php-guard: skip manifest (unexpected): ${ path.relative( root, filePath ) }\n`
		);
		return;
	}
	saveGuarded( filePath, body, 'manifest' );
}

const dirs = [
	path.join( root, 'assets', 'dist' ),
	path.join( root, 'build' ),
];

for ( const dir of dirs ) {
	for ( const file of collectAssetPhp( dir ) ) {
		wrapAssetFile( file );
	}
}

for ( const file of collectBlocksManifests( path.join( root, 'build' ) ) ) {
	wrapBlocksManifest( file );
}
