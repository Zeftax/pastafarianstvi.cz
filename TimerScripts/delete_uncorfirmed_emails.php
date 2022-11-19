<?php
require_once ("../config.php");

// Prepare a select statement
$sql = "DELETE FROM user_awaiting_registration_email WHERE expires_on < ?";

if($stmt = mysqli_prepare($link, $sql))
{
    // Bind variables to the prepared statement as parameters
    mysqli_stmt_bind_param($stmt, "s", $param_datetime);
    
    // Set parameters
    $param_datetime = time();
    
    // Attempt to execute the prepared statement
    if(mysqli_stmt_execute($stmt))
    {
        printf("uspech");
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
?>