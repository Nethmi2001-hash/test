<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "nethmi";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
	if ($conn->connect_error) {
	    die("Connection failed: " . $conn->connect_error);
		}

		$sql = "INSERT INTO user(username,password,email) 
		VALUES ('nethmi', '12345', 'nethmi@example.com')";


			//if ($conn->query($sql) === TRUE) {
			 // echo "New record created successfully";
			//} else {
			 // echo "Error: " . $sql . "<br>" . $conn->error;
			//}
			$id= $_POST['id'] ?? '';
			//if(empty($id)){
			 //echo"id can not be empty";
			//} else{
				
			$sql = "SELECT id, username,password,email FROM user ";
			$result = $conn->query($sql);

					if ($result->num_rows > 0) {
					// output data of each row
					echo "<table border='1'>";
					echo "<tr><th>Name</th><th>Email</th></tr>";
					while($row = $result->fetch_assoc()) {
					//echo "id: " . $row["id"]. " - Name: " . $row["username"]. " " . $row["password"]. " ".$row["email"]. "<br>";
					$name=$row["username"]; 
					$email=$row["email"];
					
					echo "<tr><td>$name</td><td>$email</td></tr>";
				  }
				  echo "</table>";
				} else {
				  echo "0 results";
				 
				} 
				//}


				$conn->close();
	?>
