/**
 * Player template editor: list/grid toolbar and layout like core Post template.
 */
import clsx from 'clsx';
import { useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { _x } from '@wordpress/i18n';
import {
	BlockControls,
	BlockContextProvider,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { ToolbarButton, ToolbarGroup } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';

import './editor.scss';

function ListLayoutIcon() {
	return (
		<svg
			xmlns="http://www.w3.org/2000/svg"
			width="24"
			height="24"
			viewBox="0 0 24 24"
			aria-hidden="true"
		>
			<path
				fill="currentColor"
				d="M4 4h16v2H4V4zm0 5h16v2H4V9zm0 5h16v2H4v-2z"
			/>
		</svg>
	);
}

function GridLayoutIcon() {
	return (
		<svg
			xmlns="http://www.w3.org/2000/svg"
			width="24"
			height="24"
			viewBox="0 0 24 24"
			aria-hidden="true"
		>
			<path
				fill="currentColor"
				d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z"
			/>
		</svg>
	);
}

const INNER_TEMPLATE = [
	[ 'clanspress/player-avatar', { avatarPreset: 'medium' } ],
	[ 'clanspress/player-display-name' ],
];

export default function PlayerTemplateEdit( {
	attributes,
	setAttributes,
	context,
	__unstableLayoutClassNames,
} ) {
	const { layout } = attributes;
	const { type: layoutType, columnCount = 3 } = layout || {};

	const previewUserId = useSelect( ( select ) => {
		const user = select( coreStore ).getCurrentUser();
		return user?.id ? Number( user.id ) : 0;
	}, [] );

	const previewContext = useMemo( () => {
		const base =
			context && typeof context === 'object' ? { ...context } : {};
		if ( previewUserId > 0 ) {
			base[ 'clanspress/playerId' ] = previewUserId;
		}
		return base;
	}, [ context, previewUserId ] );

	const blockProps = useBlockProps( {
		className: clsx( __unstableLayoutClassNames, {
			[ `columns-${ columnCount }` ]:
				layoutType === 'grid' && columnCount,
		} ),
	} );

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'clanspress-player-template__preview-inner',
		},
		{
			template: INNER_TEMPLATE,
			__unstableDisableLayoutClassNames: true,
		}
	);

	const setDisplayLayout = ( partial ) =>
		setAttributes( {
			layout: { ...layout, ...partial },
		} );

	const listLabel = _x(
		'List view',
		'Player template block display setting',
		'clanspress'
	);
	const gridLabel = _x(
		'Grid view',
		'Player template block display setting',
		'clanspress'
	);
	const listActive =
		layoutType === 'default' ||
		layoutType === 'constrained' ||
		! layoutType;

	return (
		<>
			<BlockControls>
				<ToolbarGroup>
					<ToolbarButton
						icon={ ListLayoutIcon }
						label={ listLabel }
						isPressed={ listActive }
						onClick={ () =>
							setDisplayLayout( { type: 'default' } )
						}
					/>
					<ToolbarButton
						icon={ GridLayoutIcon }
						label={ gridLabel }
						isPressed={ layoutType === 'grid' }
						onClick={ () =>
							setDisplayLayout( {
								type: 'grid',
								columnCount,
							} )
						}
					/>
				</ToolbarGroup>
			</BlockControls>
			<ul { ...blockProps }>
				<BlockContextProvider value={ previewContext }>
					<li { ...innerBlocksProps } />
				</BlockContextProvider>
			</ul>
		</>
	);
}
