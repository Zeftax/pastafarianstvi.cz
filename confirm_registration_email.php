<?php
include("header.php");

$error = "";
// Processing form data when form is submitted
if(isset($_GET["user_id"]) && isset($_GET["email_key"]))
{
    $uid = trim($_GET["user_id"]);
    $key = trim($_GET["email_key"]);

    // Prepare a select statement
    $sql = "DELETE FROM user_awaiting_registration_email WHERE user_id = ? AND email_key = ?";

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
                echo "<h1>Email úspěšně ověřen.</h1>";
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
    $key = trim($_GET["email_key"]);

    // Check if key is correct
    // Prepare a select statement
    $sql = "SELECT email_key FROM user_awaiting_registration_email WHERE user_id = ?";

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
            mysqli_stmt_bind_result($stmt, $email_key);

            if(mysqli_stmt_fetch($stmt))
            {
                if($key != bin_to_uuid($email_key))
                {
                    $error = "Vas klic neni platny klic pro aktivaci e-mailu pro tento ucet.";
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


include("footer.php");
?>