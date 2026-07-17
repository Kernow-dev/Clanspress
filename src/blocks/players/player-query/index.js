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
import { clanbiteBlockIcon } from '../../shared/block-icons/icons/player-query';

const TEMPLATE = [
	[
		'clanbite/player-template',
		{},
		[
			[ 'clanbite/player-avatar', { avatarPreset: 'medium' } ],
			[ 'clanbite/player-display-name' ],
		],
	],
];

const ORDERBY_OPTIONS = [
	{
		label: __( 'Default (sorted user ID)', 'clanbite' ),
		value: 'default',
	},
	{
		label: __( 'Roster order (member map)', 'clanbite' ),
		value: 'roster',
	},
	{
		label: __( 'User ID', 'clanbite' ),
		value: 'id',
	},
	{
		label: __( 'Display name', 'clanbite' ),
		value: 'display_name',
	},
	{
		label: __( 'Username (login)', 'clanbite' ),
		value: 'login',
	},
	{
		label: __( 'Nicename', 'clanbite' ),
		value: 'nicename',
	},
	{
		label: __( 'Email', 'clanbite' ),
		value: 'email',
	},
	{
		label: __( 'URL', 'clanbite' ),
		value: 'url',
	},
	{
		label: __( 'Registered', 'clanbite' ),
		value: 'registered',
	},
	{
		label: __( 'Post count', 'clanbite' ),
		value: 'post_count',
	},
	{
		label: __( 'Random', 'clanbite' ),
		value: 'rand',
	},
	{
		label: __( 'User meta value', 'clanbite' ),
		value: 'meta_value',
	},
	{
		label: __( 'User meta value (numeric)', 'clanbite' ),
		value: 'meta_value_num',
	},
];

registerBlockType( metadata, {
	icon: clanbiteBlockIcon(),
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
			className: 'clanbite-player-query',
		} );

		const needsMetaKey =
			queryOrderby === 'meta_value' || queryOrderby === 'meta_value_num';

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Team roster', 'clanbite' ) }>
						<ToggleControl
							label={ __(
								'Inherit team from template',
								'clanbite'
							) }
							checked={ inheritTeamContext }
							onChange={ ( v ) =>
								setAttributes( { inheritTeamContext: v } )
							}
							help={ __(
								'Uses the current team on singular team pages, or each team when this block appears inside a Query Loop.',
								'clanbite'
							) }
							__nextHasNoMarginBottom
						/>
						{ ! inheritTeamContext && (
							<TextControl
								label={ __( 'Team post ID', 'clanbite' ) }
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
								'clanbite'
							) }
							checked={ excludeBannedMembers }
							onChange={ ( v ) =>
								setAttributes( { excludeBannedMembers: v } )
							}
							__nextHasNoMarginBottom
						/>
					</PanelBody>
					<PanelBody
						title={ __( 'Query', 'clanbite' ) }
						initialOpen={ false }
					>
						<SelectControl
							label={ __( 'Order by', 'clanbite' ) }
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
							label={ __( 'Order', 'clanbite' ) }
							value={ queryOrder === 'DESC' ? 'DESC' : 'ASC' }
							options={ [
								{
									label: __( 'Ascending', 'clanbite' ),
									value: 'ASC',
								},
								{
									label: __( 'Descending', 'clanbite' ),
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
									'clanbite'
								) }
								value={ queryMetaKey || '' }
								onChange={ ( v ) =>
									setAttributes( { queryMetaKey: v ?? '' } )
								}
								help={ __(
									'Required when ordering by user meta.',
									'clanbite'
								) }
								__next40pxDefaultSize
								__nextHasNoMarginBottom
							/>
						) }
						<TextControl
							label={ __(
								'Max members (0 = all)',
								'clanbite'
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
							label={ __( 'Offset', 'clanbite' ) }
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
								'clanbite'
							) }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
						<TextareaControl
							label={ __(
								'Filter by meta (JSON)',
								'clanbite'
							) }
							value={ queryMetaQueryJson || '' }
							onChange={ ( v ) =>
								setAttributes( { queryMetaQueryJson: v ?? '' } )
							}
							help={ __(
								'Optional. Same shape as a WordPress meta_query: keep only roster members who match (e.g. [{"key":"country","value":"UK","compare":"="}]).',
								'clanbite'
							) }
							rows={ 4 }
							__nextHasNoMarginBottom
						/>
					</PanelBody>
					<PanelBody
						title={ __( 'Exclusions (advanced)', 'clanbite' ) }
						initialOpen={ false }
					>
						<TextControl
							label={ __( 'Exclude user IDs', 'clanbite' ) }
							value={ queryExcludeUsers || '' }
							onChange={ ( v ) =>
								setAttributes( { queryExcludeUsers: v ?? '' } )
							}
							help={ __(
								'Comma- or space-separated WordPress user IDs (like excluding posts in Query Loop).',
								'clanbite'
							) }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
						<ToggleControl
							label={ __(
								'Exclude the current user',
								'clanbite'
							) }
							checked={ !! queryExcludeCurrentUser }
							onChange={ ( v ) =>
								setAttributes( {
									queryExcludeCurrentUser: !! v,
								} )
							}
							help={ __(
								'When someone is logged in, they are omitted from the list.',
								'clanbite'
							) }
							__nextHasNoMarginBottom
						/>
						<TextControl
							label={ __( 'Exclude team roles', 'clanbite' ) }
							value={ queryExcludeRoles || '' }
							onChange={ ( v ) =>
								setAttributes( { queryExcludeRoles: v ?? '' } )
							}
							help={ __(
								'Comma-separated roster role slugs (e.g. admin, editor, member). Separate from “Exclude banned members”.',
								'clanbite'
							) }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
						<TextareaControl
							label={ __(
								'Exclude by meta (JSON)',
								'clanbite'
							) }
							value={ queryExcludeMetaQueryJson || '' }
							onChange={ ( v ) =>
								setAttributes( {
									queryExcludeMetaQueryJson: v ?? '',
								} )
							}
							help={ __(
								'Members who match this meta_query are removed from the roster list (opposite of “Filter by meta”).',
								'clanbite'
							) }
							rows={ 4 }
							__nextHasNoMarginBottom
						/>
					</PanelBody>
				</InspectorControls>
				<div { ...blockProps }>
					<p
						className="clanbite-player-query__hint"
						style={ {
							margin: '0 0 0.5rem',
							fontSize: '12px',
							opacity: 0.72,
						} }
					>
						{ __(
							'Player query — add or edit the Player template below.',
							'clanbite'
						) }
					</p>
					<InnerBlocks
						allowedBlocks={ [ 'clanbite/player-template' ] }
						template={ TEMPLATE }
						templateLock={ false }
					/>
				</div>
			</>
		);
	},
	save: () => {
		const blockProps = useBlockProps.save( {
			className: 'wp-block-clanbite-player-query',
		} );
		return (
			<div { ...blockProps }>
				<InnerBlocks.Content />
			</div>
		);
	},
} );
