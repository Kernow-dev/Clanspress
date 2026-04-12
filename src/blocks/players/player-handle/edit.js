import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import './editor.scss';

export default function Edit() {
	return (
		<div { ...useBlockProps() }>
			<p className="clanspress-player-block-placeholder">
				<span className="clanspress-player-handle__text">@username</span>
				{ ' ' }
				<span className="clanspress-player-handle__hint">
					{ __(
						'(shown when the player has a nicename; hidden on the site if empty)',
						'clanspress'
					) }
				</span>
			</p>
		</div>
	);
}
