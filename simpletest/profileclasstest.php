<?php
	// grab the unit test framework
	require_once("/usr/lib/php5/simpletest/autorun.php");
	
	// grab the functions under scrutiny
	require_once("../user.php");
	require_once("../profile.php");
	
	class ProfileTest extends UnitTestCase
	{
		private $mysqli;
		
		// variable to hold the mysql user
		private $sqlProfile;
		private $user;
		
		// constant variables to reuse
		private $userId;
		private $firstName = "Hello";
		private $lastName = "Kitty";
		private $birthday = "1111-12-12";
		private $specialNeeds = 1;
		
		// user variables
		private $email = "brad@intcorrect.com";
		private $password = "47d80e3d06534ada8054f085b1e04d1eb9e0ecab0c1ca75bdcc701a37170b7fd38d6583eb89eadc380445da3ccbed0ee488b86a69d5db61caf967e0b4b6d7427";
		private $salt = "1b5cec8c46451b5375ea7e61f310fe831ad17f62098beb7a5bfce304821e3f78";
		
		public function setUp()
		{
			mysqli_report(MYSQLI_REPORT_STRICT);
			try
			{			
				$this->mysqli = new mysqli("localhost", "airline_ericd", "1CODingF\$\$L",  "airline_ericd");
				$this->user = new User (-1, $this->email, $this->password, $this->salt);			
				$this->user->insert($this->mysqli);
				$this->userId = $this->user->getId();
			}
			catch(mysqli_sql_exception $exception)
			{
				echo "Unable to connect to mySQL: " . $exception->getMessage();
			}
		}
		
		public function testGetByUserId()
		{
			$profile = new Profile (-1, $this->userId, $this->firstName, $this->lastName, $this->birthday, $this->specialNeeds);
			$profile->insert($this->mysqli);
			$this->sqlProfile = Profile::getProfileByUserId($this->mysqli, $this->userId);
			$this->assertIdentical($profile->getId(), $this->sqlProfile->getId());
		}
		
		public function testGetByUserIdInvalid()
		{
			
			$profile = new Profile (-1, $this->userId, $this->firstName, $this->lastName, $this->birthday, $this->specialNeeds);
			$profile->insert($this->mysqli);
			$this->sqlProfile = $profile;
			$this->expectException("Exception");
			@Profile::getProfileByUserId($this->mysqli, -2);
		}
		
		public function testProfileById()
		{
			
			$profile = new Profile (-1, $this->userId, $this->firstName, $this->lastName, $this->birthday, $this->specialNeeds);
			$profile->insert($this->mysqli);
			$this->sqlProfile = Profile::getProfileById($this->mysqli, $profile->getId());
			$this->assertIdentical($profile->getId(), $this->sqlProfile->getId());
		}
		
		public function testGetProfileByIdInvalid()
		{
			
			$profile = new Profile (-1, $this->userId, $this->firstName, $this->lastName, $this->birthday, $this->specialNeeds);
			$profile->insert($this->mysqli);
			$this->sqlProfile = $profile;
			$this->expectException("Exception");
			@Profile::getProfileById($this->mysqli, -2);
		}
		
		public function testCreateValidProfile()
		{
			// create an insert the user
			$profile = new Profile (-1, $this->userId, $this->firstName, $this->lastName, $this->birthday, $this->specialNeeds);
			$profile->insert($this->mysqli);
			
			//select the user from mySQL and assert it was inserted properly
			$this->sqlProfile = Profile::getProfileByUserId($this->mysqli, $this->userId);
			$this->assertIdentical($this->sqlProfile->getFirstName(), $this->firstName);
			$this->assertIdentical($this->sqlProfile->getLastName(), $this->lastName);
			$this->assertIdentical($this->sqlProfile->getBirthday(), $this->birthday);
			$this->assertIdentical($this->sqlProfile->getSpecialNeeds(), $this->specialNeeds);
			$this->assertTrue($this->sqlProfile->getId() > 0);
		}
		
		public function testValidUpdateValidProfile()
		{
			// create an insert the user
			$profile = new Profile (-1, $this->userId, $this->firstName, $this->lastName, $this->birthday, $this->specialNeeds);
			$profile->insert($this->mysqli);
			
			$newLastName = "Puppy";
			$profile->setLastName($newLastName);
			$profile->update($this->mysqli);
			
			//select the user from mySQL and assert it was inserted properly
			$this->sqlProfile = Profile::getProfileByUserId($this->mysqli, $this->userId);
			
			// verify the email changed
			$this->assertIdentical($this->sqlProfile->getLastName(), $newLastName);
			$this->assertIdentical($this->sqlProfile->getFirstName(), $this->firstName);
			$this->assertIdentical($this->sqlProfile->getBirthday(), $this->birthday);
			$this->assertTrue($this->sqlProfile->getId() > 0);
		}
		
		// teardown
		public function tearDown()
		{
			$this->sqlProfile->delete($this->mysqli);
			$this->user->delete($this->mysqli);
			$this->mysqli->close();
		}
	}
?>