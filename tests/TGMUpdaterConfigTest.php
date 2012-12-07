<?php

class TGMUpdaterConfigTest extends PHPUnit_Framework_TestCase
{
	public function testClassImplementsArrayAccess()
	{
		$config = new TGM_Updater_Config();
		$this->assertInstanceOf('ArrayAccess', $config);

		// offsetSet / offsetGet
		$config['foo'] = 'bar';
		$this->assertEquals('bar', $config['foo']);

		// offsetExists
		$this->assertNotEmpty($config['foo']);
		$this->assertTrue(isset($config['foo']));

		// offsetUnset
		unset($config['foo']);
		$this->assertNull($config['foo']);
	}

	public function testDefaultsAreSet()
	{
		$config = new TGM_Updater_Config(array('plugin_name' => 'FooBar'));
		$this->assertEquals('FooBar', $config['plugin_name']);
		$this->assertFalse($config['plugin_url']);
		$this->assertEquals(43200, $config['time']);

		$config['plugin_name'] = 'Ultimate FooBar';
		$this->assertEquals('Ultimate FooBar', $config['plugin_name']);
	}
}
