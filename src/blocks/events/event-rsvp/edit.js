import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	ToggleControl,
	SelectControl,
} from '@wordpress/components';
import { sprintf, __ } from '@wordpress/i18n';

export default function Edit( { attributes, setAttributes, context } ) {
	const { eventType, eventId, showAttendees } = attributes;
	const postId = context?.postId ?? 0;
	const postType = context?.postType ?? '';

	const resolvedId =
		eventId > 0
			? eventId
			: postId > 0 &&
			  ( ( eventType === 'match' && postType === 'cp_match' ) ||
					( eventType === 'group' && postType === 'cp_group' ) ||
					( eventType === 'clanspress_event' &&
						postType === 'cp_event' ) )
			? postId
			: 0;

	const blockProps = useBlockProps( {
		className: 'clanspress-event-rsvp-editor',
	} );

	const typeLabel =
		eventType === 'group'
			? __( 'Group', 'clanspress' )
			: eventType === 'clanspress_event'
			? __( 'Event', 'clanspress' )
			: __( 'Match', 'clanspress' );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Event', 'clanspress' ) }>
					<SelectControl
						label={ __( 'Event type', 'clanspress' ) }
						value={ eventType }
						options={ [
							{
								label: __( 'Match (cp_match)', 'clanspress' ),
								value: 'match',
							},
							{
								label: __( 'Group (cp_group)', 'clanspress' ),
								value: 'group',
							},
							{
								label: __(
									'Scheduled event (cp_event)',
									'clanspress'
								),
								value: 'clanspress_event',
							},
						] }
						onChange={ ( v ) => setAttributes( { eventType: v } ) }
					/>
					<TextControl
						label={ __( 'Event ID (optional)', 'clanspress' ) }
						help={ __(
							'Leave 0 to use the current template post in the editor or on single event pages.',
							'clanspress'
						) }
						type="number"
						value={ eventId || '' }
						onChange={ ( v ) =>
							setAttributes( { eventId: parseInt( v, 10 ) || 0 } )
						}
						__next40pxDefaultSize
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={ __( 'Show attendee list', 'clanspress' ) }
						checked={ showAttendees }
						onChange={ ( v ) =>
							setAttributes( { showAttendees: v } )
						}
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>
			<p className="clanspress-event-rsvp-editor__title">
				<strong>{ __( 'Event RSVP', 'clanspress' ) }</strong>
			</p>
			{ resolvedId < 1 && (
				<p>
					{ __(
						'Select a match or group template, or set an event ID.',
						'clanspress'
					) }
				</p>
			) }
			{ resolvedId > 0 && (
				<p>
					{ sprintf(
						/* translators: 1: event kind, 2: numeric ID */
						__( 'Linked %1$s — ID %2$d', 'clanspress' ),
						typeLabel,
						resolvedId
					) }
				</p>
			) }
		</div>
	);
}
