/**
 * Notification Bell block editor script.
 */
import { registerBlockType } from '@wordpress/blocks';

import './style.scss';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: function Edit( { attributes, setAttributes } ) {
		const { showDropdown, dropdownCount } = attributes;
		const blockProps = useBlockProps( {
			className: 'clanspress-notification-bell',
		} );

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Settings', 'clanspress' ) }>
						<ToggleControl
							label={ __(
								'Show dropdown on click',
								'clanspress'
							) }
							checked={ showDropdown }
							onChange={ ( value ) =>
								setAttributes( { showDropdown: value } )
							}
							__nextHasNoMarginBottom
						/>
						{ showDropdown && (
							<RangeControl
								label={ __(
									'Notifications in dropdown',
									'clanspress'
								) }
								value={ dropdownCount }
								onChange={ ( value ) =>
									setAttributes( { dropdownCount: value } )
								}
								min={ 3 }
								max={ 20 }
							/>
						) }
					</PanelBody>
				</InspectorControls>
				<div { ...blockProps }>
					<button
						type="button"
						className="clanspress-notification-bell__trigger"
						aria-label={ __( 'Notifications', 'clanspress' ) }
					>
						<svg
							xmlns="http://www.w3.org/2000/svg"
							viewBox="0 0 24 24"
							width="24"
							height="24"
							fill="currentColor"
						>
							<path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2zm-2 1H8v-6c0-2.48 1.51-4.5 4-4.5s4 2.02 4 4.5v6z" />
						</svg>
						<span className="clanspress-notification-bell__badge">
							3
						</span>
					</button>
				</div>
			</>
		);
	},
} );
