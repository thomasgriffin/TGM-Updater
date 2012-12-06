<?php
/**
 * Config class for TGM Updater.
 *
 * @since 1.0.0
 *
 * @author  Thomas Griffin
 * @link    https://github.com/thomasgriffin/TGM-Updater
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class TGM_Updater_Config implements ArrayAccess {

    /**
     * Holds updater args passed into the constructor.
     *
     * @since 1.0.0
     *
     * @var array
     */
    protected $container;

    /**
     * Constructor. Sets config args into class property.
     *
     * @since 1.0.0
     *
     * @param array $args Empty array
     */
    public function __construct( array $args = array() ) {

        // Set class property to config args
        $this->container = $args;
		$this->sanitize_args();
    }

	/**
	 * Provide defaults for important arguments.
	 *
	 * @since 1.0.0
	 */
	protected function sanitize_args() {

		$defaults = array(
            'plugin_name' => false,
            'plugin_slug' => false,
            'plugin_path' => false,
            'plugin_url'  => false,
            'version'     => false,
            'remote_url'  => false,
            'time'        => 43200, // 12 hours
        );
		$this->container = wp_parse_args( $this->container, $defaults);

	}

    /**
     * Assign a value to the specified offset.
     *
     * @since 1.0.0
     */
    public function offsetSet( $offset, $value ) {

        if ( is_null( $offset ) )
            $this->container[] = $value;
        else
            $this->container[$offset] = $value;

    }

    /**
     * Unset the specified offset.
     *
     * @since 1.0.0
     */
    public function offsetUnset( $offset ) {

        unset( $this->container[$offset] );

    }

    /**
     * Check whether or not the specified offset exists.
     *
     * @since 1.0.0
     *
     * @return bool True if it exists, false otherwise
     */
    public function offsetExists( $offset ) {

        return isset( $this->container[$offset] );

    }

    /**
     * Retrieve the value of the specified offset.
     *
     * @since 1.0.0
     *
     * @return string|null Offset value if it exists, null otherwise
     */
    public function offsetGet( $offset ) {

        return isset( $this->container[$offset] ) ? $this->container[$offset] : null;

    }

}