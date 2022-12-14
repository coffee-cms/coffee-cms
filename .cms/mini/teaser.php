<?php

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

echo <<<TEASER
<div class=teaser>
<h2><a href="{$page["url"]}">{$page["title"]}</a></h2>
{$preview}
</div>
TEASER;