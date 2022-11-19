<?php
include("header.php");

// Define variables
$viewingId=uuid_to_bin($_GET["id"]);
header_remove();

// display user info or 404
$sql = "SELECT username, created_at, motto, avatar FROM users WHERE id = ?";

if($stmt = mysqli_prepare($link, $sql))
{
	// Bind variables to the prepared statement as parameters
	mysqli_stmt_bind_param($stmt, "s", $param_userid);

	// Set parameters
	$param_userid = $viewingId;

	// Attempt to execute the prepared statement
	if(mysqli_stmt_execute($stmt))
	{
		// Store result
		mysqli_stmt_store_result($stmt);

		// Check if username exists, if yes then verify password
		if(mysqli_stmt_num_rows($stmt) == 1)
		{
			// Bind result variables
			mysqli_stmt_bind_result($stmt, $viewingUsername, $viewingCreationDate, $viewingMotto, $viewingAvatar);
			if(mysqli_stmt_fetch($stmt))
			{
				// Store data in viewing user variable
				$_VIEWINGUSER["username"] = $viewingUsername;
				$_VIEWINGUSER["created_at"] = $viewingCreationDate;
				$_VIEWINGUSER["motto"] = $viewingMotto;
				$_VIEWINGUSER["avatar"] = $viewingAvatar;
			}
		}
		else
		{
			// Article doesn't exist, display 404
			http_response_code(404);
			//header("location: /404.php");
			die();
		}
	}
	else
	{
		printf("Error: %s.\n", mysqli_stmt_error($stmt));
	}

	// Close statement
	mysqli_stmt_close($stmt);
}

// prepare user tags variable
$sql = "SELECT user_tags.color, user_tags.name
FROM ((users
INNER JOIN user_has_tag ON users.id = user_has_tag.user_id)
INNER JOIN user_tags ON user_has_tag.tag_id = user_tags.id)
WHERE users.id = ?";

if($stmt = mysqli_prepare($link, $sql))
{
	$USERTAGS = "";

	// Bind variables to the prepared statement as parameters
	mysqli_stmt_bind_param($stmt, "s", $param_userid);

	// Set parameters
	$param_userid = $viewingId;

	// Attempt to execute the prepared statement
	if(mysqli_stmt_execute($stmt))
	{
		// Store result
		mysqli_stmt_bind_result($stmt, $tag_color, $tag_name);

		while(mysqli_stmt_fetch($stmt))
		{
			$USERTAGS .= "<div style=\"float: left; background-color=222222; border: solid {$tag_color}; margin-right: 8px; padding: 4px; font-weight: bold;\">{$tag_name}</div>";
		}
	}
	else
	{
		printf("Error: %s.\n", mysqli_stmt_error($stmt));
	}

	// Close statement
	mysqli_stmt_close($stmt);
}
?>

<article style="min-height: 256px">
	<p class="date">
	Přidal se: <?php echo $_VIEWINGUSER["created_at"]; ?>
	</p>

	<table style="width: 100%;">
		<tr>
			<td style="width: 128px; padding-right: 16px;">
				<img src="<?php echo $_VIEWINGUSER["avatar"]?>" style="width: 128px; height: 128px;"/>
			</td>

			<td style="word-wrap: break-word;">
				<h1 style="width: 100%;">
					<?php echo $_VIEWINGUSER["username"]; ?>
				</h1>
				<p style="width: 100%;">
					<?php echo $_VIEWINGUSER["motto"]; ?>
				</p>
			</td>
		</tr>
	</table>
	<div>
<?php
	echo ($USERTAGS);
?>
	</div>
</article>
<?php include("footer.php"); ?>
