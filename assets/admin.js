/**
 * Custom Login Logo by VFIX.PK - Admin JS
 *
 * Wires the "Choose Image" / "Remove Logo" buttons on the settings page to
 * the native WordPress Media Library uploader (wp.media). No custom upload
 * handling, no AJAX file transport, no direct file access of any kind --
 * everything goes through core WordPress media APIs.
 */
( function ( $ ) {
	'use strict';

	$( function () {
		var frame;
		var $chooseButton = $( '#cll-choose-logo' );
		var $removeButton = $( '#cll-remove-logo' );
		var $preview       = $( '#cll-logo-preview' );
		var $hiddenInput   = $( '#cll_logo_attachment_id' );

		$chooseButton.on( 'click', function ( e ) {
			e.preventDefault();

			if ( frame ) {
				frame.open();
				return;
			}

			frame = wp.media( {
				title: ( window.CLLAdmin && CLLAdmin.chooseTitle ) || 'Select or Upload Login Logo',
				button: {
					text: ( window.CLLAdmin && CLLAdmin.chooseButton ) || 'Use this image'
				},
				library: {
					type: 'image'
				},
				multiple: false
			} );

			frame.on( 'select', function () {
				var attachment = frame.state().get( 'selection' ).first().toJSON();

				$hiddenInput.val( attachment.id );

				var previewSrc = ( attachment.sizes && attachment.sizes.medium )
					? attachment.sizes.medium.url
					: attachment.url;

				$preview.attr( 'src', previewSrc ).show();
				$removeButton.show();
			} );

			frame.open();
		} );

		$removeButton.on( 'click', function ( e ) {
			e.preventDefault();
			$hiddenInput.val( '0' );
			$preview.attr( 'src', '' ).hide();
			$removeButton.hide();
		} );
	} );
} )( jQuery );
