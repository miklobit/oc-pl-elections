<?php
require_once("../ElectionController.php");
use Election\VotingStatus;
$elections = new Election\ElectionController();
session_start();
if (empty($_SESSION['canVote'])) {
    $_SESSION['canVote'] = VotingStatus::SHOW_LOGIN_BUTTON;
}
if(isset($_POST["logMeIn"]) == 1) {
    $elections->logMeIn();
}
if(isset($_GET["oauth_token"]) && isset($_GET["oauth_verifier"])) {
    $elections->authorizeMe($_GET["oauth_token"]);
}
if(isset($_POST["voted"]) && $_POST["voted"]==1) {
    $elections->addResultsToDb();
}
?>
<!DOCTYPE html>
    <head>
        <meta charset="utf-8">
      	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta name="viewport" content="width=device-width">

        <title>System przeprowadzania wyborów opecaching.pl</title>

		<link href='https://fonts.googleapis.com/css?family=Open+Sans:400,300,300italic,400italic,600,600italic,700,700italic,800,800italic&amp;subset=latin,latin-ext' rel='stylesheet' type='text/css'>
        <link rel="stylesheet" href="css/main.css">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
        <script>
            $(document).ready(function(){
                $("button").click(function(){
                    $("#spinner").show();
                    $( this ).hide();
                });
            });
        </script>
    </head>
    <body>
        <section id="wrapper">
            <div class="main-container">
                <div class="header-wrap">
                    <div class="header">
                        <p class="top">System przeprowadzania wyborów Opencaching Polska</p>
                        <?php print $elections->getElectionTitle(); ?>
                    </div>
                </div>
                <div class="main wrapper clearfix">
                    <div class="content">
                        <?php print $elections->getContent($_SESSION['canVote']); ?>
                    </div>
                </div> <!-- .main -->
            </div> <!-- #main-container -->

            <!-- FOOTER -->
            <div id="footer">
                <div class="legal"><a href="http://opencaching.pl" target="_blank">Informacja wyborcza.</a></div>
            </div>
            <!-- End Footer -->

		</section>
    </body>
</html>