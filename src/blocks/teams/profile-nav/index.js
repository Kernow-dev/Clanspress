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
				'clanbite-team-profile-nav clanbite-team-profile-nav--editor',
			role: 'navigation',
			'aria-label': metadata.title,
		} );
		return (
			<nav { ...blockProps }>
				<ul className="clanbite-team-profile-nav__list">
					<li className="clanbite-team-profile-nav__item is-active">
						<span className="clanbite-team-profile-nav__link">
							Home
						</span>
					</li>
					<li className="clanbite-team-profile-nav__item">
						<span className="clanbite-team-profile-nav__link">
							…
						</span>
					</li>
				</ul>
			</nav>
		);
	},
} );
