<?php
/**
 * Tests for Asenine User.php
 *
 * @author Pontus Persson <pom@spotify.com>
 */

use Asenine\Access\User;

class UserTest extends PHPUnit_Framework_TestCase {

	function setUp() { }

	function tearDown() { }

	function testAdministrator()
	{
		$User = new User();

		/* Real "true" should validate as administrator */
		$User->isAdministrator(true);
		$this->assertTrue($User->isAdministrator());

		/* Administrators always passes policytests */
		$this->assertTrue($User->hasPolicy('RandomPolicyThatHasNotBeenAddedSpecifically'));
		$this->assertTrue($User->hasPolicies('Random', 'Policies', 'That', 'Has', 'Not', 'Been', 'Added', 'Specifically'));


		/* No true:ish values should validate as administrator */
		$User->isAdministrator(1);
		$this->assertFalse($User->isAdministrator());
	}

	function testPolicy()
	{
		$policy1 = 'AllowFoo';
		$policy2 = 'AllowBar';
		$policy3 = 'AllowFooBar';

		$User = new User();

		/* Add all policies and assert all tests are valid */
		$User->addPolicy($policy1);
		$User->addPolicy($policy2);
		$User->addPolicy($policy3);

		$this->assertTrue($User->hasPolicy($policy1));
		$this->assertTrue($User->hasAnyPolicy($policy1, 'NonExistingPolicy', 'AnotherNonExistingPolicy'));
		$this->assertTrue($User->hasPolicies($policy1, $policy2, $policy3));

		$this->assertFalse($User->hasPolicy('NonExistingPolicy'));
		$this->assertFalse($User->hasAnyPolicy('NonExistingPolicy', 'AnotherNonExistingPolicy', 'YetAnotherNonExistingPolicy'));
		$this->assertFalse($User->hasPolicies('NonExistingPolicy', $policy2, $policy3));
		$this->assertFalse($User->hasPolicies($policy1, 'NonExistingPolicy', $policy3));
		$this->assertFalse($User->hasPolicies($policy1, $policy2, 'NonExistingPolicy'));


		/* Remove one policy and assert chosen policy dont pass */
		$User->dropPolicy($policy1);

		$this->assertFalse($User->hasPolicy($policy1));
		$this->assertFalse($User->hasPolicies($policy1, $policy2, $policy3));
	}
}