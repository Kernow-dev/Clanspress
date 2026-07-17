/**
 * Block editor script: Team challenge button (front behavior in view.js).
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import { clanbiteBlockIcon } from '../../shared/block-icons/icons/team-challenge-button';
import './editor.scss';

function Edit() {
	return (
		<div { ...useBlockProps() }>
			<Placeholder
				icon={ clanbiteBlockIcon() }
				label={ __( 'Team challenge button', 'clanbite' ) }
				instructions={ __(
					'On the team profile, this shows a Challenge button when the team accepts challenges and the Matches extension is enabled.',
					'clanbite'
				) }
			/>
		</div>
	);
}

registerBlockType( metadata, {
	icon: clanbiteBlockIcon(),
	edit: Edit,
} );
