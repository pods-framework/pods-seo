jQuery( window ).on( 'YoastSEO:ready', function() {

	YoastSEO.app.registerPlugin( 'Pods_Content_Analysis', { status : 'loading' } );

	var pods_field_content = '',
		pods_key,
		$pods_field,
		pods_value;

	for ( pods_key in pods_seo_settings.fields ) {
		$pods_field = jQuery( pods_seo_settings.fields[ pods_key ] );

		if ( $pods_field[0] ) {
			pods_value = '';

			$pods_field.each( function() {

				var value = pods_get_tinymce_content( jQuery( this ) );

				if ( '' !== value ) {
					pods_value += ' ' + value;
				}

			} );

			if ( '' !== pods_value ) {
				pods_field_content += ' ' + pods_value;
			}
		}
	}

	YoastSEO.app.pluginReady( 'Pods_Content_Analysis' );
	YoastSEO.app.registerModification( 'content', pods_append_content, 'Pods_Content_Analysis', 50 );

	function pods_append_content( content ) {

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

} );
