import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function Save( { attributes } ) {
	const { teamName, gameTitle, description } = attributes;

	return (
		<div { ...useBlockProps.save() }>
			<RichText.Content tagName="h3" value={ teamName } />
			{ gameTitle ? (
				<RichText.Content tagName="p" value={ gameTitle } />
			) : null }
			{ description ? (
				<RichText.Content tagName="p" value={ description } />
			) : null }
		</div>
	);
}
