/**
 * Player avatar block editor.
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { EntityLinkInspector } from '../../shared/entity-link-inspector';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const { allowFrontEndMediaEdit } = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Profile media', 'clanspress' ) }
					initialOpen={ false }
				>
					<ToggleControl
						label={ __( 'Allow front-end editing', 'clanspress' ) }
						help={ __(
							'When enabled, the profile owner can change their avatar from this block on the front end.',
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
