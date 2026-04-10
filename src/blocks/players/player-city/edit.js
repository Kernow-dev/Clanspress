import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import './editor.scss';

export default function Edit() {
	return (
		<div { ...useBlockProps() }>
			<p className="clanspress-player-block-placeholder">
				{ __(
					'Player city (only shows when the player has set one)',
					'clanspress'
				) }
			</p>
		</div>
	);
}
