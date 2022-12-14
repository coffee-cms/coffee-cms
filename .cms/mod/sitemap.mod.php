<?php

$cms["modules"]["sitemap.mod.php"] = array(
    "name"        => __( "module_name" ),
    "description" => __( "module_description" ),
    "version"     => "",
    "files" => array(
        ".cms/mod/sitemap.mod.php",
        ".cms/css/sitemap.css",
        ".cms/js/sitemap.js",
        ".cms/lang/ru_RU.UTF-8/sitemap.mod.php",
        ".cms/lang/en_US.UTF-8/sitemap.mod.php",
        ".cms/lang/uk_UA.UTF-8/sitemap.mod.php",
    ),
);

// Return if module disabled
if ( ! empty( $cms["config"]["sitemap.mod.php"]["disabled"] ) ) {

    return;

} else {

    // Default Settings

    if ( empty( $cms["config"]["sitemap.mod.php"]["update_interval"] ) ) {
        $cms["config"]["sitemap.mod.php"]["update_interval"] = 60;
    }

    if ( empty( $cms["config"]["sitemap.mod.php"]["exclude"] ) ) {
        $cms["config"]["sitemap.mod.php"]["exclude"] = "";
    }

    if ( empty( $cms["config"]["sitemap.mod.php"]["include"] ) ) {
        $cms["config"]["sitemap.mod.php"]["include"] = "";
    }

    if ( empty( $cms["config"]["sitemap.mod.php"]["gen"] ) ) {
        $cms["config"]["sitemap.mod.php"]["gen"] = "static";
    }

    if ( empty( $cms["config"]["sitemap.mod.php"]["lastrun"] ) ) {
        $cms["config"]["sitemap.mod.php"]["lastrun"] = "";
    }

    if ( is_admin() ) {
        cms_add_function( "admin",         "cms_sitemap_admin" );
        cms_add_function( "admin_header",  "cms_sitemap_admin_header" );
    }

    if ( $cms["config"]["sitemap.mod.php"]["gen"] === "static" ) {
        cms_add_function( "cron", "cms_sitemap_cron" );
    }
        
    $cms["urls"]["^{$cms['base_path']}sitemap.xml$"] = "sitemap.xml";
    cms_add_function( "sitemap.xml", "cms_sitemap_xml" );

}

function cms_sitemap_admin_header() {
    global $cms;
    echo "<link rel=stylesheet href='{$cms['base_path']}css/sitemap.css'>";
    echo "<script src='{$cms['base_path']}js/sitemap.js'></script>";
}

function cms_sitemap_cron() {
    global $cms;
    if ( $cms["config"]["sitemap.mod.php"]["lastrun"] < date( "Y-m-d H:i:s", time() - $cms["config"]["sitemap.mod.php"]["update_interval"] * 60) ) {
        unlink( "{$cms['site_dir']}/sitemap.xml" );
        $cms["config"]["sitemap.mod.php"]["lastrun"] = date( "Y-m-d H:i:s");
        cms_save_config();
    }
}

function cms_sitemap_xml() {
    global $cms;
    header( "Content-Type: text/xml" );
    $sitemap = cms_sitemap_generate();
    echo $sitemap;
    if ( $cms["config"]["sitemap.mod.php"]["gen"] === "static" ) {
        file_put_contents( "{$cms['site_dir']}/sitemap.xml", $sitemap );
    }
}

function cms_sitemap_update() {
    global $cms;
    // Return if module disabled
    if ( ! empty( $cms["config"]["sitemap.mod.php"]["disabled"] ) ) {
        return;
    }

    $file = "{$cms['site_dir']}/sitemap.xml";
    if ( file_exists( $file ) ) unlink( $file );
}

function cms_sitemap_generate() {
    global $cms;
    if ( empty( $cms["base"] ) ) cms_base_connect();
    if ( empty( $cms["base"] ) ) return;
    if ( mysqli_connect_error() ) return;

    if ( $cms["config"]["sitemap.mod.php"]["gen"] === "static" && ! empty( $cms["config"]["sitemap.mod.php"]["domain"] ) ) {
        $domain = $cms["config"]["sitemap.mod.php"]["domain"];
    } else {
        $domain = "{$cms['url']['scheme']}://{$cms['url']['host']}";
    }

    // Формат массива
    // $cms["sitemap"]["https://site.com/link"] = array(
    //      "lastmod" => "2022-08-27T21:25:17+03:00" || "",
    //      "changefreq" => "weekly",
    //      "priority"   => "0.8"
    // );
    $cms["sitemap"] = array();

    $exclude = explode( "\n", $cms["config"]["sitemap.mod.php"]["exclude"] );

    $date = date( "Y-m-d H:i:s" );
    $q = "SELECT title, url, created, modified FROM pages WHERE `created`<='{$date}' ORDER BY created DESC";
    if ( $res = mysqli_query( $cms["base"], $q ) ) {
        while ( $page = mysqli_fetch_assoc( $res ) ) {
            if ( ! empty( $page["url"] ) and ! in_array( $page["url"], $exclude ) ) {
                if ( $modified = max( strtotime( $page["created"] ), (int) $page["modified"] ) ) {
                    $modified = date( "c", $modified );
                } else {
                    $modified = "";
                }
                $link = $domain.$page["url"];
                if ( ! isset( $cms["sitemap"][$link] ) ) {
                    $cms["sitemap"][$link] = array( "lastmod" => $modified );
                }
            }
        }
    }

    // Обязательные для включения ссылки
    foreach( explode( "\n", $cms["config"]["sitemap.mod.php"]["include"] ) as $link ) {
        $link = trim( $link );
        if ( ! empty( $link ) ) {
            $cms["sitemap"][$link] = array( "lastmod" => "" );
        }
    }

    cms_do_stage( "sitemap" );

    // Генерация файла
    $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
    foreach( $cms["sitemap"] as $url => $attr ) {
        if ( empty( $attr["lastmod"] ) ) {
            $lastmod = "";
        } else {
            $lastmod = "<lastmod>{$attr['lastmod']}</lastmod>";
        }
        if ( empty( $attr["changefreq"] ) ) {
            $changefreq = "";
        } else {
            $changefreq = "<changefreq>{$attr['changefreq']}</changefreq>";
        }
        if ( empty( $attr["priority"] ) ) {
            $priority = "";
        } else {
            $priority = "<priority>{$attr['priority']}</priority>";
        }
        $content .= "<url><loc>{$url}</loc>{$lastmod}{$changefreq}{$priority}</url>\n";
    }
    $content .= "</urlset>";
    return $content;
}


function cms_sitemap_admin() {
    global $cms;

    // Save settings
    if ( ! empty( $_POST["save_settings_sitemap"] ) ) {
        $list = preg_split( "/\r\n|\n\r|\r|\n/", $_POST["exclude"] );
        $site = "{$cms['url']['scheme']}://{$cms['url']['host']}";
        foreach ( $list as $key => $value ) {
            // remove domain
            $list[$key] = trim( str_replace( $site, "", $value ) );
            if ( empty( $list[$key] ) ) {
                unset( $list[$key] );
            } else {
                if ( substr( $list[$key], 0, 1 ) !== "/" ) $list[$key] = "/" . $list[$key];
            }
        }
        $list = implode( "\n", $list );
        $cms["config"]["sitemap.mod.php"]["gen"]             = $_POST["gen"];
        $cms["config"]["sitemap.mod.php"]["exclude"]         = $list;
        $cms["config"]["sitemap.mod.php"]["include"]         = $_POST["include"];
        $cms["config"]["sitemap.mod.php"]["domain"]          = rtrim( $_POST["domain"], "/" );
        $cms["config"]["sitemap.mod.php"]["update_interval"] =  trim( $_POST["update_interval"] );
        cms_save_config();
        if ( $cms["config"]["sitemap.mod.php"]["gen"] === "dynamic" ) {
            unlink( "{$cms['site_dir']}/sitemap.xml" );
        }
        cms_sitemap_update();
        header( "Location: {$cms['config']['admin.mod.php']['admin_url']}" );
        return;
    }

    // Create menu item if not exists
    if ( empty( $cms["config"]["sitemap.mod.php"]["menu"]["sitemap"] ) ) {
        $cms["config"]["sitemap.mod.php"]["menu"]["sitemap"] = array(
            "title"    => "module_name",
            "sort"     => 60,
            "section"  => "settings",
        );
        cms_save_config();
    }

    $ch["static"] = "";
    $ch["dynamic"] = "";
    $ch[ $cms["config"]["sitemap.mod.php"]["gen"] ] = "checked";

    $sitemap = "{$cms['url']['scheme']}://{$cms['url']['host']}{$cms['base_path']}sitemap.xml";

    @$page = "
<div class=sitemap>" . __( "view_sitemap" ) . " <a href='{$sitemap}' target=_blank>{$sitemap}</a></div>

<form method=post>
    <div class=xml-wrapper-hidden>
        <div>" . __( "excluded_links" ) . "</div>
        <textarea name=exclude rows=12 autocomplete=off>{$cms['config']['sitemap.mod.php']['exclude']}</textarea>
        <div class=save_2_col>
            <button name=save_settings_sitemap value=save>" . __( "save" ) . "</button>
                <div class=select-dropdown>
                    <div class=field-search>
                        <input class=search-field autocomplete=off placeholder='" . __( "placeholder" ) . "'>
                    </div>
                    <ul class=list-search>
                        
                    </ul>
                </div>
        </div>
    </div>
    <div class=xml-wrapper-link>
        <div>" . __( "included_links" ) . "</div>
        <textarea name=include rows=12 autocomplete=off>{$cms['config']['sitemap.mod.php']['include']}</textarea>
        <button name=save_settings_sitemap value=save>" . __( "save" ) . "</button>
    </div>
    <div class=xml-static-dynamic>
        <div>" . __( "sitemap_gen" ) . "</div>
        <label><input name=gen type=radio value=dynamic {$ch['dynamic']}> " . __( "dynamic" ) . "</label>
        <label><input name=gen type=radio value=static {$ch['static']}> " . __( "static" ) . "</label>
        <div> </div>
        <div>" . __( "domain" ) . "</div>
        <input type=text name=domain value='{$cms['config']['sitemap.mod.php']['domain']}' placeholder='http://example.com'>
        <button name=save_settings_sitemap value=save>" . __( "save" ) . "</button>
    </div>
    <div class=xml-static-cron>
        <div>" . __( "update_freq" ) . "</div>
        <p>" . __( "static_freq" ) . " <input type=text name=update_interval value={$cms['config']['sitemap.mod.php']['update_interval']}> " . __( "minutes" ) . "</p>
        <p>" . __( "freq_help" ) . "</p>
        <button name=save_settings_sitemap value=save>" . __( "save" ) . "</button>
    </div>
</form>

<div class=sitemap-manual>
    <div>" . __( "help" ) . "</div>
    <p>" . __( "help_p1" ) . "</p>
    <p>" . __( "help_p2" ) . " " . __( "help_p3" ) . " {$cms['url']['scheme']}://{$cms['url']['host']}{$cms['config']['admin.mod.php']['cron_url']} " . __( "help_p4" ) . "</p>
    <p>" . __( "help_p5" ) . "</p>
    <p>" . __( "help_p6" ) . "</p>
</div>
    ";

    $cms["admin_pages"]["sitemap"] = $page;
}
