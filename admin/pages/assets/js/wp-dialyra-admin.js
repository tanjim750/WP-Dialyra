(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	$(function() {
		var updateDynamicFields = function( $scope ) {
			$scope.find( '[data-dialyra-dynamic-group], [data-dtmf-action]' ).addBack( '[data-dialyra-dynamic-group], [data-dtmf-action]' ).each(function() {
				var $group = $( this );
				var activeValues = [];

				$group.find( '[data-dialyra-dynamic-select]' ).each(function() {
					var $control = $( this );

					if ( $control.is( ':radio, :checkbox' ) && ! $control.is( ':checked' ) ) {
						return;
					}

					activeValues.push( String( $control.val() ) );
				});

				$group.find( '[data-dialyra-show-for]' ).each(function() {
					var $field = $( this );
					var allowedValues = String( $field.data( 'dialyraShowFor' ) ).split( /\s+/ );
					var shouldShow = allowedValues.some(function( value ) {
						return activeValues.indexOf( value ) !== -1;
					});

					$field.prop( 'hidden', ! shouldShow );
				});
			});
		};

		var refreshDtmfAction = function( $action, index ) {
			$action.find( '[id]' ).each(function() {
				var $item = $( this );
				$item.attr( 'id', $item.attr( 'id' ).replace( /-\d+$/, '-' + index ) );
			});

			$action.find( '[for]' ).each(function() {
				var $item = $( this );
				$item.attr( 'for', $item.attr( 'for' ).replace( /-\d+$/, '-' + index ) );
			});

			$action.find( '[name]' ).each(function() {
				var $item = $( this );
				$item.attr( 'name', $item.attr( 'name' ).replace( /_\d+$/, '_' + index ) );
			});
		};

		var updateSetupBusinessChoice = function() {
			if ( ! $( '[data-dialyra-business-creation]' ).length ) {
				return;
			}

			var showCreateBusiness = 'new' === String( $( 'input[name="dialyra_business_choice"]:checked' ).val() || '' );
			$( '[data-dialyra-business-creation]' ).prop( 'hidden', ! showCreateBusiness );
		};

		$( document ).on( 'change', '[data-dialyra-dynamic-select]', function() {
			var $scope = $( this ).closest( '[data-dialyra-dynamic-group], .wp-dialyra-flow-builder, .wp-dialyra-setup' );
			updateDynamicFields( $scope.length ? $scope : $( document ) );
		});

		$( document ).on( 'change', 'input[name="dialyra_business_choice"]', function() {
			updateSetupBusinessChoice();
		});

		$( document ).on( 'change', '#wp-dialyra-setup-business', function() {
			var selectedBusinessId = String( $( this ).val() || '' );

			if ( selectedBusinessId ) {
				$( 'input[name="dialyra_business_choice"][value="' + selectedBusinessId + '"]' ).prop( 'checked', true );
				updateSetupBusinessChoice();
			}
		});

		$( document ).on( 'change', '.wp-dialyra-day-picker input[type="checkbox"]', function() {
			var $checkbox = $( this );
			var $picker = $checkbox.closest( '.wp-dialyra-day-picker' );

			if ( 'all' === String( $checkbox.val() ) && $checkbox.is( ':checked' ) ) {
				$picker.find( 'input[type="checkbox"]' ).not( $checkbox ).prop( 'checked', false );
				return;
			}

			if ( 'all' !== String( $checkbox.val() ) && $checkbox.is( ':checked' ) ) {
				$picker.find( 'input[type="checkbox"][value="all"]' ).prop( 'checked', false );
			}

			if ( ! $picker.find( 'input[type="checkbox"]:checked' ).length ) {
				$picker.find( 'input[type="checkbox"][value="all"]' ).prop( 'checked', true );
			}
		});

		$( document ).on( 'click', '#wp-dialyra-add-dtmf-action', function() {
			var $list = $( '#wp-dialyra-dtmf-actions' );
			var $template = $list.find( '[data-dtmf-action]' ).first();
			var $clone = $template.clone( true, true );
			var nextIndex = $list.find( '[data-dtmf-action]' ).length + 1;
			var nextKey = Math.min( nextIndex, 9 );

			refreshDtmfAction( $clone, nextIndex );
			$clone.find( '.wp-dialyra-dtmf-actions__key select' ).val( String( nextKey ) );
			$clone.find( 'input[type="text"]' ).val( '' );
			$clone.find( 'select[name^="response_type"]' ).val( 'tts' );
			$clone.find( 'select[name^="business_action"]' ).val( 'no_action' );
			$clone.find( 'select[name^="next_step"]' ).val( 'hangup' );

			$list.append( $clone );
			updateDynamicFields( $clone );
		});

		$( document ).on( 'click', '[data-remove-dtmf-action]', function() {
			var $actions = $( '#wp-dialyra-dtmf-actions [data-dtmf-action]' );

			if ( $actions.length <= 1 ) {
				return;
			}

			$( this ).closest( '[data-dtmf-action]' ).remove();
		});

		$( document ).on( 'click', '[data-dialyra-open-product-picker]', function() {
			$( '[data-dialyra-product-picker]' ).prop( 'hidden', false );
		});

		$( document ).on( 'click', '[data-dialyra-close-product-picker]', function() {
			$( '[data-dialyra-product-picker]' ).prop( 'hidden', true );
		});

		$( document ).on( 'keydown', function( event ) {
			if ( 'Escape' === event.key ) {
				$( '[data-dialyra-product-picker]' ).prop( 'hidden', true );
			}
		});

		updateDynamicFields( $( document ) );
		updateSetupBusinessChoice();
	});

})( jQuery );
