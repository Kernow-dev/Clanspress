/**
 * User Navigation block editor script.
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import './style.scss';

import metadata from './block.json';
import { clanbiteBlockIcon } from '../../shared/block-icons/icons/user-nav';

registerBlockType( metadata, {
	icon: clanbiteBlockIcon(),
	edit: function Edit( { attributes, setAttributes } ) {
		const { avatarSize, showUsername } = attributes;
		const blockProps = useBlockProps( {
			className: 'clanbite-user-nav is-editor-preview',
		} );

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Settings', 'clanbite' ) }>
						<RangeControl
							label={ __( 'Avatar Size', 'clanbite' ) }
							value={ avatarSize }
							onChange={ ( value ) =>
								setAttributes( { avatarSize: value } )
							}
							min={ 20 }
							max={ 64 }
							step={ 2 }
						/>
						<ToggleControl
							label={ __( 'Show Username', 'clanbite' ) }
							help={ __(
								'Display the username next to the avatar.',
								'clanbite'
							) }
							checked={ showUsername }
							onChange={ ( value ) =>
								setAttributes( { showUsername: value } )
							}
							__nextHasNoMarginBottom
						/>
					</PanelBody>
				</InspectorControls>
				<div { ...blockProps }>
					<div className="clanbite-user-nav__trigger">
						<span
							className="clanbite-user-nav__avatar clanbite-user-nav__avatar--placeholder"
							style={ {
								width: avatarSize,
								height: avatarSize,
							} }
						></span>
						{ showUsername && (
							<span className="clanbite-user-nav__username">
								{ __( 'Username', 'clanbite' ) }
							</span>
						) }
						<svg
							xmlns="http://www.w3.org/2000/svg"
							viewBox="0 0 24 24"
							width="16"
							height="16"
							fill="currentColor"
							className="clanbite-user-nav__caret"
						>
							<path d="M7 10l5 5 5-5z" />
						</svg>
					</div>
				</div>
			</>
		);
	},
} );
