<?php

include("header.php");

// Define variables
$viewingId=htmlspecialchars($_GET["id"]);
header_remove();

$editButton = "";
if($_SESSION["is_admin"])
{
	$editButton =
	"
		<a href=\"/editShanty.php/_?id=".$viewingId."\">
			<img/ src=\"/Images/EditIcon.png\" class=\"borderOnHover\" style=\"float: right; width: 50px; height: 50px;\">
		</a>
	";
}

$post_comment_err = "";

// Processing form data when form is submitted
if(isset($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] == "POST")
{
	

	if(empty(trim($_POST["content"])))
	{
		$post_comment_err = "Váš komentář nemá žádný obsah!";
	}
	else if(!isset($_SESSION["username"]))
	{
		$post_comment_err = "Musíte být přihlášeni abyste mohli komentovat!";
	}
	if(empty($post_comment_err))
	{
		// Prepare an insert statement
		$sql = "INSERT INTO shanties_comments (article_id, poster_id, content) VALUES (?, ?, ?)";
		if($stmt = mysqli_prepare($link, $sql))
		{
			// Bind variables to the prepared statement as parameters
			mysqli_stmt_bind_param($stmt, "sss", $param_article, $param_poster, $param_content);
			
			// Set parameters
			$param_article = $_POST["viewingId"];
			$param_poster = $_SESSION["userid"];
			$param_content = $_POST["content"];
						
			// Attempt to execute the prepared statement
			if(mysqli_stmt_execute($stmt))
			{
				// Redirect to login page
				header("location: /shantiesView.php/_?id=".$_POST["viewingId"]);
				die;
			}
			else
			{
				printf("Error: %s.\n", mysqli_stmt_error($stmt));
				mysqli_stmt_close($stmt);
			}
		}
		else
		{
			echo "Error: %s.\n", mysqli_error($link);
		}
	}
}
// Prepare a select statement
$sql = "SELECT credits, name, content FROM shanties WHERE id = ?";

if($stmt = mysqli_prepare($link, $sql))
{
	// Bind variables to the prepared statement as parameters
	mysqli_stmt_bind_param($stmt, "s", $param_id);

	// Set parameters
	$param_id = $viewingId;

	// Attempt to execute the prepared statement
	if(mysqli_stmt_execute($stmt))
	{
		// Store result
		mysqli_stmt_store_result($stmt);

		// Check if entry exists, if yes then display
		if(mysqli_stmt_num_rows($stmt) == 1)
		{
			// Bind result variables
			mysqli_stmt_bind_result($stmt, $credits, $name, $content);
			if(mysqli_stmt_fetch($stmt))
			{
				$Parsedown = new Parsedown();

				$parsedContent = $Parsedown->text($content);
				$parsedCredits = $Parsedown->text($credits);

				printf("
	<div style=\"width: 100%%; display inline-blok\">
		<h1 style=\"float: left;\"> %s </h1>
		".$editButton."
		<br style=\"clear:both;\"/>
	</div>
	<div id=\"shantyContainer\">
		<article id=\"shantyText\">
			%s
		</article>
		<article id=\"shantyCredits\">
			%s
		</article>
		<br style=\"clear:both;\"/>
	</div>
				",  $name, $parsedContent, $parsedCredits);

			}
		}
		else
		{
			// Article doesn't exist, display 404
			http_response_code(404);
			header("location: /404.php");
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

// COMMENT BOX
echo
"
	<hr/>
	<h3>Zanechat Komentář</h3>".$post_comment_err."
	<form action=\"".htmlspecialchars($_SERVER["PHP_SELF"])."?id=".htmlspecialchars($_GET["id"])."\" class=\"commentForm\" method=\"post\">
		<div class=\"centeredContainer\">
			<textarea name=\"content\" class=\"commentBox\" placeholder=\"Komentář\"></textarea>
		</div>
		<input type=\"hidden\" name=\"viewingId\" value=\"".$viewingId."\"/>
		<input type=\"submit\" class=\"btn btn-primary comment-btn\" value=\"Odeslat\"/>
	</form>
	<h3>Komentáře:</h3>
";
// / COMMENT BOX

// LOAD THE COMMENTS
// Prepare a select statement
$sql = "SELECT users.username, shanties_comments.date_posted, shanties_comments.content FROM shanties_comments INNER JOIN users ON shanties_comments.poster_id = users.id WHERE article_id = ? ORDER BY date_posted DESC";

if($stmt = mysqli_prepare($link, $sql))
{
	// Bind variables to the prepared statement as parameters
	mysqli_stmt_bind_param($stmt, "s", $param_id);

	// Set parameters
	$param_id = $viewingId;

	// Attempt to execute the prepared statement
	if(mysqli_stmt_execute($stmt))
	{
		// Store result
		mysqli_stmt_store_result($stmt);

		// Check if username exists, if yes then verify password
		if(mysqli_stmt_num_rows($stmt) > 0)
		{
			// Bind result variables
			mysqli_stmt_bind_result($stmt, $poster_name, $date_posted, $content);
			while(mysqli_stmt_fetch($stmt))
			{
				$Parsedown = new Parsedown();

				$config = HTMLPurifier_Config::createDefault();
				$config->set('HTML.Allowed', 'p,b,a[href],i');  
				$config->set('HTML.AllowedAttributes', 'a.href,img.src');  

				$Purifier = new HTMLPurifier($config);

				$dirty_html = $Parsedown
					->text($content);

				$clean_html = $Purifier
					->purify($dirty_html);

				printf("
					<article class=\"comment\">
						<div class=\"sidFlex\">
							<p style=\"margin: 0;\"> %s </p>
							<p class=\"date\" style=\"margin: 0;\"> %s </p>
						</div>
						<hr/>
						<div style=\"clear: both;\"></div>
						<p> %s </p>
					</article>
				", $poster_name, $date_posted, $clean_html);
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
	echo "Error: %s.\n", mysqli_error($link);
}
// / LOAD THE COMMENTS

include("footer.php");

?>
