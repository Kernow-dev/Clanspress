/**
 * Factory for crisp block inserter icons (2× render, 1× display).
 *
 * @package clanbite
 */

import { createElement } from '@wordpress/element';

/** Display size in the block inserter (px). */
const DISPLAY_PX = 24;

/** Internal SVG resolution (2× display for sharper edges on HiDPI). */
const RENDER_PX = 48;

/**
 * @param {import('react').ComponentType<import('react').SVGProps<SVGSVGElement>>} IconComponent Heroicon component.
 * @return {() => import('react').ReactElement} Block icon factory.
 */
export function createClanbiteBlockIcon( IconComponent ) {
	return function clanbiteBlockIcon() {
		return createElement( IconComponent, {
			width: RENDER_PX,
			height: RENDER_PX,
			'aria-hidden': true,
			'data-clanbite-icon': 'true',
			style: {
				width: DISPLAY_PX,
				height: DISPLAY_PX,
				display: 'block',
				flexShrink: 0,
			},
		} );
	};
}
