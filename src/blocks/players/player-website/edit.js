import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { EntityLinkInspector } from '../../shared/entity-link-inspector';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	return (
		<>
			<EntityLinkInspector
				attributes={ attributes }
				setAttributes={ setAttributes }
				toggleLabel={ __( 'Link to website URL', 'clanspress' ) }
			/>
			<div { ...useBlockProps() }>
				<p className="clanspress-player-block-placeholder">
					{ __(
						'Player website (only shows when the player has set one)',
						'clanspress'
					) }
				</p>
			</div>
		</>
	);
}
