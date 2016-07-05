jQuery( window ).on( 'YoastSEO:ready', function() {

	YoastSEO.app.registerPlugin( 'Pods_Content_Analysis', { status : 'loading' } );

	var pods_field_content = '';

	function pods_build_content() {

		var pods_key,
			$pods_field,
			pods_value = '';

		pods_field_content = '';

		// Text
		for ( pods_key in pods_seo_settings.fields.text ) {
			$pods_field = jQuery( pods_seo_settings.fields.text[ pods_key ] );

			if ( $pods_field[0] ) {

				$pods_field.each( function() {

					var value = pods_get_tinymce_content( jQuery( this ) );

					if ( '' !== value ) {
						pods_value += ' ' + value;
					}

				} );

			}
		}

		// Images
		for ( pods_key in pods_seo_settings.fields.images ) {
			$pods_field = jQuery( pods_seo_settings.fields.images[ pods_key ] );

			if ( $pods_field[0] ) {

				$pods_field.each( function() {

					var value = pods_get_image_content( jQuery( this ) );

					if ( '' !== value ) {
						pods_value += ' ' + value;
					}

				} );

			}
		}

		if ( '' !== pods_value ) {
			pods_field_content += ' ' + pods_value;
		}

	}

	YoastSEO.app.pluginReady( 'Pods_Content_Analysis' );
	YoastSEO.app.registerModification( 'content', pods_append_content, 'Pods_Content_Analysis', 50 );

	function pods_append_content( content ) {

		pods_build_content();

		if ( '' !== pods_field_content ) {
			content += ' ' + pods_field_content;
		}

		return content;

	}

	function pods_get_tinymce_content( $element ) {

		var content = '',
			$editor;

		if ( $element[0] ) {
			content = $element.val();

			if ( pods_is_tinymce_active( $element ) ) {
				$editor = tinyMCE.get( $element.attr( 'id' ) );

				if ( $editor ) {
					content = $editor.getContent();
				}
			}

			content = jQuery.trim( content );
		}

		return content;

	}

	function pods_is_tinymce_active( $element ) {

		var is_tinymce = false;

		if ( $element[0] ) {
			var $parent_wrap = $element.closest( '.wp-editor-wrap' );

			if ( $parent_wrap[0] ) {
				is_tinymce = $parent_wrap.hasClass( 'tmce-active' );
			}
		}

		return is_tinymce;

	}

	/**
	 * Get image content from image fields and convert them to a HTML string for analyzing
	 * @since 2.0.1
	 */
	function pods_get_image_content( $element ) {

		var content = '';

		if ( $element[0] ) {
			
			// Pods 2.7+
			if ( $element.find('.pods-flex-list').length ) {

				$element.find('.pods-flex-item').each( function() {

					var img = jQuery( '.pods-flex-icon img', this ).attr( 'src' );
					var alt = '';

					if ( jQuery( '.pods-flex-name input', this ).length ) {
						// Titles are editable
						alt = jQuery( '.pods-flex-name input', this ).val();
					} else {
						// Just the image titles (not advisable for good analysis)
						alt = jQuery( '.pods-flex-name', this ).text();
					}
					content += ' <img src="' + img + '" alt="' + alt.trim() + '" />';
				} );
			}

			// Pods < 2.7
			if ( $element.find('pods-files-list').length ) {

				$element.find('.pods-file').each( function() {

					var img = jQuery( '.pods-file-icon img', this ).attr( 'src' );
					var alt = '';

					if ( jQuery( '.pods-file-name input', this ).length ) {
						// Titles are editable
						alt = jQuery( '.pods-file-name input', this ).val();
					} else {
						// Just the image titles (not advisable for good analysis)
						alt = jQuery( '.pods-file-name', this ).text();
					}
					content += ' <img src="' + img + '" alt="' + alt.trim() + '" />';
				} );
			}

		}

		return content;
	}

} );
