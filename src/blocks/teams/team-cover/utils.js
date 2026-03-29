/**
 * Shared helpers for team cover (aligned with core cover / player-cover positioning).
 */

/**
 * @param {string|undefined} contentPosition e.g. "top left", "center center".
 * @return {string} Class names for content position.
 */
export function getPositionClassName( contentPosition ) {
	if ( ! contentPosition ) {
		return '';
	}
	const parts = contentPosition.split( ' ' );
	const y = parts[ 0 ];
	const x = parts[ 1 ] ?? parts[ 0 ];
	const classes = [];
	if ( y ) {
		classes.push( `is-position-${ y }` );
	}
	if ( x && x !== y ) {
		classes.push( `is-position-${ x }` );
	}
	return classes.join( ' ' );
}

/**
 * @param {string|undefined} contentPosition
 * @return {boolean} True when position is default center.
 */
export function isContentPositionCenter( contentPosition ) {
	return ! contentPosition || contentPosition === 'center center';
}
