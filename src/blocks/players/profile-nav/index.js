import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import metadata from './block.json';
import { clanbiteBlockIcon } from '../../shared/block-icons/icons/profile-nav';
import './style.scss';

registerBlockType( metadata, {
	icon: clanbiteBlockIcon(),
	edit() {
		const blockProps = useBlockProps( {
			className:
				'clanbite-player-profile-nav clanbite-player-profile-nav--editor',
			role: 'navigation',
			'aria-label': metadata.title,
		} );
		return (
			<nav { ...blockProps }>
				<ul className="clanbite-player-profile-nav__list">
					<li className="clanbite-player-profile-nav__item is-active">
						<span className="clanbite-player-profile-nav__link">
							Home
						</span>
					</li>
					<li className="clanbite-player-profile-nav__item">
						<span className="clanbite-player-profile-nav__link">
							…
						</span>
					</li>
				</ul>
			</nav>
		);
	},
} );
