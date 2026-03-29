/**
 * Match list block: inspector controls for query attributes.
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl, SelectControl } from '@wordpress/components';

const STATUS_OPTIONS = [
	{ label: __( 'Any status', 'clanspress' ), value: '' },
	{ label: __( 'Scheduled', 'clanspress' ), value: 'scheduled' },
	{ label: __( 'Live', 'clanspress' ), value: 'live' },
	{ label: __( 'Finished', 'clanspress' ), value: 'finished' },
	{ label: __( 'Cancelled', 'clanspress' ), value: 'cancelled' },
];

export default function Edit( { attributes, setAttributes } ) {
	const { teamId, limit, statusFilter, order } = attributes;

	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<PanelBody title={ __( 'Match list', 'clanspress' ) }>
					<TextControl
						label={ __( 'Team post ID (optional)', 'clanspress' ) }
						help={ __(
							'Limit to matches involving this team (`cp_team` ID). Leave 0 for all teams.',
							'clanspress'
						) }
						type="number"
						value={ teamId || '' }
						onChange={ ( v ) =>
							setAttributes( { teamId: parseInt( v, 10 ) || 0 } )
						}
					/>
					<TextControl
						label={ __( 'Max matches', 'clanspress' ) }
						help={ __(
							'0 uses the default from Clanspress → Matches settings.',
							'clanspress'
						) }
						type="number"
						value={ limit || '' }
						onChange={ ( v ) =>
							setAttributes( { limit: parseInt( v, 10 ) || 0 } )
						}
					/>
					<SelectControl
						label={ __( 'Status filter', 'clanspress' ) }
						value={ statusFilter }
						options={ STATUS_OPTIONS }
						onChange={ ( v ) =>
							setAttributes( { statusFilter: v } )
						}
					/>
					<SelectControl
						label={ __( 'Sort by scheduled time', 'clanspress' ) }
						value={ order }
						options={ [
							{
								label: __( 'Ascending', 'clanspress' ),
								value: 'asc',
							},
							{
								label: __( 'Descending', 'clanspress' ),
								value: 'desc',
							},
						] }
						onChange={ ( v ) => setAttributes( { order: v } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<p className="clanspress-match-list-editor-note">
				{ __( 'Match list (preview on the front end).', 'clanspress' ) }
			</p>
		</div>
	);
}
