<?php

$cms["modules"]["sitemap.mod.php"] = array(
    "name"        => __( "Карта сайта" ),
    "description" => __( "Модуль настроек карты сайта sitemap.xml" ),
    "version"     => "",
    "locale"      => "ru_RU.UTF-8",
    "files" => array(
        ".cms/mod/sitemap.mod.php",
        ".cms/css/sitemap.css",
        ".cms/js/sitemap.js",
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
            $lastmod = "<lastmod>{$attr["lastmod"]}</lastmod>";
        }
        if ( empty( $attr["changefreq"] ) ) {
            $changefreq = "";
        } else {
            $changefreq = "<changefreq>{$attr["changefreq"]}</changefreq>";
        }
        if ( empty( $attr["priority"] ) ) {
            $priority = "";
        } else {
            $priority = "<priority>{$attr["priority"]}</priority>";
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
        exit;
    }

    // Create menu item if not exists
    if ( empty( $cms["config"]["sitemap.mod.php"]["menu"]["sitemap"] ) ) {
        $cms["config"]["sitemap.mod.php"]["menu"]["sitemap"] = array(
            "title"    => "Карта сайта",
            "sort"     => 60,
            "class"    => "",
            "section"  => "Настройки",
        );
        cms_save_config();
    }

    $tr_save           = __( "Сохранить" );

    $ch["static"] = "";
    $ch["dynamic"] = "";
    $ch[ $cms["config"]["sitemap.mod.php"]["gen"] ] = "checked";

    @$page = "
<form method=post>
    <div class=xml-wrapper-hidden>
        <div>" . __( "Скрыть ссылки" ) . "</div>
        <textarea name=exclude rows=12>{$cms['config']['sitemap.mod.php']['exclude']}</textarea>
        <button name=save_settings_sitemap value=save>{$tr_save}</button>
    </div>
    <div class=xml-wrapper-link>
        <div>" . __( "Добавить ссылки" ) . "</div>
        <textarea name=include rows=12>{$cms['config']['sitemap.mod.php']['include']}</textarea>
        <button name=save_settings_sitemap value=save>{$tr_save}</button>
    </div>
    <div class=xml-static-dynamic>
        <div>" . __( "Правила обработки файла sitemap.xml" ) . "</div>
        <label><input name=gen type=radio value=dynamic {$ch['dynamic']}> " . __( "Динамическая генерация карты сайта" ) . "</label>
        <label><input name=gen type=radio value=static {$ch['static']}> " . __( "Статическая генерация карты сайта (рекомендуется)" ) . "</label>
        <div> </div>
        <div>" . __( "Протокол и Домен для статической карты сайта (пусто = авто)*:" ) . "</div>
        <input type=text name=domain value='{$cms['config']['sitemap.mod.php']['domain']}' placeholder='http://example.com'>
        <button name=save_settings_sitemap value=save>{$tr_save}</button>
    </div>
    <div class=xml-static-cron>
        <div>" . __( "Ограничения частоты пересоздания" ) . "</div>
        <p>" . __( "Статическая генерация по CRON с интервалом" ) . " <input type=text name=update_interval value={$cms['config']['sitemap.mod.php']['update_interval']}> " . __( "минут" ) . "</p>
        <p>" . __( "Данная функция работает при включенном CRON. Информацию по использованию CRON можете получить у вашего хостинг провайдера." ) . "</p>
        <button name=save_settings_sitemap value=save>{$tr_save}</button>
    </div>
</form>

<div class=sitemap-manual>
    <div>" . __( "Справка по опциям sitemap.xml" ) . "</div>
    <p>" . __( "Если Вам не нужно делать отложенные на будущее авто-публикации (без Вашего участия), оставляйте статическую генерацию. При сохранении страницы или свойств страницы происходит пересоздание карты сайта." ) . "</p>
    <p>" . __( "Если имеются отложенные на будущее публикации, то можно выставить динамическую генерацию карты сайта или статическую с использованием планировщика CRON." ) . " " . __( "Планировщик CRON должен опрашивать сайт по адресу" ) . " {$cms['url']['scheme']}://{$cms['url']['host']}{$cms['config']['admin.mod.php']['cron_url']} " . __( "и тем самым пересоздавать карту сайта с установленным вами временным интервалом." ) . "</p>
    <p>" . __( "Рекомендуем использовать статическую генерацию для снижения нагрузки на сервер." ) . "</p>
    <p>" . __( "* Опция нужна если сайт работает на двух доменах." ) . "</p>
</div>
    ";

    $cms["admin_pages"]["sitemap"] = $page;
}
