import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import metadata from './block.json';
import { clanbiteBlockIcon } from '../../shared/block-icons/icons/team-wins';
import './style.scss';
import './editor.scss';

registerBlockType( metadata, {
	icon: clanbiteBlockIcon(),
	edit: Edit,
} );
