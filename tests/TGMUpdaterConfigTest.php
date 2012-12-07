<?php
/**
 * Unit tests for the TGM Updater and TGM Updater Config classes.
 *
 * @since 1.0.0
 *
 * @package TGM Updater
 * @author  Thomas Griffin
 * @link    https://github.com/thomasgriffin/TGM-Updater
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */
class TGMUpdaterConfigTest extends WP_UnitTestCase {

    /**
     * Generate the WordPress specific unit testing environment.
     *
     * @since 1.0.0
     */
    public function setUp() {

        parent::setUp();

    }

    /**
     * Run tests for testing that the TGM Updater Config class properly
     * implements ArrayAccess.
     *
     * @since 1.0.0
     */
    public function testClassImplementsArrayAccess() {

        $config = new TGM_Updater_Config();
        $this->assertInstanceOf( 'ArrayAccess', $config );

        // offsetSet / offsetGet
        $config['foo'] = 'bar';
        $this->assertEquals( 'bar', $config['foo'] );

        // offsetExists
        $this->assertNotEmpty( $config['foo'] );
        $this->assertTrue( isset( $config['foo'] ) );

        // offsetUnset
        unset( $config['foo'] );
        $this->assertNull( $config['foo'] );

    }

    /**
     * Run tests to ensure defaults are properly set.
     *
     * @since 1.0.0
     */
    public function testDefaultsAreSet() {

        $config = new TGM_Updater_Config( array( 'plugin_name' => 'FooBar' ) );
        $this->assertEquals( 'FooBar', $config['plugin_name'] );
        $this->assertFalse( $config['plugin_url'] );
        $this->assertEquals( 43200, $config['time'] );

        $config['plugin_name'] = 'Ultimate FooBar';
        $this->assertEquals( 'Ultimate FooBar', $config['plugin_name'] );

    }

}