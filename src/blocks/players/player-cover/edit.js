/**
 * WordPress dependencies
 */
import {
	useBlockProps,
	useInnerBlocksProps,
	store as blockEditorStore,
	BlockControls,
	__experimentalBlockAlignmentMatrixControl as BlockAlignmentMatrixControl,
	__experimentalBlockFullHeightAligmentControl as FullHeightAlignmentControl,
} from '@wordpress/block-editor';
import { useRef, useMemo, useState } from '@wordpress/element';
import { Placeholder } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';
import {
	getPositionClassName,
	isContentPositionCenter,
	cleanEmptyObject,
} from '../utils';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @param  root0
 * @param  root0.attributes
 * @param  root0.clientId
 * @param  root0.context
 * @param  root0.context.postId
 * @param  root0.context.postType
 * @param  root0.setAttributes
 * @param  root0.toggleSelection
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit( {
	attributes,
	clientId,
	context: { postId, postType },
	setAttributes,
	toggleSelection,
} ) {
	const { contentPosition, id, minHeight, minHeightUnit, allowedBlocks, templateLock } =
		attributes;

	const [ prevMinHeightValue, setPrevMinHeightValue ] = useState( minHeight );
	const [ prevMinHeightUnit, setPrevMinHeightUnit ] =
		useState( minHeightUnit );
	const [ isEmbedUrlInputOpen, setIsEmbedUrlInputOpen ] = useState( false );
	const isMinFullHeight =
		minHeightUnit === 'vh' &&
		minHeight === 100 &&
		! attributes?.style?.dimensions?.aspectRatio;

	const hasInnerBlocks = useSelect(
		( select ) =>
			select( blockEditorStore ).getBlock( clientId ).innerBlocks.length >
			0,
		[ clientId ]
	);

	const toggleMinFullHeight = () => {
		if ( isMinFullHeight ) {
			// If there aren't previous values, take the default ones.
			if ( prevMinHeightUnit === 'vh' && prevMinHeightValue === 100 ) {
				return setAttributes( {
					minHeight: undefined,
					minHeightUnit: undefined,
				} );
			}

			// Set the previous values of height.
			return setAttributes( {
				minHeight: prevMinHeightValue,
				minHeightUnit: prevMinHeightUnit,
			} );
		}

		setPrevMinHeightValue( minHeight );
		setPrevMinHeightUnit( minHeightUnit );

		// Set full height, and clear any aspect ratio value.
		return setAttributes( {
			minHeight: 100,
			minHeightUnit: 'vh',
			style: cleanEmptyObject( {
				...attributes?.style,
				dimensions: {
					...attributes?.style?.dimensions,
					aspectRatio: undefined, // Reset aspect ratio when minHeight is set.
				},
			} ),
		} );
	};

	const ref = useRef();

	const classes = clsx(
		{
			'has-custom-content-position':
				! isContentPositionCenter( contentPosition ),
		},
		getPositionClassName( contentPosition )
	);

	const blockProps = useBlockProps( { ref } );

	const innerBlocksTemplate = [];

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'player-cover__inner-container',
		},
		{
			// Avoid template sync when the `templateLock` value is `all` or `contentOnly`.
			// See: https://github.com/WordPress/gutenberg/pull/45632
			template: ! hasInnerBlocks ? innerBlocksTemplate : undefined,
			templateInsertUpdatesSelection: true,
			allowedBlocks,
			templateLock,
		}
	);

	return (
		<>
			<BlockControls group="block">
				<BlockAlignmentMatrixControl
					label={ __( 'Change content position' ) }
					value={ contentPosition }
					onChange={ ( nextPosition ) =>
						setAttributes( {
							contentPosition: nextPosition,
						} )
					}
					isDisabled={ ! hasInnerBlocks }
				/>
				<FullHeightAlignmentControl
					isActive={ isMinFullHeight }
					onToggle={ toggleMinFullHeight }
					isDisabled={ ! hasInnerBlocks }
				/>
			</BlockControls>
			<div
				{ ...blockProps }
				className={ clsx( classes, blockProps.className ) }
			>
				<Placeholder
					className="player-cover__image-background--placeholder-image"
					withIllustration
				/>
				<div { ...innerBlocksProps } />
			</div>
		</>
	);
}
