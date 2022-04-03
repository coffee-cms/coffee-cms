<main>
<?php 
    $q = "SELECT * FROM pages WHERE tpl = 'post' ORDER BY id DESC";
    if ( $res = mysqli_query( $cms["base"], $q ) ) {

        $pager = 0;
        $pcount = 0;
        $cluster = "";

        while ( $page = mysqli_fetch_assoc( $res ) ) {
            if ( $page['created'] <= date( 'Y-m-d H:i:s' ) ) {

                if ( preg_match( '/<img[^>]+data-preview[^>]*>/s', $page['text'], $m ) ) {
                    $preview_img = $m[0];
                } else {
                    $preview_img = '';
                }

                if ( preg_match( '/^(.*)(<!--[\s]*preview-start[\s]*-->)(.*)(<!--[\s]*preview-end[\s]*-->)(.*)$/s', $page['text'], $m ) ) {
                    $preview = $m[3];
                } else {
                    $preview = preg_replace( "/<h1>(.*?)<\/h1>/", "", $page['text'] );
                    $preview = mb_substr( strip_tags( $preview ), 0, 500 );
                    $preview = preg_replace( '/\s\S*$/', ' ...', $preview );
                    $preview = "<p>{$preview}</p>";
                }

                $html = "
                <article>
                    {$preview_img}
                    <h2><a href='{$page['url']}'>{$page['title']}</a></h2>
                    {$preview}
                </article>
                ";

                if ( $pcount < 12 ) {

                    $cluster .= $html;
                    $pcount++;

                } elseif ( $pager == 0 ) {

                    echo $cluster;
                    $cluster = $html;
                    $pager++;
                    $pcount = 1;

                } else {

                    if ( substr( $cms["page"]["url"], -1, 1 ) == "/" ) {
                        $dir = "{$cms['site_dir']}{$cms['page']['url']}";
                        if ( ! file_exists( $dir ) ) {
                            mkdir( $dir, 0777, true );
                        }
                    }

                    $file = "{$cms['site_dir']}{$cms['page']['url']}{$pager}";
                    file_put_contents( $file, $cluster );
                    $cluster = $html;
                    $pager++;
                    $pcount = 1;

                }

            }
        }

        if ( $pager == 0 ) {

            echo $cluster;

        } else {

            if ( substr( $cms["page"]["url"], -1, 1 ) == "/" ) {
                $dir = "{$cms['site_dir']}{$cms['page']['url']}";
                if ( ! file_exists( $dir ) ) {
                    mkdir( $dir, 0777, true );
                }
            }

            $file = "{$cms['site_dir']}{$cms['page']['url']}{$pager}";
            file_put_contents( $file, $cluster );

        }

    }
?>
<script>
    "use strict";
    document.addEventListener( "DOMContentLoaded", function( event ) {
        let n = 1;
        let lock = 0;
        window.addEventListener( "scroll", download );
        async function download() {
            if ( lock ) { return; } else { lock = 1; }
            let windowRelativeBottom = document.documentElement.getBoundingClientRect().bottom;
            let bottom = document.documentElement.clientHeight + 200;
            while ( windowRelativeBottom < bottom && n > 0 ) {
                let url = window.location.protocol + "//" + window.location.host + window.location.pathname + n;
                try {
                    let response = await fetch( url );
                    if ( response.ok ) { // HTTP-status: 200-299
                        let page = await response.text();
                        document.querySelector( "body > main" ).insertAdjacentHTML( "beforeend", page );
                        n++;
                        windowRelativeBottom = document.documentElement.getBoundingClientRect().bottom;
                    } else if ( response.status == 404 ) {
                        n = 0;
                    }
                } catch( error ) {
                    alert( response.status );
                    n = 0;
                }
            }
            lock = 0;
        }
        download();
    } );
</script>
</main>