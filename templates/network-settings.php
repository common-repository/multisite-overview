<?php
if ( ! current_user_can( 'manage_network' ) ) {
	wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
}

$title = __( 'Multisite Overview Settings' );

$option = get_site_option( Multisite_Overview::NETWORK_SETTING_NAME );

$sites = list_sites();

if (isset($_GET['updated'])) {
?>
<div id="message" class="updated"><p><strong><?php _e( 'Options saved.' ) ?></strong></p></div><?php
}
?>

<div class="wrap">
	<?php screen_icon( 'options-general' ); ?>
	<h2><?php echo esc_html( $title ); ?></h2>

	<p>The following setting allow you to define whether each site administrator is able to decide on their own, whether
		their contents are visible to other sites or only network administrators should be able to decide this. Changing
		this
		setting has no influence on the current settings of each site.</p>

	<form method="post"
	      action="<?php echo admin_url( 'admin-post.php?action=update_multisite_overview_network_settings' ); ?>">
		<?php wp_nonce_field( 'multisite_settings' ); ?>
		<table class="form-table">
			<tbody>
			<tr valign="top">
				<th scope="row"><label for="<?php echo Multisite_Overview::NETWORK_SETTING_NAME ?>[deciding_role]">Deciding
						Role on Sharing Content</label></th>
				<td>
					<select name="<?php echo Multisite_Overview::NETWORK_SETTING_NAME ?>[deciding_role]" required>
						<option <?php selected( $option['deciding_role'], 'site_admin', true ) ?> value="site_admin">
							Site Admin
						</option>
						<option <?php selected( $option['deciding_role'], 'network_admin', true ) ?>
							value="network_admin">Network Admin
						</option>
					</select>
				</td>
			</tr>
			</tbody>
		</table>
		<h3><?php echo __( 'Site Sharing Settings' ) ?></h3>
		<table class="form-table">
			<thead>
				<tr valign="top">
					<th><strong>Site</strong></th>
					<th><strong>Content is visible</strong></th>
					<th><strong>Content is not visible</strong></th>
					<th><strong>Has description</strong></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $sites as $site_id ) {
				$option = Multisite_Overview::get_plugin_option( 'share_site', $site_id );
				$description = Multisite_Overview::get_plugin_option( 'site_description', $site_id );
				$site = get_blog_option( $site_id, 'blogname' );
				?>
				<tr valign="top">
					<th scope="row"><a href="<?php echo get_admin_url($site_id, 'options-reading.php') ?>"><?php echo $site ?></a></th>
					<td><input type="radio" name="share_site[<?php echo $site_id ?>]" value="1" <?php checked( $option, '1' ) ?> "></td>
					<td><input type="radio" name="share_site[<?php echo $site_id ?>]" value="0" <?php checked( $option, '0' ) ?> "></td>
					<td><?php if($description != null) echo 'yes'; else echo 'no'?></td>
				</tr>
				<?php
			} ?>
			</tbody>
		</table>
		<?php submit_button(); ?>
	</form>
</div>