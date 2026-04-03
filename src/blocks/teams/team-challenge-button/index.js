/**
 * Block editor script: Team challenge button (front behavior in view.js).
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './editor.scss';

function Edit() {
	return (
		<div { ...useBlockProps() }>
			<Placeholder
				icon="flag"
				label={ __( 'Team challenge button', 'clanspress' ) }
				instructions={ __(
					'On the team profile, this shows a Challenge button when the team accepts challenges and the Matches extension is enabled.',
					'clanspress'
				) }
			/>
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
} );
