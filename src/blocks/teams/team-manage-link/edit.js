import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const { label } = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Link text', 'clanspress' ) }>
					<TextControl
						label={ __( 'Label', 'clanspress' ) }
						help={ __(
							'Leave empty to use the default “Manage team” label on the front end.',
							'clanspress'
						) }
						value={ label }
						onChange={ ( value ) =>
							setAttributes( { label: value ?? '' } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps() }>
				<p className="clanspress-team-manage-link clanspress-team-manage-link--editor-preview">
					<a
						href="#clanspress-team-manage-link-preview"
						onClick={ ( e ) => e.preventDefault() }
					>
						{ label || __( 'Manage team', 'clanspress' ) }
					</a>
				</p>
				<p className="clanspress-team-block-placeholder">
					{ __(
						'On the site, this link only appears for users who can edit this team.',
						'clanspress'
					) }
				</p>
			</div>
		</>
	);
}
