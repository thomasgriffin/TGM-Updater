<?php
/**
 * A professional private and commercial plugin update class for WordPress.
 *
 * @since 1.0.0
 *
 * @author  Thomas Griffin
 * @link    https://github.com/thomasgriffin/TGM-Updater
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class TGM_Updater {

    /**
     * Plugin name.
     *
     * @since 1.0.0
     *
     * @var bool|string
     */
    protected $plugin_name = false;

    /**
     * Plugin slug.
     *
     * @since 1.0.0
     *
     * @var bool|string
     */
    protected $plugin_slug = false;

    /**
     * Plugin path.
     *
     * @since 1.0.0
     *
     * @var bool|string
     */
    protected $plugin_path = false;

    /**
     * URL of the plugin.
     *
     * @since 1.0.0
     *
     * @var bool|string
     */
    protected $plugin_url = false;

    /**
     * Version number of the plugin.
     *
     * @since 1.0.0
     *
     * @var bool|int
     */
    protected $version = false;

    /**
     * Remote URL for getting plugin updates.
     *
     * @since 1.0.0
     *
     * @var bool|string
     */
    protected $remote_url = false;

    /**
     * Time period between update checks.
     *
     * @since 1.0.0
     *
     * @var bool|int
     */
    protected $time = false;

    /**
     * Plugins to be used in update checks.
     *
     * @since 1.0.0
     *
     * @var bool|array
     */
    protected $plugins = false;

    /**
     * Flag for determining if the plugin has an update.
     *
     * @since 1.0.0
     *
     * @var bool
     */
    protected $has_update = false;

    /**
     * Namespace for class options and transients.
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected $namespace = 'tgm_plugins_';

    /**
     * Constructor. Parses default args with new args and sets up interactions
     * within the admin area of WordPress.
     *
     * @since 1.0.0
     *
     * @param TGM_Updater_Config $config Updater config args
     */
    public function __construct( TGM_Updater_Config $config ) {

        // Set class properties
        $accepted_args = array(
            'plugin_name',
            'plugin_slug',
            'plugin_path',
            'plugin_url',
            'version',
            'remote_url',
            'time'
        );
        foreach ( $accepted_args as $arg )
            $this->$arg = $config[$arg];

        // Grab and store the plugin options
        $this->plugins = $this->get_plugin_options();

        // Load interactions with WordPress
        add_action( 'load-update-core.php', array( $this, 'force_update_check' ) );
        add_filter( 'plugins_api', array( $this, 'plugins_api' ), 5, 3 );

    }

    /**
     * Run the plugin update checks.
     *
     * @since 1.0.0
     *
     * @return null Return early if current user does not have sufficient privileges
     */
    public function update_plugins() {

        if ( ! current_user_can( 'update_plugins' ) )
            return;

        $this->check_periodic_updates();
        if ( isset( $this->plugins[$this->plugin_slug]->new_version ) ) {
            if ( ! version_compare( $this->version, $this->plugins[$this->plugin_slug]->new_version, '>=' ) ) {
                $this->has_update = true;
                add_filter( 'site_transient_update_plugins', array( $this, 'update_plugins_filter' ), 1000 );
            }
        }

    }

    /**
     * Performs a periodic update check to see if the plugin needs to be
     * updated or not.
     *
     * @since 1.0.0
     */
    protected function check_periodic_updates() {

        $last_update = isset( $this->plugins[$this->plugin_slug]->last_update ) ? $this->plugins[$this->plugin_slug]->last_update : false;

        // If we haven't performed an update, perform one now
        if ( ! $last_update ) {
            $last_update = $this->check_for_updates();
            $last_update = isset( $last_update->last_update ) ? $last_update->last_update : time();
        }

        // If the time since the last update is greater than the time specified in the constructor, perform an update check
        if ( ( time() - $last_update ) > $this->time )
            $this->check_for_updates();

    }

    /**
     * Checks to see if plugin should have an update check run or not.
     *
     * @since 1.0.0
     *
     * @param bool $manual Flag for checking automatically or not
     * @return bool|stdClass Return early if plugin is not in an array, else return update object
     */
    protected function check_for_updates( $manual = false ) {

        // If plugin is not in an array, return early
        if ( ! is_array( $this->plugins ) )
            return false;

        // If plugin options don't exist, create them
        if ( ! isset( $this->plugins[$this->plugin_slug] ) )
            $this->set_plugin_options();

        $current_plugin = $this->plugins[$this->plugin_slug];

        // If the time since the last update is greater than the time specified in the constructor or manual is true, perform an update check
        if ( ( time() - $current_plugin->last_update ) > $this->time || $manual ) {
            // Perform the remote request to check for updates
            $version_info = $this->perform_remote_request( 'do-plugin-update-check', array( 'tgm-updater-plugin' => $this->plugin_slug ) );

            // Bail out if there are any errors
            if ( is_wp_error( $version_info ) )
                return false;

            // The query should return the plugin version and a download url
            if ( isset( $version_info->version ) && isset( $version_info->download_url ) ) {
                if ( is_multisite() )
                    delete_site_transient( $this->namespace . 'filter_' . $this->plugin_slug );
                else
                    delete_transient( $this->namespace . 'filter_' . $this->plugin_slug );

                $current_plugin->new_version       = $version_info->version;
                $current_plugin->package           = $version_info->download_url;
                $current_plugin->last_update       = time();
                $this->plugins[$this->plugin_slug] = $current_plugin;
                $this->save_plugin_options();
            }
        }

        return $this->plugins[$this->plugin_slug];

    }

    /**
     * Force the updater to run an update check whenever a user visits the Updates page
     * in the WordPress dashboard.
     *
     * @since 1.0.0
     */
    public function force_update_check() {

        $this->check_for_updates( true );

    }

    /**
     * Return the plugin basename and download package when it runs its own update checker.
     * Responses are cached to avoid having API pings run on every page load
     * once an update has been found.
     *
     * @since 1.0.0
     *
     * @param object $value The updater object for the plugin
     * @return object $value Amended updater object with our plugin and download package
     */
    public function update_plugins_filter( $value ) {

        if ( isset( $this->plugins[$this->plugin_slug] ) && $this->plugin_path ) {
            // MultiSite check
            if ( is_multisite() ) {
                if ( false === ( $version_info = get_site_transient( $this->namespace . 'filter_' . $this->plugin_slug ) ) ) {
                    $version_info = $this->perform_remote_request( 'get-plugin-package', array( 'tgm-updater-plugin' => $this->plugin_slug ) );

                    if ( is_wp_error( $version_info ) || isset( $version_info->key_error ) )
                        delete_site_transient( $this->namespace . 'filter_' . $this->plugin_slug );
                    else
                        set_site_transient( $this->namespace . 'filter_' . $this->plugin_slug, $version_info, $this->time - 60 );
                }
            } else {
                if ( false === ( $version_info = get_transient( $this->namespace . 'filter_' . $this->plugin_slug ) ) ) {
                    $version_info = $this->perform_remote_request( 'get-plugin-package', array( 'tgm-updater-plugin' => $this->plugin_slug ) );

                    if ( is_wp_error( $version_info ) || isset( $version_info->key_error ) )
                        delete_transient( $this->namespace . 'filter_' . $this->plugin_slug );
                    else
                        set_transient( $this->namespace . 'filter_' . $this->plugin_slug, $version_info, $this->time - 60 );
                }
            }

            $value->response[$this->plugin_path]          = $this->plugins[$this->plugin_slug];
            $value->response[$this->plugin_path]->package = isset( $version_info->download_link ) ? $version_info->download_link : '';
        }

        return $value;

    }

    /**
     * Filters the plugins_api function to get our own custom plugin information
     * from our private repo.
     *
     * @since 1.0.0
     *
     * @param object $api The original plugins_api object
     * @param string $action The action sent by plugins_api
     * @param array $args Additional args to send to plugins_api
     * @return stdClass $api New stdClass with plugin information on success, default response on failure
     */
    public function plugins_api( $api, $action, $args ) {

        $plugin = ( 'plugin_information' == $action ) && isset( $args->slug ) && ( $this->plugin_slug == $args->slug );

        // If our plugin matches the request, set our own plugin data, else return the default response
        if ( $plugin )
            return $api = $this->set_plugins_api();
        else
            return $api;

    }

    /**
     * Queries the remote URL via wp_remote_post and returns a json decoded response.
     *
     * @since 1.0.0
     *
     * @param string $action The name of the $_POST action var
     * @param array $body The content to retrieve from the remote URL
     * @param array $headers The headers to send to the remote URL
     * @param string $return_format The format for returning content from the remote URL
     * @return string|bool Json decoded response on success, false on failure
     */
    protected function perform_remote_request( $action, array $body = array(), array $headers = array(), $return_format = 'json' ) {

        // Build body
        $body = wp_parse_args( $body, array(
            'tgm-updater-action'     => $action,
            'tgm-updater-wp-version' => get_bloginfo( 'version' ),
            'tgm-updater-referer'    => site_url()
        ) );
        $body = http_build_query( $body, '', '&' );

        // Build headers
        $headers = wp_parse_args( $headers, array(
            'Content-Type'   => 'application/x-www-form-urlencoded',
            'Content-Length' => strlen( $body )
        ) );

        // Setup variable for wp_remote_post
        $post = array(
            'headers' => $headers,
            'body'    => $body
        );

        // Perform the query and retrieve the response
        $response      = wp_remote_post( esc_url_raw( $this->remote_url ), $post );
        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );

        // Bail out early if there are any errors
        if ( 200 != $response_code || is_wp_error( $response_body ) )
            return false;

        // Return body content if not json, else decode json
        if ( 'json' != $return_format )
            return $response_body;
        else
            return json_decode( $response_body );

        return false;

    }

    /**
     * Pings a remote API to retrieve plugin information for WordPress to display.
     *
     * @since 1.0.0
     *
     * @return stdClass $api Return custom plugin information to plugins_api
     */
    protected function set_plugins_api() {

        // Perform the remote request to retrieve our plugin information
        $plugin_info = $this->perform_remote_request( 'get-plugin-info', array( 'tgm-updater-plugin' => $this->plugin_slug ) );

        // Create a new stdClass object and populate it with our plugin information
        $api                           = new stdClass;
        $api->name                     = isset( $plugin_info->name )           ? $plugin_info->name           : '';
        $api->slug                     = isset( $plugin_info->slug )           ? $plugin_info->slug           : '';
        $api->version                  = isset( $plugin_info->version )        ? $plugin_info->version        : '';
        $api->author                   = isset( $plugin_info->author )         ? $plugin_info->author         : '';
        $api->author_profile           = isset( $plugin_info->author_profile ) ? $plugin_info->author_profile : '';
        $api->requires                 = isset( $plugin_info->requires )       ? $plugin_info->requires       : '';
        $api->tested                   = isset( $plugin_info->tested )         ? $plugin_info->tested         : '';
        $api->last_updated             = isset( $plugin_info->last_updated )   ? $plugin_info->last_updated   : '';
        $api->homepage                 = isset( $plugin_info->homepage )       ? $plugin_info->homepage       : '';
        $api->sections['description']  = isset( $plugin_info->description )    ? $plugin_info->description    : '';
        $api->sections['installation'] = isset( $plugin_info->installation )   ? $plugin_info->installation   : '';
        $api->sections['changelog']    = isset( $plugin_info->changelog )      ? $plugin_info->changelog      : '';
        $api->sections['FAQ']          = isset( $plugin_info->FAQ )            ? $plugin_info->FAQ            : '';
        $api->download_link            = isset( $plugin_info->download_link )  ? $plugin_info->download_link  : '';

        return $api;

    }

    /**
     * Returns options for the plugin set for automatic updates.
     *
     * @since 1.0.0
     *
     * @return array $options Plugin options
     */
    protected function get_plugin_options() {

        // MultiSite check
        if ( is_multisite() )
            $options = get_site_option( $this->namespace . $this->plugin_slug, false, false );
        else
            $options = get_option( $this->namespace . $this->plugin_slug );

        if ( ! $options )
            $options = array();

        return $options;

    }

    /**
     * Sets the plugin options and stores them for use inside the class.
     *
     * @since 1.0.0
     */
    protected function set_plugin_options() {

        // Set plugin options by creating a new stdClass object
        $plugin_options              = new stdClass;
        $plugin_options->url         = $this->plugin_url;
        $plugin_options->slug        = $this->plugin_slug;
        $plugin_options->package     = '';
        $plugin_options->new_version = $this->version;
        $plugin_options->last_update = time();
        $plugin_options->id          = '0';

        // Store the new object in $this->plugins and save the values
        $this->plugins[$this->plugin_slug] = $plugin_options;
        $this->save_plugin_options();

    }

    /**
     * Update and save plugin options.
     *
     * @since 1.0.0
     */
    protected function save_plugin_options() {

        // MultiSite check
        if ( is_multisite() )
            update_site_option( $this->namespace . $this->plugin_slug, $this->plugins );
        else
            update_option( $this->namespace . $this->plugin_slug, $this->plugins );

    }

    /**
     * Helper function to determine if the plugin has an update or not.
     *
     * @since 1.0.0
     *
     * @return bool True if an update exists, false otherwise
     */
    public function has_update() {

        return $this->has_update;

    }

}