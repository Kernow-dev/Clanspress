const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		'clanspress-admin': path.resolve( __dirname, 'src', 'admin', 'index.js' ),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'assets', 'dist' ),
	},
	plugins: ( defaultConfig.plugins || [] ).filter( ( plugin ) => {
		const n = plugin?.constructor?.name || '';
		// Avoid copying block assets / PHP from the whole plugin tree into dist/.
		return n !== 'CopyPlugin' && n !== 'PhpFilePathsPlugin';
	} ),
};
