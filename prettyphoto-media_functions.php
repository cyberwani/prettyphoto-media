<?php
/*
 * prettyPhoto Media plugin functions
 */

add_action( 'init', 'prettyphoto_init_functions' );

function prettyphoto_init_functions() {
	$old_version = prettyphoto_get_option( 'version' );

	if ( get_option( 'prettyphoto_settings' === false ) ) {
		add_option( 'prettyphoto_settings', prettyphoto_get_default_settings() );
	} elseif ( $old_version != PRETTYPHOTO_VERSION ) {
		prettyphoto_update_settings();
	}

	if ( !is_admin() ) {
		if ( prettyphoto_get_option( 'loadppcss' ) )
			wp_enqueue_style( 'prettyphoto', PRETTYPHOTO_URI . 'css/prettyPhoto.min.css', false, '3.1.5', 'screen' );
		if ( prettyphoto_get_option( 'loadppjs' ) )
			wp_enqueue_script( 'prettyphoto', PRETTYPHOTO_URI . 'js/jquery.prettyPhoto.min.js', array('jquery'), '3.1.5', true );
	}

	if ( prettyphoto_get_option( 'wpautogallery' ) ) {
		add_filter( 'wp_get_attachment_link', 'prettyphoto_get_attachment_link', 10, 4 );
	}
	add_action( 'wp_footer', 'prettyphoto_action_footer', 99 );
}

function prettyphoto_action_footer() {
	$ppm_defaults = prettyphoto_get_default_settings( 'ppm_custom' );
	$ppm_custom = prettyphoto_get_option( 'ppm_custom' );

	foreach ( $ppm_defaults as $key => $value ) {
		$saved_value = $ppm_custom[$key];
		if ( $saved_value !== $value ) {
			if ( is_bool( $saved_value ) ) {
				$options_changed[] = ($saved_value == 1) ? $key . ': ' . 'true' : $key . ': ' . 'false';
			} elseif ( is_numeric( $saved_value ) ) {
				$options_changed[] = $key . ': ' . $saved_value;
			} elseif ( is_string( $saved_value ) ) {
				$options_changed[] = $key . ': ' . '\'' . $saved_value . '\'';
			} else {
				$options_changed[] = $key . ': ' . $saved_value;
			}
		}
	}
	$pphook = prettyphoto_get_option( 'hook' );
	$ppselector = prettyphoto_get_option( 'ppselector' );
	$ppoptions = isset( $options_changed ) ? '{' . implode( ', ', $options_changed ) . '}' : '';
	?>
	<script>
		(function($) {
			$(function() {
	<?php if ( prettyphoto_get_option( 'jquery_attributes' ) ) : ?>
					$('a[href$=".jpg"], a[href$=".jpeg"], a[href$=".png"], a[href$=".gif"]').not('[<?php echo $pphook; ?>*="<?php echo $ppselector; ?>"], .nolightbox').attr('<?php echo $pphook; ?>', function() {
						var gallery_id = $(this).closest('[id^="gallery-"]').attr('id');
						var post_id = $(this).closest('[id^="post-"]').attr('id');
						return '<?php echo $ppselector; ?>' + '[' + (gallery_id ? gallery_id : post_id) + ']';
					});
	<?php endif; ?>
				responsive_pp();
				$(window).resize(function() {
					responsive_pp();
				});
			});
			function responsive_pp() {
				var ppsel = $('a[<?php echo $pphook; ?>^="<?php echo $ppselector; ?>"]');
				if ($(window).width() < 400) {
					ppsel.unbind('click.prettyphoto');
				} else {
					ppsel.prettyPhoto(<?php echo $ppoptions; ?>);
				}
			}
		})(jQuery);
	</script>
	<?php
}

function prettyphoto_get_attachment_link( $html, $id, $size, $permalink ) {
	global $post;
	$pid = $post->ID;
	$hook = prettyphoto_get_option( 'hook' );
	$selector = prettyphoto_get_option( 'ppselector' );

	if ( !$permalink )
		$html = preg_match( '/' . $hook . '="/', $html ) ? str_replace( $hook . '="', $hook . '="' . $selector . '[gallery-' . $pid . '] ', $html ) : str_replace( '<a ', '<a ' . $hook . '="' . $selector . '[gallery-' . $pid . ']" ', $html );

	return $html;
}

function prettyphoto_get_option( $option ) {
	$settings = get_option( 'prettyphoto_settings' );

	if ( !is_array( $settings ) || !array_key_exists( $option, $settings ) )
		return false;

	return $settings[$option];
}

function prettyphoto_update_settings() {
	$settings = get_option( 'prettyphoto_settings' );
	$default_settings = prettyphoto_get_default_settings();
	$settings['version'] = PRETTYPHOTO_VERSION;

	foreach ( $settings as $set_key => $set_value ) {
		if ( !array_key_exists( $set_key, $default_settings ) ) {
			delete_option( "prettyphoto_settings[$set_key]" );
			unset( $settings[$set_key] );
		}
	}

	foreach ( $default_settings as $def_key => $def_value ) {
		if ( !array_key_exists( $def_key, $settings ) )
			$settings[$def_key] = $def_value;
	}

	update_option( 'prettyphoto_settings', $settings );
}

function prettyphoto_get_default_settings( $subgroup = '' ) {
	$settings = array(
		'version' => PRETTYPHOTO_VERSION,
		'loadppcss' => 1,
		'loadppjs' => 1,
		'ppselector' => 'prettyPhoto',
		'hook' => 'rel',
		'wpautogallery' => 0,
		'jquery_attributes' => 0,
		'show_twitter' => false,
		'show_facebook' => false,
		'ppm_custom' => array(
			'theme' => 'pp_default',
			'animation_speed' => 'fast',
			'slideshow' => 5000,
			'autoplay_slideshow' => false,
			'opacity' => .8,
			'show_title' => true,
			'allow_resize' => true,
			'allow_expand' => true,
			'default_width' => 500,
			'default_height' => 344,
			'counter_separator_label' => '/',
			'horizontal_padding' => 20,
			'hideflash' => false,
			'wmode' => 'opaque',
			'autoplay' => true,
			'modal' => false,
			'deeplinking' => true,
			'overlay_gallery' => true,
			'overlay_gallery_max' => 30,
			'keyboard_shortcuts' => true,
			'ie6_fallback' => true,
			'social_tools' => false
		)
	);

	if ( $subgroup )
		return $settings[$subgroup];

	return $settings;
}
