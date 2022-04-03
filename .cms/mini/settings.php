<?php

$name = $cms["config"]["template.mod.php"]["template"];

// Translate
$cms["lang"][$name]["ru_RU.UTF-8"] = array(
    "Header"   => "Верхнее",
    "Footer"   => "Нижнее",
    "Side"     => "Боковое",
);

// Areas
$cms["menu_areas"]["header"]   = array( "title" => __( "Header",   $name ) );
$cms["menu_areas"]["footer"]   = array( "title" => __( "Footer",   $name ) );
$cms["menu_areas"]["side"]     = array( "title" => __( "Side",     $name ) );


// Editable theme files
$cms["templates"][$name]["files"] = array(
    ".cms/{$name}/style.css",
    ".cms/{$name}/html.php",
    ".cms/{$name}/html-page.php",
    ".cms/{$name}/404.en_US.UTF-8.php",
    ".cms/{$name}/404.ru_RU.UTF-8.php",
    ".cms/{$name}/404.uk_UA.UTF-8.php",
);

$cms["lang"][$name]["ru_RU.UTF-8"] = array(
    "Title" => "Заголовок",
    "Paragraph" => "Параграф",
);

$cms["lang"][$name]["uk_UA.UTF-8"] = array(
    "Title" => "Заголовок",
    "Paragraph" => "Параграф",
);

$title = __( "Title",   $name );
$text  = __( "Paragraph",   $name );

$cms["templates"][$name]["page_templates"]["page"] = <<<EOP
<h1>{$title}</h1>

<p>{$text}</p>
EOP;

cms_add_function( "clear_cache", "template_mini_clear_cache" );

if ( ! function_exists( "template_mini_clear_cache" ) ) {
    function template_mini_clear_cache() {
        global $cms;

        $q = "SELECT * FROM pages WHERE tpl = 'blog'";
        if ( $res1 = mysqli_query( $cms["base"], $q ) ) {
            
            while ( $blog = mysqli_fetch_assoc( $res1 ) ) {

                $q = "SELECT url FROM pages WHERE tpl != 'blog'";
                if ( $res2 = mysqli_query( $cms["base"], $q ) ) {

                    $pager = 0;
                    $pcount = 0;

                    while ( $page = mysqli_fetch_assoc( $res2 ) ) {

                        if ( $pcount < 12 ) {

                            $pcount++;
        
                        } elseif ( $pager == 0 ) {
        
                            $pager++;
                            $pcount = 1;
        
                        } else {
        
                            $file = "{$cms['site_dir']}{$blog['url']}{$pager}";
                            if ( file_exists( $file ) ) {
                                unlink( $file );
                            }
                            $pager++;
                            $pcount = 1;
        
                        }

                    }

                    if ( $pager == 0 ) {

                    } else {
            
                        $file = "{$cms['site_dir']}{$blog['url']}{$pager}";
                        if ( file_exists( $file ) ) {
                            unlink( $file );
                        }
            
                    }
                }

            }

        }

    }
}