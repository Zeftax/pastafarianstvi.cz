<?php

// Initialize the session
session_start();
$_SESSION = array();

// Include config file
require_once "config.php";
require_once (__DIR__."/libraries/Parsedown.php");
require_once (__DIR__.'/libraries/htmlpurifier-4.14.0/library/HTMLPurifier.auto.php');
require_once (__DIR__."/libraries/GUID.php");

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

function login_user($userId, $link)
{
    // Prepare a select statement
    $sql = "SELECT id, username, password FROM users WHERE id = ?";

    if($stmt = mysqli_prepare($link, $sql))
    {
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, "s", $param_userId);
    
        // Set parameters
        $param_userId = $userId;
    
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
                    // Store data in session variables
                    $_SESSION["loggedin"] = true;
                    $_SESSION["userid"] = bin_to_uuid($id);
                    $_SESSION["username"] = $username;
                    $_SESSION["is_admin"] = is_user_admin($link, $username);
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
}


$error = "";
// Processing form data when form is submitted
if(isset($_GET["user_id"]) && isset($_GET["access_key"]))
{
    $uid = trim($_GET["user_id"]);
    $key = trim($_GET["access_key"]);

    // Prepare a select statement
    $sql = "DELETE FROM user_one_time_access_token WHERE user_id = ? AND access_key = ?";

    if($stmt = mysqli_prepare($link, $sql))
    {
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, "ss", $param_uid, $param_key);
        
        // Set parameters
        $param_uid = uuid_to_bin($uid);
        $param_key = uuid_to_bin($key);
        
        // Attempt to execute the prepared statement
        if(mysqli_stmt_execute($stmt))
        {
            /* store result */
            mysqli_stmt_store_result($stmt);
            if(mysqli_stmt_affected_rows($stmt) == 1)
            {
                login_user($param_uid, $link);
                header("location: /index.php");
            }
            else
            {
                $error = "Neznamy error.";
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
else
{
    echo("Chyba, neni nastaveno ID uzivatele nebo klic.");
}
if(!empty($error))
{
    $uid = trim($_GET["user_id"]);
    $key = trim($_GET["access_key"]);

    // Check if key is correct
    // Prepare a select statement
    $sql = "SELECT access_key FROM user_one_time_access_token WHERE user_id = ?";

    if($stmt = mysqli_prepare($link, $sql))
    {
        // Bind variables to the prepared statement as parameters
        mysqli_stmt_bind_param($stmt, "s", $param_uid);
        
        // Set parameters
        $param_uid = uuid_to_bin($uid);
        
        // Attempt to execute the prepared statement
        if(mysqli_stmt_execute($stmt))
        {
            /* store result */
            mysqli_stmt_store_result($stmt);
        
            // Bind result variables
            mysqli_stmt_bind_result($stmt, $access_key);

            if(mysqli_stmt_fetch($stmt))
            {
                if($key != bin_to_uuid($access_key))
                {
                    $error = "Vas klic neni platny klic pro jednorázový login pro tento ucet.";
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

    echo $error;
}
?>