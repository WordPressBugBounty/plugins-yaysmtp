<?php
use YaySMTP\Helper\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$yaysmtpImportPlugins = Utils::getYaysmtpImportPlugins();

$yaySmtpImportTitle = __( 'We have found previous SMTP settings from other plugin on your site. Please choose one plugin\'s settings that you want to import to YaySMTP.', 'yay-smtp' );
if ( empty( $yaysmtpImportPlugins ) ) {
	$yaySmtpImportTitle = __( 'We have found no previous SMTP settings from other plugins on your site.', 'yay-smtp' );
}
?>

<div class="yay-smtp-card-body">
	<div class="setting-el">
		<div class="setting-label">
			<label><?php echo esc_html__( 'Import SMTP settings to YaySMTP', 'yay-smtp' ); ?></label>
		</div>

		<div class="yay-smtp-card">
			<div class="yay-smtp-card-body">	
				<div>
					<label><?php echo wp_kses_post( $yaySmtpImportTitle ); ?></label>
					<?php if ( ! empty( $yaysmtpImportPlugins ) ) { ?>
					<div class="yay-smtp-card-body-child yaysmtp-settings-plugin-import-wrap">
						<select class="yay-settings settings-plugin-import" id="yaysmtp_settings_plugin_import">
						<?php
						foreach ( $yaysmtpImportPlugins as $pluginEl ) {
							$optEl = '<option value="' . esc_attr( $pluginEl['val'] ) . '" data-icon-src="' . esc_attr( YAY_SMTP_PLUGIN_URL )  . 'assets/img/' . esc_attr( $pluginEl['img'] ) . '">';
							$optEl .= esc_attr( $pluginEl['title'] );
							$optEl .= '</option>';

							echo $optEl;
						}
						?>
						</select>
					</div>
					<?php } ?>
				</div>
				<div>
				<?php if ( ! empty( $yaysmtpImportPlugins ) ) { ?>
				<button type="button" class="yay-smtp-button-secondary yaysmtp-import-settings-btn"><?php echo esc_html__( 'Import Settings', 'yay-smtp' ); ?></button>
				<?php } ?>
			</div>
		</div>
		
	</div>
	</div>
</div>





