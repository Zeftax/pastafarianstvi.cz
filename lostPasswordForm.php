<?php
include("header.php");

// Define variables and initialize with empty values
$email = "";
$email_err = "";

function query_user($email, $link)
{
    // Prepare a select statement
    $sql = "SELECT username, id FROM users WHERE email = ?";
    $username = "";
    $user_id = "";

    if($stmt = mysqli_prepare($link, $sql))
    {
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, "s", $param_email);

        // Set parameters
        $param_email = $email;

        // Attempt to execute the prepared statement
        if(mysqli_stmt_execute($stmt))
        {
            // Store result
            mysqli_stmt_store_result($stmt);

            if(mysqli_stmt_num_rows($stmt) == 1)
            {
                mysqli_stmt_bind_result($stmt, $username, $user_id);
                mysqli_stmt_fetch($stmt);
            }
        }
        else
        {
            printf("Error: %s.\n", mysqli_stmt_error($stmt));
        }

        // Close statement
        mysqli_stmt_close($stmt);
    }
	return [$username, $user_id];
}

function is_login_request_active($user_id, $link)
{
    // Prepare a select statement
    $sql = "SELECT * FROM user_one_time_access_token WHERE user_id = ?";
    $result;

    if($stmt = mysqli_prepare($link, $sql))
    {
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, "s", $param_id);

        // Set parameters
        $param_id = $user_id;

        // Attempt to execute the prepared statement
        if(mysqli_stmt_execute($stmt))
        {
            // Store result
            mysqli_stmt_store_result($stmt);

            if(mysqli_stmt_num_rows($stmt) == 1)
            {
                $result = true;
            }
            else
            {
                $result = false;
            }
        }
        else
        {
            printf("Error: %s.\n", mysqli_stmt_error($stmt));
        }

        // Close statement
        mysqli_stmt_close($stmt);
    }
	return $result;
}

// Processing form data when form is submitted
if(isset($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] == "POST")
{ 
	// Check if email is empty
	if(empty(trim($_POST["email"])))
	{
		$email_err = "Pros??m zadejte v???? email.";
	}
	else
	{
		$email = trim($_POST["email"]);
	}
    
	// Validate credentials
	if(empty($email_err))
	{
		[$username, $user_id] = query_user($email, $link);

        if(empty($user_id))
        {
            $email_err = "Tento email u n??s nen?? zaregistrovan??.";
        }
        else if(is_login_request_active($user_id, $link))
        {
            $email_err = "Pro tuto adresu ji?? je aktivn?? token. Zkontrolujte svou e-mailovou schr??nku.";
        }
        else
        {
            // Add the user to awaiting approval list
            $sql = "INSERT INTO user_one_time_access_token (user_id , expires_on, access_key) VALUES (?, ?, ?)";
            if($stmt = mysqli_prepare($link, $sql))
            {
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "sss", $param_id, $param_expiry_date, $param_key);
                $param_id = $user_id;
                $param_expiry_date = date("Y-m-d H:i:s", strtotime('+15 minutes'));
                $param_key = GenerateUUID();

                // redirect to the page that tells you to verify your email
                if(mysqli_stmt_execute($stmt))
                {
                    $to = $email;
                    $subject = "Obnoven?? hesla";
                    $message = "
                    <html>
                        <head>
                        <title>Obnoven?? hesla</title>
                        </head>
                        <body style=\"font-family: 'Times New Roman', Times, serif; font-size: large;\">
                            <h1>N??kdo po????dal o obnoven?? hesla tohoto ????tu</h1>
                            <p>
                                Pr??v?? jsme p??ijali tvou ????dost o obnoven?? hesla pro ????et".$username." na <a href=\"https://pastafarianstvi.cz\" style=\"text-decoration: none; border-bottom: dotted black thin;\">pastafari??nstv?? webstr??nky</a>. Pro jednor??zov?? p??ihl????en?? do sv??ho ????tu a n??slednou zm??nu hesla klikni na tla????tko n????e nebo jdi na odkaz pod n??m.
                            </p>
                            <form action=\"https://pastafarianstvi.cz/oneTimeEmailLogin.php\" method=\"get\">
                                <input type=\"hidden\" id=\"user_id\" name=\"user_id\" value=\"".bin_to_uuid($param_id)."\">
                                <input type=\"hidden\" id=\"access_key\" name=\"access_key\" value=\"".bin_to_uuid($param_key)."\">
                                <input type=\"submit\" value=\"Klikni sem pro potvrzen?? registrace\" />
                            </form>
                            <a href=\"https://pastafarianstvi.cz/oneTimeEmailLogin.php/_?user_id=".bin_to_uuid($param_id)."&access_key=".bin_to_uuid($param_key)."\" style=\"text-decoration: none; border-bottom: dotted black thin;\">https://pastafarianstvi.cz/oneTimeEmailLogin.php/_?user_id=".bin_to_uuid($param_id)."&access_key=".bin_to_uuid($param_key)."</a>
                            <hr>
                            <p>
                                Nebyl jsi to vy? Nevad??, sta???? tento e-mail ignorovat, bez tvoj?? akce nebudeme nic m??nit.
                            </p>
                        </body>
                    </html>
                    ";
                    $headers = array(
                    "MIME-Version" => "1.0",
                    "Content-type" => "text/html; charset=UTF-8",
                    "from" => "Pastafari??nstv?? noreply <noreply@pastafarianstvi.cz>",
                    "X-mailer" => "phpWebmail"
                    );

                    mail($to, $subject, $message, $headers);
                    echo("
                    <h3>??sp??ch! Zaslali jsme odkaz pro jednor??zov?? p??ihl????en?? na v???? e-mail.</h3>
                    <p>
                    Ne??ekejte s t??m! Token po 15 minut??ch vyprch??.
                    </p>
                    ");
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
}
?>
 
<article>
	<h2>Zapomenut?? heslo</h2>
    <p>
        Zadejte sv??j e-mail, za??leme v??m jednor??zov?? odkaz pro p??ihl????en?? se do sv??ho ????tu.
    </p>

	<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
		<table>
			<tr>
				<td><label>E-mail: </label></td>
				<td><input type="text" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>"/></td>
				<td><span class="invalid-feedback"><?php echo $email_err; ?></span></td>
			</tr>
		</table>
		<input type="submit" class="btn btn-primary" value="Obnovit heslo"/>
	</form>
</article>

<?php include("footer.php");?>