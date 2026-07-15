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
					$field.find( 'input, select, textarea, button' ).prop( 'disabled', ! shouldShow );
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

		var updateScheduleFields = function( $scope ) {
			$scope.find( '[data-dialyra-schedule-group]' ).addBack( '[data-dialyra-schedule-group]' ).each(function() {
				var $group = $( this );
				var availabilityMode = String( $group.find( '[data-dialyra-schedule-mode]' ).val() || 'always_open' );
				var holidayMode = String( $group.find( '[data-dialyra-holiday-mode]' ).val() || 'closed' );
				var isScheduled = 'scheduled' === availabilityMode;
				var isCustomHoliday = isScheduled && 'custom' === holidayMode;

				$group.find( '[data-dialyra-scheduled-fields]' ).prop( 'hidden', ! isScheduled ).toggleClass( 'wp-dialyra-is-hidden', ! isScheduled );
				$group.find( '[data-dialyra-holiday-custom-fields]' ).prop( 'hidden', ! isCustomHoliday ).toggleClass( 'wp-dialyra-is-hidden', ! isCustomHoliday );
			});
		};

		$( document ).on( 'change', '[data-dialyra-dynamic-select]', function() {
			var $scope = $( this ).closest( '[data-dialyra-dynamic-group], .wp-dialyra-flow-builder, .wp-dialyra-setup' );
			updateDynamicFields( $scope.length ? $scope : $( document ) );
		});

		$( document ).on( 'change', '[data-dialyra-schedule-mode], [data-dialyra-holiday-mode]', function() {
			var $scope = $( this ).closest( '[data-dialyra-schedule-group]' );
			updateScheduleFields( $scope.length ? $scope : $( document ) );
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

		var updateAgentEditor = function( $select ) {
			var $option = $select.find( 'option:selected' );
			var $form = $select.closest( 'form' );

			$form.find( '[name="name"]' ).val( $option.data( 'name' ) || '' );
			$form.find( '[name="phone"]' ).val( $option.data( 'phone' ) || '' );
			$form.find( '[name="max_concurrent_calls"]' ).val( $option.data( 'maxCalls' ) || 1 );
			$form.find( '[name="skills_language"]' ).val( $option.data( 'skills' ) || '' );
			$form.find( '[name="status"]' ).val( $option.data( 'status' ) || 'active' );
			$form.find( '[name="availability_status"]' ).val( $option.data( 'availability' ) || 'offline' );
		};

		$( document ).on( 'change', '[data-dialyra-agent-editor]', function() {
			updateAgentEditor( $( this ) );
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

		var openDialog = function( dialogId ) {
			var $dialog = $( '#' + dialogId );

			if ( ! $dialog.length ) {
				return;
			}

			$( '[data-dialyra-dialog]' ).not( $dialog ).prop( 'hidden', true ).removeClass( 'wp-dialyra-dialog--open' );
			$dialog.prop( 'hidden', false ).addClass( 'wp-dialyra-dialog--open' );
			$( 'body' ).addClass( 'wp-dialyra-dialog-lock' );
			updateDynamicFields( $dialog );
			updateScheduleFields( $dialog );

			window.setTimeout(function() {
				var $focusTarget = $dialog.find( 'input, select, textarea, button' ).filter( ':visible' ).first();

				if ( $focusTarget.length ) {
					$focusTarget.trigger( 'focus' );
				}
			}, 40 );
		};

		var closeDialogs = function() {
			$( '[data-dialyra-dialog]' ).prop( 'hidden', true ).removeClass( 'wp-dialyra-dialog--open' );
			$( 'body' ).removeClass( 'wp-dialyra-dialog-lock' );
		};

		$( document ).on( 'click', '[data-dialyra-dialog-open]', function() {
			openDialog( String( $( this ).data( 'dialyraDialogOpen' ) || '' ) );
		});

		$( document ).on( 'click', '[data-dialyra-dialog-close]', function() {
			closeDialogs();
		});

		var setAudioButtonState = function( audio, isPlaying ) {
			if ( ! audio || ! audio.id ) {
				return;
			}

			var $button = $( '[data-dialyra-audio-toggle="' + audio.id + '"]' );
			var $icon = $button.find( '.dashicons' );
			var $row = $( audio ).closest( 'article' );

			$button.toggleClass( 'wp-dialyra-audio-action--playing', isPlaying );
			$button.attr( 'title', isPlaying ? 'Pause' : 'Play' );
			$button.attr( 'aria-label', isPlaying ? 'Pause audio' : 'Play audio' );
			$icon.toggleClass( 'dashicons-controls-play', ! isPlaying ).toggleClass( 'dashicons-controls-pause', isPlaying );
			$row.toggleClass( 'wp-dialyra-audio-row--playing', isPlaying );
		};

		$( document ).on( 'click', '[data-dialyra-audio-toggle]', function() {
			var $button = $( this );
			var audioId = String( $button.data( 'dialyraAudioToggle' ) || '' );
			var audio = audioId ? document.getElementById( audioId ) : null;

			if ( ! audio ) {
				return;
			}

			$( '.wp-dialyra-audio-player' ).each(function() {
				if ( this !== audio ) {
					this.pause();
					this.currentTime = 0;
				}
			});

			if ( audio.paused ) {
				var playRequest = audio.play();
				setAudioButtonState( audio, true );

				if ( playRequest && 'function' === typeof playRequest.catch ) {
					playRequest.catch(function() {
						setAudioButtonState( audio, false );
					});
				}
			} else {
				audio.pause();
				setAudioButtonState( audio, false );
			}
		});

		document.addEventListener( 'play', function( event ) {
			if ( event.target && event.target.classList && event.target.classList.contains( 'wp-dialyra-audio-player' ) ) {
				setAudioButtonState( event.target, true );
			}
		}, true );

		document.addEventListener( 'pause', function( event ) {
			if ( event.target && event.target.classList && event.target.classList.contains( 'wp-dialyra-audio-player' ) ) {
				setAudioButtonState( event.target, false );
			}
		}, true );

		document.addEventListener( 'ended', function( event ) {
			if ( event.target && event.target.classList && event.target.classList.contains( 'wp-dialyra-audio-player' ) ) {
				setAudioButtonState( event.target, false );
			}
		}, true );

		$( document ).on( 'keydown', function( event ) {
			if ( 'Escape' === event.key ) {
				$( '[data-dialyra-product-picker]' ).prop( 'hidden', true );
				closeDialogs();
			}
		});

		updateDynamicFields( $( document ) );
		updateSetupBusinessChoice();
		updateScheduleFields( $( document ) );
		$( '[data-dialyra-agent-editor]' ).each(function() {
			updateAgentEditor( $( this ) );
		});
	});

})( jQuery );
