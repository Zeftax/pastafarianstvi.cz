<?php
include("header.php");

$sql = "SELECT news.id, news.date_posted, news.name, news.content, users.username FROM news INNER JOIN users ON news.poster_id = users.id ORDER BY date_posted DESC; ";

if($stmt = mysqli_prepare($link, $sql))
{
	// Attempt to execute the prepared statement
	if(mysqli_stmt_execute($stmt))
	{
		// Store result
		mysqli_stmt_bind_result($stmt, $id, $date_posted, $name, $content, $poster_name);

		while(mysqli_stmt_fetch($stmt))
		{
			$Parsedown = new Parsedown();

			$parsedContent = $Parsedown->text($content);

			printf("
				<article style=\"padding: 0\">
					<a href=\"/newsView.php/_?id=%s\" >
						<div class=\"bgOnHover\" style=\"padding: 8px 16px 8px 16px;\">
							<p class=\"date\" style=\"margin: 0;\"> %s </p>
							<h1 style=\"float: left; margin: 0;\"> %s </h1>
							<p style=\"float: right; margin: 0;\"> %s </p>
							<div style=\"clear: both;\"></div> 
						</div>
					</a>
					<div style=\"padding: 8px 16px 8px 16px;\">
						%s
					</div>
				</article>
				
			", $id, $date_posted, $name, $poster_name, $parsedContent);
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

include("footer.php");
?>
