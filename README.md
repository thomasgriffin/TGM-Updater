TGM Updater
===========

Here we go, world. A first-class premium plugin updater class that has been battle-tested and approved. It has been tested, refined, used and abused on over 10,000 WordPress installs. To put it short - **this class simply works.** Just follow the instructions correctly and you will be up and running with your own automatic plugin updates in no time flat!

This class has been tested and works with both regular and Multisite installs, and it even works with services like ManageWP!

### Usage ###

Drop the following code somewhere inside your main plugin file. Adjust the commented settings with your own plugin information.

```php
add_action( 'init', 'tgm_updater_plugin_load' );
/**
 * Loads the updater file and initializes the updater.
 *
 * IMPORTANT: Namespace this function.
 *
 * @since 1.0.0
 *
 * @package Test TGM Updater Plugin
 * @author  Thomas Griffin
 */
function tgm_updater_plugin_load() {
	
	// Return early if not in the admin.
	if ( ! is_admin() ) {
		return;
	}
	
	// Load the updater file.
	if ( ! class_exists( 'TGM_Updater' ) ) {
		require plugin_dir_path( __FILE__ ) . 'class-tgm-updater.php';
	}
	
	// Prepare updater args and initialize the updater.
	$args = array(
        'plugin_name' => 'Your Plugin Name',		  // Your plugin name goes here.
        'plugin_slug' => 'your-plugin-slug',		  // Your plugin slug goes here.
        'plugin_path' => plugin_basename( __FILE__ ),
        'plugin_url'  => trailingslashit( WP_PLUGIN_URL ) . 'your-plugin-slug',
        'remote_url'  => 'http://yourdomain.com',     // Set to the domain that should receive update requests.
        'version'     => '1.0.0',					  // Adjust to your latest plugin version.
        'key'         => 'license_key_here_if_needed' // Optionally, you can set this to false if you don't want to verify updates.
    );
    $tgm_updater = new TGM_Updater( $args );
	
}
```

**That's it.** You've now integrated the updater class into your plugin.

### Credits ###
This class was developed and is maintained by [Thomas Griffin](https://thomasgriffin.io).