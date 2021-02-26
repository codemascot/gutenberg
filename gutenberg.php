<?php
/**
 * Plugin Name: Gutenberg
 * Plugin URI: https://github.com/WordPress/gutenberg
 * Description: Printing since 1440. This is the development plugin for the new block editor in core.
 * Requires at least: 5.3
 * Requires PHP: 5.6
 * Version: 10.1.0-rc.1
 * Author: Gutenberg Team
 * Text Domain: gutenberg
 *
 * @package gutenberg
 */

// BEGIN AUTO-GENERATED DEFINES
defined( 'GUTENBERG_DEVELOPMENT_MODE' ) or define( 'GUTENBERG_DEVELOPMENT_MODE', true );
// END AUTO-GENERATED DEFINES

gutenberg_pre_init();

/**
 * Gutenberg's Menu.
 *
 * Adds a new wp-admin menu page for the Gutenberg editor.
 *
 * @since 0.1.0
 */
function gutenberg_menu() {
	add_menu_page(
		'Gutenberg',
		'Gutenberg',
		'edit_posts',
		'gutenberg',
		'',
		'dashicons-edit'
	);

	add_submenu_page(
		'gutenberg',
		__( 'Demo', 'gutenberg' ),
		__( 'Demo', 'gutenberg' ),
		'edit_posts',
		'gutenberg'
	);

	if ( gutenberg_use_widgets_block_editor() ) {
		add_theme_page(
			__( 'Widgets', 'gutenberg' ),
			__( 'Widgets', 'gutenberg' ),
			'edit_theme_options',
			'gutenberg-widgets',
			'the_gutenberg_widgets'
		);
		remove_submenu_page( 'themes.php', 'widgets.php' );
	}

	if ( get_option( 'gutenberg-experiments' ) ) {
		if ( array_key_exists( 'gutenberg-navigation', get_option( 'gutenberg-experiments' ) ) ) {
			add_submenu_page(
				'gutenberg',
				__( 'Navigation (beta)', 'gutenberg' ),
				__( 'Navigation (beta)', 'gutenberg' ),
				'edit_theme_options',
				'gutenberg-navigation',
				'gutenberg_navigation_page'
			);
		}
	}
	if ( current_user_can( 'edit_posts' ) ) {
		add_submenu_page(
			'gutenberg',
			__( 'Support', 'gutenberg' ),
			__( 'Support', 'gutenberg' ),
			'edit_posts',
			__( 'https://wordpress.org/support/plugin/gutenberg/', 'gutenberg' )
		);
		add_submenu_page(
			'gutenberg',
			__( 'Documentation', 'gutenberg' ),
			__( 'Documentation', 'gutenberg' ),
			'edit_posts',
			'https://developer.wordpress.org/block-editor/'
		);
	}

	add_submenu_page(
		'gutenberg',
		__( 'Experiments Settings', 'gutenberg' ),
		__( 'Experiments', 'gutenberg' ),
		'edit_posts',
		'gutenberg-experiments',
		'the_gutenberg_experiments'
	);
}
add_action( 'admin_menu', 'gutenberg_menu', 9 );

/**
 * Site editor's Menu.
 *
 * Adds a new wp-admin menu item for the Site editor.
 *
 * @since 9.4.0
 */
function gutenberg_site_editor_menu() {
	if ( gutenberg_is_fse_theme() ) {
		add_menu_page(
			__( 'Site Editor (beta)', 'gutenberg' ),
			sprintf(
			/* translators: %s: "beta" label. */
				__( 'Site Editor %s', 'gutenberg' ),
				'<span class="awaiting-mod">' . __( 'beta', 'gutenberg' ) . '</span>'
			),
			'edit_theme_options',
			'gutenberg-edit-site',
			'gutenberg_edit_site_page',
			'dashicons-layout'
		);
	}
}
add_action( 'admin_menu', 'gutenberg_site_editor_menu', 9 );

/**
 * Modify WP admin bar.
 *
 * @param WP_Admin_Bar $wp_admin_bar Core class used to implement the Toolbar API.
 */
function modify_admin_bar( $wp_admin_bar ) {
	if ( gutenberg_use_widgets_block_editor() ) {
		$wp_admin_bar->add_menu(
			array(
				'id'   => 'widgets',
				'href' => admin_url( 'themes.php?page=gutenberg-widgets' ),
			)
		);
	}
}
add_action( 'admin_bar_menu', 'modify_admin_bar', 40 );


remove_action( 'welcome_panel', 'wp_welcome_panel' );
/**
 * Modify Dashboard welcome panel.
 *
 * When widgets are merged in core this should go into `wp-admin/includes/dashboard.php`
 * and replace the widgets link in the `wp_welcome_panel` checking for the same condition,
 * because then `gutenberg_use_widgets_block_editor` will exist in core.
 */
function modify_welcome_panel() {
	ob_start();
	wp_welcome_panel();
	$welcome_panel = ob_get_clean();
	if ( gutenberg_use_widgets_block_editor() ) {
		echo str_replace(
			admin_url( 'widgets.php' ),
			admin_url( 'themes.php?page=gutenberg-widgets' ),
			$welcome_panel
		);
	} else {
		echo $welcome_panel;
	}
}
add_action( 'welcome_panel', 'modify_welcome_panel', 40 );

/**
 * Display a version notice and deactivate the Gutenberg plugin.
 *
 * @since 0.1.0
 */
function gutenberg_wordpress_version_notice() {
	echo '<div class="error"><p>';
	/* translators: %s: Minimum required version */
	printf( __( 'Gutenberg requires WordPress %s or later to function properly. Please upgrade WordPress before activating Gutenberg.', 'gutenberg' ), '5.3.0' );
	echo '</p></div>';

	deactivate_plugins( array( 'gutenberg/gutenberg.php' ) );
}

/**
 * Display a build notice.
 *
 * @since 0.1.0
 */
function gutenberg_build_files_notice() {
	echo '<div class="error"><p>';
	_e( 'Gutenberg development mode requires files to be built. Run <code>npm install</code> to install dependencies, <code>npm run build</code> to build the files or <code>npm run dev</code> to build the files and watch for changes. Read the <a href="https://github.com/WordPress/gutenberg/blob/HEAD/docs/contributors/getting-started.md">contributing</a> file for more information.', 'gutenberg' );
	echo '</p></div>';
}

/**
 * Verify that we can initialize the Gutenberg editor , then load it.
 *
 * @since 1.5.0
 */
function gutenberg_pre_init() {
	global $wp_version;
	if ( defined( 'GUTENBERG_DEVELOPMENT_MODE' ) && GUTENBERG_DEVELOPMENT_MODE && ! file_exists( __DIR__ . '/build/blocks' ) ) {
		add_action( 'admin_notices', 'gutenberg_build_files_notice' );
		return;
	}

	// Get unmodified $wp_version.
	include ABSPATH . WPINC . '/version.php';

	// Strip '-src' from the version string. Messes up version_compare().
	$version = str_replace( '-src', '', $wp_version );

	if ( version_compare( $version, '5.3.0', '<' ) ) {
		add_action( 'admin_notices', 'gutenberg_wordpress_version_notice' );
		return;
	}

	require_once __DIR__ . '/lib/load.php';
}

/**
 * Outputs a WP REST API nonce.
 */
function gutenberg_rest_nonce() {
	exit( wp_create_nonce( 'wp_rest' ) );
}
add_action( 'wp_ajax_gutenberg_rest_nonce', 'gutenberg_rest_nonce' );


/**
 * Exposes the site icon url to the Gutenberg editor through the WordPress REST
 * API. The site icon url should instead be fetched from the wp/v2/settings
 * endpoint when https://github.com/WordPress/gutenberg/pull/19967 is complete.
 *
 * @since 8.2.1
 *
 * @param WP_REST_Response $response Response data served by the WordPress REST index endpoint.
 * @return WP_REST_Response
 */
function register_site_icon_url( $response ) {
	$data                  = $response->data;
	$data['site_icon_url'] = get_site_icon_url();
	$response->set_data( $data );
	return $response;
}

add_filter( 'rest_index', 'register_site_icon_url' );

add_theme_support( 'widgets-block-editor' );

class Date_Widget extends WP_Widget {
	protected $default_instance = array(
		'date' => null,
	);

	public function __construct() {
		$widget_ops  = array(
			'classname'                   => 'widget_date',
			'description'                 => __( 'A date.', 'gutenberg' ),
			'customize_selective_refresh' => true,
		);
		$control_ops = array(
			'width'  => 200,
			'height' => 350,
		);
		parent::__construct( 'date', __( 'Date', 'gutenberg' ), $widget_ops, $control_ops );
	}

	public function widget( $args, $instance ) {
		echo $args['before_widget'];
		if ( $instance['date'] ) {
			echo $instance['date']->format( 'c' );
		}
		echo $args['after_widget'];
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array_merge( $this->default_instance, $old_instance );
		if ( $new_instance['date'] ) {
			$instance['date'] = DateTime::createFromFormat( 'Y-m-d', $new_instance['date'] );
		} else {
			$instance['date'] = null;
		}
		return $instance;
	}

	public function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, $this->default_instance );
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'date' ); ?>">Date:</label>
			<input
				id="<?php echo $this->get_field_id( 'date' ); ?>"
				class="widefat"
				name="<?php echo $this->get_field_name( 'date' ); ?>"
				type="date"
				value="<?php echo $instance['date'] ? $instance['date']->format( 'Y-m-d' ) : ''; ?>"
			/>
		</p>
		<?php
	}
}
add_action(
	'widgets_init',
	function() {
		register_widget( 'Date_Widget' );
	}
);

function marquee_greeting_init() {
	wp_register_sidebar_widget(
		'marquee_greeting',
		'Marquee Greeting',
		function() {
			$greeting = get_option( 'widget_marquee_greeting', 'Hello!' );
			printf( '<marquee>%s</marquee>', esc_html( $greeting ) );
		}
	);

	wp_register_widget_control(
		'marquee_greeting',
		'Marquee Greeting',
		function() {
			if ( isset( $_POST['marquee-greeting'] ) ) {
				update_option(
					'widget_marquee_greeting',
					sanitize_text_field( $_POST['marquee-greeting'] )
				);
			}

			$greeting = get_option( 'widget_marquee_greeting' );
			?>
			<p>
				<label for="marquee-greeting">Greeting:</label>
				<input
					id="marquee-greeting"
					class="widefat"
					name="marquee-greeting"
					type="text"
					value="<?php echo esc_attr( $greeting ); ?>"
					placeholder="Hello!"
				/>
			</p>
			<?php
		}
	);
}
add_action( 'init', 'marquee_greeting_init' );
