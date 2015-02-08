<?php
add_action('admin_print_scripts', 'wpomc_admin_scripts');
function wpomc_admin_scripts() {
	wp_enqueue_style('morris_css', '//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.css');
	wp_enqueue_script('raphael_js', '//cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js', false, '1.0');
	wp_enqueue_script('morris_js', '//cdnjs.cloudflare.com/ajax/libs/morris.js/0.5.1/morris.min.js', false, '1.0');
}

add_action( 'admin_menu', 'wpomc_admin_menu' );

function wpomc_admin_menu() {
	add_options_page( __( 'WP One Metric Setting', WPOMC_DOMAIN ), __( 'WP One Metric Setting', WPOMC_DOMAIN ), 'manage_options', 'wpomc', 'wpomc_options_page');
}

function wpomc_options_page() {
?>
<div class="wrap">
<?php screen_icon(); ?>

<h2><?php _e( 'WP One Metric Setting', WPOMC_DOMAIN ); ?></h2>

<form action="options.php" method="post">
<?php settings_fields( 'wpomc_options' ); ?>
<?php do_settings_sections( 'wpomc' ); ?>

<p class="submit"><input name="Submit" type="submit" value="<?php _e( 'save', WPOMC_DOMAIN ) ?>" class="button-primary" /></p>
</form>

<div id="one-metric" style="height: 250px;"></div>

</div>
<?php
}

add_action( 'admin_init', 'wpomc_admin_init' );
function wpomc_admin_init() {
	register_setting( 'wpomc_options', 'wpomc_options', 'wpomc_options_validate' );

	add_settings_section( 'wpomc_main', __( 'Google Analytics Setting', WPOMC_DOMAIN ), 'wpomc_section_text', 'wpomc' );

	add_settings_field( 'wpomc_email', __( 'E-Mail', WPOMC_DOMAIN ), 'wpomc_setting_email',
		'wpomc', 'wpomc_main' );

	add_settings_field( 'wpomc_pass', __( 'Password', WPOMC_DOMAIN ), 'wpomc_setting_pass',
		'wpomc', 'wpomc_main' );

	add_settings_field( 'wpomc_profile_id',  __( 'Profile ID', WPOMC_DOMAIN ), 'wpomc_setting_profile_id',
		'wpomc', 'wpomc_main' );

}

function wpomc_section_text() {
}

function wpomc_setting_email() {
	$options = get_option( 'wpomc_options' );

	echo '<input id="wpomc_email" name="wpomc_options[email]" size="40" type="text" value="' . esc_attr( $options['email'] ) . '" />';
}

function wpomc_setting_pass() {
	$options = get_option( 'wpomc_options' );

	echo '<input id="wpomc_pass" name="wpomc_options[pass]" size="40" type="password" value="' . esc_attr( $options['pass'] ) . '" />';
}

function wpomc_setting_profile_id() {
	$options = get_option( 'wpomc_options' );

	echo '<input id="wpomc_user_profile_id" name="wpomc_options[profile_id]" size="40" type="text" value="' . esc_attr( $options['profile_id'] ) . '" />';
}

function wpomc_options_validate( $input ) {
	$newinput['email'] = trim( $input['email'] );
	$newinput['pass'] = trim( $input['pass'] );
	$newinput['profile_id'] = trim( $input['profile_id'] );

	return $newinput;
}

?>