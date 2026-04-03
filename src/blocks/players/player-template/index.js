/**
 * Player template block: inner layout for each roster member (like core Post template).
 */
import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

import Edit from './edit';
import metadata from './block.json';
import './style.scss';

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => <InnerBlocks.Content />,
} );
