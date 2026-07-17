/**
 * Adds clanbiteBlockIconFromMetadata to every block index.js registration.
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const root = path.join( path.dirname( fileURLToPath( import.meta.url ) ), '..' );
const blocksRoot = path.join( root, 'src', 'blocks' );

const importLine =
	"import { clanbiteBlockIconFromMetadata } from '../../shared/block-icons';";

function patchFile( filePath ) {
	let src = fs.readFileSync( filePath, 'utf8' );

	if ( src.includes( 'clanbiteBlockIconFromMetadata' ) ) {
		return false;
	}

	if ( ! src.includes( 'registerBlockType' ) ) {
		return false;
	}

	if ( ! src.includes( "from './block.json'" ) && ! src.includes( 'from "./block.json"' ) ) {
		console.warn( 'skip (no block.json import):', filePath );
		return false;
	}

	// Insert import after block.json import line.
	src = src.replace(
		/(import metadata from ['"]\.\/block\.json['"];?\n)/,
		`$1${ importLine }\n`
	);

	// Add icon as first registration property when missing.
	src = src.replace(
		/registerBlockType\(\s*metadata,\s*\{\s*\n(\s*)(?!icon:)/,
		'registerBlockType( metadata, {\n$1icon: clanbiteBlockIconFromMetadata( metadata ),\n$1'
	);

	// Single-line or compact registrations.
	src = src.replace(
		/registerBlockType\(\s*metadata,\s*\{(?!\s*icon:)/,
		'registerBlockType( metadata, {\n\ticon: clanbiteBlockIconFromMetadata( metadata ),'
	);

	fs.writeFileSync( filePath, src );
	return true;
}

function walk( dir ) {
	let count = 0;
	for ( const entry of fs.readdirSync( dir, { withFileTypes: true } ) ) {
		const full = path.join( dir, entry.name );
		if ( entry.isDirectory() ) {
			count += walk( full );
		} else if ( entry.name === 'index.js' && full.includes( `${ path.sep }blocks${ path.sep }` ) ) {
			if ( patchFile( full ) ) {
				console.log( 'patched', path.relative( root, full ) );
				count++;
			}
		}
	}
	return count;
}

const patched = walk( blocksRoot );
console.log( `Done. Patched ${ patched } file(s).` );
