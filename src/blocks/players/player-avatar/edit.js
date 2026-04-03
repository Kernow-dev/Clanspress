/**
 * Player avatar block editor.
 */
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
				toggleLabel={ __( 'Link image to player profile', 'clanspress' ) }
			/>
			<div { ...useBlockProps() }>
				<p>{ __( 'Player avatar block', 'clanspress' ) }</p>
			</div>
		</>
	);
}
