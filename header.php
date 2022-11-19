<?php

ob_start();

// Initialize the session
session_start();

// Include config file
require_once "config.php";
require_once (__DIR__."/libraries/Parsedown.php");
require_once (__DIR__.'/libraries/htmlpurifier-4.14.0/library/HTMLPurifier.auto.php');
require_once (__DIR__."/libraries/GUID.php");

// Check if the user is already logged in, if yes then redirect him to welcome page
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true)
{
	$username=htmlspecialchars($_SESSION["username"]);
	$userid=htmlspecialchars($_SESSION["userid"]);
	$firstUserMenuItem="<a href=\"/userView.php/_?id=".$userid."\">Uživatel ".$username." je přihlášen</a>";
	$secondUserMenuItem="<a href=\"/logout.php\">Odhlásit se</a>";
}
else
{
	$firstUserMenuItem="<a href=\"/login.php\">Přihlásit se</a>";
	$secondUserMenuItem="<a href=\"/register.php\">Registrace</a>";
}
?>


<!DOCTYPE html>
<html lang="cs">

<head>
	<meta name="description" content="Náboženství které má koule."/>
	<meta name="keywords" content="Religion, Náboženství, Pastafariánství, Pastafariáni, Létající Špagetové Monstrum, Špagety, LŠM, FSM, Cedník, Piráti, Pirát, Carbonara">
	<meta charset="utf-8">
	<title>Pastafariánství Česká Republika</title>
	<link rel="icon" href="/Images/favicon.jpg" sizes="192x192" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="author" content="Zdeněk Borovec">
	<link rel="stylesheet" href="/Styles/style.css?_ver:7.4">
	<!-- Image link preview-->
	<link rel="image_src" href="Images/PirateFish.png"/>
	<meta property="og:image" content="Images/PirateFish.png"/>
</head>

<body>
<!-- Image link preview-->
<img src="/Images/PirateFish.png" style="display: none;" />

<aside id="leftAside">
<?php
	if(isset($_SESSION["is_admin"]) && $_SESSION["is_admin"])
	{
		echo
		"
	<div id=\"admin-tools\">
		<div>
			<a href=\"/editArticle.php\">
				<img/ src=\"/Images/PenAndQuillIcon.png\" class=\"borderOnHover\">
			</a>
		</div>
		<div>
			<a href=\"/editShanty.php\">
				<img/ src=\"/Images/AccordionIcon.png\" class=\"borderOnHover\">
			</a>
		</div>
	</div>
	<div class=\"flexBreak\"></div>
		";
	}
?>
	<div class="advert">
		<a href="https://www.venganza.org">
			<img src="https://www.spaghettimonster.org/wp-content/uploads/2008/01/fsmdivine.jpg"/>
		</a>
	</div>
	<div class="advert">
		<a href="https://www.youtube.com/channel/UCNHlZN6fuqs8EsjU-Dcp2Hg">
			<img src="/Images/AdvertBanners/youtubebanner.png"/>
		</a>
	</div>
	<div class="advert">
		<a href="https://www.facebook.com/PastafarianstviCeskaRepublika">
			<img src="/Images/AdvertBanners/facebook.webp"/>
		</a>
	</div>
	<div class="advert">
		<a href="https://www.instagram.com/pastafarianstvicr/">
			<img src="/Images/AdvertBanners/instagram.png"/>
		</a>
	</div>
	<div class="advert">
		<a href="https://www.davidjaen.cz">
			<img src="/Images/AdvertBanners/animatedbanner.gif"/>
		</a>
	</div>
	<div class="advert">
		<div>
		<a href="/donateMe.php">
			<img src="/Images/AdvertBanners/donatebanner.png"/>
		</a>
	</div>
</aside>

<div id="order: 2;"></div>

<aside id="rightAside">
<div class="advert">
		<h1> Nejnovější komentáře: </h1>
</div>

<?php
	$sql = "
	SELECT users.username, news_comments.date_posted, news_comments.content, news_comments.article_id, 'news' as source
	FROM news_comments INNER JOIN users ON news_comments.poster_id = users.id
	
	UNION
	
	SELECT users.username, shanties_comments.date_posted, shanties_comments.content, shanties_comments.article_id, 'shanties' as source
	FROM shanties_comments INNER JOIN users ON shanties_comments.poster_id = users.id
	ORDER BY date_posted DESC
	LIMIT 5;
	";

	if($stmt = mysqli_prepare($link, $sql))
	{	
		// Attempt to execute the prepared statement
		if(mysqli_stmt_execute($stmt))
		{
			// Store result
			mysqli_stmt_store_result($stmt);
	
			// Check if username exists, if yes then verify password
			if(mysqli_stmt_num_rows($stmt) > 0)
			{
				// Bind result variables
				mysqli_stmt_bind_result($stmt, $poster_name, $date_posted, $content, $article_id, $source);
				while(mysqli_stmt_fetch($stmt))
				{
					$innerSQL="SELECT name FROM ".$source." WHERE id = ".$article_id;
					
					if($innerstmt = mysqli_prepare($link, $innerSQL))
					{
						if(mysqli_stmt_execute($innerstmt))
						{
							// Store result
							mysqli_stmt_store_result($innerstmt);
					
							// Check if username exists, if yes then verify password
							if(mysqli_stmt_num_rows($innerstmt) > 0)
							{
								// Bind result variables
								mysqli_stmt_bind_result($innerstmt, $name);
								while(mysqli_stmt_fetch($innerstmt))
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
									<article class=\"commentPreview previewElement\">
										<a href=\"/%sView.php/_?id=%s\">
											<div class=\"bgOnHover previewContent\">
												<h3>%s</h3>
												<div class=\"sidFlex\">
													<p style=\"margin: 0;\"> %s </p>
													<p class=\"date\" style=\"margin: 0;\"> %s </p>
												</div>
												<hr/>
											</div>
										</a>
										<div style=\"clear: both;\"></div>
										<div class=\"previewContent\">
											%s
										</div>
									</article>
									", $source, $article_id, $name, $poster_name, $date_posted, $clean_html);
								}
							}
						}
					}
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
?>
</aside>

<div style="order: 4"></div>

<div id="page-container">
	<div id="headerContainer">
		<ul id="userMenu">
<?php
			echo "<li>".$firstUserMenuItem."</li>";
			echo "<li>".$secondUserMenuItem."</li>";
?>
		</ul>
		<nav>
			<ul id="navMenu">
				<li><a href="/index.php">Domů</a></li>
				<li><a href="/translation.php">Překlad</a></li>
				<li><a href="/shanties.php">Modlitby</a></li>
			</ul>
		</nav>
	</div>

	<div id="content-wrap">
