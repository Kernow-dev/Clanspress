import { registerBlockType } from '@wordpress/blocks';
import metadata from './block.json';
import { clanbiteBlockIcon } from '../../shared/block-icons/icons/event-calendar';
import Edit from './edit';
import './style.scss';

registerBlockType( metadata, {
	icon: clanbiteBlockIcon(),
	edit: Edit,
	save: () => null,
} );
