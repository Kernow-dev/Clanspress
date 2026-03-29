import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const { prefix, postfix } = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Text', 'clanspress' ) }>
					<TextControl
						label={ __( 'Prefix', 'clanspress' ) }
						help={ __(
							'Shown before the number. Leave empty for the default “Losses”.',
							'clanspress'
						) }
						value={ prefix }
						onChange={ ( v ) =>
							setAttributes( { prefix: v ?? '' } )
						}
					/>
					<TextControl
						label={ __( 'Postfix', 'clanspress' ) }
						help={ __( 'Shown after the number.', 'clanspress' ) }
						value={ postfix }
						onChange={ ( v ) =>
							setAttributes( { postfix: v ?? '' } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps() }>
				<p className="clanspress-team-block-placeholder">
					{ __( 'Team losses', 'clanspress' ) }
				</p>
			</div>
		</>
	);
}
