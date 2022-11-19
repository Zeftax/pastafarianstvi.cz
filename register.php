<?php
include("header.php");
 
// Define variables and initialize with empty values
$username = $email = $password = $confirm_password = "";
$username_err = $email_err = $password_err = $confirm_password_err = "";
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST")
{
	// Validate username
	if(empty(trim($_POST["username"])))
	{
		$username_err = "Prosím Zadejte přezdívku.";
	}
	elseif(!preg_match('/^[a-zA-Z0-9_]+$/', trim($_POST["username"])))
	{
		$username_err = "Přezdívka může obsahovat pouze písmena, čísla a podtržítka.";
	}
	else
	{
		// Prepare a select statement
		$sql = "SELECT id FROM users WHERE username = ?";
	
		if($stmt = mysqli_prepare($link, $sql))
		{
			// Bind variables to the prepared statement as parameters
			mysqli_stmt_bind_param($stmt, "s", $param_username);
			
			// Set parameters
			$param_username = trim($_POST["username"]);
			
			// Attempt to execute the prepared statement
			if(mysqli_stmt_execute($stmt))
			{
				/* store result */
				mysqli_stmt_store_result($stmt);
			
				if(mysqli_stmt_num_rows($stmt) == 1)
				{
					$username_err = "Přezdívka již zabraná.";
				}
				else
				{
					$username = trim($_POST["username"]);
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

	// Validate email
	if(!empty($_POST["email"]))
	{
		$email = trim($_POST["email"]);

		// validate email has not been used before 
		// Prepare a select statement
		$sql = "SELECT id FROM users WHERE email = ?";
	
		if($stmt = mysqli_prepare($link, $sql))
		{
			// Bind variables to the prepared statement as parameters
			mysqli_stmt_bind_param($stmt, "s", $param_email);
			
			// Set parameters
			$param_email = $email;
			
			// Attempt to execute the prepared statement
			if(mysqli_stmt_execute($stmt))
			{
				/* store result */
				mysqli_stmt_store_result($stmt);
			
				if(mysqli_stmt_num_rows($stmt) == 1)
				{
					$email_err = "Email je již používán.";
				}
			} 
			else
			{
				printf("Error: %s.\n", mysqli_stmt_error($stmt));
			}

			// Close statement
			mysqli_stmt_close($stmt);
		}

		// validate email is valid email address
		if(!filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			$email_err = "Email není platný.";
		}
	}
		
	$nejcastejsihesla = [
		'123456',
		'heslo',
		'12345',
		'123456789',
		'martin',
		'aaaaaa',
		'michal',
		'internet',
		'aaaaaa',
		'666666',
		'159753',
		'hesloheslo',
		'111111',
		'heslo123',
		'genius',
		'matrix',
		'hovno',
		'12345678',
		'000000',
		'ahojky',
		'password',
		'slunicko',
		'tomas',
		'tunning',
		'000000',
		'nevim',
		'killer',
		'lopata',
		'pavel',
		'monika',
		'lukasek',
		'qwerty',
		'poklop',
		'11111',
		'asdfgh',
		'asdasd',
		'nasrat',
		'qwert',
		'jahoda',
		'lucinka',
		'sparta',
		'heslo123'
	];

	// Validate password
	if(empty(trim($_POST["password"])))
	{
		$password_err = "Prosím zadejte heslo.";	
	}
	elseif(strlen(trim($_POST["password"])) < 6)
	{
		$password_err = "Heslo musí bý alespoň 6 charakterů dlouhé.";
	}
	elseif(in_array(trim($_POST["password"]), $nejcastejsihesla))
	{
		$password_err = "Toto heslo již používá uživatel pepa.novak@seznam.cz.";
	}
	else
	{
		$password = trim($_POST["password"]);
	}
		
	// Validate confirm password
	if(empty(trim($_POST["confirm_password"])))
	{
		$confirm_password_err = "Prosím potvrďte heslo.";	
	}
	else
	{
		$confirm_password = trim($_POST["confirm_password"]);
		if(empty($password_err) && ($password != $confirm_password))
		{
			$confirm_password_err = "Hesla se neshodují.";
		}
	}
		
	// Check input errors before inserting in database
	if(empty($username_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err))
	{
		// Prepare an insert statement
		$sql = "INSERT INTO users (id, username, email, password, avatar) VALUES (?, ?, ?, ?, ?)";
		 
		if($stmt = mysqli_prepare($link, $sql))
		{
			// Bind variables to the prepared statement as parameters
			mysqli_stmt_bind_param($stmt, "sssss", $param_id, $param_username, $param_email, $param_password, $param_avatar);
			
			// Set parameters
			$param_id = GenerateUUID();
			$param_username = $username;
			$param_email = $email;
			$param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
			$param_avatar = '/Images/DefaultProfile.jpg';
				
			// Attempt to execute the prepared statement
			if(mysqli_stmt_execute($stmt))
			{
				// If no email has been entered, congratulate on registration
				if(empty($email))
				{
					header("location: /registration_finish.php/_?emailEntered=false");
				}
				// If user has entered email, start validation process, and warn the user that vvalidation is mandatory.
				else
				{
					// Add the user to awaiting approval list
					$sql = "INSERT INTO user_awaiting_registration_email (user_id , expires_on, email_key) VALUES (?, ?, ?)";
					if($stmt2 = mysqli_prepare($link, $sql))
					{
						// Bind variables to the prepared statement as parameters
						mysqli_stmt_bind_param($stmt2, "sss", $param_id, $param_expiry_date, $param_email_key);
						$param_expiry_date = date("Y-m-d H:i:s", strtotime('+1 day'));
						$param_email_key = GenerateUUID();

						// redirect to the page that tells you to verify your email
						if(mysqli_stmt_execute($stmt2))
						{
							$to = $email;
							$subject = "Potvrzení registrace";
							$message = "
							<html>
								<head>
								<title>Potvrzení registrace</title>
								</head>
								<body style=\"font-family: 'Times New Roman', Times, serif; font-size: large;\">
									<h1>Ahoj námořníku, vítej na palubě!</h1>
									<p>
									Právě jsme přijali tvou žádost o registraci na <a href=\"https://pastafarianstvi.cz\" style=\"text-decoration: none; border-bottom: dotted black thin;\">pastafariánství webstránky</a>. Musíš ale prvně potvrdit že se doopravdy chceš vytvořit účet na naší stránce, tak učiníš kliknutím na tlačítko níže, nebo když půjdeš na odkaz pod ním
									</p>
									<form action=\"https://pastafarianstvi.cz/confirm_registration_email.php\" method=\"get\">
										<input type=\"hidden\" id=\"user_id\" name=\"user_id\" value=\"".bin_to_uuid($param_id)."\">
										<input type=\"hidden\" id=\"email_key\" name=\"email_key\" value=\"".bin_to_uuid($param_email_key)."\">
										<input type=\"submit\" value=\"Klikni sem pro potvrzení registrace\" />
									</form>
									<a href=\"https://pastafarianstvi.cz/confirm_registration_email.php/_?user_id=".bin_to_uuid($param_id)."&email_key=".bin_to_uuid($param_email_key)."\" style=\"text-decoration: none; border-bottom: dotted black thin;\">https://pastafarianstvi.cz/confirm_registration_email.php/_?user_id=".bin_to_uuid($param_id)."&email_key=".bin_to_uuid($param_email_key)."</a>
									<hr>
									<p>
									Nezaregistrovali jste se? Nemusíte nic dělat, stačí ignorovat tuto zprávu. Bez potvrzení bude váš e-mail za cca den smazán z naší databáze.
									</p>
								</body>
							</html>
							";
							$headers = array(
							"MIME-Version" => "1.0",
							"Content-type" => "text/html; charset=UTF-8",
							"from" => "Pastafariánství noreply <noreply@pastafarianstvi.cz>",
							"X-mailer" => "phpWebmail"
							);

							mail($to, $subject, $message, $headers);
							header("location: /registration_finish.php/_?emailEntered=true");
						}
						else
						{
							printf("Error: %s.\n", mysqli_stmt_error($stmt2));
						}
						// Close statement
						mysqli_stmt_close($stmt2);
					}
					else
					{
						printf("Error: %s.\n", mysqli_stmt_error($stmt2));
					}
				}
			}
			else
			{
				printf("Error: %s.\n", mysqli_stmt_error($stmt));
			}

			// Close statement
			mysqli_stmt_close($stmt);
		}
		else
		{
			printf("Error: %s.\n", mysqli_stmt_error($stmt));
		}
	}
}
?>
 
<article>
	<h2>Vytvořit účet</h2>
	<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
	<table>
		<tr>
			<td><label>Přezdívka:</label></td>
			<td><input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>"></td>
			<td><span class="invalid-feedback"><?php echo $username_err; ?></span></td>
		</tr>		
		<tr>
			<td><label>Email:</label></td>
			<td><input type="text" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>"></td>
			<td><span class="invalid-feedback"><?php echo $email_err; ?></span></td>
		</tr>		
		<tr>
			<td><label>Heslo:</label></td>
			<td><input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $password; ?>"></td>
			<td><span class="invalid-feedback"><?php echo $password_err; ?></span></td>
		</tr>
		<tr>
			<td><label>Potvrdit heslo:</label></td>
			<td><input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $confirm_password; ?>"></td>
			<td><span class="invalid-feedback"><?php echo $confirm_password_err; ?></span></td>
		</tr>
	</table>	
	<input type="submit" class="btn btn-primary" value="Potvrdit">
</form>
<hr>
<p>
	*Email není povinný údaj, ale umožní vám obnovit své heslo v případě zapomenutí.
</article>		

<?php include("footer.php"); ?>
