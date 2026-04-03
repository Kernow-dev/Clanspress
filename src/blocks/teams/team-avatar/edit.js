import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
} from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { EntityLinkInspector } from '../../shared/entity-link-inspector';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const { allowFrontEndMediaEdit } = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Team media', 'clanspress' ) }
					initialOpen={ false }
				>
					<ToggleControl
						label={ __(
							'Allow front-end editing',
							'clanspress'
						) }
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
