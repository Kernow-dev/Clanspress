/**
 * Visibility container: server-rendered wrapper with InnerBlocks.
 */
import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

import Edit from './edit';
import metadata from './block.json';
import { clanbiteBlockIcon } from '../../shared/block-icons/icons/visibility-container';
import './style.scss';

registerBlockType( metadata, {
	icon: clanbiteBlockIcon(),
	edit: Edit,
	save: () => <InnerBlocks.Content />,
} );
