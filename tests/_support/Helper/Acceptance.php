<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Acceptance extends \Codeception\Module {

	/**
	 * Get current url from WebDriver
	 *
	 * @throws \Codeception\Exception\ModuleException
	 *
	 * @return mixed
	 */
	public function getCurrentUrl() {
		return $this->getModule('WebDriver')->_getCurrentUri();
	}
}
