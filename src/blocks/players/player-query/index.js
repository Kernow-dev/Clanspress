/**
 * Player query block: provides team roster context (like core Query).
 */
import { registerBlockType } from '@wordpress/blocks';
import {
	InnerBlocks,
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	TextControl,
	SelectControl,
	TextareaControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';

const TEMPLATE = [
	[
		'clanspress/player-template',
		{},
		[
			[ 'clanspress/player-avatar', { avatarPreset: 'medium' } ],
			[ 'clanspress/player-display-name' ],
		],
	],
];

const ORDERBY_OPTIONS = [
	{
		label: __( 'Default (sorted user ID)', 'clanspress' ),
		value: 'default',
	},
	{
		label: __( 'Roster order (member map)', 'clanspress' ),
		value: 'roster',
	},
	{
		label: __( 'User ID', 'clanspress' ),
		value: 'id',
	},
	{
		label: __( 'Display name', 'clanspress' ),
		value: 'display_name',
	},
	{
		label: __( 'Username (login)', 'clanspress' ),
		value: 'login',
	},
	{
		label: __( 'Nicename', 'clanspress' ),
		value: 'nicename',
	},
	{
		label: __( 'Email', 'clanspress' ),
		value: 'email',
	},
	{
		label: __( 'URL', 'clanspress' ),
		value: 'url',
	},
	{
		label: __( 'Registered', 'clanspress' ),
		value: 'registered',
	},
	{
		label: __( 'Post count', 'clanspress' ),
		value: 'post_count',
	},
	{
		label: __( 'Random', 'clanspress' ),
		value: 'rand',
	},
	{
		label: __( 'User meta value', 'clanspress' ),
		value: 'meta_value',
	},
	{
		label: __( 'User meta value (numeric)', 'clanspress' ),
		value: 'meta_value_num',
	},
];

registerBlockType( metadata.name, {
	edit: ( { attributes, setAttributes } ) => {
		const {
			teamId,
			inheritTeamContext,
			excludeBannedMembers,
			queryOrderby,
			queryOrder,
			queryMetaKey,
			queryPerPage,
			queryOffset,
			queryMetaQueryJson,
			queryExcludeUsers,
			queryExcludeCurrentUser,
			queryExcludeRoles,
			queryExcludeMetaQueryJson,
		} = attributes;

		const blockProps = useBlockProps( {
			className: 'clanspress-player-query',
		} );

		const needsMetaKey =
			queryOrderby === 'meta_value' || queryOrderby === 'meta_value_num';

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Team roster', 'clanspress' ) }>
						<ToggleControl
							label={ __(
								'Inherit team from template',
								'clanspress'
							) }
							checked={ inheritTeamContext }
							onChange={ ( v ) =>
								setAttributes( { inheritTeamContext: v } )
							}
							help={ __(
								'Uses the current team on singular team pages, or each team when this block appears inside a Query Loop.',
								'clanspress'
							) }
							__nextHasNoMarginBottom
						/>
						{ ! inheritTeamContext && (
							<TextControl
								label={ __( 'Team post ID', 'clanspress' ) }
								value={ teamId ? String( teamId ) : '' }
								onChange={ ( v ) =>
									setAttributes( {
										teamId: parseInt( v, 10 ) || 0,
									} )
								}
								type="number"
								min={ 0 }
								__next40pxDefaultSize
								__nextHasNoMarginBottom
							/>
						) }
						<ToggleControl
							label={ __(
								'Exclude banned members',
								'clanspress'
							) }
							checked={ excludeBannedMembers }
							onChange={ ( v ) =>
								setAttributes( { excludeBannedMembers: v } )
							}
							__nextHasNoMarginBottom
						/>
					</PanelBody>
					<PanelBody
						title={ __( 'Query', 'clanspress' ) }
						initialOpen={ false }
					>
						<SelectControl
							label={ __( 'Order by', 'clanspress' ) }
							value={ queryOrderby || 'default' }
							options={ ORDERBY_OPTIONS }
							onChange={ ( v ) =>
								setAttributes( {
									queryOrderby: v || 'default',
								} )
							}
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
						<SelectControl
							label={ __( 'Order', 'clanspress' ) }
							value={ queryOrder === 'DESC' ? 'DESC' : 'ASC' }
							options={ [
								{
									label: __( 'Ascending', 'clanspress' ),
									value: 'ASC',
								},
								{
									label: __( 'Descending', 'clanspress' ),
									value: 'DESC',
								},
							] }
							onChange={ ( v ) =>
								setAttributes( { queryOrder: v || 'ASC' } )
							}
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
						{ needsMetaKey && (
							<TextControl
								label={ __(
									'Meta key (for sort)',
									'clanspress'
								) }
								value={ queryMetaKey || '' }
								onChange={ ( v ) =>
									setAttributes( { queryMetaKey: v ?? '' } )
								}
								help={ __(
									'Required when ordering by user meta.',
									'clanspress'
								) }
								__next40pxDefaultSize
								__nextHasNoMarginBottom
							/>
						) }
						<TextControl
							label={ __(
								'Max members (0 = all)',
								'clanspress'
							) }
							type="number"
							min={ 0 }
							value={ queryPerPage ? String( queryPerPage ) : '' }
							onChange={ ( v ) =>
								setAttributes( {
									queryPerPage: parseInt( v, 10 ) || 0,
								} )
							}
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
						<TextControl
							label={ __( 'Offset', 'clanspress' ) }
							type="number"
							min={ 0 }
							value={ queryOffset ? String( queryOffset ) : '' }
							onChange={ ( v ) =>
								setAttributes( {
									queryOffset: parseInt( v, 10 ) || 0,
								} )
							}
							help={ __(
								'Skip this many members after filters (ordering applies first when using Order by).',
								'clanspress'
							) }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
						<TextareaControl
							label={ __(
								'Filter by meta (JSON)',
								'clanspress'
							) }
							value={ queryMetaQueryJson || '' }
							onChange={ ( v ) =>
								setAttributes( { queryMetaQueryJson: v ?? '' } )
							}
							help={ __(
								'Optional. Same shape as a WordPress meta_query: keep only roster members who match (e.g. [{"key":"country","value":"UK","compare":"="}]).',
								'clanspress'
							) }
							rows={ 4 }
							__nextHasNoMarginBottom
						/>
					</PanelBody>
					<PanelBody
						title={ __( 'Exclusions (advanced)', 'clanspress' ) }
						initialOpen={ false }
					>
						<TextControl
							label={ __( 'Exclude user IDs', 'clanspress' ) }
							value={ queryExcludeUsers || '' }
							onChange={ ( v ) =>
								setAttributes( { queryExcludeUsers: v ?? '' } )
							}
							help={ __(
								'Comma- or space-separated WordPress user IDs (like excluding posts in Query Loop).',
								'clanspress'
							) }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
						<ToggleControl
							label={ __(
								'Exclude the current user',
								'clanspress'
							) }
							checked={ !! queryExcludeCurrentUser }
							onChange={ ( v ) =>
								setAttributes( {
									queryExcludeCurrentUser: !! v,
								} )
							}
							help={ __(
								'When someone is logged in, they are omitted from the list.',
								'clanspress'
							) }
							__nextHasNoMarginBottom
						/>
						<TextControl
							label={ __( 'Exclude team roles', 'clanspress' ) }
							value={ queryExcludeRoles || '' }
							onChange={ ( v ) =>
								setAttributes( { queryExcludeRoles: v ?? '' } )
							}
							help={ __(
								'Comma-separated roster role slugs (e.g. admin, editor, member). Separate from “Exclude banned members”.',
								'clanspress'
							) }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
						<TextareaControl
							label={ __(
								'Exclude by meta (JSON)',
								'clanspress'
							) }
							value={ queryExcludeMetaQueryJson || '' }
							onChange={ ( v ) =>
								setAttributes( {
									queryExcludeMetaQueryJson: v ?? '',
								} )
							}
							help={ __(
								'Members who match this meta_query are removed from the roster list (opposite of “Filter by meta”).',
								'clanspress'
							) }
							rows={ 4 }
							__nextHasNoMarginBottom
						/>
					</PanelBody>
				</InspectorControls>
				<div { ...blockProps }>
					<p
						className="clanspress-player-query__hint"
						style={ {
							margin: '0 0 0.5rem',
							fontSize: '12px',
							opacity: 0.72,
						} }
					>
						{ __(
							'Player query — add or edit the Player template below.',
							'clanspress'
						) }
					</p>
					<InnerBlocks
						allowedBlocks={ [ 'clanspress/player-template' ] }
						template={ TEMPLATE }
						templateLock={ false }
					/>
				</div>
			</>
		);
	},
	save: () => {
		const blockProps = useBlockProps.save( {
			className: 'wp-block-clanspress-player-query',
		} );
		return (
			<div { ...blockProps }>
				<InnerBlocks.Content />
			</div>
		);
	},
} );
