import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, SelectControl } from '@wordpress/components';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const { showCode, countryDisplay, flagFirst } = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Country display', 'clanspress' ) }>
					<SelectControl
						label={ __( 'Show', 'clanspress' ) }
						value={ countryDisplay || 'both' }
						options={ [
							{
								label: __( 'Flag and country', 'clanspress' ),
								value: 'both',
							},
							{
								label: __( 'Flag only', 'clanspress' ),
								value: 'flag',
							},
							{
								label: __( 'Country only', 'clanspress' ),
								value: 'text',
							},
						] }
						onChange={ ( v ) =>
							setAttributes( { countryDisplay: v || 'both' } )
						}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					{ countryDisplay === 'both' || ! countryDisplay ? (
						<SelectControl
							label={ __( 'Order', 'clanspress' ) }
							value={ flagFirst ? 'flag' : 'text' }
							options={ [
								{
									label: __( 'Flag first', 'clanspress' ),
									value: 'flag',
								},
								{
									label: __( 'Country first', 'clanspress' ),
									value: 'text',
								},
							] }
							onChange={ ( v ) =>
								setAttributes( { flagFirst: v === 'flag' } )
							}
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
					) : null }
					<ToggleControl
						label={ __(
							'Show country code in text',
							'clanspress'
						) }
						checked={ !! showCode }
						onChange={ ( v ) =>
							setAttributes( { showCode: !! v } )
						}
						help={ __(
							'When the country name is shown, append the ISO code in parentheses.',
							'clanspress'
						) }
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>
			<div
				{ ...useBlockProps( {
					className: 'clanspress-team-country-editor',
				} ) }
			>
				<div className="clanspress-country-display clanspress-country-display--preview">
					{ ( countryDisplay === 'both' || ! countryDisplay ) && (
						<>
							{ flagFirst ? (
								<>
									<span
										className="clanspress-country-flag clanspress-country-flag--preview"
										aria-hidden="true"
									/>
									<span className="clanspress-country-display__label">
										{ __( 'United Kingdom', 'clanspress' ) }
									</span>
								</>
							) : (
								<>
									<span className="clanspress-country-display__label">
										{ __( 'United Kingdom', 'clanspress' ) }
									</span>
									<span
										className="clanspress-country-flag clanspress-country-flag--preview"
										aria-hidden="true"
									/>
								</>
							) }
						</>
					) }
					{ countryDisplay === 'text' && (
						<span className="clanspress-country-display__label">
							{ __( 'United Kingdom', 'clanspress' ) }
						</span>
					) }
					{ countryDisplay === 'flag' && (
						<span
							className="clanspress-country-flag clanspress-country-flag--preview"
							aria-hidden="true"
						/>
					) }
				</div>
				<p className="clanspress-team-block-placeholder">
					{ __(
						'Team country (single team template)',
						'clanspress'
					) }
				</p>
			</div>
		</>
	);
}
