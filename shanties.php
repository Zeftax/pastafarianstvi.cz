<?php
include("header.php");

$sql = "SELECT id, date_posted, name, content FROM shanties ORDER BY date_posted DESC";

if($stmt = mysqli_prepare($link, $sql))
{
	// Attempt to execute the prepared statement
	if(mysqli_stmt_execute($stmt))
	{
		// Store result
		mysqli_stmt_bind_result($stmt, $id, $date_posted, $name, $content);

		echo"<div id=\"shantysContainer\">";

		while(mysqli_stmt_fetch($stmt))
		{
			$Parsedown = new Parsedown();

			$parsed = $Parsedown->text($content);

			$arr = explode("</p>", $parsed);

			$str = array_values($arr)[0]."</p>";

			$arr = explode("</h2>", $str);

			$str = array_values($arr)[1];

			printf("
				<a href=\"/shantiesView.php/_?id=%s\" style=\"padding: 0\">
					<article class=\"bgOnHover\" style=\"width:\">
						<p class=\"date\" style=\"margin: 0;\"> %s </p>
						<h1 style=\"float: left; margin: 0;\"> %s </h1>
						<div style=\"clear: both;\"></div> 
						<p> %s </p>
					</article>
				</a>
			", $id, $date_posted, $name, $str);
		}
		echo "</div>";
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

echo"<article>
	<p>
		Překlad / skladba / kolekce námořnických písniček je komunitní projekt snažící se přinést co nejvíce dobrých námořnických písniček do českého jazyka. Pokud chcete poslat písničku, použijte adresu modlitby@pastafarianstvi.cz. Pokud máte nápad na vylepšení písničky, která již na stránce je, tak jej napište do komentářů. Můžou sem jít překlady, existující i originální tvorba. Jen dodržovat námořnickou / pirátskou tématiku \"shanties\". Pokud máte jen nápad na část písničky / jejího překladu, nebojte se to zaslat i tak, od toho se zvažují návrhy v komentářích. V emailu můžete přiložit své jméno/přezdívku na stránce, původní jméno písničky, původní autory / překladatele. Pokusím se co nejvíce faktorů vepsat pod každou písničku do autorů. Připnuti budou i komentátoři, jejichž nápady budou uplatněny v hlavním těle.
	</p>
</article>
";
include("footer.php");
?>
