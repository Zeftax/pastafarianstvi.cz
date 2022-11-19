<?php
include("header.php");
?>

<h1>
    Registrace proběhla úspěšně!
</h1>
<?php
if($_GET["emailEntered"] == "true")
{
    echo
    "
    Prosím ověřte svůj email odkazem který vám přišel do schránky pro dokončení registrace.<br/>
    <p style=\"font-weight: bold; font-size: 20px;color: red\">
        Pokud do dne svůj e-mail neověříte, bude odstraněn z vašeho účtu.
    </p>
    ";
}
else
{
    echo
    "
    Nezadali jste svůj e-mail, to znamená že pokud ztratíte své heslo, tak nemůžeme ověřit, zdali to jste opravdu vy, a váš účet bude ztracen navždy.
    ";
}
?>
<?php
include("footer.php");
?>
