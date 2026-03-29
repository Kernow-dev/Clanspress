/**
 * Tabbed team manage UI (mirrors clanspress-team-create-form behaviour; all tabs are reachable without a wizard).
 */
import { store, getContext, getElement } from '@wordpress/interactivity';
import {
	syncTeamFormTabs,
	focusActiveTabButton,
} from '../shared/sync-team-form-tabs.js';

const { state } = store( 'clanspress-team-manage-form', {
	state: {
		root: null,
		step: 1,
		stepCount: 1,
		maxStep() {
			const total = Number( state.stepCount );
			return total > 0 ? total : 1;
		},
		canGoNext() {
			return state.step < state.maxStep();
		},
		canGoBack() {
			return state.step > 1;
		},
	},
	actions: {
		nextStep( event ) {
			event.preventDefault();
			if ( state.step >= state.maxStep() ) {
				return;
			}
			state.step += 1;
			const { ref } = getElement();
			const root = ref?.closest( '.clanspress-team-manage-form--tabbed' );
			syncTeamFormTabs( root, state.step, { wizard: false } );
			focusActiveTabButton( root, state.step );
		},
		previousStep( event ) {
			event.preventDefault();
			if ( state.step <= 1 ) {
				return;
			}
			state.step -= 1;
			const { ref } = getElement();
			const root = ref?.closest( '.clanspress-team-manage-form--tabbed' );
			syncTeamFormTabs( root, state.step, { wizard: false } );
			focusActiveTabButton( root, state.step );
		},
		goToStepTab( event ) {
			event.preventDefault();
			const btn = event?.currentTarget;
			const n = Number( btn?.getAttribute( 'data-team-tab' ) );
			if ( ! n || n < 1 || n > state.maxStep() || n === state.step ) {
				return;
			}
			state.step = n;
			const { ref } = getElement();
			const root = ref?.closest( '.clanspress-team-manage-form--tabbed' );
			syncTeamFormTabs( root, state.step, { wizard: false } );
		},
		onTabListKeydown( event ) {
			const key = event.key;
			if (
				key !== 'ArrowLeft' &&
				key !== 'ArrowRight' &&
				key !== 'Home' &&
				key !== 'End'
			) {
				return;
			}
			event.preventDefault();
			const max = state.maxStep();
			let next = state.step;
			if ( key === 'ArrowRight' ) {
				next = Math.min( state.step + 1, max );
			} else if ( key === 'ArrowLeft' ) {
				next = Math.max( state.step - 1, 1 );
			} else if ( key === 'Home' ) {
				next = 1;
			} else if ( key === 'End' ) {
				next = max;
			}
			if ( next === state.step ) {
				return;
			}
			state.step = next;
			const { ref } = getElement();
			const root = ref?.closest( '.clanspress-team-manage-form--tabbed' );
			syncTeamFormTabs( root, state.step, { wizard: false } );
			focusActiveTabButton( root, state.step );
		},
	},
	callbacks: {
		init() {
			const { ref } = getElement();
			if ( ! ref ) {
				return;
			}
			const ctx = getContext();
			const fromServer = Number( ctx?.stepCount );
			state.stepCount =
				Number.isFinite( fromServer ) && fromServer > 0
					? fromServer
					: 1;
			state.root = ref;
			syncTeamFormTabs( ref, state.step, { wizard: false } );
		},
	},
} );
