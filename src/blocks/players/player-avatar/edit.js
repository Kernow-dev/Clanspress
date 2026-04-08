/**
 * Player avatar block editor.
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { EntityLinkInspector } from '../../shared/entity-link-inspector';
import './editor.scss';

const AVATAR_PRESET_OPTIONS = [
	{
		label: __( 'Large — profiles', 'clanspress' ),
		value: 'large',
	},
	{
		label: __( 'Medium — feeds & lists', 'clanspress' ),
		value: 'medium',
	},
	{
		label: __( 'Small — compact UI', 'clanspress' ),
		value: 'small',
	},
];

export default function Edit( { attributes, setAttributes } ) {
	const { avatarPreset } = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Avatar output', 'clanspress' ) }>
					<SelectControl
						label={ __( 'Image size preset', 'clanspress' ) }
						help={ __(
							'Uses the matching size from Clanspress → Players → Player avatar image sizes.',
							'clanspress'
						) }
						value={ avatarPreset || 'large' }
						options={ AVATAR_PRESET_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( {
								avatarPreset: value || 'large',
							} )
						}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>
			<EntityLinkInspector
				attributes={ attributes }
				setAttributes={ setAttributes }
				toggleLabel={ __(
					'Link image to player profile',
					'clanspress'
				) }
			/>
			<div { ...useBlockProps() }>
				<p>{ __( 'Player avatar block', 'clanspress' ) }</p>
			</div>
		</>
	);
}
