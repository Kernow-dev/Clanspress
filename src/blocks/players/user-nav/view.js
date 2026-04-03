/**
 * User Navigation block - Interactivity API view script.
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

store( 'clanspress/user-nav', {
	state: {},

	actions: {
		toggleDropdown( event ) {
			event.stopPropagation();
			const ctx = getContext();
			ctx.isOpen = ! ctx.isOpen;
		},

		closeDropdown() {
			const ctx = getContext();
			ctx.isOpen = false;
		},

		handleOutsideClick( event ) {
			const ctx = getContext();
			if ( ! ctx.isOpen ) {
				return;
			}

			const { ref } = getElement();
			if ( ref && ! ref.contains( event.target ) ) {
				ctx.isOpen = false;
			}
		},

		handleKeydown( event ) {
			const ctx = getContext();

			if ( event.key === 'Escape' && ctx.isOpen ) {
				ctx.isOpen = false;
				event.preventDefault();
			}
		},
	},

	callbacks: {},
} );
