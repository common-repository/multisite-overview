<?php

class Multisite_Overview
{
	const SITE_SETTING_NAME = 'multisite_overview';
	const NETWORK_SETTING_NAME = 'multisite_overview_network';

	/**
	 * Construct the plugin object by registering actions and shortcodes.
	 */
	public function __construct() {
		add_action( 'admin_init', array(&$this, 'admin_init') );
		add_action( 'network_admin_menu', array(&$this, 'add_network_admin_page') );

		add_shortcode( 'multisite', array(&$this, 'multisite_overview') );
		add_shortcode( 'multisite-featured', array(&$this, 'multisite_featured') );
		add_shortcode( 'multisite-all', array(&$this, 'multisite_all') );

		add_action( 'wpmu_new_blog', array(&$this, 'add_new_site') );

		add_action( 'admin_post_update_multisite_overview_network_settings',
			array(&$this, 'update_network_settings') );

		add_action( 'widgets_init', function () {
			register_widget( 'Site_Description_Field_Widget' );
		} );
	}

	/**
	 * Performs the required setup on activation. Setting default values for the settings.
	 */
	public static function activate() {
		$site_list = list_sites();
		foreach ( $site_list as $site_id ) {
			update_blog_option( $site_id, Multisite_Overview::SITE_SETTING_NAME, array(
					'share_site' => '0',
					'site_description' => '')
			);
		}
		update_site_option( Multisite_Overview::NETWORK_SETTING_NAME, array('deciding_role' => 'network_admin') );
	}

	/**
	 * Tear down on deactivation. Deletes all the settings.
	 */
	public static function deactivate() {
		$site_list = list_sites();
		foreach ( $site_list as $site_id ) {
			delete_blog_option( $site_id, Multisite_Overview::SITE_SETTING_NAME );
		}
		delete_site_option( Multisite_Overview::NETWORK_SETTING_NAME );
	}

	/**
	 * Hook executed when a new site is created.
	 * @param $site_id int id of the new site.
	 */
	public function add_new_site( $site_id ) {
		update_blog_option( $site_id, Multisite_Overview::SITE_SETTING_NAME, 0 );
	}

	/**
	 * Hook into WP's admin_init action hook.
	 */
	public function admin_init() {
		$this->init_settings();
	}

	/**
	 * Hook into WP's network_admin_menu action hook.
	 */
	public function add_network_admin_page() {
		add_submenu_page( 'settings.php', 'Multisite Overview Settings', 'Multisite Overview', 'manage_network',
			'multisite-overview-network-settings', array(&$this, 'network_settings_page') );
	}

	/**
	 * Callback for the settings page for the network admin.
	 */
	public function network_settings_page() {
		if ( ! current_user_can( 'manage_network' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		include dirname( __FILE__ ) . '/templates/network-settings.php';
	}

	/**
	 * Callback for the new action hook to update the network settings.
	 */
	public function update_network_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		check_admin_referer( 'multisite_settings' );

		$new_option = $_POST[Multisite_Overview::NETWORK_SETTING_NAME];
		if ( $new_option['deciding_role'] === 'site_admin' || $new_option['deciding_role'] === 'network_admin' ) {
			$option = get_site_option( Multisite_Overview::NETWORK_SETTING_NAME );
			$option['deciding_role'] = $new_option['deciding_role'];
			update_site_option( Multisite_Overview::NETWORK_SETTING_NAME, $option );
		}

		$site_options = $_POST['share_site'];
		if ( is_array( $site_options ) ) {
			foreach ( $site_options as $site_id => $new_site_option ) {
				$site_option = get_blog_option( $site_id, Multisite_Overview::SITE_SETTING_NAME );
				$site_option['share_site'] = $new_site_option;
				update_blog_option( $site_id, Multisite_Overview::SITE_SETTING_NAME, $site_option );
			}
		}

		wp_redirect( add_query_arg( 'updated', 'true', network_admin_url( 'settings.php?page=multisite-overview-network-settings' ) ) );
	}

	/**
	 * Adds a setting for the network whether site admins are allowed to opt out on their own. Also adds the individual
	 * settings for each site whether they should be displayed in the overview or not.
	 */
	public function init_settings() {
		define('PAGE', 'reading');
		define('SECTION', 'share_site_settings');

		add_settings_section( SECTION, 'Multisite Overview Settings', array(&$this, 'render_sharing_settings_section'), PAGE );
		add_settings_field( 'share_site', 'Show your content', array(&$this, 'render_share_site_setting'), PAGE, SECTION );
		add_settings_field( 'site_description', 'Site Description', array(&$this, 'render_description_setting'), PAGE, SECTION );
		register_setting( PAGE, Multisite_Overview::SITE_SETTING_NAME, array(&$this, 'validate_update_site_settings') );

		register_setting( 'multisite_settings', Multisite_Overview::NETWORK_SETTING_NAME );
	}

	public function render_sharing_settings_section() {
		if ( $this->is_user_allowed_to_edit_site_settings() )
			echo '<p>There may be times when the network wants to list all sites, including their recent posts. Would you like
 			your site to be shown in these lists?  (Note that the network administrators may override your setting.)</p>';
		else
			echo '<p>There may be times when the network wants to list all sites, including their recent posts. Whether
			your site is shown in these lists or not is decided by your network administrators. <a href="mailto:' .
				get_site_option( 'admin_email' ) . '">Contact</a> them, if you would like to change this setting.';
	}

	public function render_share_site_setting() {
		$option = $this->get_plugin_option( 'share_site' );

		if ( $this->is_user_allowed_to_edit_site_settings() ) {
			?>
			<label for="share_site_include">
				<input id="share_site_include" type="radio"
				       name="<?php echo Multisite_Overview::SITE_SETTING_NAME ?>[share_site]"
				       value="1" <?php checked( $option, '1' ) ?>
				"> Yes, show it!
			</label>
			<br>
			<label for="share_site_exclude">
				<input id="share_site_exclude" type="radio"
				       name="<?php echo Multisite_Overview::SITE_SETTING_NAME ?>[share_site]"
				       value="0" <?php echo checked( $option, '0' ) ?>
				"> No, do not show it at this time.
			</label>
		<?php
		} else {
			if ( $option === '0' )
				echo 'Your content is currently not shown.';
			else
				echo 'Your content is shown!';
		}
	}

	public function render_description_setting() {
		$option = $this->get_plugin_option( 'site_description' );

		$editor_settings = array(
			'wpautop' => true,
			'media_buttons' => false,
			'textarea_rows' => 10,
			'teeny' => true,
			'tinymce' => array(
				'theme_advanced_buttons1' => 'bold,italic,|,link,unlink'
			)
		);

		echo '<p class="description">This text is used whenever a summary of your blog is needed. Keep it brief but informative.</p>';
		wp_editor( $option, Multisite_Overview::SITE_SETTING_NAME . '[site_description]', $editor_settings );
	}

	public function validate_update_site_settings( $input ) {
		$output = get_option( Multisite_Overview::SITE_SETTING_NAME );
		if ( $input != null ) {
			if ( isset($input['share_site']) ) {
				if ( $this->is_user_allowed_to_edit_site_settings() ) {
					$output['share_site'] = $input['share_site'] === '1' ? '1' : '0';
				} else {
					wp_die( 'You do not have sufficient permissions to modify this setting.', E_USER_ERROR );
				}
			}
			if ( isset($input['site_description']) ) {
				$allowed_html = array(
					'a' => array(
						'href' => array(),
						'title' => array(),
						'target' => array()
					),
					'br' => array(),
					'em' => array(),
					'strong' => array(),
					'p' => array()
				);
				$output['site_description'] = wp_kses( $input['site_description'], $allowed_html );
			}
		}
		return $output;
	}


	/**
	 * Displays a overview of all sites in a two column display in alphabetic order.
	 *
	 * @param $atts array with a include list and a exclude list. If include is empty, all sites are included,
	 * except the ones in the exclude list. This is always overridden by the sharing setting of the individual blog.
	 * Also accepts a numposts parameter for the number of recent posts displayed. Can be 0 and defaults to 2. The sort
	 * parameter can be either 'abc' or 'posts'. 'abc' is the default value and sorts the posts alphabetically. 'posts'
	 * sorts the sites so that the one with the most recents posts are listed first. Finally it takes a layout parameter
	 * which takes either 'grid' (default) or 'table' and displays the sites accordingly.
	 */
	public function multisite_overview( $atts ) {
		extract( shortcode_atts( array(
			'include' => $this->get_shared_sites(),
			'exclude' => array(),
			'numposts' => 2,
			'sort' => 'abc',
			'layout' => 'grid'
		), $atts, 'multisite' ) );

		if ( ! is_array( $include ) ) {
			$include = explode( ',', $include );
		}
		if ( ! is_array( $exclude ) ) {
			$exclude = explode( ',', $exclude );
		}

		if ( ! is_numeric( $numposts ) || $numposts < 0 ) {
			return '<p><b>Illegal parameter <code>numposts</code> (must be integer value greater or equal than 0).</b></p>';
		}

		if ( strcmp( $sort, 'abc' ) !== 0 && strcmp( $sort, 'posts' ) !== 0 ) {
			return '<p><b>Illegal parameter <code>sort</code> (must be <code>abc</code> or <code>posts</code>).</b></p>';
		}

		if ( strcmp( $layout, 'grid' ) !== 0 && strcmp( $layout, 'table' ) !== 0 ) {
			return '<p><b>Illegal parameter <code>layout</code> (must be <code>grid</code> or <code>table</code>).</b></p>';
		}

		$include = array_intersect( $include, $this->get_shared_sites() );
		$sites = array_diff( $include, $exclude );

		if ( empty($sites) ) {
			return '<p><b>No sites to display.</b></p>';
		}

		wp_register_style( 'multisite_overview', plugins_url( 'multisite-overview.css', __FILE__ ) );
		wp_enqueue_style( 'multisite_overview' );

		$this->sort_sites( $sites, $sort );

		$result = '';
		if ( strcmp( $layout, 'grid' ) === 0 ) {
			$i = 0;
			foreach ( $sites as $site_id ) {
				switch_to_blog( $site_id );
				$result .= '<div class="multisite-site ' . (($i ++ % 2 == 0) ? 'even' : 'odd') . '">
				<h2 class="site-title"><a href="' . site_url() . '">' . get_bloginfo() . '</a></h2>' .
					wpautop( $this->get_plugin_option( 'site_description' ) );
				if ( $numposts > 0 ) {
					$result .= $this->get_recent_posts( $numposts );
				}
				$result .= '</div>';
				restore_current_blog();
			}
		} else if ( strcmp( $layout, 'table' ) === 0 ) {
			$result .= '<table class="multisite-site"><tbody>';
			foreach ( $sites as $site_id ) {
				switch_to_blog( $site_id );
				$result .= '<tr><td>';
				$result .= '<h2 class="site-title"><a href="' . site_url() . '">' . get_bloginfo() . '</a></h2>';
				$result .= wpautop( $this->get_plugin_option( 'site_description' ) );
				$result .= '</td>';
				if ( $numposts > 0 ) {
					$result .= '<td>' . $this->get_recent_posts( $numposts ) . '</td>';
				}
				$result .= '</tr></tbody></table';
			}
		}
		return $result;
	}

	/**
	 * Displays one specific site in a featured way with an image.
	 *
	 * @param $atts array with a required id of the site to display and a url to a image for the site. Also accepts a
	 * numposts parameter for the number of recent posts displayed. Can be 0 and defaults to 2.
	 */
	public function multisite_featured( $atts ) {
		extract( shortcode_atts( array(
			'id' => "error",
			'img' => "",
			'numposts' => 2
		), $atts, 'featured-site' ) );

		if ( ! in_array( $id, $this->get_shared_sites() ) ) {
			return '<p><b>Not a valid blog id or sharing has been disabled for this blog.</b></p>';
		}

		if ( ! is_numeric( $numposts ) || $numposts < 0 ) {
			return '<p><b>Illegal parameter <code>numposts</code> (must be integer value greater or equal than 0).</b></p>';
		}

		wp_register_style( 'multisite_overview', plugins_url( 'multisite-overview.css', __FILE__ ) );
		wp_enqueue_style( 'multisite_overview' );

		switch_to_blog( $id );

		$result = '<div class="featured-site"><h2 class="site-title"><a href="' . site_url() . '">' .
			get_bloginfo() . '</a></h2><div class="site-description">';
		if ( ! empty($img) ) {
			$result .= '<a href="' . site_url() . '"><img src="' . $img . '"></a>';
		}
		$result .= wpautop( $this->get_plugin_option( 'site_description' ) );
		if ( $numposts > 0 ) {
			$result .= $this->get_recent_posts( $numposts );
		}
		$result .= '</div></div>';

		restore_current_blog();
		return $result;
	}

	/**
	 * Displays a index of all available sites in an alphabetic order.
	 *
	 * @param $atts array with a title attribute displayed before the list.
	 */
	public function multisite_all( $atts ) {
		extract( shortcode_atts( array(
			'title' => "All Sites"
		), $atts, 'multisite-all' ) );
		$sites = $this->get_shared_sites();
		if ( empty($sites) ) {
			return '<p><b>No sites to display.</b></p>';
		}

		wp_register_style( 'multisite_overview', plugins_url( 'multisite-overview.css', __FILE__ ) );
		wp_enqueue_style( 'multisite_overview' );

		$this->sort_sites( $sites );

		$sites = array_chunk( $sites, max( count( $sites ) / 2, 1 ) );

		$result = '<div class="multisite-all"><h2>' . $title . '</h2>';
		foreach ( $sites as $site_chunk ) {
			$result .= '<ul>';
			foreach ( $site_chunk as $site_id ) {
				switch_to_blog( $site_id );
				$result .= '<li><a href="' . site_url() . '">' . get_bloginfo() . '</a></li>';
				restore_current_blog();
			}
			$result .= '</ul>';
		}
		$result .= '</div>';
		return $result;
	}

	/**
	 * Checks whether the user has the appropriate role to update the site settings. This depends on the role of the user
	 * and the settings of the plugin.
	 *
	 * @return bool true if the settings allow the current user to update the site settings.
	 */
	private function is_user_allowed_to_edit_site_settings() {
		return ($this->get_plugin_option( 'deciding_role', $network = true ) == 'site_admin' AND
			current_user_can( 'manage_options' )) OR
		current_user_can( 'manage_networks' );
	}

	private function get_shared_sites() {
		$shared_sites = array();
		$site_list = list_sites();
		foreach ( $site_list as $site_id ) {
			if ( '1' === $this->get_plugin_option( 'share_site', $site_id ) ) {
				array_push( $shared_sites, $site_id );
			}
		}
		return $shared_sites;
	}

	private function get_recent_posts( $number_of_posts ) {
		$result = '<h4>Most recent posts</h4>
		<ul class="site-recent-post">';

		$recent_posts = wp_get_recent_posts( array('numberposts' => $number_of_posts, 'post_status' => 'publish') );
		foreach ( $recent_posts as $post ) {
			$result .= '<li><a href="' . get_permalink( $post["ID"] ) . '" title="Read ' . $post["post_title"] . '.">'
				. $post["post_title"] . '</a><span class="date">'
				. date_i18n( get_option( 'date_format' ), strtotime( $post["post_date"] ) )
				. '</span></li>';
		}
		$result .= '</ul>';
		return $result;
	}

	private function sort_sites( &$sites, $sorting = 'abc' ) {
		if ( strcmp( $sorting, 'abc' ) === 0 ) {
			usort( $sites, function ( $site_a, $site_b ) {
				return strcmp( get_blog_option( $site_a, 'blogname' ), get_blog_option( $site_b, 'blogname' ) );
			} );
		} else if ( strcmp( $sorting, 'posts' ) === 0 ) {
			usort( $sites, function ( $site_a, $site_b ) {
				switch_to_blog( $site_a );
				$recent = wp_get_recent_posts( array('numberposts' => 1, 'post_status' => 'publish') );
				if ( empty ($recent) ) {
					return - 1000;
				} else {
					$recent = array_pop( $recent );
				}
				$date_recent_post_a = $recent['post_date'];
				switch_to_blog( $site_b );
				$recent = wp_get_recent_posts( array('numberposts' => 1, 'post_status' => 'publish') );
				if ( empty ($recent) ) {
					return 1000;
				} else {
					$recent = array_pop( $recent );
				}
				$date_recent_post_b = $recent['post_date'];
				restore_current_blog();
				return strcmp( $date_recent_post_b, $date_recent_post_a );
			} );
		}

	}

	public static function get_plugin_option( $tag, $blog_id = null, $network = false ) {
		if ( $network ) {
			$option = get_site_option( Multisite_Overview::NETWORK_SETTING_NAME );
		} else if ( $blog_id != null ) {
			$option = get_blog_option( $blog_id, Multisite_Overview::SITE_SETTING_NAME );
		} else {
			$option = get_option( Multisite_Overview::SITE_SETTING_NAME );
		}
		if ( isset($option[$tag]) ) {
			return $option[$tag];
		} else {
			return null;
		}
	}
}

/**
 * Build a list of all sites in a network.
 */
function list_sites( $expires = 7200 ) {
	if ( ! is_multisite() ) return false;
	if ( false === ($site_list = get_transient( 'multisite_site_list' )) ) {
		global $wpdb;
		$site_list = $wpdb->get_col( "SELECT * FROM $wpdb->blogs ORDER BY blog_id ASC" );
		set_site_transient( 'multisite_site_list', $site_list, $expires );
	}
	return $site_list;
}