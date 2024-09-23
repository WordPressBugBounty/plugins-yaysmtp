<?php
use YaySMTP\Helper\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


$importedLogPlugins   = Utils::getImportedLogPluginSetting();
$yaysmtpImportPlugins = Utils::getEmailLogsImportPlugins();

$optDisabledCount = 0; 
foreach ( $yaysmtpImportPlugins as $pluginEl ) {
	if( !empty($importedLogPlugins) && !empty($importedLogPlugins[$pluginEl['val']])) {
		$optDisabledCount++;
	}
}

$yaySmtpImportTitle = __( 'We have found previous email logs from other plugin on your site. Please choose one plugin\'s email logs that you want to import to YaySMTP.', 'yay-smtp' );
if ( empty( $yaysmtpImportPlugins ) ) {
	$yaySmtpImportTitle = __( 'We have found no previous email logs from other plugins on your site.', 'yay-smtp' );
}
?>

<div class="yay-smtp-card-body">
	<div class="setting-el">
		<div class="setting-label">
			<label><?php echo esc_html__( 'Import email logs to YaySMTP', 'yay-smtp' ); ?></label>
		</div>

		<div class="yay-smtp-card">
			<div class="yay-smtp-card-body">	
				<div>
					<label><?php echo wp_kses_post( $yaySmtpImportTitle ); ?></label>
					<?php if ( ! empty( $yaysmtpImportPlugins )) { 
					?>
					<div class="yay-smtp-card-body-child yaysmtp-email-logs-plugin-import-wrap">
						<select class="yay-settings email-logs-plugin-import" id="yaysmtp_email_logs_plugin_import">
						<?php
						if( $optDisabledCount === count($yaysmtpImportPlugins) ){
							echo '<option value="">' . esc_html__( 'All plugins have been imported', 'yay-smtp' ) . '</option>';
						}
						foreach ( $yaysmtpImportPlugins as $pluginEl ) {
							$disable = !empty($importedLogPlugins) && !empty($importedLogPlugins[$pluginEl['val']]) ? 'disabled' : '';

							$optEl = '<option value="' . esc_attr( $pluginEl['val'] ) . '"' . $disable . ' data-icon-src="' . esc_attr( YAY_SMTP_PLUGIN_URL )  . 'assets/img/' . esc_attr( $pluginEl['img'] ) . '">';
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
				<button type="button" <?php echo count($yaysmtpImportPlugins) == $optDisabledCount ? 'disabled': '' ?> class="yay-smtp-button-secondary yaysmtp-import-email-logs-btn"><?php echo esc_html__( 'Import email logs', 'yay-smtp' ); ?></button>
				<?php } ?>
			</div>
		</div>
		
	</div>
	</div>
</div>







