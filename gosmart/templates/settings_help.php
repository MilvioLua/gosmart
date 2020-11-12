<div id="screen_preloader"><h3>Modal Survey for WordPress</h3><img src="<?php print(plugins_url( '/assets/img/screen_preloader.gif' , __FILE__ ));?>"><h5><?php esc_html_e( 'LOADING', MODAL_SURVEY_TEXT_DOMAIN );?><br><br><?php esc_html_e( 'Please wait...', MODAL_SURVEY_TEXT_DOMAIN );?></h5></div>
<div class="wrap pantherius-jquery-ui wrap-padding">
<br />
<div class="title-border">
	<h3><?php esc_html_e( 'Help', MODAL_SURVEY_TEXT_DOMAIN );?></h3>
</div>
	<form method="post" class="ee-form" action="options.php">
		<p>
			<?php esc_html_e( 'To see the full documentation, please click on the following link:', MODAL_SURVEY_TEXT_DOMAIN );?> <a target="_blank" href="http://modalsurvey.pantherius.com/documentation"><?php esc_html_e( 'Documentation', MODAL_SURVEY_TEXT_DOMAIN );?></a>
		</p>
		<p>    
		<?php print(file_get_contents("http://static.pantherius.com/plugin_directory.html")); ?>
		</p>
	</form>
</div>