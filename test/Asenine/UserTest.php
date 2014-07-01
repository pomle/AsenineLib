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

	function test_Administrator()
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

	function test_Policy()
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

	function test_Storing()
	{
		/** ### Test uncommented since it depends on a db
		$username_desired = 'phpunit';
		$password_desired = 'phpunitpassword';

		if($User = User::loadByUsername($username_desired)) {
			User::removeFromDB($User);
		}

		$User = new User();

		$User->isEnabled = true;
		$User->username = $username_desired;

		User::saveToDB($User);

		$userID = $User->userID;

		unset($User);

		$User = User::loadFromDB($userID);

		$this->assertSame(true, $User->isEnabled);
		$this->assertSame($username_desired, $User->username);

		### Set password
		$this->assertTrue($User->setPassword($password_desired));
		unset($User);

		### Ensure login is possible with desired password
		$User = User::login($username_desired, $password_desired);

		$this->assertTrue($User instanceof User);

		User::removeFromDB($User);

		### Ensure user is no longer in database
		$this->assertFalse(User::loadByUsername($username_desired));*/
	}
}
