import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, SelectControl } from '@wordpress/components';
import { EntityLinkInspector } from '../../shared/entity-link-inspector';
import './editor.scss';

const AVATAR_PRESET_OPTIONS = [
	{
		label: __( 'Large — team profiles', 'clanspress' ),
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
	const { allowFrontEndMediaEdit, avatarPreset } = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Avatar output', 'clanspress' ) }>
					<SelectControl
						label={ __( 'Image size preset', 'clanspress' ) }
						help={ __(
							'Uses the matching size from Clanspress → Teams → Team avatar image sizes.',
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
				<PanelBody
					title={ __( 'Team media', 'clanspress' ) }
					initialOpen={ false }
				>
					<ToggleControl
						label={ __( 'Allow front-end editing', 'clanspress' ) }
						help={ __(
							'When enabled, team managers can change the avatar from this block on the front end.',
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
			<EntityLinkInspector
				attributes={ attributes }
				setAttributes={ setAttributes }
				toggleLabel={ __( 'Link image to team profile', 'clanspress' ) }
			/>
			<div { ...useBlockProps() }>
				<p className="clanspress-team-block-placeholder">
					{ __( 'Team avatar (single team template)', 'clanspress' ) }
				</p>
			</div>
		</>
	);
}
