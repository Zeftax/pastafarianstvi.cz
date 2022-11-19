<?php
include("header.php");

function is_user_admin($link, $username)
{
	// prepare user tags variable
	$sql = "SELECT user_tags.color, user_tags.name
	FROM ((users
	INNER JOIN user_has_tag ON users.id = user_has_tag.user_id)
	INNER JOIN user_tags ON user_has_tag.tag_id = user_tags.id)
	WHERE users.username = ? and user_tags.name = 'Admin'";

	if($stmt = mysqli_prepare($link, $sql))
	{
		// Bind variables to the prepared statement as parameters
		mysqli_stmt_bind_param($stmt, "s", $param_username);

		// Set parameters
		$param_username = $username;

		// Attempt to execute the prepared statement
		if(mysqli_stmt_execute($stmt))
		{
			// Store result
			mysqli_stmt_store_result($stmt);

			// Check if username exists, if yes then verify password
			if(mysqli_stmt_num_rows($stmt) > 0)
			{
				$returnval = true;
			}
			else
			{
				$returnval = false;
			}
		}
		else
		{
			printf("Error: %s.\n", mysqli_stmt_error($stmt));
		}

		// Close statement
		mysqli_stmt_close($stmt);
	}
	return $returnval;
}

// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = $login_err = "";
 
// Processing form data when form is submitted
if(isset($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] == "POST")
{ 
	// Check if username is empty
	if(empty(trim($_POST["username"])))
	{
		$username_err = "Prosím zadejte váš login.";
	}
	else
	{
		$username = trim($_POST["username"]);
	}
    
	// Check if password is empty
	if(empty(trim($_POST["password"])))
	{
		$password_err = "Prosím zadejte heslo.";
	}
	else
	{
		$password = trim($_POST["password"]);
	}
    
	// Validate credentials
	if(empty($username_err) && empty($password_err))
	{
		// Prepare a select statement
		$sql = "SELECT id, username, password FROM users WHERE username = ?";
	
		if($stmt = mysqli_prepare($link, $sql))
		{
			// Bind variables to the prepared statement as parameters
			mysqli_stmt_bind_param($stmt, "s", $param_username);
	    
			// Set parameters
			$param_username = $username;
	    
			// Attempt to execute the prepared statement
			if(mysqli_stmt_execute($stmt))
			{
				// Store result
				mysqli_stmt_store_result($stmt);
		
				// Check if username exists, if yes then verify password
				if(mysqli_stmt_num_rows($stmt) == 1)
				{
					// Bind result variables
					mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password);
					if(mysqli_stmt_fetch($stmt))
					{
						if(password_verify($password, $hashed_password))
						{
							// Password is correct, so start a new session
							session_start();
					    	// Store data in session variables
							$_SESSION["loggedin"] = true;
							$_SESSION["userid"] = bin_to_uuid($id);
							$_SESSION["username"] = $username;
							$_SESSION["is_admin"] = is_user_admin($link, $username);

							// Redirect user to welcome page
							header("location: index.php");
						}
						else
						{
							// Password is not valid, display a generic error message
							$login_err = "Neplatná přezdívka nebo heslo.";
						}
					}
				}
				else
				{
					// Username doesn't exist, display a generic error message
					$login_err = "Účet neexistuje.";
				}
			}
			else
			{
				printf("Error: %s.\n", mysqli_stmt_error($stmt));
			}

			// Close statement
			mysqli_stmt_close($stmt);
		}
	}
}
?>
 
<article>
	<h2>Přihlásit se</h2>

	<?php 
	if(!empty($login_err)){
		echo '<div class="alert alert-danger">' . $login_err . '</div>';
	}	 
	?>

	<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
		<table>
			<tr>
				<td><label>Přezdívka: </label></td>
				<td><input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>"/></td>
				<td><span class="invalid-feedback"><?php echo $username_err; ?></span></td>
			</tr>    
			<tr>
				<td><label>Heslo: </label></td>
				<td><input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>"/></td>
				<td><span class="invalid-feedback"><?php echo $password_err; ?></span></td>
			</tr>
		</table>
		<input type="submit" class="btn btn-primary" value="Přihlásit se"/>
	</form>

	<?php 
	if(!empty($login_err) || !empty($username_err)|| !empty($password_err))
	{
		echo "<hr/>";
		echo "Nepamatujete si svůj login/heslo? <a href=\"/lostPasswordForm.php\">Klikněte sem pro obnovení</a>";
	}	 
	?>
</article>
<?php include("footer.php");?>
