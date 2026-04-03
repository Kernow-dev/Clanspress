import { __ } from '@wordpress/i18n';
import { RichText, useBlockProps } from '@wordpress/block-editor';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const prefixFromLegacyLabel =
		attributes.label && '' === attributes.prefix ? attributes.label : '';
	const prefixValue = attributes.prefix || prefixFromLegacyLabel || '';
	const postfixValue = attributes.postfix || '';

	const blockProps = useBlockProps( {
		className:
			'clanspress-team-stat-edit clanspress-team-stat-edit--members-count',
	} );

	const onPrefixChange = ( v ) => {
		const next = { prefix: v ?? '' };
		if ( attributes.label ) {
			next.label = '';
		}
		setAttributes( next );
	};

	return (
		<div { ...blockProps }>
			<RichText
				key="clanspress-team-members-prefix"
				tagName="span"
				className="clanspress-team-members-count__prefix"
				value={ prefixValue }
				onChange={ onPrefixChange }
				placeholder={ __( 'Members', 'clanspress' ) }
				allowedFormats={ [ 'core/bold', 'core/italic', 'core/link' ] }
			/>
			<span
				className="clanspress-team-members-count__value clanspress-team-members-count__value--editor-placeholder"
				aria-hidden="true"
			>
				0
			</span>
			<RichText
				key="clanspress-team-members-postfix"
				tagName="span"
				className="clanspress-team-members-count__postfix"
				value={ postfixValue }
				onChange={ ( v ) => setAttributes( { postfix: v ?? '' } ) }
				placeholder={ __( 'Postfix…', 'clanspress' ) }
				allowedFormats={ [ 'core/bold', 'core/italic', 'core/link' ] }
			/>
		</div>
	);
}
