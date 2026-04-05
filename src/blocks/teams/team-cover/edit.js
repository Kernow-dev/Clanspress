/* eslint-disable @wordpress/no-unsafe-wp-apis -- BlockAlignmentMatrixControl matches player cover / core cover. */
/**
 * WordPress dependencies
 */
import {
	useBlockProps,
	useInnerBlocksProps,
	store as blockEditorStore,
	BlockControls,
	InspectorControls,
	__experimentalBlockAlignmentMatrixControl as BlockAlignmentMatrixControl,
} from '@wordpress/block-editor';
import { Placeholder, PanelBody, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';

import { getPositionClassName, isContentPositionCenter } from './utils';

import './editor.scss';

export default function Edit( { attributes, clientId, setAttributes } ) {
	const { allowFrontEndMediaEdit, contentPosition, templateLock } =
		attributes;

	const hasInnerBlocks = useSelect(
		( select ) =>
			select( blockEditorStore ).getBlock( clientId ).innerBlocks.length >
			0,
		[ clientId ]
	);

	const classes = clsx(
		{
			'has-custom-content-position':
				! isContentPositionCenter( contentPosition ),
		},
		getPositionClassName( contentPosition )
	);

	const blockProps = useBlockProps();

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'team-cover__inner-container',
		},
		{
			template: ! hasInnerBlocks ? [] : undefined,
			templateInsertUpdatesSelection: true,
			templateLock,
		}
	);

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Team media', 'clanspress' ) }
					initialOpen={ false }
				>
					<ToggleControl
						label={ __( 'Allow front-end editing', 'clanspress' ) }
						help={ __(
							'When enabled, team managers can change the cover from this block on the front end.',
							'clanspress'
						) }
						checked={ !! allowFrontEndMediaEdit }
						onChange={ ( value ) =>
							setAttributes( {
								allowFrontEndMediaEdit: value,
							} )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<BlockControls group="block">
				<BlockAlignmentMatrixControl
					label={ __( 'Change content position', 'clanspress' ) }
					value={ contentPosition }
					onChange={ ( nextPosition ) =>
						setAttributes( {
							contentPosition: nextPosition,
						} )
					}
					isDisabled={ ! hasInnerBlocks }
				/>
			</BlockControls>
			<div
				{ ...blockProps }
				className={ clsx( classes, blockProps.className ) }
			>
				<Placeholder
					className="team-cover__background--placeholder"
					withIllustration
				/>
				<div { ...innerBlocksProps } />
			</div>
		</>
	);
}
