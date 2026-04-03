/**
 * Ensure generated *.asset.php files include a direct-access guard before `return`.
 * @wordpress/scripts emits one-line `<?php return array(...);` by default; Plugin Check expects ABSPATH.
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname( fileURLToPath( import.meta.url ) );
const root = path.resolve( __dirname, '..' );

const GUARD = "\n\ndefined( 'ABSPATH' ) || exit;\n\n";

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
 * @param {string} filePath
 */
function wrapFile( filePath ) {
	let raw = fs.readFileSync( filePath, 'utf8' );
	if ( raw.includes( "defined( 'ABSPATH' ) || exit" ) ) {
		return;
	}
	// Strip optional BOM and opening PHP tag.
	let body = raw.replace( /^\ufeff?<\?php\s*/u, '' ).trimStart();
	if ( ! body.startsWith( 'return ' ) ) {
		process.stderr.write(
			`wrap-asset-php-guard: skip (unexpected shape): ${ path.relative( root, filePath ) }\n`
		);
		return;
	}
	const out =
		'<?php' +
		GUARD +
		body +
		( body.endsWith( '\n' ) ? '' : '\n' );
	fs.writeFileSync( filePath, out );
	process.stdout.write(
		`wrap-asset-php-guard: wrapped ${ path.relative( root, filePath ) }\n`
	);
}

const dirs = [
	path.join( root, 'assets', 'dist' ),
	path.join( root, 'build' ),
];

for ( const dir of dirs ) {
	for ( const file of collectAssetPhp( dir ) ) {
		wrapFile( file );
	}
}
