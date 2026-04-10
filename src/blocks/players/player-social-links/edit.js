import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const { iconSize } = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Icon size', 'clanspress' ) }>
					<SelectControl
						label={ __( 'Size', 'clanspress' ) }
						value={ iconSize }
						options={ [
							{
								label: __( 'Small', 'clanspress' ),
								value: 'small',
							},
							{
								label: __( 'Medium', 'clanspress' ),
								value: 'medium',
							},
							{
								label: __( 'Large', 'clanspress' ),
								value: 'large',
							},
						] }
						onChange={ ( v ) => setAttributes( { iconSize: v } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps() }>
				<p className="clanspress-player-block-placeholder">
					{ __(
						'Player social links (icons from Profile → Social Networks)',
						'clanspress'
					) }
				</p>
			</div>
		</>
	);
}
