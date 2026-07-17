import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import Save from './save';
import metadata from './block.json';
import { clanbiteBlockIcon } from '../../shared/block-icons/icons/team-cover';
import './style.scss';
import './editor.scss';

registerBlockType( metadata, {
	icon: clanbiteBlockIcon(),
	edit: Edit,
	save: Save,
} );
