import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

export default function Edit() {
	return (
		<div { ...useBlockProps() }>
			<p>{ __( 'Team create form block', 'clanspress' ) }</p>
			<p>
				{ __( 'Rendered dynamically on the front end.', 'clanspress' ) }
			</p>
		</div>
	);
}
