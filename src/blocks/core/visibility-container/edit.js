/**
 * Visibility container block editor.
 */
import { __ } from '@wordpress/i18n';
import {
	InnerBlocks,
	useBlockProps,
	useInnerBlocksProps,
	InspectorControls,
} from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	FormTokenField,
	Notice,
} from '@wordpress/components';

import './editor.scss';

const SHOW_OPTIONS = [
	{ label: __( 'Everyone', 'clanspress' ), value: 'all' },
	{
		label: __( 'Guests only (not logged in)', 'clanspress' ),
		value: 'guests',
	},
	{ label: __( 'Logged-in users', 'clanspress' ), value: 'logged_in' },
	{ label: __( 'Selected roles only', 'clanspress' ), value: 'roles' },
];

const HIDE_OPTIONS = [
	{ label: __( 'No one (do not hide)', 'clanspress' ), value: 'none' },
	{ label: __( 'Guests', 'clanspress' ), value: 'guests' },
	{ label: __( 'Logged-in users', 'clanspress' ), value: 'logged_in' },
	{ label: __( 'Selected roles', 'clanspress' ), value: 'roles' },
];

function getRoleSuggestions() {
	const cfg =
		typeof window !== 'undefined'
			? window.clanspressVisibilityContainer
			: null;
	if ( ! cfg?.roles?.length ) {
		return [];
	}
	return cfg.roles.map( ( r ) => r.label || r.slug );
}

function tokensToSlugs( tokens ) {
	const cfg =
		typeof window !== 'undefined'
			? window.clanspressVisibilityContainer
			: null;
	if ( ! cfg?.roles?.length ) {
		return tokens.map( ( t ) =>
			String( t ).toLowerCase().replace( /\s+/g, '_' )
		);
	}
	const byLabel = new Map(
		cfg.roles.map( ( r ) => [ String( r.label ).toLowerCase(), r.slug ] )
	);
	return tokens.map( ( t ) => {
		const key = String( t ).toLowerCase();
		if ( byLabel.has( key ) ) {
			return byLabel.get( key );
		}
		return sanitizeSlug( t );
	} );
}

function slugsToTokens( slugs, suggestionsData ) {
	if ( ! suggestionsData?.length || ! slugs?.length ) {
		return slugs || [];
	}
	const bySlug = new Map(
		suggestionsData.map( ( r ) => [ r.slug, r.label || r.slug ] )
	);
	return slugs.map( ( s ) => bySlug.get( s ) || s );
}

function sanitizeSlug( raw ) {
	return String( raw )
		.toLowerCase()
		.replace( /[^a-z0-9_-]+/g, '_' )
		.replace( /^_+|_+$/g, '' );
}

export default function Edit( { attributes, setAttributes } ) {
	const {
		showTo,
		hideFrom,
		showToRoles = [],
		hideFromRoles = [],
	} = attributes;

	const blockProps = useBlockProps( {
		className: 'clanspress-visibility-container-editor',
	} );

	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		renderAppender: InnerBlocks.ButtonBlockAppender,
	} );

	const suggestions = getRoleSuggestions();
	const cfg =
		typeof window !== 'undefined'
			? window.clanspressVisibilityContainer
			: null;

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Visibility', 'clanspress' ) }
					initialOpen={ true }
				>
					<SelectControl
						label={ __( 'Show to', 'clanspress' ) }
						help={ __(
							'Who should see the content inside this block.',
							'clanspress'
						) }
						value={ showTo }
						options={ SHOW_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( { showTo: value || 'all' } )
						}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					{ showTo === 'roles' && (
						<FormTokenField
							label={ __( 'Roles (show to)', 'clanspress' ) }
							value={ slugsToTokens( showToRoles, cfg?.roles ) }
							suggestions={ suggestions }
							onChange={ ( tokens ) =>
								setAttributes( {
									showToRoles: tokensToSlugs( tokens ),
								} )
							}
							__experimentalShowHowTo={ false }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
					) }
					<SelectControl
						label={ __( 'Hide from', 'clanspress' ) }
						help={ __(
							'Remove the block for these visitors (applied after “Show to”).',
							'clanspress'
						) }
						value={ hideFrom }
						options={ HIDE_OPTIONS }
						onChange={ ( value ) =>
							setAttributes( { hideFrom: value || 'none' } )
						}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					{ hideFrom === 'roles' && (
						<FormTokenField
							label={ __( 'Roles (hide from)', 'clanspress' ) }
							value={ slugsToTokens( hideFromRoles, cfg?.roles ) }
							suggestions={ suggestions }
							onChange={ ( tokens ) =>
								setAttributes( {
									hideFromRoles: tokensToSlugs( tokens ),
								} )
							}
							__experimentalShowHowTo={ false }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
					) }
					{ showTo === 'roles' && showToRoles.length === 0 && (
						<Notice status="warning" isDismissible={ false }>
							{ __(
								'Pick at least one role, or nobody will see this block.',
								'clanspress'
							) }
						</Notice>
					) }
					{ ! cfg?.roles?.length && (
						<Notice status="info" isDismissible={ false }>
							{ __(
								'Role labels load from the site; you can type role slugs (e.g. administrator) in the token fields.',
								'clanspress'
							) }
						</Notice>
					) }
				</PanelBody>
			</InspectorControls>
			<div { ...innerBlocksProps } />
		</>
	);
}
