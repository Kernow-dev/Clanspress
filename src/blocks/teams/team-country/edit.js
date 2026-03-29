import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import './editor.scss';

export default function Edit() {
	return (
		<div { ...useBlockProps() }>
			<p className="clanspress-team-block-placeholder">
				{ __( 'Team country (single team template)', 'clanspress' ) }
			</p>
		</div>
	);
}
