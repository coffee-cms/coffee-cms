<?php

$cms["modules"]["sitemap.mod.php"] = array(
    "name" => __( "Sitemap", "sitemap.mod.php" ),
    "description" => __( "Module for configure sitemap.xml", "sitemap.mod.php" ),
    "version" => "22.04",
    "files" => array(
        ".cms/mod/sitemap.mod.php",
        ".cms/css/sitemap.css",
        ".cms/js/sitemap.js",
        ".cms/lang/ru_RU.UTF-8/sitemap.mod.php",
        ".cms/lang/uk_UA.UTF-8/sitemap.mod.php",
    ),
    "sort" => 20,
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
    if ( empty( $cms["config"]["sitemap.mod.php"]["excluded_types"] ) ) {
        $cms["config"]["sitemap.mod.php"]["excluded_types"] = array( "redirect", "admin" );
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

    $exclude = explode( "\n", $cms["config"]["sitemap.mod.php"]["exclude"] );

    $excluded_types = "'" . implode( "','", $cms["config"]["sitemap.mod.php"]["excluded_types"] ) . "'";
    $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
    $q = "SELECT title, url, created, modified FROM pages WHERE `created`<='" . date( "Y-m-d H:i:s" ) . "' AND `tpl` NOT IN ({$excluded_types}) ORDER BY created DESC";
    if ( $res = mysqli_query( $cms["base"], $q ) ) {
        $links = array();
        while ( $page = mysqli_fetch_assoc( $res ) ) {
            if ( ! empty( $page["url"] ) ) {
                $link = $domain.$page["url"];
                $modified = "";
                $created  = strtotime( $page["created"] );
                if ( $created > 0 and $modified = max( $created, (int)$page["modified"] ) ) {
                    $modified = "<lastmod>" . date( "c", $modified ) . "</lastmod>";
                }
                if ( ! in_array( $page["url"], $exclude ) && ! in_array( $link, $links ) ) {
                    $links[] = $link;
                    $content .= "<url><loc>{$link}</loc>{$modified}</url>\n";
                }
            }
        }
        $link = $domain . "/";
        if ( ! in_array( $link, $links ) ) $links[] = $link;

    }
    foreach( explode( "\n", $cms["config"]["sitemap.mod.php"]["include"] ) as $link ) {
        $link = trim( $link );
        if ( ! empty( $link ) ) {
            $content .= "<url><loc>{$link}</loc></url>";
        }
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
        exit;
    }

    // Create menu item if not exists
    if ( empty( $cms["config"]["sitemap.mod.php"]["menu"]["sitemap"] ) ) {
        $cms["config"]["sitemap.mod.php"]["menu"]["sitemap"] = array(
            "title"    => "Sitemap",
            "sort"     => 60,
            "class"    => "",
            "section"  => "Settings",
        );
        cms_save_config();
    }

    $tr_excluded_links = __( "Excluded links", "sitemap.mod.php" );
    $tr_included_links = __( "Included links", "sitemap.mod.php" );
    $tr_save           = __( "Save", "sitemap.mod.php" );
    $tr_rules          = __( "Rules for processing sitemap.xml file", "sitemap.mod.php" );
    $tr_dynamic        = __( "Dynamic generation", "sitemap.mod.php" );
    $tr_static         = __( "Static generation (recommended)", "sitemap.mod.php" );
    $tr_domain         = __( "Protocol and Domain for static map (leave empty for auto)*:", "sitemap.mod.php" );
    $tr_freq           = __( "Frequency Recreate", "sitemap.mod.php" );
    $tr_cron_1         = __( "Static CRON generation at", "sitemap.mod.php" );
    $tr_cron_2         = __( "-minute intervals", "sitemap.mod.php" );
    $tr_help           = __( "This function works when CRON is on. Information on using CRON can be obtained from your hosting provider.", "sitemap.mod.php" );
    $tr_manual_title   = __( "Help on sitemap.xml options", "sitemap.mod.php" );
    $tr_manual_1       = __( "If you don't need to do pending auto posts - you need to keep static generation. When you save the page or page properties, the site map is recreated.", "sitemap.mod.php" );
    $tr_manual_2       = __( "If you have pending publications for the future, you can set the dynamic generation of the sitemap or static using the CRON scheduler.", "sitemap.mod.php" );
    $tr_manual_3       = __( "The CRON scheduler should poll the site at", "sitemap.mod.php" );
    $tr_manual_4       = __( "and thereby recreate the site map with the time interval you set.", "sitemap.mod.php" );
    $tr_manual_5       = __( "We recommend using static generation to reduce server load.", "sitemap.mod.php" );
    $tr_manual_6       = __( "* This option is needed if the site operates on two domains.", "sitemap.mod.php" );

    $ch["static"] = "";
    $ch["dynamic"] = "";
    $ch[ $cms["config"]["sitemap.mod.php"]["gen"] ] = "checked";

    @$page = "
<form method=post>
    <div class=xml-wrapper-hidden>
        <div>{$tr_excluded_links}</div>
        <textarea name=exclude rows=12>{$cms['config']['sitemap.mod.php']['exclude']}</textarea>
        <input type=submit name=save_settings_sitemap value='{$tr_save}'>
    </div>
    <div class=xml-wrapper-link>
        <div>{$tr_included_links}</div>
        <textarea name=include rows=12>{$cms['config']['sitemap.mod.php']['include']}</textarea>
        <input type=submit name=save_settings_sitemap value='{$tr_save}'>
    </div>
    <div class=xml-static-dynamic>
        <div>{$tr_rules}</div>
        <label><input name=gen type=radio value=dynamic {$ch['dynamic']}> {$tr_dynamic}</label>
        <label><input name=gen type=radio value=static {$ch['static']}> {$tr_static}</label>
        <div> </div>
        <div>{$tr_domain}</div>
        <input type=text name=domain value='{$cms['config']['sitemap.mod.php']['domain']}' placeholder='http://example.com'>
        <input type=submit name=save_settings_sitemap value='{$tr_save}'>
    </div>
    <div class=xml-static-cron>
        <div>{$tr_freq}</div>
        <p>{$tr_cron_1} <input type=text name=update_interval value={$cms['config']['sitemap.mod.php']['update_interval']}> {$tr_cron_2}</p>
        <p>{$tr_help}</p>
        <input type=submit name=save_settings_sitemap value='{$tr_save}'>
    </div>
</form>

<div class=sitemap-manual>
    <div>{$tr_manual_title}</div>
    <p>{$tr_manual_1}</p>
    <p>{$tr_manual_2} {$tr_manual_3} {$cms['url']['scheme']}://{$cms['url']['host']}{$cms['config']['admin.mod.php']['cron_url']} {$tr_manual_4}</p>
    <p>{$tr_manual_5}</p>
    <p>{$tr_manual_6}</p>
</div>
    ";

    $cms["admin_pages"]["sitemap"] = $page;
}
