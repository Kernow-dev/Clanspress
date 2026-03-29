/**
 * Match card block: choose match post ID.
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
	const { matchId } = attributes;

	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<PanelBody title={ __( 'Match card', 'clanspress' ) }>
					<TextControl
						label={ __( 'Match post ID', 'clanspress' ) }
						help={ __(
							'The `cp_match` post ID to display.',
							'clanspress'
						) }
						type="number"
						value={ matchId || '' }
						onChange={ ( v ) =>
							setAttributes( { matchId: parseInt( v, 10 ) || 0 } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<p className="clanspress-match-card-editor-note">
				{ __( 'Match card (preview on the front end).', 'clanspress' ) }
			</p>
		</div>
	);
}
