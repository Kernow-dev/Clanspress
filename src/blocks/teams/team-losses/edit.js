import { __ } from '@wordpress/i18n';
import { RichText, useBlockProps } from '@wordpress/block-editor';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const { prefix, postfix } = attributes;
	const blockProps = useBlockProps( {
		className:
			'clanspress-team-stat-edit clanspress-team-stat-edit--losses',
	} );

	return (
		<div { ...blockProps }>
			<RichText
				key="clanspress-team-losses-prefix"
				tagName="span"
				className="clanspress-team-stat__prefix"
				value={ prefix }
				onChange={ ( v ) => setAttributes( { prefix: v ?? '' } ) }
				placeholder={ __( 'Losses', 'clanspress' ) }
				allowedFormats={ [ 'core/bold', 'core/italic', 'core/link' ] }
			/>
			<span
				className="clanspress-team-stat__value clanspress-team-stat__value--editor-placeholder"
				aria-hidden="true"
			>
				0
			</span>
			<RichText
				key="clanspress-team-losses-postfix"
				tagName="span"
				className="clanspress-team-stat__postfix"
				value={ postfix }
				onChange={ ( v ) => setAttributes( { postfix: v ?? '' } ) }
				placeholder={ __( 'Postfix…', 'clanspress' ) }
				allowedFormats={ [ 'core/bold', 'core/italic', 'core/link' ] }
			/>
		</div>
	);
}
