import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import './editor.scss';

export default function Edit() {
	return (
		<div { ...useBlockProps() }>
			<p className="clanspress-team-block-placeholder">
				{ __(
					'Team description — uses the team post content.',
					'clanspress'
				) }
			</p>
		</div>
	);
}
