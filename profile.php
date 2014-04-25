<?php
	class User
	{
		// state variables
		private $id;
		private $email;
		private $password;
		private $salt;
	
		/* constructor for a User object
		* input: (integer) new Id
		* input: (string) new email
		* input: (string) new password
		* input: (string) new salt
		* throws: when invalid input detected */
		public function __construct($newId, $newEmail, $newPassword, $newSalt)
		{
			try
			{
				// use the mutator methods since they have all input sanitization
				$this->setId($newId);
				$this->setEmail($newEmail);
				$this->setPassword($newPassword);
				$this->setSalt($newSalt);
			}
			catch(Exception $exception)
			{
				// rethrow the exception to the caller
				throw(new Exception("Unable to build user", 0, $exception));
			}
		}
		
		// accessors functions
		public function getId()
		{
			return($this->id);
		}
		
		public function getEmail()
		{
			return($this->email);
		}
		
		public function getPassword()
		{
			return($this->password);
		}
		
		public function getSalt()
		{
			return($this->salt);
		}
		
		// mutator functions
		
		/* for id
		* input: (integer) new id
		* output: n/a
		* throws: invalid input detected */
		public function setId($newId)
		{
			if(is_numeric($newId) === false)
			{
				throw(new Exception("Invalid user id detected: $newId"));
			}
			
			// convert the id to an integer
			$newId = intval($newId);
			
			// throw out negative ids except -1, which is our placeholder
			if($newId < -1)
			{
				throw(new Exception("Invalid user id detected: $newId"));
			}
			
			// sanitized; assign value
			$this->id = $newId;
		}
		
		/* for email
		* input: (string) new email
		* output: n/a
		* throws: invalid email */
		public function setEmail($newEmail)
		{
			// trim the email
			$newEmail = trim($newEmail);
			
			// require an @
			if(strpos($newEmail, "@") === false)
			{
				throw(new Exception("Invalid email detected: $newEmail"));
			}
			
			// sanitized; assign the value
			$this->email = $newEmail;
		}
		
		/* for password
		* input: (string) new password
		* output: n/a
		* throws: invalid email */
		public function setPassword($newPassword)
		{
			// trim the password
			$newPassword = trim($newPassword);
			
			// convert A-F to a-f
			$newPassword = strtolower($newPassword);
			
			// enforce 128 hexadecimal bytes
			$regexp = "/^[\da-f]{128}$/";
			if(preg_match($regexp, $newPassword) !== 1)
			{
				throw(new Exception("Invalid password detected: $newPassword"));
			}
			
			// sanitized; assign the value
			$this->password = $newPassword;
		}
		
		/* for salt
		* input: (string) salt
		* output: n/a
		* throws: if invalid salt */ 
		public function setSalt($newSalt)
		{
			// trim the salt
			$newSalt = trim($newSalt);
			
			// convert A-F to a-f
			$newSalt = strtolower($newSalt);
			
			// enforce 64 hexadecimal bytes
			$regexp = "/^[\da-f]{64}$/";
			if(preg_match($regexp, $newSalt) !== 1)
			{
				throw(new Exception("Invalid password detected: $newSalt"));
			}
			
			// sanitized; assign the value
			$this->salt = $newSalt;
		}
		
		// mySQL mutator methods
		
		/* inserts a new object into mySQL
		* input: (pointer) mySQL connection, by reference
		* output: n/a
		* throws: if the object could not be inserted */
		public function insert(&$mysqli)
		{
			// handle degenerate cases
			if(is_object($mysqli) === false || get_class($mysqli) !== "mysqli")
			{
				throw(new Exception("Non mySQL pointer detected."));
			}
			
			// verify the id is -1 (i.e., a new user)
			if($this->id !== -1)
			{
				throw(new Exception("Non new id detected."));
			}
			
			// a create a query template
			$query = "INSERT INTO user(email, password, salt) VALUES(?, ?, ?)";
			
			// prepare the query statement
			$statement = $mysqli->prepare($query);
			if($statement === false)
			{
				throw(new Exception("Unable to prepare the statement."));
			}
			
			// bind parameters to the query template
			$wasClean = $statement->bind_param("sss", $this->email, $this->password, $this->salt);
			if($wasClean === false)
			{
				throw(new Exception("Unable to bind paramenters."));
			}
			
			// ok, let's rock!
			if($statement->execute() === false)
			{
				throw(new Exception("Unable to execute the statement."));
			}
			
			$statement->close();
			
			// trash the statement and create another
			$statement = null;
			$query = "SELECT id FROM user WHERE email = ?";
			$statement = $mysqli->prepare($query);
			if($statement === false)
			{
				throw(new Exception("Unable to prepare the statement."));
			}
			
			// bind the query
			$wasClean = $statement->bind_param("s", $this->email);
			if($wasClean === false)
			{
				throw(new Exception("Unable to bind paramenters."));
			}
			
			// ok, let's rock!
			if($statement->execute() === false)
			{
				throw(new Exception("Unable to execute the statement."));
			}
			
			// get the result and make sure only 1 row is deleted
			$result = $statement->get_result();
			if($result === false || $result->num_rows !== 1)
			{
				throw(new Exception("Unable to determine user id: invalid result set"));
			}
			
			// get the row and set the id
			$row = $result->fetch_assoc();
			$newId = $row["id"];
			try
			{
				$this->setId($newId);
			}
			catch(Exception $exception)
			{
				throw(new Exception("Unable to determine user id", 0, $exception));
			}
			
			$statement->close();
			
		}
		
		/* function to delete
		 * input: (pointer) mySQL connection, by reference
		 * output: N/A
		 * throws: if the object could not be deleted */
		public function delete(&$mysqli)
		{
			// check for a good mySQL pointer
			if(is_object($mysqli) === false || get_class($mysqli) !== "mysqli")
			{
				throw(new Exception("Non mySQL pointer detected."));
			}
			
			// verify the id is not -1 (which would be a new user)
			if($this->id === -1)
			{
				throw(new Exception("New id detected"));
			}
			
			// create the query template
			$query = "DELETE FROM user WHERE id = ?";
			
			// prepare the query statement
			$statement = $mysqli->prepare($query);
			if($statement === false)
			{
				throw(new Exception("Unable to prepare statement."));
			}
			
			// bind parameters to the query template
			$wasClean = $statement->bind_param("i", $this->id);
			if($wasClean === false)
			{
				throw(new Exception("Unable to bind paramenters."));
			}
			
			// ok, let's rock!
			if($statement->execute() === false)
			{
				throw(new Exception("Unable to execute the statement."));
			}
			
			$statement->close();
			
		}
		
		/* update function
		 * input: (pointer) mysql connection
		 * output: n/a
		 * throws: when the object was not updated */
		public function update(&$mysqli)
		{
			// check for a good mySQL pointer
			if(is_object($mysqli) === false || get_class($mysqli) !== "mysqli")
			{
				throw(new Exception("Non mySQL pointer detected."));
			}
			
			// verify the id is not -1 (which would be a new user)
			if($this->id === -1)
			{
				throw(new Exception("New id detected"));
			}
			
			// create the query template
			$query = "UPDATE user SET email = ?, password = ?, salt = ? WHERE id = ?";
			
			// prepare the query statement
			$statement = $mysqli->prepare($query);
			if($statement === false)
			{
				throw(new Exception("Unable to prepare statement."));
			}
			
			// bind parameters to the query template
			$wasClean = $statement->bind_param("sssi", $this->email, $this->password, $this->salt, $this->id);
			if($wasClean === false)
			{
				throw(new Exception("Unable to bind paramenters."));
			}
			
			// ok, let's rock!
			if($statement->execute() === false)
			{
				throw(new Exception("Unable to execute the statement."));
			}
			
			$statement->close();
		}
		
		//static methods
		
		/* static method to get user by email
		 * input: (pointer) to mysql
		 * input: (string) email to search by
		 * output: (object) user */
		public static function getUserByEmail(&$mysqli, $email)
		{
			// check for a good mySQL pointer
			if(is_object($mysqli) === false || get_class($mysqli) !== "mysqli")
			{
				throw(new Exception("Non mySQL pointer detected."));
			}
			
			// create the query template
			$query = "SELECT id, email, password, salt FROM user WHERE email = ?";
			
			// prepare the query statement
			$statement = $mysqli->prepare($query);
			if($statement === false)
			{
				throw(new Exception("Unable to prepare statement."));
			}
			
			// bind parameters to the query template
			$wasClean = $statement->bind_param("s", $email);
			if($wasClean === false)
			{
				throw(new Exception("Unable to bind paramenters."));
			}
			
			// ok, let's rock!
			if($statement->execute() === false)
			{
				throw(new Exception("Unable to execute the statement."));
			}
			
			// get the result and make a new object
			$result = $statement->get_result();
			if($result === false || $result->num_rows !== 1)
			{
				throw(new Exception("Unable to determine user: email not found."));
			}
			
			// get the row and set the id
			$row = $result->fetch_assoc();
			$user = new User($row["id"], $row["email"], $row["password"], $row["salt"]);
			return($user);
			
			$statement->close();
		}
		
		/* static method to get user by id
		 * input: (pointer) to mysql
		 * input: (string) id to search by
		 * output: (object) user */
		public static function getUserById(&$mysqli, $id)
		{
			// check for a good mySQL pointer
			if(is_object($mysqli) === false || get_class($mysqli) !== "mysqli")
			{
				throw(new Exception("Non mySQL pointer detected."));
			}
			
			// create the query template
			$query = "SELECT id, email, password, salt FROM user WHERE id = ?";
			
			// prepare the query statement
			$statement = $mysqli->prepare($query);
			if($statement === false)
			{
				throw(new Exception("Unable to prepare statement."));
			}
			
			// bind parameters to the query template
			$wasClean = $statement->bind_param("i", $id);
			if($wasClean === false)
			{
				throw(new Exception("Unable to bind paramenters."));
			}
			
			// ok, let's rock!
			if($statement->execute() === false)
			{
				throw(new Exception("Unable to execute the statement."));
			}
			
			// get the result and make a new object
			$result = $statement->get_result();
			if($result === false || $result->num_rows !== 1)
			{
				throw(new Exception("Unable to determine user: id not found."));
			}
			
			// get the row and set the id
			$row = $result->fetch_assoc();
			$user = new User($row["id"], $row["email"], $row["password"], $row["salt"]);
			return($user);
			
			$statement->close();
		}
	}
?>