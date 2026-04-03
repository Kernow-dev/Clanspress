/**
 * Copy Flagpack medium SVGs into assets/flags (lowercase filenames).
 * Run after `npm install`. See assets/flags/ATTRIBUTION.txt for license.
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname( fileURLToPath( import.meta.url ) );
const root = path.resolve( __dirname, '..' );
const srcDir = path.join( root, 'node_modules', 'flagpack-core', 'lib', 'flags', 'm' );
const destDir = path.join( root, 'assets', 'flags' );

if ( ! fs.existsSync( srcDir ) ) {
	console.error(
		'copy-flagpack-flags: missing node_modules/flagpack-core. Run npm install.'
	);
	process.exit( 1 );
}

fs.mkdirSync( destDir, { recursive: true } );

const files = fs.readdirSync( srcDir ).filter( ( f ) => f.endsWith( '.svg' ) );
let n = 0;
for ( const f of files ) {
	const base = f.replace( /\.svg$/i, '' );
	const destName = `${ base.toLowerCase() }.svg`;
	fs.copyFileSync( path.join( srcDir, f ), path.join( destDir, destName ) );
	n++;
}

const attribution = `Flag SVGs are from Flagpack (https://flagpack.xyz / https://github.com/Yummygum/flagpack-core), used under the MIT License.
`;

fs.writeFileSync( path.join( destDir, 'ATTRIBUTION.txt' ), attribution, 'utf8' );
console.log( `copy-flagpack-flags: copied ${ n } SVGs to assets/flags/` );
