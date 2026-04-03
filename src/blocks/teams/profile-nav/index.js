import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import metadata from './block.json';
import './style.scss';

registerBlockType( metadata.name, {
	edit() {
		const blockProps = useBlockProps( {
			className: 'clanspress-team-profile-nav clanspress-team-profile-nav--editor',
			role: 'navigation',
			'aria-label': metadata.title,
		} );
		return (
			<nav { ...blockProps }>
				<ul className="clanspress-team-profile-nav__list">
					<li className="clanspress-team-profile-nav__item is-active">
						<span className="clanspress-team-profile-nav__link">Home</span>
					</li>
					<li className="clanspress-team-profile-nav__item">
						<span className="clanspress-team-profile-nav__link">…</span>
					</li>
				</ul>
			</nav>
		);
	},
} );
