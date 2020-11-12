	<div id="screen_preloader"><h3>Modal Survey for WordPress</h3><img src="<?php print(plugins_url( '/assets/img/screen_preloader.gif' , __FILE__ ));?>"><h5><?php esc_html_e( 'LOADING', MODAL_SURVEY_TEXT_DOMAIN );?><br><br><?php esc_html_e( 'Please wait...', MODAL_SURVEY_TEXT_DOMAIN );?></h5></div>
	<div class="wrap pantherius-jquery-ui wrap-padding">
	<br />
	<div class="title-border">
		<h3><?php esc_html_e( 'Social Settings', MODAL_SURVEY_TEXT_DOMAIN );?></h3>
		<div class="help_link"><a target="_blank" href="http://modalsurvey.pantherius.com/documentation/#line5"><?php esc_html_e( 'Documentation', MODAL_SURVEY_TEXT_DOMAIN );?></a></div>
	</div>
		<form method="post" action="options.php">
		<?php
			if ( isset( $_REQUEST[ 'settings-updated' ] ) ) {
		?>
			<div id="message" class="updated below-h2">
				<p>
					<?php esc_html_e( 'Settings saved.', MODAL_SURVEY_TEXT_DOMAIN );?>
				</p>
			</div>
		<?php 
			}
		?>
			<?php settings_fields( 'modal_survey_social-group' ); ?>
			<?php do_settings_fields( 'modal_survey_social-group', 'modal_survey_social-section' ); ?>
			<?php do_settings_sections( 'modal_survey_social' ); ?>
			<?php submit_button(); ?>
		</form>
	</div>