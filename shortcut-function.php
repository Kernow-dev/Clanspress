<?php
use Kernowdev\Clanspress\Main;

/**
 * Grab the Main object and return it.
 * Wrapper for Main::instance().
 *
 * @return Main Singleton instance of plugin class.
 */
function clanspress(): Main {
	return Main::instance();
}
