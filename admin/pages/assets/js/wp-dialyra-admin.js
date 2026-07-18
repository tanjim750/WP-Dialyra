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

		var updateBusinessHoursFields = function( $scope ) {
			$scope.find( '[data-dialyra-business-hours-group]' ).addBack( '[data-dialyra-business-hours-group]' ).each(function() {
				var $group = $( this );
				var isAlwaysActive = $group.find( '[data-dialyra-business-hours-toggle]' ).is( ':checked' );

				$group.find( '[data-dialyra-business-hours-always]' )
					.prop( 'hidden', ! isAlwaysActive )
					.toggleClass( 'wp-dialyra-is-hidden', ! isAlwaysActive )
					.find( 'input, select, textarea, button' )
					.prop( 'disabled', ! isAlwaysActive );

				$group.find( '[data-dialyra-business-hours-scheduled]' )
					.prop( 'hidden', isAlwaysActive )
					.toggleClass( 'wp-dialyra-is-hidden', isAlwaysActive )
					.find( 'input, select, textarea, button' )
					.prop( 'disabled', isAlwaysActive );
			});
		};

		$( document ).on( 'change', '[data-dialyra-dynamic-select]', function() {
			var $scope = $( this ).closest( '[data-dialyra-dynamic-group], .wp-dialyra-flow-builder, .wp-dialyra-setup' );
			updateDynamicFields( $scope.length ? $scope : $( document ) );
		});

		$( document ).on( 'change', '[data-dialyra-business-hours-toggle]', function() {
			var $scope = $( this ).closest( '[data-dialyra-business-hours-group]' );
			updateBusinessHoursFields( $scope.length ? $scope : $( document ) );
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

		var updateSettingsBusinessDetails = function( $select ) {
			var $option = $select.find( 'option:selected' );
			var $form = $select.closest( 'form' );
			var status = String( $option.data( 'status' ) || 'inactive' );
			var safeStatus = [ 'active', 'inactive', 'suspended', 'deleted' ].indexOf( status ) !== -1 ? status : 'inactive';
			var timezone = String( $option.data( 'timezone' ) || '' );
			var $statusDot = $form.find( '[data-dialyra-business-status-dot]' );

			if ( '+06:00' === timezone ) {
				timezone = 'Asia/Dhaka';
			}

			$form.find( '[name="dialyra_business_name"]' ).val( $option.data( 'name' ) || '' );
			$form.find( '[name="dialyra_business_email"]' ).val( $option.data( 'email' ) || '' );
			$form.find( '[name="dialyra_business_phone"]' ).val( $option.data( 'phone' ) || '' );
			$form.find( '[name="dialyra_business_timezone"]' ).val( timezone );
			$form.find( '[name="dialyra_business_country"]' ).val( $option.data( 'country' ) || '' );

			$statusDot
				.removeClass( 'wp-dialyra-status-dot--active wp-dialyra-status-dot--inactive wp-dialyra-status-dot--suspended wp-dialyra-status-dot--deleted' )
				.addClass( 'wp-dialyra-status-dot--' + safeStatus )
				.attr( 'title', 'Business status: ' + status );
		};

		$( document ).on( 'change', '#wp-dialyra-business-select', function() {
			updateSettingsBusinessDetails( $( this ) );
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
			var $form = $list.closest( '[data-dialyra-flow-builder-form]' );
			var $template = $form.data( 'dialyraDtmfTemplate' ) || $list.find( '[data-dtmf-action]' ).first();
			var $clone;
			var nextIndex = $list.find( '[data-dtmf-action]' ).length + 1;
			var usedKeys = [];
			var nextKey = '1';

			if ( ! $template.length || nextIndex > 9 ) {
				return;
			}

			$list.find( '.wp-dialyra-dtmf-actions__key select' ).each(function() {
				usedKeys.push( String( $( this ).val() || '' ) );
			});

			for ( var keyIndex = 1; keyIndex <= 9; keyIndex++ ) {
				if ( usedKeys.indexOf( String( keyIndex ) ) === -1 ) {
					nextKey = String( keyIndex );
					break;
				}
			}

			$clone = $template.clone( true, true );

			refreshDtmfAction( $clone, nextIndex );
			$list.find( '.wp-dialyra-flow-empty-inline' ).remove();
			$clone.find( '.wp-dialyra-dtmf-actions__key select' ).val( nextKey );
			$clone.find( 'input[type="text"]' ).val( '' );
			$clone.find( 'select[name^="response_type"]' ).val( 'tts' );
			$clone.find( 'select[name^="business_action"]' ).val( 'no_action' );
			$clone.find( 'select[name^="next_step"]' ).val( 'hangup' );

			$list.append( $clone );
			updateDynamicFields( $clone );
			updateFlowBuilderPreview( $form );
		});

		$( document ).on( 'click', '[data-remove-dtmf-action]', function() {
			var $list = $( '#wp-dialyra-dtmf-actions' );
			var $form = $list.closest( '[data-dialyra-flow-builder-form]' );
			var $action = $( this ).closest( '[data-dtmf-action]' );

			if ( ! $action.length ) {
				return;
			}

			if ( ! $form.data( 'dialyraDtmfTemplate' ) ) {
				$form.data( 'dialyraDtmfTemplate', $action.clone( true, true ) );
			}

			$action.remove();

			if ( ! $list.find( '[data-dtmf-action]' ).length ) {
				$list.append(
					$( '<div class="wp-dialyra-flow-empty-inline"></div>' )
						.append( $( '<span class="dashicons dashicons-controls-volumeon" aria-hidden="true"></span>' ) )
						.append( $( '<strong></strong>' ).text( 'No DTMF actions yet' ) )
						.append( $( '<small></small>' ).text( 'Use + Add DTMF Action when this menu needs keypad choices.' ) )
				);
			}

			updateFlowBuilderPreview( $form );
		});

		var getFieldValue = function( $scope, selector, fallback ) {
			var $field = $scope.find( selector ).first();
			var value = $field.length ? $field.val() : fallback;

			if ( Array.isArray( value ) ) {
				return value;
			}

			value = 'undefined' === typeof value || null === value ? fallback : value;

			return 'undefined' === typeof value || null === value ? '' : String( value );
		};

		var normalizeMenuId = function( value ) {
			value = String( value || '' ).toLowerCase().trim();

			if ( ! value ) {
				return '';
			}

			return value.replace( /[^a-z0-9]+/g, '_' ).replace( /^_+|_+$/g, '' ) || 'main_menu';
		};

		var titleCaseValue = function( value ) {
			value = String( value || '' ).replace( /_/g, ' ' ).trim();

			return value.replace( /\w\S*/g, function( word ) {
				return word.charAt( 0 ).toUpperCase() + word.slice( 1 );
			});
		};

		var buildFlowMessage = function( type, text, language, provider, voice, audioAssetId, allowNone ) {
			type = String( type || ( allowNone ? 'none' : 'tts' ) );
			text = String( text || '' ).trim();

			if ( allowNone && 'none' === type ) {
				return { type: 'none' };
			}

			if ( 'audio' === type ) {
				return {
					type: 'audio',
					audioAssetId: parseInt( audioAssetId || 0, 10 ) || 0
				};
			}

			return {
				type: 'tts',
				message: text || 'Review and update this message.',
				language: String( language || 'en' ),
				provider: String( provider || 'google' ),
				voice: String( voice || 'gtts:free' )
			};
		};

		var normalizeSerializedMessage = function( message, allowNone ) {
			message = $.extend( true, {}, message || {} );
			message.type = String( message.type || ( allowNone ? 'none' : 'tts' ) );

			if ( allowNone && 'none' === message.type ) {
				return { type: 'none' };
			}

			if ( 'audio' === message.type ) {
				return {
					type: 'audio',
					audioAssetId: parseInt( message.audioAssetId || message.audio_asset_id || 0, 10 ) || 0
				};
			}

			return {
				type: 'tts',
				message: String( message.message || message.text || '' ).trim() || 'Review and update this message.',
				language: String( message.language || 'en' ),
				provider: String( message.provider || 'google' ),
				voice: String( message.voice || 'gtts:free' )
			};
		};

		var normalizeSerializedNextStep = function( nextStep, fallbackType ) {
			nextStep = $.extend( true, {}, nextStep || {} );
			nextStep.type = String( nextStep.type || fallbackType || 'hangup' );

			if ( 'go_to_menu' === nextStep.type ) {
				nextStep.targetMenuId = normalizeMenuId( nextStep.targetMenuId || nextStep.targetMenu || 'main_menu' );
			}

			return nextStep;
		};

		var buildNextStep = function( type, targetMenuId ) {
			type = String( type || 'hangup' );

			if ( 'go_to_menu' === type ) {
				return {
					type: 'go_to_menu',
					targetMenuId: normalizeMenuId( targetMenuId || 'main_menu' )
				};
			}

			return { type: type };
		};

		var nextStepTargetMenuId = function( nextStep ) {
			nextStep = nextStep || {};

			return 'go_to_menu' === nextStep.type ? normalizeMenuId( nextStep.targetMenuId || nextStep.targetMenu || '' ) : '';
		};

		var menuReferenceLabels = function( state, targetMenuId ) {
			var labels = [];

			targetMenuId = normalizeMenuId( targetMenuId );

			( state.menus || [] ).forEach(function( menu ) {
				if ( ! menu || menu.id === targetMenuId ) {
					return;
				}

				( menu.dtmfActions || [] ).forEach(function( action ) {
					if ( nextStepTargetMenuId( action.nextStep ) === targetMenuId ) {
						labels.push( ( menu.name || menu.id ) + ' / key ' + ( action.inputKey || '?' ) );
					}
				});

				if ( nextStepTargetMenuId( menu.invalidInputHandling && menu.invalidInputHandling.afterMaxInvalidRetryAction ) === targetMenuId ) {
					labels.push( ( menu.name || menu.id ) + ' / invalid input' );
				}

				if ( nextStepTargetMenuId( menu.timeoutHandling && menu.timeoutHandling.nextStep ) === targetMenuId ) {
					labels.push( ( menu.name || menu.id ) + ' / timeout' );
				}
			});

			return labels;
		};

		var buildEditorMenuState = function( $form ) {
			var menuName = getFieldValue( $form, '[name="menu_name"]', 'Main Menu' );
			var menuId = normalizeMenuId( menuName || 'Main Menu' );
			var currentState = $form.data( 'dialyraFlowMenus' ) || {};
			var activeMenuId = String( $form.data( 'dialyraActiveMenuId' ) || currentState.activeMenuId || 'main_menu' );
			var startMenuId = normalizeMenuId( currentState.startMenuId || currentState.start_menu_id || 'main_menu' );
			var menu = {
				id: menuId,
				name: menuName || 'Main Menu',
				isStart: activeMenuId === startMenuId,
				description: getFieldValue( $form, '[name="menu_description"]', '' ),
				customerInstructionMessage: buildFlowMessage(
					getFieldValue( $form, '[name="menu_message_type"]', 'tts' ),
					getFieldValue( $form, '[name="menu_message_text"]', '' ),
					getFieldValue( $form, '[name="menu_tts_language"]', 'en' ),
					getFieldValue( $form, '[name="menu_tts_provider"]', 'google' ),
					getFieldValue( $form, '[name="menu_tts_voice"]', 'gtts:free' ),
					getFieldValue( $form, '[name="menu_audio_asset"]', 0 ),
					false
				),
				inputSettings: {
					maxDigits: parseInt( getFieldValue( $form, '[name="max_digits"]', 1 ), 10 ) || 1,
					timeoutSeconds: parseInt( getFieldValue( $form, '[name="timeout_seconds"]', 5 ), 10 ) || 5,
					maxInvalidRetries: parseInt( getFieldValue( $form, '[name="max_invalid_retries"]', 2 ), 10 ) || 0,
					maxTimeoutRetries: parseInt( getFieldValue( $form, '[name="max_timeout_retries"]', 1 ), 10 ) || 0
				},
				dtmfActions: [],
				invalidInputHandling: {
					responseMessage: buildFlowMessage(
						getFieldValue( $form, '[name="invalid_message_type"]', 'tts' ),
						getFieldValue( $form, '[name="invalid_message"]', '' ),
						getFieldValue( $form, '[name="invalid_tts_language"]', 'en' ),
						getFieldValue( $form, '[name="invalid_tts_provider"]', 'google' ),
						getFieldValue( $form, '[name="invalid_tts_voice"]', 'gtts:free' ),
						getFieldValue( $form, '[name="invalid_audio"]', 0 ),
						true
					),
					afterMaxInvalidRetryAction: buildNextStep(
						getFieldValue( $form, '[name="invalid_action"]', 'repeat_current_menu' ),
						getFieldValue( $form, '[name="invalid_target_menu"]', menuId )
					)
				},
				timeoutHandling: {
					responseMessage: buildFlowMessage(
						getFieldValue( $form, '[name="timeout_message_type"]', 'tts' ),
						getFieldValue( $form, '[name="timeout_text"]', '' ),
						getFieldValue( $form, '[name="timeout_tts_language"]', 'en' ),
						getFieldValue( $form, '[name="timeout_tts_provider"]', 'google' ),
						getFieldValue( $form, '[name="timeout_tts_voice"]', 'gtts:free' ),
						getFieldValue( $form, '[name="timeout_audio"]', 0 ),
						true
					),
					nextStep: buildNextStep(
						getFieldValue( $form, '[name="timeout_next_step"]', 'repeat_current_menu' ),
						getFieldValue( $form, '[name="timeout_target_menu"]', menuId )
					)
				}
			};

			$form.find( '#wp-dialyra-dtmf-actions [data-dtmf-action]' ).each(function() {
				var $action = $( this );
				var inputKey = getFieldValue( $action, '.wp-dialyra-dtmf-actions__key select', '' );
				var businessActionType = getFieldValue( $action, 'select[name^="business_action"]', 'no_action' );
				var businessAction = { type: businessActionType };

				if ( 'transfer_department' === businessActionType ) {
					businessAction.departmentId = parseInt( getFieldValue( $action, 'select[name^="department_target"]', 0 ), 10 ) || 0;
				}

				menu.dtmfActions.push({
					inputKey: inputKey,
					responseMessage: buildFlowMessage(
						getFieldValue( $action, 'select[name^="response_type"]', 'none' ),
						getFieldValue( $action, 'input[name^="response_text"]', '' ),
						getFieldValue( $action, 'select[name^="response_tts_language"]', 'en' ),
						getFieldValue( $action, 'select[name^="response_tts_provider"]', 'google' ),
						getFieldValue( $action, 'select[name^="response_tts_voice"]', 'gtts:free' ),
						getFieldValue( $action, 'select[name^="response_audio"]', 0 ),
						true
					),
					businessAction: businessAction,
					nextStep: buildNextStep(
						getFieldValue( $action, 'select[name^="next_step"]', 'hangup' ),
						getFieldValue( $action, 'select[name^="target_menu"]', menuId )
					)
				});
			});

			return menu;
		};

		var ensureFlowMenuState = function( $form ) {
			var state = $form.data( 'dialyraFlowMenus' );

			if ( state && Array.isArray( state.menus ) ) {
				return state;
			}

			state = {
				startMenuId: 'main_menu',
				activeMenuId: 'main_menu',
				menus: [ buildEditorMenuState( $form ) ]
			};
			state.menus[0].id = 'main_menu';
			state.menus[0].name = state.menus[0].name || 'Main Menu';
			state.menus[0].isStart = true;
			state.menus[0].customerInstructionMessage = state.menus[0].customerInstructionMessage || state.menus[0].message || {
				type: 'tts',
				message: 'Please choose an option.',
				language: 'en',
				provider: 'google',
				voice: 'gtts:free'
			};
			delete state.menus[0].message;
			$form.data( 'dialyraFlowMenus', state );
			$form.data( 'dialyraActiveMenuId', 'main_menu' );

			return state;
		};

		var saveActiveMenuState = function( $form ) {
			var state = ensureFlowMenuState( $form );
			var activeMenuId = String( $form.data( 'dialyraActiveMenuId' ) || state.activeMenuId || 'main_menu' );
			var startMenuId = normalizeMenuId( state.startMenuId || state.start_menu_id || 'main_menu' );
			var menu = buildEditorMenuState( $form );
			var existingIndex = -1;

			menu.id = activeMenuId;
			menu.isStart = startMenuId === activeMenuId;

			state.menus.forEach(function( item, index ) {
				if ( item.id === activeMenuId ) {
					existingIndex = index;
				}
			});

			if ( existingIndex >= 0 ) {
				state.menus[ existingIndex ] = menu;
			} else {
				state.menus.push( menu );
			}

			state.activeMenuId = activeMenuId;
			state.startMenuId = startMenuId;
			$form.data( 'dialyraFlowMenus', state );

			return state;
		};

		var renderMenuList = function( $form ) {
			var state = ensureFlowMenuState( $form );
			var activeMenuId = String( $form.data( 'dialyraActiveMenuId' ) || 'main_menu' );
			var $list = $form.find( '[data-dialyra-menu-list]' );

			$list.empty();

			state.menus.forEach(function( menu ) {
				var $item = $( '<article class="wp-dialyra-menu-list__item" data-dialyra-menu-id=""></article>' );
				var $button = $( '<button type="button" data-dialyra-select-menu></button>' );
				var $delete = $( '<button type="button" class="wp-dialyra-flow-icon-button wp-dialyra-flow-icon-button--delete" data-dialyra-remove-menu><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>' );
				var isMainMenu = 'main_menu' === menu.id;
				var references = menuReferenceLabels( state, menu.id );

				$item.attr( 'data-dialyra-menu-id', menu.id );
				$item.toggleClass( 'wp-dialyra-menu-list__item--selected', menu.id === activeMenuId );
				$button.append( $( '<strong></strong>' ).text( menu.name || 'Untitled Menu' ) );
				$button.append( $( '<small></small>' ).text( menu.isStart ? 'Start menu' : ( ( menu.dtmfActions || [] ).length + ' choices' ) ) );
				$item.append( $button );
				$item.append( $( '<em class="wp-dialyra-result wp-dialyra-result--success"></em>' ).text( 'Valid' ) );
				$delete
					.attr( 'aria-label', 'Remove menu' )
					.attr( 'data-tooltip', isMainMenu ? 'Main Menu cannot be deleted' : ( references.length ? 'Menu is used by another menu' : 'Remove menu' ) )
					.prop( 'disabled', isMainMenu || references.length > 0 );
				$item.append( $delete );
				$list.append( $item );
			});
		};

		var syncMenuTargetOptions = function( $form ) {
			var state = ensureFlowMenuState( $form );

			$form.find( 'select[name^="target_menu"], #wp-dialyra-invalid-target-menu, select[name$="_target_menu"]' ).each(function() {
				var $select = $( this );
				var selected = String( $select.val() || 'main_menu' );

				$select.empty();
				state.menus.forEach(function( menu ) {
					$select.append( $( '<option></option>' ).val( menu.id ).text( menu.name || 'Untitled Menu' ) );
				});

				$select.val( state.menus.some(function( menu ) {
					return menu.id === selected;
				}) ? selected : 'main_menu' );
			});
		};

		var applyMessageState = function( $scope, prefix, message ) {
			var $typeSelect = $scope.find( '[name="' + prefix + '_message_type"]' );
			var supportsNone = $typeSelect.find( 'option[value="none"]' ).length > 0;

			message = message || { type: 'tts' };

			if ( 'none' === message.type && ! supportsNone ) {
				message = {
					type: 'tts',
					message: 'Review and update this message.',
					language: 'en',
					provider: 'google',
					voice: 'gtts:free'
				};
			}

			$typeSelect.val( message.type || 'tts' );
			$scope.find( '[name="' + prefix + '_message_text"], [name="' + prefix + '_message"], [name="' + prefix + '_text"], [name="' + prefix + '"]' ).val( message.message || ( 'tts' === message.type ? 'Review and update this message.' : '' ) );
			$scope.find( '[name="' + prefix + '_tts_language"]' ).val( message.language || 'en' );
			$scope.find( '[name="' + prefix + '_tts_provider"]' ).val( message.provider || 'google' );
			$scope.find( '[name="' + prefix + '_tts_voice"]' ).val( message.voice || 'gtts:free' );
			$scope.find( '[name="' + prefix + '_audio_asset"], [name="' + prefix + '_audio"]' ).val( message.audioAssetId || '' );
		};

		var renderDtmfActions = function( $form, actions ) {
			var $list = $form.find( '#wp-dialyra-dtmf-actions' );
			var $template = $form.data( 'dialyraDtmfTemplate' ) || $list.find( '[data-dtmf-action]' ).first().clone( true, true );

			if ( ! Array.isArray( actions ) ) {
				actions = [ {
					inputKey: '1',
					responseMessage: { type: 'tts', message: '', language: 'en', provider: 'google', voice: 'gtts:free' },
					businessAction: { type: 'no_action' },
					nextStep: { type: 'hangup' }
				} ];
			}

			$form.data( 'dialyraDtmfTemplate', $template.clone( true, true ) );
			$list.empty();

			if ( ! actions.length ) {
				$list.append(
					$( '<div class="wp-dialyra-flow-empty-inline"></div>' )
						.append( $( '<span class="dashicons dashicons-controls-volumeon" aria-hidden="true"></span>' ) )
						.append( $( '<strong></strong>' ).text( 'No DTMF actions yet' ) )
						.append( $( '<small></small>' ).text( 'Use + Add DTMF Action when this menu needs keypad choices.' ) )
				);
				syncMenuTargetOptions( $form );
				updateDynamicFields( $list );
				return;
			}

			actions.forEach(function( action, index ) {
				var number = index + 1;
				var $row = $template.clone( true, true );

				refreshDtmfAction( $row, number );
				$row.find( '.wp-dialyra-dtmf-actions__key select' ).val( action.inputKey || String( number ) );
				$row.find( 'select[name^="response_type"]' ).val( ( action.responseMessage && action.responseMessage.type ) || 'none' );
				$row.find( 'input[name^="response_text"]' ).val( ( action.responseMessage && action.responseMessage.message ) || '' );
				$row.find( 'select[name^="response_tts_language"]' ).val( ( action.responseMessage && action.responseMessage.language ) || 'en' );
				$row.find( 'select[name^="response_tts_provider"]' ).val( ( action.responseMessage && action.responseMessage.provider ) || 'google' );
				$row.find( 'select[name^="response_tts_voice"]' ).val( ( action.responseMessage && action.responseMessage.voice ) || 'gtts:free' );
				$row.find( 'select[name^="response_audio"]' ).val( ( action.responseMessage && action.responseMessage.audioAssetId ) || '' );
				$row.find( 'select[name^="business_action"]' ).val( ( action.businessAction && action.businessAction.type ) || 'no_action' );
				$row.find( 'select[name^="department_target"]' ).val( ( action.businessAction && action.businessAction.departmentId ) || '' );
				$row.find( 'select[name^="next_step"]' ).val( ( action.nextStep && action.nextStep.type ) || 'hangup' );
				$row.find( 'select[name^="target_menu"]' ).val( ( action.nextStep && action.nextStep.targetMenuId ) || 'main_menu' );
				$list.append( $row );
			});

			syncMenuTargetOptions( $form );
			updateDynamicFields( $list );
		};

		var loadMenuEditor = function( $form, menuId ) {
			var state = ensureFlowMenuState( $form );
			var menu = state.menus.find(function( item ) {
				return item.id === menuId;
			});

			if ( ! menu ) {
				return;
			}

			$form.data( 'dialyraActiveMenuId', menu.id );
			$form.find( '[name="menu_name"]' ).val( menu.name || '' );
			$form.find( '[name="menu_description"]' ).val( menu.description || '' );
			var instructionMessage = menu.customerInstructionMessage || menu.message || {};
			$form.find( '[name="menu_message_type"]' ).val( instructionMessage.type || 'tts' );
			$form.find( '[name="menu_message_text"]' ).val( instructionMessage.message || '' );
			$form.find( '[name="menu_tts_language"]' ).val( instructionMessage.language || 'en' );
			$form.find( '[name="menu_tts_provider"]' ).val( instructionMessage.provider || 'google' );
			$form.find( '[name="menu_tts_voice"]' ).val( instructionMessage.voice || 'gtts:free' );
			$form.find( '[name="menu_audio_asset"]' ).val( instructionMessage.audioAssetId || '' );
			$form.find( '[name="max_digits"]' ).val( ( menu.inputSettings && menu.inputSettings.maxDigits ) || 1 );
			$form.find( '[name="timeout_seconds"]' ).val( ( menu.inputSettings && menu.inputSettings.timeoutSeconds ) || 5 );
			$form.find( '[name="max_invalid_retries"]' ).val( ( menu.inputSettings && menu.inputSettings.maxInvalidRetries ) || 2 );
			$form.find( '[name="max_timeout_retries"]' ).val( ( menu.inputSettings && menu.inputSettings.maxTimeoutRetries ) || 1 );
			applyMessageState( $form, 'invalid', menu.invalidInputHandling && menu.invalidInputHandling.responseMessage );
			$form.find( '[name="invalid_action"]' ).val( ( menu.invalidInputHandling && menu.invalidInputHandling.afterMaxInvalidRetryAction && menu.invalidInputHandling.afterMaxInvalidRetryAction.type ) || 'repeat_current_menu' );
			$form.find( '[name="invalid_target_menu"]' ).val( ( menu.invalidInputHandling && menu.invalidInputHandling.afterMaxInvalidRetryAction && menu.invalidInputHandling.afterMaxInvalidRetryAction.targetMenuId ) || 'main_menu' );
			applyMessageState( $form, 'timeout', menu.timeoutHandling && menu.timeoutHandling.responseMessage );
			$form.find( '[name="timeout_next_step"]' ).val( ( menu.timeoutHandling && menu.timeoutHandling.nextStep && menu.timeoutHandling.nextStep.type ) || 'repeat_current_menu' );
			$form.find( '[name="timeout_target_menu"]' ).val( ( menu.timeoutHandling && menu.timeoutHandling.nextStep && menu.timeoutHandling.nextStep.targetMenuId ) || 'main_menu' );
			renderDtmfActions( $form, menu.dtmfActions || [] );
			renderMenuList( $form );
			updateDynamicFields( $form );
		};

		var buildFlowBuilderState = function( $form ) {
			var state = saveActiveMenuState( $form );
			var startMenuId = normalizeMenuId( state.startMenuId || state.start_menu_id || 'main_menu' );
			var menus = state.menus.map(function( menu ) {
				var normalizedMenu = $.extend( true, {}, menu );

				normalizedMenu.isStart = normalizedMenu.id === startMenuId;
				normalizedMenu.customerInstructionMessage = normalizedMenu.customerInstructionMessage || normalizedMenu.message || {
					type: 'tts',
					message: 'Please choose an option.',
					language: 'en',
					provider: 'google',
					voice: 'gtts:free'
				};
				normalizedMenu.customerInstructionMessage = normalizeSerializedMessage( normalizedMenu.customerInstructionMessage, false );
				normalizedMenu.invalidInputHandling = normalizedMenu.invalidInputHandling || {};
				normalizedMenu.invalidInputHandling.responseMessage = normalizeSerializedMessage( normalizedMenu.invalidInputHandling.responseMessage, true );
				normalizedMenu.invalidInputHandling.afterMaxInvalidRetryAction = normalizeSerializedNextStep( normalizedMenu.invalidInputHandling.afterMaxInvalidRetryAction, 'repeat_current_menu' );
				normalizedMenu.timeoutHandling = normalizedMenu.timeoutHandling || {};
				normalizedMenu.timeoutHandling.responseMessage = normalizeSerializedMessage( normalizedMenu.timeoutHandling.responseMessage, true );
				normalizedMenu.timeoutHandling.nextStep = normalizeSerializedNextStep( normalizedMenu.timeoutHandling.nextStep, 'repeat_current_menu' );
				normalizedMenu.dtmfActions = ( normalizedMenu.dtmfActions || [] ).map(function( action ) {
					action = $.extend( true, {}, action || {} );
					action.responseMessage = normalizeSerializedMessage( action.responseMessage, true );
					action.businessAction = action.businessAction || { type: 'no_action' };
					action.nextStep = normalizeSerializedNextStep( action.nextStep, 'hangup' );
					return action;
				});
				delete normalizedMenu.message;

				return normalizedMenu;
			});

			return {
				name: getFieldValue( $form, '[name="flow_name"]', 'Order Confirmation Flow' ),
				description: getFieldValue( $form, '[name="flow_description"]', '' ),
				startMenuId: startMenuId,
				menus: menus,
				transferTimeout: {
					responseMessage: buildFlowMessage(
						getFieldValue( $form, '[name="transfer_timeout_message_type"]', 'tts' ),
						getFieldValue( $form, '[name="transfer_timeout_text"]', '' ),
						getFieldValue( $form, '[name="transfer_timeout_tts_language"]', 'en' ),
						getFieldValue( $form, '[name="transfer_timeout_tts_provider"]', 'google' ),
						getFieldValue( $form, '[name="transfer_timeout_tts_voice"]', 'gtts:free' ),
						getFieldValue( $form, '[name="transfer_timeout_audio"]', 0 ),
						true
					),
					nextStep: buildNextStep(
						getFieldValue( $form, '[name="transfer_timeout_next_step"]', 'hangup' ),
						getFieldValue( $form, '[name="transfer_timeout_target_menu"]', 'main_menu' )
					)
				},
				transferFailed: {
					responseMessage: buildFlowMessage(
						getFieldValue( $form, '[name="transfer_failed_message_type"]', 'tts' ),
						getFieldValue( $form, '[name="transfer_failed_text"]', '' ),
						getFieldValue( $form, '[name="transfer_failed_tts_language"]', 'en' ),
						getFieldValue( $form, '[name="transfer_failed_tts_provider"]', 'google' ),
						getFieldValue( $form, '[name="transfer_failed_tts_voice"]', 'gtts:free' ),
						getFieldValue( $form, '[name="transfer_failed_audio"]', 0 ),
						true
					),
					nextStep: buildNextStep(
						getFieldValue( $form, '[name="transfer_failed_next_step"]', 'hangup' ),
						getFieldValue( $form, '[name="transfer_failed_target_menu"]', 'main_menu' )
					)
				}
			};
		};

		var updateFlowBuilderPreview = function( $form ) {
			var flow = buildFlowBuilderState( $form );
			var startMenu = flow.menus.find(function( menu ) {
				return menu.isStart;
			}) || flow.menus[0] || {};
			var lines = [ startMenu.name || 'Main Menu' ];

			( startMenu.dtmfActions || [] ).forEach(function( action ) {
				var label = 'No action';

				if ( action.businessAction && 'no_action' !== action.businessAction.type ) {
					label = titleCaseValue( action.businessAction.type );
				} else if ( action.nextStep && action.nextStep.type ) {
					label = titleCaseValue( action.nextStep.type );
				}

				lines.push( '├── ' + action.inputKey + ' → ' + label );
			});

			lines.push( '└── Timeout → ' + titleCaseValue( ( startMenu.timeoutHandling && startMenu.timeoutHandling.nextStep && startMenu.timeoutHandling.nextStep.type ) || 'repeat_current_menu' ) );
			$form.find( '.wp-dialyra-flow-preview' ).text( lines.join( "\n" ) );
			$form.find( '#wp-dialyra-frontend-flow-json' ).val( JSON.stringify( flow ) );
			renderMenuList( $form );
			syncMenuTargetOptions( $form );

			return flow;
		};

		$( document ).on( 'change input', '[data-dialyra-flow-builder-form] input, [data-dialyra-flow-builder-form] select, [data-dialyra-flow-builder-form] textarea', function() {
			var $form = $( this ).closest( '[data-dialyra-flow-builder-form]' );

			if ( $form.length ) {
				updateFlowBuilderPreview( $form );
			}
		});

		$( document ).on( 'click', '#wp-dialyra-add-menu', function() {
			var $form = $( this ).closest( '[data-dialyra-flow-builder-form]' );
			var state;
			var nextNumber;
			var menuId;
			var menuName;

			if ( ! $form.length ) {
				return;
			}

			state = saveActiveMenuState( $form );
			nextNumber = state.menus.length + 1;
			menuName = 'Menu ' + nextNumber;
			menuId = normalizeMenuId( menuName );

			while ( state.menus.some(function( menu ) {
				return menu.id === menuId;
			}) ) {
				nextNumber++;
				menuName = 'Menu ' + nextNumber;
				menuId = normalizeMenuId( menuName );
			}

				state.menus.push({
				id: menuId,
				name: menuName,
				isStart: false,
				description: '',
				customerInstructionMessage: {
					type: 'tts',
					message: 'Please choose an option.',
					language: 'en',
					provider: 'google',
					voice: 'gtts:free'
				},
				inputSettings: {
					maxDigits: 1,
					timeoutSeconds: 5,
					maxInvalidRetries: 2,
					maxTimeoutRetries: 1
				},
				dtmfActions: [
					{
						inputKey: '1',
						responseMessage: { type: 'tts', message: '', language: 'en', provider: 'google', voice: 'gtts:free' },
						businessAction: { type: 'no_action' },
						nextStep: { type: 'hangup' }
					}
				],
				invalidInputHandling: {
					responseMessage: { type: 'tts', message: 'Sorry, that option is not available.', language: 'en', provider: 'google', voice: 'gtts:free' },
					afterMaxInvalidRetryAction: { type: 'repeat_current_menu' }
				},
				timeoutHandling: {
					responseMessage: { type: 'tts', message: 'We could not complete this step. Please try again.', language: 'en', provider: 'google', voice: 'gtts:free' },
					nextStep: { type: 'repeat_current_menu' }
				}
			});

			$form.data( 'dialyraFlowMenus', state );
			loadMenuEditor( $form, menuId );
			updateFlowBuilderPreview( $form );
		});

		$( document ).on( 'click', '[data-dialyra-remove-menu]', function() {
			var $form = $( this ).closest( '[data-dialyra-flow-builder-form]' );
			var state;
			var menuId = String( $( this ).closest( '[data-dialyra-menu-id]' ).data( 'dialyraMenuId' ) || '' );
			var menu;
			var references;

			if ( ! $form.length || ! menuId ) {
				return;
			}

			state = saveActiveMenuState( $form );
			menu = ( state.menus || [] ).find(function( item ) {
				return item.id === menuId;
			});

			if ( ! menu ) {
				return;
			}

			if ( 'main_menu' === menuId ) {
				showFlowBuilderMenuDialog({
					mode: 'notice',
					tone: 'danger',
					title: 'Main Menu cannot be deleted',
					message: 'Main Menu is the start point for every call flow, so it must stay in the builder.'
				});
				return;
			}

			references = menuReferenceLabels( state, menuId );

			if ( references.length ) {
				showFlowBuilderMenuDialog({
					mode: 'notice',
					tone: 'danger',
					title: 'Menu is still connected',
					message: 'This menu is already used as a Go To Menu target in another menu. Change those Next step fields first, then delete it.',
					references: references
				});
				return;
			}

			showFlowBuilderMenuDialog({
				mode: 'confirm',
				tone: 'danger',
				title: 'Delete menu?',
				message: 'This removes "' + ( menu.name || menuId ) + '" from the flow builder. This action only changes the draft until you save or publish.',
				confirmText: 'Delete menu',
				onConfirm: function() {
					state.menus = state.menus.filter(function( item ) {
						return item.id !== menuId;
					});

					state.activeMenuId = state.startMenuId || 'main_menu';
					$form.data( 'dialyraFlowMenus', state );
					loadMenuEditor( $form, state.activeMenuId );
					updateFlowBuilderPreview( $form );
				}
			});
		});

		$( document ).on( 'click', '[data-dialyra-select-menu]', function() {
			var $form = $( this ).closest( '[data-dialyra-flow-builder-form]' );
			var menuId = String( $( this ).closest( '[data-dialyra-menu-id]' ).data( 'dialyraMenuId' ) || '' );

			if ( $form.length && menuId ) {
				saveActiveMenuState( $form );
				loadMenuEditor( $form, menuId );
				updateFlowBuilderPreview( $form );
			}
		});

		var applyFallbackState = function( $form, prefix, fallback, defaultNextStep ) {
			fallback = fallback || {};
			applyMessageState( $form, prefix, fallback.responseMessage || { type: 'tts' } );
			$form.find( '[name="' + prefix + '_next_step"]' ).val( ( fallback.nextStep && fallback.nextStep.type ) || defaultNextStep || 'hangup' );
			$form.find( '[name="' + prefix + '_target_menu"]' ).val( ( fallback.nextStep && fallback.nextStep.targetMenuId ) || 'main_menu' );
		};

		var hydrateFlowBuilderDraft = function( $form ) {
			var $draft = $( '#wp-dialyra-flow-draft-json' );
			var rawDraft = $.trim( $draft.text() || '' );
			var draft;
			var startMenuId;
			var state;

			if ( ! rawDraft || '[]' === rawDraft || '{}' === rawDraft ) {
				return false;
			}

			try {
				draft = JSON.parse( rawDraft );
			} catch ( error ) {
				return false;
			}

			if ( ! draft || ! Array.isArray( draft.menus ) || ! draft.menus.length ) {
				return false;
			}

			startMenuId = normalizeMenuId( draft.startMenuId || draft.start_menu_id || draft.startMenu || draft.start_menu || draft.menus[0].id || 'main_menu' );
			state = {
				startMenuId: startMenuId,
				activeMenuId: startMenuId,
				menus: draft.menus.map(function( menu, index ) {
					var menuId = normalizeMenuId( menu.id || menu.menuId || menu.menu_id || menu.name || ( 'menu_' + ( index + 1 ) ) );
					var normalizedMenu = $.extend( true, {}, menu );

					normalizedMenu.id = menuId;
					normalizedMenu.name = normalizedMenu.name || normalizedMenu.menuName || normalizedMenu.menu_name || titleCaseValue( menuId ) || 'Main Menu';
					normalizedMenu.description = normalizedMenu.description || normalizedMenu.menuDescription || normalizedMenu.menu_description || '';
					normalizedMenu.customerInstructionMessage = normalizedMenu.customerInstructionMessage || normalizedMenu.customer_instruction_message || normalizedMenu.instructionMessage || normalizedMenu.message || {
						type: 'tts',
						message: 'Please choose an option.',
						language: 'en',
						provider: 'google',
						voice: 'gtts:free'
					};
					normalizedMenu.inputSettings = normalizedMenu.inputSettings || normalizedMenu.menuInputSettings || normalizedMenu.menu_input_settings || {
						maxDigits: 1,
						timeoutSeconds: 5,
						maxInvalidRetries: 2,
						maxTimeoutRetries: 1
					};
					normalizedMenu.dtmfActions = normalizedMenu.dtmfActions || normalizedMenu.dtmf_actions || normalizedMenu.actions || [];
					normalizedMenu.invalidInputHandling = normalizedMenu.invalidInputHandling || normalizedMenu.invalid_input_handling || {
						responseMessage: { type: 'tts', message: 'Sorry, that option is not available.', language: 'en', provider: 'google', voice: 'gtts:free' },
						afterMaxInvalidRetryAction: { type: 'repeat_current_menu' }
					};
					normalizedMenu.timeoutHandling = normalizedMenu.timeoutHandling || normalizedMenu.timeout_handling || {
						responseMessage: { type: 'tts', message: 'We could not complete this step. Please try again.', language: 'en', provider: 'google', voice: 'gtts:free' },
						nextStep: { type: 'repeat_current_menu' }
					};
					normalizedMenu.isStart = menuId === startMenuId;
					delete normalizedMenu.message;

					return normalizedMenu;
				})
			};

			if ( ! state.menus.some(function( menu ) {
				return menu.id === startMenuId;
			}) ) {
				startMenuId = state.menus[0].id;
				state.startMenuId = startMenuId;
				state.activeMenuId = startMenuId;
				state.menus[0].isStart = true;
			}

			$form.find( '[name="flow_name"]' ).val( draft.name || '' );
			$form.find( '[name="flow_description"]' ).val( draft.description || '' );
			$form.data( 'dialyraFlowMenus', state );
			$form.data( 'dialyraActiveMenuId', startMenuId );
			applyFallbackState( $form, 'transfer_timeout', draft.transferTimeout || draft.transfer_timeout, 'hangup' );
			applyFallbackState( $form, 'transfer_failed', draft.transferFailed || draft.transfer_failed, 'hangup' );
			loadMenuEditor( $form, startMenuId );

			return true;
		};

		$( document ).on( 'click', '[data-dialyra-flow-action]', function() {
			$( '#wp-dialyra-flow-builder-action' ).val( String( $( this ).data( 'dialyraFlowAction' ) || 'publish_flow' ) );
		});

		$( document ).on( 'submit', '[data-dialyra-flow-builder-form]', function() {
			updateFlowBuilderPreview( $( this ) );
		});

		$( document ).on( 'click', '[data-dialyra-flow-preview-link]', function() {
			var $form = $( '[data-dialyra-flow-builder-form]' ).first();

			if ( $form.length && window.localStorage ) {
				window.localStorage.setItem( 'wpDialyraFrontendFlow', JSON.stringify( updateFlowBuilderPreview( $form ) ) );
			}
		});

		var updateProductPickerSummary = function( $picker ) {
			var selectedNames = [];

			$picker.find( '[data-dialyra-product-checkbox]:checked' ).each(function() {
				var label = $( this ).closest( '[data-dialyra-product-option]' ).find( 'span' ).first().text();

				if ( label ) {
					selectedNames.push( label );
				}
			});

			$picker.find( '[data-dialyra-selected-product-count]' ).text( selectedNames.length );

			var $selectedList = $picker.find( '[data-dialyra-selected-product-list]' );
			$selectedList.empty();

			selectedNames.slice( 0, 8 ).forEach(function( name ) {
				$( '<em />' ).text( name ).appendTo( $selectedList );
			});

			if ( selectedNames.length > 8 ) {
				$( '<em />' ).text( '+' + ( selectedNames.length - 8 ) + ' more' ).appendTo( $selectedList );
			}
		};

		$( document ).on( 'click', '[data-dialyra-open-product-picker]', function() {
			var $button = $( this );
			var $picker = $( '[data-dialyra-product-picker]' );
			var selectedProducts = String( $button.data( 'dialyraSelectedProducts' ) || '' ).split( ',' ).filter(Boolean);
			var flowName = String( $button.data( 'dialyraFlowName' ) || '' );

			$picker.find( '[data-dialyra-product-flow-id]' ).val( $button.data( 'dialyraFlowId' ) || '' );
			$picker.find( '[data-dialyra-product-flow-name]' ).text( flowName ? 'Assign products to “' + flowName + '”.' : 'Choose products that should use this flow instead of the default flow.' );
			$picker.find( '[data-dialyra-product-search]' ).val( '' );
			$picker.find( '[data-dialyra-product-option]' ).prop( 'hidden', false );
			$picker.find( '[data-dialyra-product-checkbox]' ).prop( 'checked', false ).each(function() {
				$( this ).prop( 'checked', -1 !== selectedProducts.indexOf( String( $( this ).val() ) ) );
			});
			updateProductPickerSummary( $picker );
			$picker.prop( 'hidden', false );
		});

		$( document ).on( 'input', '[data-dialyra-product-search]', function() {
			var query = String( $( this ).val() || '' ).toLowerCase();
			var $picker = $( this ).closest( '[data-dialyra-product-picker]' );

			$picker.find( '[data-dialyra-product-option]' ).each(function() {
				var haystack = String( $( this ).data( 'dialyraProductText' ) || '' );
				$( this ).prop( 'hidden', query && -1 === haystack.indexOf( query ) );
			});
		});

		$( document ).on( 'change', '[data-dialyra-product-checkbox]', function() {
			updateProductPickerSummary( $( this ).closest( '[data-dialyra-product-picker]' ) );
		});

		$( document ).on( 'click', '[data-dialyra-close-product-picker]', function() {
			$( '[data-dialyra-product-picker]' ).prop( 'hidden', true );
		});

		var openDialog = function( dialogId ) {
			var $dialog = $( '#' + dialogId );

			if ( ! $dialog.length ) {
				return;
			}

			if ( ! $dialog.parent().is( 'body' ) ) {
				$dialog.appendTo( document.body );
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

		var showFlowBuilderMenuDialog = function( options ) {
			var $dialog = $( '#wp-dialyra-flow-menu-dialog' );
			var $confirmButton = $dialog.find( '[data-dialyra-flow-menu-confirm]' );
			var $references = $dialog.find( '[data-dialyra-flow-menu-references]' );

			options = options || {};

			if ( ! $dialog.length ) {
				return;
			}

			$dialog
				.toggleClass( 'wp-dialyra-dialog--danger', 'danger' === options.tone )
				.data( 'dialyraFlowMenuConfirm', 'function' === typeof options.onConfirm ? options.onConfirm : null );
			$dialog.find( '[data-dialyra-flow-menu-title]' ).text( options.title || 'Menu action' );
			$dialog.find( '[data-dialyra-flow-menu-message]' ).text( options.message || '' );

			$references.empty();
			( options.references || [] ).forEach(function( reference ) {
				$references.append( $( '<li></li>' ).text( reference ) );
			});
			$references.prop( 'hidden', ! ( options.references || [] ).length );

			$confirmButton
				.text( options.confirmText || 'Confirm' )
				.prop( 'hidden', 'confirm' !== options.mode );

			openDialog( 'wp-dialyra-flow-menu-dialog' );
		};

		$( document ).on( 'click', '[data-dialyra-flow-menu-confirm]', function() {
			var $dialog = $( this ).closest( '[data-dialyra-dialog]' );
			var onConfirm = $dialog.data( 'dialyraFlowMenuConfirm' );

			if ( 'function' === typeof onConfirm ) {
				onConfirm();
			}

			closeDialogs();
		});

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
		updateBusinessHoursFields( $( document ) );
		$( '#wp-dialyra-business-select' ).each(function() {
			updateSettingsBusinessDetails( $( this ) );
		});
		$( '[data-dialyra-agent-editor]' ).each(function() {
			updateAgentEditor( $( this ) );
		});
		$( '[data-dialyra-flow-builder-form]' ).each(function() {
			var $form = $( this );

			hydrateFlowBuilderDraft( $form );
			updateFlowBuilderPreview( $form );
		});
	});

})( jQuery );
