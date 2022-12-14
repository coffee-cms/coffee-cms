<main>
<?php
    echo $cms["page"]["text"];

    $date = date( 'Y-m-d H:i:s' );
    $q = "SELECT * FROM pages WHERE tpl = 'post' AND `created` < '{$date}' ORDER BY id DESC LIMIT 6";

    if ( $res = mysqli_query( $cms["base"], $q ) ) {

        while ( $page = mysqli_fetch_assoc( $res ) ) {

            include( __DIR__ . "/teaser.php" );

        }

    }
?>
</main>