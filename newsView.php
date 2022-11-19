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
		<a href=\"/editArticle.php/_?id=".$viewingId."\" style=\"width: 50px; height: 50px;\">
			<img/ src=\"/Images/EditIcon.png\" class=\"borderOnHover\" style=\"width: 45px; height: 45px;\">
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
		//header("location: /newsView.php/_?id=".$_POST["viewingId"]);
		//die;
	}
	else if(!isset($_SESSION["username"]))
	{
		$post_comment_err = "Musíte být přihlášeni abyste mohli komentovat!";
	}

	if(empty($post_comment_err))
	{
		// Prepare an insert statement
		$sql = "INSERT INTO news_comments (article_id, poster_id, content) VALUES (?, ?, ?)";
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
				header("location: /newsView.php/_?id=".$_POST["viewingId"]);
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

// LOAD THE NEWS ARTICLE
// Prepare a select statement
$sql = "SELECT users.username, news.date_posted, news.name, news.content FROM news INNER JOIN users ON news.poster_id = users.id WHERE news.id = ?";

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
		if(mysqli_stmt_num_rows($stmt) == 1)
		{
			// Bind result variables
			mysqli_stmt_bind_result($stmt, $poster_name, $date_posted, $name, $content);
			if(mysqli_stmt_fetch($stmt))
			{
				$Parsedown = new Parsedown();
				$Purifier = new HTMLPurifier($config);

				$parsedContent = $Parsedown->text($content);
				$clean_html = $Purifier->purify($parsedContent);

				printf("
					<article>
						<div class=\"sidFlex\">
							<h1 style=\"float: left;\"> %s </h1>
							".$editButton."
							<div style=\"float: right; text-align: right; top: 0\">
								<div>
									%s
								</div>
								<div>
									%s
								</div>
								<br style=\"clear:both;\"/>
							</div>
						</div>
						<div>
							%s
						</div>
					</article>
				",  $name, $date_posted, $poster_name, $parsedContent);
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
// /LOAD THE NEWS ARTICLE

// COMMENT BOX
echo
"
	<hr/>
	<h3>Zanechat Komentář</h3><br/>".$post_comment_err."
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
$sql = "SELECT users.username, news_comments.date_posted, news_comments.content FROM news_comments INNER JOIN users on users.id = news_comments.poster_id WHERE article_id = ? ORDER BY date_posted DESC";

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
