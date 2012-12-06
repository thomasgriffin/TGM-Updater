TGM Updater
===========

It's time that the private and commercial plugin world inside of WordPress get serious about automatic plugin updates. With the newly minted TGM Updater library, you now have no excuse. :-)

The TGM Updater library is a modification of the automatic updater class used inside of [Soliloquy](http://soliloquywp.com/), my own (and the best) responsive WordPress slider plugin. It has been tested, refined, used and abused on over 10,000 WordPress installs. To put it short - **this class simply works.** Just follow the instructions correctly and you will be up and running with your own automatic plugin updates in no time flat!

## 1. Setup ##

The TGM Updater library consists of 3 files inside of a directory entitled `updater`: `init.php`, `class-tgm-updater-config.php` and `class-tgm-updater.php`.

The `init.php` kickstarts the library by loading the two main class files.

The `class-tgm-updater-config.php` file handles and parses the configuration array. This includes things like your plugin name, plugin slug and remote URL.

The `class-tgm-updater.php` file handles the bulk of the processing work for automatic updates on the WordPress end.

## 2. Installation ##

The library is easy to install. Drop the `updater` folder into the root of your plugin. Once you have done this, you can one of the two methods below to load and instantiate the updater.

### Simple Installation ###

This is the most basic way of loading the library. Drop the following code somewhere inside your main plugin file. If you are executing this code inside of a hook, the hook must be fired at or before the `init` hook to properly hook into WordPress' update system.

```php
require_once dirname( __FILE__ ) . '/updater/init.php';
$args = array(
    'plugin_name' => 'Soliloquy',                  // Your plugin name (e.g. "Soliloquy" or "Jetpack")
    'plugin_slug' => 'soliloquy',                  // Your plugin slug (typically the plugin folder name, e.g. "soliloquy")
    'plugin_path' => plugin_basename( __FILE__ ),  // The plugin basename (e.g. plugin_basename( __FILE__ ))
    'plugin_url'  => WP_PLUGIN_URL . '/soliloquy', // The HTTP URL to the plugin (e.g. WP_PLUGIN_URL . '/soliloquy')
    'version'     => '1.0.0',                      // The current version of your plugin
    'remote_url'  => 'http://soliloquywp.com/',    // The remote API URL that should be pinged when retrieving plugin update info
    'time'        => 42300                         // The amount of time between update checks (defaults to 12 hours)
);
$config            = new TGM_Updater_Config( $args );
$namespace_updater = new TGM_Updater( $config ); // Be sure to replace "namespace" with your own custom namespace
$namespace_updater->update_plugins();            // Be sure to replace "namespace" with your own custom namespace
```

### Advanced Installation ###

Ideally, this library should be integrated seamlessly into your plugin workflow. If you are integrating this updater into a class, it would be best to pass the Updater object through your class constructor and store it in a property, like this:

```php
// Assuming you are working inside of your __construct (or other appropriate instantiation) method, store the Updater object as a property
$this->updater = $namespace_updater;

// You can then control and fire the update process like this
$this->updater->update_plugins();
```

**That's it.** You've now integrated the library into your plugin.

I am working on making a distributable version of my own remote API plugin for use - stay tuned as it will be out soon.

#### TO DO List ####

1. Create the remote API plugin that syncs with Amazon S3 for plugin updates.
2. Create WordPress-centric unit tests (found under the `tests` branch) and merge them into the master branch.

##### Credits #####
This class was developed and is maintained by [Thomas Griffin](http://thomasgriffinmedia.com/) with a special thanks to Gary Jones.