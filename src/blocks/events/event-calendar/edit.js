import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function Edit( { attributes, setAttributes } ) {
	const { scopeType, teamId, groupId, playerUserId, defaultView } =
		attributes;
	const blockProps = useBlockProps( {
		className: 'clanspress-event-calendar-editor',
	} );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Context', 'clanspress' ) }>
					<SelectControl
						label={ __( 'Scope', 'clanspress' ) }
						value={ scopeType }
						options={ [
							{
								label: __( 'Team', 'clanspress' ),
								value: 'team',
							},
							{
								label: __( 'Group', 'clanspress' ),
								value: 'group',
							},
							{
								label: __(
									'Player (own profile only)',
									'clanspress'
								),
								value: 'player',
							},
						] }
						onChange={ ( v ) => setAttributes( { scopeType: v } ) }
					/>
					{ scopeType === 'team' && (
						<TextControl
							label={ __(
								'Team ID (0 = URL context)',
								'clanspress'
							) }
							type="number"
							value={ teamId || '' }
							onChange={ ( v ) =>
								setAttributes( {
									teamId: parseInt( v, 10 ) || 0,
								} )
							}
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
					) }
					{ scopeType === 'group' && (
						<TextControl
							label={ __(
								'Group ID (0 = URL context)',
								'clanspress'
							) }
							type="number"
							value={ groupId || '' }
							onChange={ ( v ) =>
								setAttributes( {
									groupId: parseInt( v, 10 ) || 0,
								} )
							}
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
					) }
					{ scopeType === 'player' && (
						<TextControl
							help={ __(
								'0 uses the profile user from the URL; only shown to that logged-in user.',
								'clanspress'
							) }
							label={ __( 'User ID override', 'clanspress' ) }
							type="number"
							value={ playerUserId || '' }
							onChange={ ( v ) =>
								setAttributes( {
									playerUserId: parseInt( v, 10 ) || 0,
								} )
							}
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
					) }
					<SelectControl
						label={ __( 'Default view', 'clanspress' ) }
						value={ defaultView }
						options={ [
							{
								label: __( 'Month', 'clanspress' ),
								value: 'month',
							},
							{
								label: __( 'Week', 'clanspress' ),
								value: 'week',
							},
							{ label: __( 'Day', 'clanspress' ), value: 'day' },
							{
								label: __( 'List', 'clanspress' ),
								value: 'list',
							},
						] }
						onChange={ ( v ) =>
							setAttributes( { defaultView: v } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<p>{ __( 'Event calendar (front-end)', 'clanspress' ) }</p>
		</div>
	);
}
