<?php

// Initialize the session
session_start();

// Include config file
require_once "config.php";

//check if user has sufficient privileges to write articles, and kill the process if not.
if(!$_SESSION["is_admin"])
{
	include("header.php");
	echo "<article><h1>nedostatecna opravneni</h1></article>";
	include("footer.php");
	die;
}

$viewingId=htmlspecialchars($_GET["id"]);
header_remove();
$prefillName="";
$prefillContent="";
$prefillCredits="";

//if we are editing an already existing file, load its values
if(! $viewingId == NULL)
{
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
				mysqli_stmt_bind_result($stmt, $sqlPrefillCredits, $sqlPrefillName, $sqlPrefillContent);
				
				if(mysqli_stmt_fetch($stmt))
				{
					$prefillCredits = $sqlPrefillCredits;
					$prefillName = $sqlPrefillName;
					$prefillContent = $sqlPrefillContent;
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
}
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST")
{
	// Prepare an insert statement
	if(! $_POST["viewingId"] == NULL)
	{
		$sql = "UPDATE shanties SET credits=?, name=?, content=? WHERE id = ".$_POST["viewingId"];
	}
	else
	{
		$sql = "INSERT INTO shanties (credits, name, content) VALUES (?, ?, ?)";
	}

	if($stmt = mysqli_prepare($link, $sql))
	{
		// Bind variables to the prepared statement as parameters
		mysqli_stmt_bind_param($stmt, "sss", $param_credits, $param_name, $param_content);
		
		// Set parameters
		$param_credits = $_POST["credits"];
		$param_name = $_POST["name"];
		$param_content = $_POST["content"];
					
		// Attempt to execute the prepared statement
		if(mysqli_stmt_execute($stmt))
		{
			// Redirect to login page
			header("location: /index.php");			
		}
		else
		{
			// Close statement
			mysqli_stmt_close($stmt);
		}
	}
}
?>

<?php include("header.php"); ?>
<article>
	<h1>
		NAPSAT PÍSNIČKU
	</h1>
	<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
		<div class="centeredContainer">
			<input type="text" name="name" style="font-weight: bold; width: 150%" placeholder="Název" value="<?php echo $prefillName; ?>"/>
		</div>
		<div class="centeredContainer">
			<textarea name="content" style="height: 400px; width: 50%; resize: none;" placeholder="Písnička"><?php echo $prefillContent; ?></textarea>
            <textarea name="credits" style="height: 400px; width: 50%; resize: none;" placeholder="Zásluhy"><?php echo $prefillCredits; ?></textarea>
		</div>
		<input type="hidden" name="viewingId" value="<?php echo $viewingId; ?>"/>
		<input type="submit" class="btn btn-primary" value="Odeslat"/>
	</form>
</article>

<?php include("footer.php"); ?>
