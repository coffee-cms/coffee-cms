<?php

// Find and switch lang
if ( empty( $cms["config"]["locale"] ) || ! empty( $_GET["locale"] ) ) {

    // Scan all locales
    $cms["locales"] = array( "en_US.UTF-8" );
    foreach( glob( "lang/*.UTF-8",  GLOB_ONLYDIR ) as $locale ) {
        $locale = preg_replace( "/.*\//", "", $locale );
        array_push( $cms["locales"], $locale );
    }
    
    // set locale
    if ( empty( $_GET["locale"] ) ) {
        
        $locale = "en_US.UTF-8";
        $lang   = "en";
        
    } else {
    
        $locale = $_GET["locale"];
        $lang   = substr( $locale, 0, 2 );
    
    }
    
    $cms["config"]["locale"] = $locale;
    $cms["config"]["lang"] = $lang;
}

$cms["modules"]["admin.mod.php"] = array(
    "name"        => __( "module_name" ),
    "description" => __( "module_description" ),
    "version"     => "",
    "files"       => array(
        ".cms/mod/admin.mod.php",
        ".cms/js/admin.js",
        ".cms/css/admin.css",
        ".cms/lang/ru_RU.UTF-8/admin.mod.php",
        ".cms/lang/en_US.UTF-8/admin.mod.php",
        ".cms/lang/uk_UA.UTF-8/admin.mod.php",
    ),
);

// PHP_VERSION_ID available in PHP 5.2.7 and above
if ( ! defined( "PHP_VERSION_ID" ) ) {
    $version = explode( ".", PHP_VERSION );
    define( "PHP_VERSION_ID", ( $version[0] * 10000 + $version[1] * 100 + $version[2] ) );
}

// Return if module disabled
if ( ! empty( $cms["config"]["admin.mod.php"]["disabled"] ) ) {

    return;

} else {

    if ( ! empty( $cms["config"]["locale"] ) ) {
        $translit = "{$cms['cms_dir']}/lang/{$cms['config']['locale']}/translit.php";
        if ( file_exists( $translit ) ) {
            include_once( $translit );
        }
    }

    // on install stage admin_url = base_path
    if ( empty( $cms["config"]["admin.mod.php"]["admin_url"] ) ) {
        $cms["config"]["admin.mod.php"]["admin_url"] = $cms["base_path"];
    }


    if ( empty( $cms["config"]["admin.mod.php"]["api_url"] ) ) {
        $cms["config"]["admin.mod.php"]["api_url"] = "/api" . cms_admin_pass_gen();
        $cms["config_writeable"] = cms_save_config();
    }

    if ( empty( $cms["config"]["admin.mod.php"]["cron_url"] ) ) {
        $cms["config"]["admin.mod.php"]["cron_url"] = "/cron" . cms_admin_pass_gen();
        cms_save_config();
    }


    $cms["urls"]["^{$cms['config']['admin.mod.php']['admin_url']}$"] = "admin";

    $cms["urls"]["^{$cms['config']['admin.mod.php']['api_url']}$"] = "api";

    $cms["urls"]["^{$cms['config']['admin.mod.php']['cron_url']}$"] = "cron";

    // Authorized admin and not authorized admin
    cms_add_function( "admin",         "cms_admin_admin", 9999 );
    cms_add_function( "admin_header",  "cms_admin_admin_header" );
    cms_add_function( "api",           "cms_admin_api" );

}

function cms_admin_admin_header() {
    global $cms;
    echo "<script src='{$cms['base_path']}js/admin.js'></script>";
}


function is_admin() {
    global $cms;
    return isset( $_COOKIE["sess"] ) && isset( $cms["config"]["logged"][ $_COOKIE["sess"] ] );
}


// menu in aside
function cms_admin_menu() {
    global $cms;
    
    if ( ! empty( $cms["config"]["admin_sections"] ) ) {
        $cms["admin_sections"] = $cms["config"]["admin_sections"];
    } else {
        $cms["admin_sections"] = array();
    }

    foreach( $cms["config"] as $mod => $mod_cfg ) {
        // each $cms['config']['...mod.php']['menu']
        if ( substr( $mod, -8, 8 ) == ".mod.php" && empty( $mod_cfg["disabled"] ) && isset( $mod_cfg["menu"] ) ) {
            // each menu item in module
            foreach( $mod_cfg["menu"] as $menu => $menu_cfg ) {
                $section = $menu_cfg["section"];
                $menu_cfg["module"] = $mod;
                $cms["admin_sections"][$section]["items"][$menu] = $menu_cfg;
                // try translate section from module
                if ( ! isset( $cms["admin_sections"][$section]["title"] ) && isset( $cms["lang"][$mod][ $cms["config"]["locale"] ][$section] ) ) {
                    $cms["admin_sections"][$section]["title"] = $cms["lang"][$mod][ $cms["config"]["locale"] ][$section];
                }
            }
        }
    }

    // Sort & Translate
    $default_sort = array(
        "content"    => 10,
        "navigation" => 20,
        "settings"   => 30,
    );
    foreach( $cms["admin_sections"] as $section => $sec_conf ) {
        // sort
        if ( isset( $default_sort[$section] ) ) {
            if ( ! isset( $cms["admin_sections"][$section]["sort"] ) ) {
                $cms["admin_sections"][$section]["sort"] = $default_sort[$section];
            }
        } else {
            $cms["admin_sections"][$section]["sort"] = 100;
        }
        // translate
        if ( ! isset( $cms["admin_sections"][$section]["title"] ) ) {
            $cms["admin_sections"][$section]["title"] = __( $section );
        }
    }

    // Sort sections
    cms_asort( $cms["admin_sections"] );

    // Sort items inside sections
    foreach( $cms["admin_sections"] as $section_name => $section ) {
        cms_asort( $cms["admin_sections"][$section_name]["items"] );
    }
}

function cms_admin_admin() {
    global $cms;
    $cms["template"] = "admin";
    $cms["status"] = "200";
    $cms["stages"]["write"]["disabled"] = true;

    // Not required code if not authorized
    if ( ! is_admin() ) return;
    
    // Save settings
    if ( ! empty( $_POST["change_admin_login"] ) ) {
        $login     = trim( $_POST["admin_login"] );
        $password  = trim( $_POST["admin_password"] );
        $admin_url = strtolower( cms_translit( trim( $_POST["admin_url"] ), false ) );
        if ( substr( $admin_url, 0, 1 ) !== "/" ) {
            $admin_url = "/" . $admin_url;
        }
        $cms["config"]["admin.mod.php"]["admin_login"] = $login;
        $cms["config"]["admin.mod.php"]["admin_password"] = $password;
        $cms["config"]["admin.mod.php"]["admin_url"] = $admin_url;
        cms_save_config();
        header( "Location: {$cms['config']['admin.mod.php']['admin_url']}" );
        return;
    }

    $rows = "";
    foreach ( $cms["config"]["logged"] as $login => $attr ) {
        if ( $login === $_COOKIE["sess"] ) {
            $current = "class=current";
        } else {
            $current = "";
        }
        $rows .= "
<div {$current}>
    <div class=del-sess data-login='{$login}'></div>
    <div>{$attr['date']}</div>
    <div>{$attr['ip']}</div>
    <div>{$attr['user_agent']}</div>
</div>";
    }

    $logouts = "";
    if ( ! empty( $cms["config"]["logouted"] ) ) {
        // Перебираем закрытые сессии в прямом порядки
        // и очередную добавляем в начало списка,
        // чтобы получить обратный порядок отображения.
        foreach ( $cms["config"]["logouted"] as $attr ) {
            $logouts = "
<div>
    <div></div>
    <div>{$attr['date']}</div>
    <div>{$attr['ip']}</div>
    <div>{$attr['user_agent']}</div>
</div>
{$logouts}";
        }
    }
    
    // Display settings
    $tr_login     = __( "login_or_password" );
    $tr_passw     = __( "password" );
    $tr_set       = __( "set_password" );
    $tr_current   = __( "current_logins" );
    $tr_history   = __( "logins_history" );
    $tr_admin_url = __( "admin_url" );
    $form = "
<form method=post>
    <div class=admin-url>{$tr_admin_url}</div>
    <input name=admin_url type=text value='{$cms['config']['admin.mod.php']['admin_url']}' autocomplete=off size=16>
    <div class=login-title>{$tr_login}</div>
    <input name=admin_login type=text value='{$cms['config']['admin.mod.php']['admin_login']}' autocomplete=off size=16>
    <div class=passwd-title>{$tr_passw}</div>
    <div>
        <input name=admin_password type=password value='{$cms['config']['admin.mod.php']['admin_password']}' autocomplete=off size=16>
        <div class=password-eye></div>
    </div>
    <div></div>
    <button value=save name=change_admin_login>{$tr_set}</button>
</form>

<div class=current-sess>
    <div class=table-title>{$tr_current}</div>
    <div class=sess-table>{$rows}</div>
</div>

<div class=history-sess>
    <div class=table-title>{$tr_history}</div>
    <div class=sess-table>{$logouts}</div>
</div>
";
    // Create menu item if not exists
    if ( empty( $cms["config"]["admin.mod.php"]["menu"]["auth"] ) ) {
        $cms["config"]["admin.mod.php"]["menu"]["auth"] = array(
            "title"    => "auth",
            "sort"     => 10,
            "section"  => "settings",
        );
        cms_save_config();
    }
    $cms["admin_pages"]["auth"] = $form;

   
    
    // PHP Info

    ob_start();
    phpinfo();
    preg_match( "/<body>(.+)<\/body>/s", ob_get_clean(), $m );
    $page = $m[1];
    // Create menu item if not exists
    if ( empty( $cms["config"]["admin.mod.php"]["menu"]["phpinfo"] ) ) {
        $cms["config"]["admin.mod.php"]["menu"]["phpinfo"] = array(
            "title"    => "php_info",
            "sort"     => 30,
            "section"  => "settings",
        );
        cms_save_config();
    }
    $cms["admin_pages"]["phpinfo"] = $page;


    // Modules List
    $admin_menu = "
    <div class=header>
        <input id=module-upload type=file name='myfile[]' multiple class=files>
        <label for=module-upload>" . __( "mod_install" ) . "</label>
    </div>
    <div class=modules-grid>";

    // Сортировка модулей
    function modules_sort( $a, $b ) {
        return ( $a["name"] < $b["name"] ) ? -1 : 1;
    }
    uasort( $cms["modules"], "modules_sort" );

    $tr_on  = __( "mod_on" );
    $tr_off = __( "mod_off" );
    $tr_del = __( "mod_del" );
    foreach( $cms["modules"] as $mod => $mod_cfg ) {
        if ( ! empty( $cms["config"][$mod]["disabled"] ) ) {
            $status = "disabled";
            $tr_sw  = $tr_on;
        } else {
            $status = "enabled";
            $tr_sw  = $tr_off;
        }
        // Скрыть версию у модуля обновлений
        if ( $mod === "update.mod.php" ) {
            $mod_cfg['version'] = "";
        }
        $admin_menu .= "
<div class={$status} data-module={$mod}>
    <div class=module-name>{$mod_cfg['name']}</div>
    <div class=module-version>{$mod_cfg['version']}</div>
    <div class=module-description>{$mod_cfg['description']}</div>
    <a class=module-sw-btn>{$tr_sw}</a>
    <a class=module-del-btn>{$tr_del}</a>
</div>";
    }
    $admin_menu .= "</div>";

    // Create menu item if not exists
    if ( empty( $cms["config"]["admin.mod.php"]["menu"]["modules"] ) ) {
        $cms["config"]["admin.mod.php"]["menu"]["modules"] = array(
            "title"    => "modules",
            "sort"     => 50,
            "section"  => "settings",
        );
        cms_save_config();
    }
    $cms["admin_pages"]["modules"] = $admin_menu;


    // Admin

    // Create menu item if not exists
    if ( empty( $cms["config"]["admin.mod.php"]["menu"]["admin_menu"] ) ) {
        $cms["config"]["admin.mod.php"]["menu"]["admin_menu"] = array(
            "title"    => "module_name",
            "sort"     => 40,
            "section"  => "settings",
        );
        cms_save_config();
    }
    
    cms_admin_menu();

    $tr_show          = __( "show" );
    $tr_hide          = __( "hide" );
    $tr_save          = __( "save" );
    $tr_del_section   = __( "delete" );
    $tr_reset         = __( "reset" );

    // Admin Sections
    $admin_menu = "
<div class=main-main>
    <div class=am-grid>";
    foreach( $cms["admin_sections"] as $section_name => $section ) {
        if ( empty( $section["hide"] ) ) {
            $status = "showed";
            $tr_sw  = $tr_hide;
        } else {
            $status = "hidden";
            $tr_sw  = $tr_show;
        }
        $admin_menu .= "
<div>
    
    <div class={$status} data-am-type=section data-am-item='{$section_name}'>
        <input name=title type=text value='{$section['title']}'>
        <input name=sort type=text value='{$section['sort']}'>
        <div class=buttons>
            <a data-am-save>{$tr_save}</a>
            <a data-am-delete>{$tr_del_section}</a>
            <a data-am-sw>{$tr_sw}</a>
        </div>
    </div>

    <div class=items-grid data-am-childs='{$section_name}'>";

        // Admin Items
        if ( ! empty( $cms["admin_sections"][$section_name]["items"] ) )
        foreach( $cms["admin_sections"][$section_name]["items"] as $iname => $item ) {
            if ( empty( $item["hide"] ) ) {
                $status = "showed";
                $tr_sw  = $tr_hide;
            } else {
                $status = "hidden";
                $tr_sw  = $tr_show;
            }

            $title = __( $item["title"], $item["module"] );

            $options = "";
            $tr_section_name = $section_name;
            foreach( $cms["admin_sections"] as $section_name2 => $section2 ) {
                $tr_s_title = __( $section2["title"] );
                if ( $section_name2 === $section_name ) {
                    $tr_section_name = $tr_s_title;
                }
                $options .= "<option value='{$section_name2}'>{$tr_s_title}</option>";
            }

            $admin_menu .= "
<div class={$status} data-am-type=item data-am-module={$item['module']} data-am-item={$iname}>
    <div class=item-name>{$title}</div>
    <input name=sort type=text value='{$item['sort']}'>
    <div class=section-select-grid>
        <div class=field-select data-section='{$section_name}'>{$tr_section_name}</div>
        <div class=field-options>
            {$options}
        </div>
    </div>
    <div class=buttons>
        <a data-am-save>{$tr_save}</a>
        <a data-am-save data-am-reset>{$tr_reset}</a>
        <a data-am-sw>{$tr_sw}</a>
    </div>
</div>";
        }

        $admin_menu .= "
    </div> <!-- class=status -->
</div> <!-- class= -->";
    }


    $admin_menu .= "
    </div> <!-- class=am-grid -->
</div> <!-- class=main-main -->
<div class=main-footer>
    <div class=add-section>" . __( "add_section" ) . "</div>
    <div class=reset-all>" . __( "reset_all" ) . "</div>
</div>";

    $cms["admin_pages"]["admin_menu"] = $admin_menu;

}


function cms_admin_api() {
    global $cms;

    // Login
    if ( ! empty( $_POST["fn"] ) && $_POST["fn"] == "login" ) {
        $cms["status"] = "200";
        // Install process
        $login    = trim( $_POST["login"] );
        $password = trim( $_POST["password"] );
        if ( empty( $cms["config"]["admin.mod.php"]["admin_login"] )
         && empty( $cms["config"]["admin.mod.php"]["admin_password"] )
         && ( ! empty( $login ) || ! empty( $password ) ) ) {
            $cms["config"]["admin.mod.php"]["admin_login"]    = $login;
            $cms["config"]["admin.mod.php"]["admin_password"] = $password;
            $cms["config"]["admin.mod.php"]["admin_salt"]     = cms_admin_pass_gen(8);
            $cms["config"]["admin.mod.php"]["admin_url"]      = $cms["base_path"] . "-admin";
            $_POST["url"] = $cms["config"]["admin.mod.php"]["admin_url"];
            $link = "{$cms['url']['scheme']}://{$cms['url']['host']}{$cms['config']['admin.mod.php']['admin_url']}";
            // Set Locale and Timezone
            $cms["config"]["locale"] = $_POST["locale"];
            $cms["config"]["lang"]   = substr( $cms["config"]["locale"], 0, 2 );
            $translit = "{$cms['cms_dir']}/lang/{$cms['config']['locale']}/translit.php";
            if ( file_exists( $translit ) ) {
                include_once( $translit );
            }
            cms_save_config();
            if ( is_email( $cms["config"]["admin.mod.php"]["admin_login"] ) ) {
                $subject = __( "install_finished" );
                $body  = "<p>" . __( "congrat" ) . "</p>";
                $body .= "<p>" . __( "install_finished" ) . ".</p>";
                $body .= "<p>&nbsp;</p>";
                $body .= "<p>" . __( "login_info" ) . "</p>";
                $body .= "<p>" . __( "login_url" ) . ": <a href='{$link}'>{$link}</a></p>";
                $body .= "<p>" . __( "login" ) . ": {$_POST['login']}</p>";
                $body .= "<p>" . __( "password" ) . ": {$_POST['password']}</p>";
                $body .= "<p>&nbsp;</p>";
                $body .= "<p>" . __( "mail_db_hint" ) . ".</p>";
                cms_email( array(
                    "type" => "text/html",
                    "from_email" => "noreply@" . $cms["url"]["host"],
                    "from_name"  => $cms["url"]["host"],
                    "to_email"   => $_POST["login"],
                    "subject"    => $subject,
                    "email_body" => $body,
                ) );
            }
        }
        // Check login and password
        if ( $_POST["login"]    === $cms["config"]["admin.mod.php"]["admin_login"] && 
             $_POST["password"] === $cms["config"]["admin.mod.php"]["admin_password"] &&
             $_POST["url"]      === $cms["config"]["admin.mod.php"]["admin_url"] ) // disabled $_SERVER['HTTP_REFERER']
        {

            $d        = date( "Y-m-d H:i:s" );
            $sess    = sha1( $cms["config"]["admin.mod.php"]["admin_login"] . $cms["config"]["admin.mod.php"]["admin_salt"] . $d );
            
            // Prepend New Session
            if ( ! isset( $cms["config"]["logged"] ) ) {
                $cms["config"]["logged"] = array();
            }
            $cms["config"]["logged"] = array(
                $sess => array(
                    "ip"         => $_SERVER["REMOTE_ADDR"],
                    "date"       => $d,
                    "user_agent" => $_SERVER["HTTP_USER_AGENT"],
                )
            ) + $cms["config"]["logged"];

            // Set Locale and Timezone
            $cms["config"]["locale"] = $_POST["locale"];
            $cms["config"]["lang"]   = substr( $cms["config"]["locale"], 0, 2 );
            $translit = "{$cms['cms_dir']}/lang/{$cms['config']['locale']}/translit.php";
            if ( file_exists( $translit ) ) {
                include_once( $translit );
            }

            if ( cms_save_config() ) {
                if ( PHP_VERSION_ID < 70300 ) {
                    setcookie( "sess", $sess, time() + 365 * 24 * 60 * 60 );
                } else {
                    setcookie( "sess", $sess, array( "SameSite" => "Lax", "expires" => time() + 365 * 24 * 60 * 60 ) );
                }
            } else {
                echo( json_encode( array(
                    "info_text"  => __( "cant_write_config" ) . " .cms/config.php",
                    "info_class" => "info-error",
                    "info_time"  => 5000,
                ) ) );
                return;
            }

            echo( json_encode( array(
                "reload"  => $cms["config"]["admin.mod.php"]["admin_url"],
            ) ) );
            return;
        } else {
            $cms["config"]["locale"] = $_POST["locale"];
            echo( json_encode( array(
                "info_text"  => __( "access_denied" ),
                "info_class" => "info-error",
                "info_time"  => 5000,
            ) ) );
            return;
        }
    }

    // Cookie, Logout, Admin, Modules
    if ( ! empty( $_POST["fn"] ) && is_admin() ) {
        switch ( $_POST["fn"] ) {

            case "logout":
                $cms["status"] = "200";
                if ( empty( $_POST["sess"] ) ) {
                    if ( PHP_VERSION_ID < 70300 ) {
                        setcookie( "sess", "", 365 * 24 * 60 * 60 );
                    } else {
                        setcookie( "sess", "", array( "SameSite" => "Lax", "expires" => time() + 365 * 24 * 60 * 60 ) );
                    }
                    $login  = $_COOKIE["sess"];
                    $result = "refresh";
                } else {
                    $login = $_POST["sess"];
                    if ( $_POST["sess"] === $_COOKIE["sess"] ) {
                        $result = "refresh";
                    } else {
                        $result = "ok";
                    }
                }

                if ( ! isset($cms["config"]["logouted"]) ) {
                    $cms["config"]["logouted"] = array();
                }
                // Добавляем закрытую сессию в конец массива в историю
                array_push( $cms["config"]["logouted"], $cms["config"]["logged"][$login] );
                // Оставим только последние 10 выходов в истории
                $cms["config"]["logouted"] = array_slice( $cms["config"]["logouted"], -10 );
                unset( $cms["config"]["logged"][$login] );
                cms_save_config();

                echo( json_encode( array(
                    "info_text"  => __( "logout_completed" ),
                    "info_class" => "info-success",
                    "info_time"  => 5000,
                    "result"     => $result,
                ) ) );
                return;
            break;

            case "admin_menu_save":
                switch ( $_POST["type"] ) {
                    case "section":
                        if ( empty( $_POST["title"] ) ) {
                            unset ( $cms["config"]["admin_sections"][ $_POST["item"] ]["title"] );
                        } else {
                            $cms["config"]["admin_sections"][ $_POST["item"] ]["title"] = $_POST["title"];
                        }
                        $n = (int) $_POST["sort"];
                        if ( $n ) {
                            $cms["config"]["admin_sections"][ $_POST["item"] ]["sort"]  = $n;
                        } else {
                            unset( $cms["config"]["admin_sections"][ $_POST["item"] ]["sort"] );
                        }
                    break;

                    case "item":
                        if ( $_POST["reset"] === "true" ) {
                            unset( $cms["config"][ $_POST["module"] ]["menu"][ $_POST["item"] ] );
                        } else {
                            $cms["config"][ $_POST["module"] ]["menu"][ $_POST["item"] ]["section"] = $_POST["section"];
                            $cms["config"][ $_POST["module"] ]["menu"][ $_POST["item"] ]["sort"]    = (int) $_POST["sort"];
                        }
                    break;
                }
                cms_save_config();
                echo( json_encode( array(
                    "ok" => "true",
                ) ) );
                return;
            break;

            case "admin_menu_hide":
                $hide = $_POST["hide"] === "true";
                switch ( $_POST["type"] ) {
                    case "section":
                        $cms["config"]["admin_sections"][ $_POST["item"] ]["hide"] = $hide;
                        break;
                    case "item":
                        $cms["config"][ $_POST["module"] ]["menu"][ $_POST["item"] ]["hide"] = $hide;
                        break;
                }
                cms_save_config();
                echo( json_encode( array(
                    "ok" => "true",
                ) ) );
                return;
            break;

            case "admin_menu_add_section":
                $n = 1;
                while ( isset( $cms["config"]["admin_sections"]["Section".$n] ) ) {
                    $n++;
                }
                $cms["config"]["admin_sections"]["Section".$n] = array(
                    "title" => __( "add_section" ),
                    "sort"  => ( count( $cms["config"]["admin_sections"] ) + 1 ) * 10,
                    "hide"  => false,
                );
                cms_save_config();
                echo( json_encode( array(
                    "info_text"  => __( "section_added" ),
                    "info_class" => "info-success",
                    "info_time"  => 3000,
                ) ) );
                return;
            break;

            case "admin_menu_del":
                $cms["status"] = "200";
                unset( $cms["config"]["admin_sections"][ $_POST["item"] ]);
                cms_save_config();
                echo( json_encode( array(
                    "info_text"  => __( "deleted" ),
                    "info_class" => "info-success",
                    "info_time"  => 1000,
                ) ) );
                return;
            break;

            case "module_disable":
                if ( $_POST["module"] == "admin.mod.php" || $_POST["module"] == "template.mod.php" ) {
                    echo( json_encode( array(
                        "info_text"  => __( "not_disabled" ),
                        "info_class" => "info-error",
                        "info_time"  => 5000,
                    ) ) );
                    return;
                }
                if ( $_POST["disable"] === "true" ) {
                    $cms["config"][$_POST["module"]]["disabled"] = true;
                } else {
                    unset( $cms["config"][$_POST["module"]]["disabled"] );
                }
                cms_save_config();
                echo( json_encode( array(
                    "ok"  => "true",
                ) ) );
                return;
            break;

            case "module_del":
                $text = __( "delete_this_files" );
                foreach( $cms["modules"][$_POST["module"]]["files"] as $file ) {
                    $text .= "<br>{$file}";
                }
                echo( json_encode( array(
                    "info_text"  => $text,
                    "info_class" => "info-error",
                    "info_time"  => 60000,
                ) ) );
                return;
            break;

            case "install_module":
                $success = true;
                $text = "dev.coffee-cms.ru";
                if ( $cms["url"]["host"] !== $text ) {
                    foreach ( $_FILES["myfile"]["name"] as $n => $name ) {
                        if ( $_FILES["myfile"]["error"][$n] ) {
                            $success = false;
                            $text = str_replace( "xxx", $name, __( "upload_error_xxx" ) );
                            break;
                        } else {
                            // Unpack Module
                            // Object Oriented Style for future compability with PHP 8
                            $zip = new ZipArchive;
                            if ( $zip->open( $_FILES["myfile"]["tmp_name"][$n] ) === TRUE ) {
                                $zip->extractTo( $cms["site_dir"] );
                                $zip->close();
                                // Load module
                                $name = preg_replace( "/\..+/", "", $name );
                                $fname = "{$cms['cms_dir']}/mod/{$name}.mod.php";
                                if ( file_exists( $fname ) ) {
                                    include_once( $fname );
                                    cms_do_stage( "create_tables" );
                                }

                                // Выполнить запросы в БД, находящиеся в файле $name.update.sql
                                $update_sql = "{$cms['cms_dir']}/mod/{$name}.update.sql";
                                $q = "";
                                if ( file_exists( $update_sql ) ) {
                                    $q = file_get_contents( $update_sql );
                                }
                                if ( $q ) {
                                    cms_base_connect();
                                    if ( $cms["base"] ) {
                                        $res = mysqli_multi_query( $cms["base"], $q );
                                    }
                                }

                                $text = __( "install_success" );
                            } else {
                                $success = false;
                                $text = str_replace( "xxx", $name, __( "cant_unzip_xxx" ) );
                                break;
                            }
                        }
                    }
                }

                if ( $success ) {
                    if ( function_exists( "cms_clear_cache" ) ) {
                        cms_clear_cache();
                    }
                    echo( json_encode( array(
                        "info_text"  => $text,
                        "info_class" => "info-success",
                        "info_time"  => 5000,
                    ) ) );
                    return;
                } else {
                    echo( json_encode( array(
                        "info_text"  => $text,
                        "info_class" => "info-error",
                        "info_time"  => 10000,
                    ) ) );
                    return;
                }
            break;

            case "no_translation":
                if ( ! empty( $cms["config"]["debug"] ) ) {
                    $new_debug = array(
                        "translate to {$cms["config"]["locale"]}" => array(
                            "{$_POST["module"]}:js" => $_POST["str"],
                        ),
                    );
                    $debug_file = $cms["cms_dir"] . "/debug.log.php";
                    if ( $f = fopen( $debug_file, "r" ) ) {
                        flock( $f, LOCK_SH );
                        include( $debug_file );
                        $cms["debug"] = array_replace_recursive( $cms["debug"], $new_debug );
                        flock( $f, LOCK_UN );
                        file_put_contents( $debug_file, '<?php $cms["debug"] = ' . var_export( $cms["debug"], true) . ";\n", LOCK_EX );
                    } else {
                        file_put_contents( $debug_file, '<?php $cms["debug"] = ' . var_export( $cms["debug"], true) . ";\n", LOCK_EX );
                    }
                }
                echo( '{ "result": "ok" }' );
                return;
            break;

            case "reset_admin_menu_items":
                reset_admin_menu_items();
                echo( json_encode( array(
                    "info_text"  => __( "reset_all_ok" ),
                    "info_class" => "info-success",
                    "info_time"  => 3000,
                ) ) );
                return;
            break;
        }
        
    }
}


function cms_admin_pass_gen( $count = 8 ) {
    $chars = "0123456789AaBbCcDdEeFfGgHhiJjKkLMmNnOoPpQqRrSsTtUuVvWwXxYyZz";
    $strlen = strlen( $chars ) - 1;
    $password = "";
    while ( $count-- ) {
        $password .= $chars[ rand( 0, $strlen ) ];
    }
    return $password;
}


function reset_admin_menu_items() {
    global $cms;
    unset( $cms["config"]["admin_sections"] );
    foreach( $cms["modules"] as $mod => $val ) {
        if ( isset( $cms["config"][$mod]["menu"] ) ) {
            unset( $cms["config"][$mod]["menu"] );
        }
    }
    cms_save_config();
}//unset( $cms["config"][ $_POST["module"] ]["menu"][ $_POST["item"] ] );