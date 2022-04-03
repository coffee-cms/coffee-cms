<?php

$cms["modules"]["update.mod.php"] = array(
    "name"        => __( "Updates", "update.mod.php" ),
    "description" => __( "Update module", "update.mod.php" ),
    "version"     => "22.04",
    "files"       => array(
        ".cms/mod/update.mod.php",
        ".cms/css/update.css",
        ".cms/js/update.js",
        ".cms/lang/ru_RU.UTF-8/update.mod.php",
        ".cms/lang/uk_UA.UTF-8/update.mod.php",
    ),
    "sort"        => 9999,
    "compat"      => "22",
    "site"        => "https://coffee-cms.com/",
);

// Return if module disabled
if ( ! empty( $cms["config"]["update.mod.php"]["disabled"] ) ) {

    return;

} else {

    if ( is_admin() ) {
        cms_add_function( "admin", "cms_update_admin" );
        cms_add_function( "admin_header", "cms_update_admin_header" );
        cms_add_function( "api", "cms_update_api" );
    }

}

function cms_update_admin_header() {
    global $cms;
    echo "<link rel=stylesheet href='{$cms['base_path']}css/update.css'>";
    echo "<script src='{$cms['base_path']}js/update.js'></script>";
}

function cms_update_admin() {
    global $cms;

    $cms_files_backup   = __( "CMS files backup", "update.mod.php" );
    $create_filelist    = __( "Create Filelist", "update.mod.php" );
    $create_zip         = __( "Create Zip", "update.mod.php" );
    $create_backup      = __( "Create", "update.mod.php" );
    $remove_backup      = __( "Remove", "update.mod.php" );

    $updates            = __( "Updates", "update.mod.php" );
    $update             = __( "Update", "update.mod.php" );
    $show_dev           = __( "Extra", "update.mod.php" );
    $dev_update         = __( "Dev Update", "update.mod.php" );
    $check_update       = __( "Check", "update.mod.php" );
    $check_dev_update   = __( "Check Dev", "update.mod.php" );

    $developers_buttons = __( "Developers Buttons", "update.mod.php" );
    
    $backup_link = ""; // prevent warning
    $del_hide    = " class=hidden";
    $tr_recovery_url = __( "Recovery URL:", "update.mod.php" );
    foreach( glob( "{$cms['site_dir']}/revert_to_*.php" ) as $file ) {
        $file = preg_replace( "/.*\//", "", $file );
        $backup_link = "<p>{$tr_recovery_url} {$cms["url"]["scheme"]}://{$cms["url"]["host"]}{$cms['base_path']}{$file}</p>";
        $del_hide    = "";
    }

    if ( empty( $cms["config"]["update.mod.php"]["update"]["last_version"] ) ) {
        $last_version = __( "Unknown", "update.mod.php" );
    } else {
        $last_version = $cms["config"]["update.mod.php"]["update"]["last_version"];
        if ( $cms["modules"]["update.mod.php"]["compat"] !== $cms["config"]["update.mod.php"]["update"]["compat"] ) {
            $last_version .= ". " . __( "Incompatible version. Please visit", "update.mod.php" ) . " <a href='{$cms['modules']['update.mod.php']['site']}' target=_blank>{$cms['modules']['update.mod.php']['site']}</a>";
        }
    }
    if ( empty( $cms["config"]["update.mod.php"]["last_check"] ) ) {
        $last_check = __( "Never", "update.mod.php" );
    } else {
        $last_check = strftime( "%c", $cms["config"]["update.mod.php"]["last_check"] );
    }

    $tr_current = __( "Current version:", "update.mod.php" );
    $tr_last_v  = __( "Last version:", "update.mod.php" );
    $tr_last_ch = __( "Last check:", "update.mod.php" );
    
    $update_info  = "<p>{$tr_current} {$cms['modules']['update.mod.php']['version']}</p>";
    $update_info .= "<p>{$tr_last_v} {$last_version}</p>";
    $update_info .= "<p>{$tr_last_ch} {$last_check}</p>";

    $page = <<<EOF
<div class=backup-window>
    <div>{$cms_files_backup}</div>
    <div class=buttons>
        <button data-fn=create_backup>{$create_backup}</button>
        <button data-fn=remove_backup{$del_hide}>{$remove_backup}</button>
    </div>
    {$backup_link}
</div>

<div class=update-window>
    <div>{$updates}</div>
    <div data-cms_check_update-answer data-cms_check_dev_update-answer>{$update_info}</div>
    <div class=buttons>
        <button data-fn=cms_check_update>{$check_update}</button>
        <button data-fn=cms_update>{$update}</button>
        <button data-show-dev>{$show_dev}</button>
    </div>
</div>

<div class='dev-window developers_only'>
    <div>{$developers_buttons}</div>
    <div class=buttons>
        <button data-fn=create_zip>{$create_zip}</button>
        <button data-fn=cms_check_dev_update>{$check_dev_update}</button>
    </div>
</div>
EOF;

    // Create menu item if not exists
    if ( empty( $cms["config"]["update.mod.php"]["menu"]["update"] ) ) {
        $cms["config"]["update.mod.php"]["menu"]["update"] = array(
            "title"    => "Updates",
            "sort"     => 20,
            "class"    => "",
            "section"  => "Settings",
        );
        cms_save_config();
    }

    $cms["admin_pages"]["update"] = $page;

}


function cms_update_api() {
    global $cms;

    if ( ! empty( $_POST["fn"] ) ) {

        switch ($_POST["fn"]) {

            case "create_zip":
                exit( json_encode( array(
                    "info_text"  => __( "Zip created", "update.mod.php" ),
                    "info_class" => "info-success",
                    "info_time"  => 5000,
                    "answer"     => cms_update_create_zip(),
                ) ) );
            break;

            case "create_backup":
                exit( json_encode( array(
                    "ok"     => "true",
                    "answer" => cms_update_create_backup(),
                ) ) );
            break;

            case "cms_check_update":
                cms_update_check( "update.json" );
            break;

            case "cms_check_dev_update":
                cms_update_check( "update_dev.json" );
            break;

            case "cms_update":
                cms_update_cms_update();
            break;
            
            case "remove_backup":
                $answer = cms_update_remove_backup();
                exit( json_encode( array(
                    "info_text"  => $answer["message"],
                    "info_class" => $answer["class"],
                    "info_time"  => 5000,
                    "answer"     => $answer["logs"],
                    "ok"         => $answer["ok"],
                ) ) );
            break;

        }

    }

}


function cms_update_create_filelist() {
    global $cms;

    $logs = "";

    $queue = array(
        "{$cms['site_dir']}/uploads/.keep",
        "{$cms['site_dir']}/.htaccess",
        "{$cms['site_dir']}/.cms/admin/html.php",
        "{$cms['site_dir']}/.cms/css/admin.css",
        "{$cms['site_dir']}/.cms/css/base.css",
        "{$cms['site_dir']}/.cms/css/menu.css",
        "{$cms['site_dir']}/.cms/css/pages.css",
        "{$cms['site_dir']}/.cms/css/sitemap.css",
        "{$cms['site_dir']}/.cms/css/template.css",
        "{$cms['site_dir']}/.cms/css/update.css",
        "{$cms['site_dir']}/.cms/css/admin.dark.dark.css",
        "{$cms['site_dir']}/.cms/css/admin.light.default.css",
        "{$cms['site_dir']}/.cms/filelist.php",
        "{$cms['site_dir']}/.cms/img",
        "{$cms['site_dir']}/.cms/js/admin.js",
        "{$cms['site_dir']}/.cms/js/menu.js",
        "{$cms['site_dir']}/.cms/js/pages.js",
        "{$cms['site_dir']}/.cms/js/template.js",
        "{$cms['site_dir']}/.cms/js/update.js",
        "{$cms['site_dir']}/.cms/lang/ru_RU.UTF-8/admin.mod.php",
        "{$cms['site_dir']}/.cms/lang/ru_RU.UTF-8/base.mod.php",
        "{$cms['site_dir']}/.cms/lang/ru_RU.UTF-8/menu.mod.php",
        "{$cms['site_dir']}/.cms/lang/ru_RU.UTF-8/pages.mod.php",
        "{$cms['site_dir']}/.cms/lang/ru_RU.UTF-8/sitemap.mod.php",
        "{$cms['site_dir']}/.cms/lang/ru_RU.UTF-8/template.mod.php",
        "{$cms['site_dir']}/.cms/lang/ru_RU.UTF-8/update.mod.php",
        "{$cms['site_dir']}/.cms/lang/ru_RU.UTF-8/translit.php",
        "{$cms['site_dir']}/.cms/lang/uk_UA.UTF-8/admin.mod.php",
        "{$cms['site_dir']}/.cms/lang/uk_UA.UTF-8/base.mod.php",
        "{$cms['site_dir']}/.cms/lang/uk_UA.UTF-8/menu.mod.php",
        "{$cms['site_dir']}/.cms/lang/uk_UA.UTF-8/pages.mod.php",
        "{$cms['site_dir']}/.cms/lang/uk_UA.UTF-8/sitemap.mod.php",
        "{$cms['site_dir']}/.cms/lang/uk_UA.UTF-8/template.mod.php",
        "{$cms['site_dir']}/.cms/lang/uk_UA.UTF-8/update.mod.php",
        "{$cms['site_dir']}/.cms/lang/uk_UA.UTF-8/translit.php",
        "{$cms['site_dir']}/.cms/lib/codemirror",
        "{$cms['site_dir']}/.cms/mini",
        "{$cms['site_dir']}/.cms/mod/admin.mod.php",
        "{$cms['site_dir']}/.cms/mod/base.mod.php",
        "{$cms['site_dir']}/.cms/mod/menu.mod.php",
        "{$cms['site_dir']}/.cms/mod/pages.mod.php",
        "{$cms['site_dir']}/.cms/mod/sitemap.mod.php",
        "{$cms['site_dir']}/.cms/mod/template.mod.php",
        "{$cms['site_dir']}/.cms/mod/update.mod.php",
        "{$cms['site_dir']}/.cms/index.fn.php",
        "{$cms['site_dir']}/.cms/index.php",
        "{$cms['site_dir']}/.cms/.unlicense.txt",
    );
    $exclude = array(
        "{$cms['site_dir']}/.cms/lang/ru_RU.UTF-8/quiz.mod.php",
        "{$cms['site_dir']}/.cms/lang/ru_RU.UTF-8/catalog.mod.php",
    );
    $no_sha1 = array(
        "{$cms['site_dir']}/.cms/filelist.php",
    );
    $list  = array();

    while ( $cur = array_shift( $queue ) ) {
        if ( in_array( $cur, $exclude ) ) {
            continue;
        }
        if ( is_dir( $cur ) ) {
            $queue = array_merge( $queue, glob( $cur . "/*" ) );
        } elseif ( is_file( $cur ) ) {
            if ( in_array( $cur, $no_sha1 ) ) {
                $sha1 = "";
                $size = "";
            } else {
                $sha1 = sha1_file( $cur );
                $size = filesize( $cur );
            }
            $file = str_replace( "{$cms['site_dir']}/", "", $cur );
            $list[$file] = array(
                "sha1" => $sha1,
                "size" => $size,
            );
        } else {
            $logs .= "<p>File or dir not exixsts '{$cur}'</p>";
        }
    }
    
    file_put_contents( "{$cms['cms_dir']}/filelist.php", '<?php $list = ' . var_export( $list, true ) . ";" );

    $count = count( $list );

    $logs .= "<p>".__( "Files:", "update.mod.php" )." {$count}</p>";

    return $logs;
    
}

function cms_update_create_backup() {
    global $cms;

    require( "{$cms['cms_dir']}/filelist.php" ); // create $list variable
    
    $old = "{$cms['site_dir']}/.old";
    recurse_rm( $old );
    foreach( glob( "{$cms['site_dir']}/revert_to_*.php" ) as $file ) {
        unlink( $file );
    }

    if ( ! is_dir( $old ) ) {
        mkdir( $old, 0777, true );
    }

    $list[".cms/config.php"] = array();
    $success = true;
    foreach( $list as $fn => $file ) {
        $from = $cms["site_dir"] . "/" . $fn;
        $to   = $old . "/" . $fn;
        $dir  = preg_replace( "/\/[^\/]+$/u", "", $to );
        if ( ! is_dir( $dir ) ) {
            mkdir( $dir, 0777, true );
        }
        $c = copy( $from, $to );
        $success = $success && $c;
        if ( ! $success ) {
            break;
        }
    }

    if ( ! $success ) {
        return __( "Error create backup on file", "update.mod.php" ) . " " . $from;
    } else {
        $fn = "revert_to_" . $cms['modules']['update.mod.php']['version'] . "_" . date( "YmdHis" ) . ".php";
        file_put_contents( $cms["site_dir"] . "/" . $fn, <<<EOF
<?php
require("{$cms["site_dir"]}/.old/.cms/filelist.php");
\$oldlist = \$list;

require("{$cms["cms_dir"]}/filelist.php");
foreach( \$list as \$fn => \$file ) {
    \$from = "{$cms["site_dir"]}/{\$fn}";
    unlink( \$from );
}

\$success = true;
foreach( \$oldlist as \$fn => \$file ) {
    \$from = "{$cms["site_dir"]}/.old/{\$fn}";
    \$to   = "{$cms["site_dir"]}/{\$fn}";
    \$dir  = preg_replace( "/\/[^\/]+$/u", "", \$to );
     if ( ! is_dir( \$dir ) ) {
        mkdir( \$dir, 0777, true );
    }
    \$c = copy( \$from, \$to );
    \$success = \$success && \$c;
}
if ( \$success ) {
    echo "success";
    recurse_rm( "{$cms["site_dir"]}/.old" );
    unlink( __FILE__ );
}

function recurse_rm( \$src ) /* bool */ {
    if ( ! file_exists( \$src ) ) {
        return false;
    }
    \$dir = opendir( \$src );
    while( false !== ( \$file = readdir( \$dir ) ) ) {
        if ( ( \$file != "." ) && ( \$file != ".." ) ) {
            if ( is_dir( \$src . "/" . \$file ) ) {
                recurse_rm( \$src . "/" . \$file );
            } else {
                if ( ! unlink( \$src . "/" . \$file ) ) {
                    return false;
                }
            }
        }
    }
    closedir( \$dir );
    return rmdir( \$src );
}
EOF
);
        return "<p>" . __( "Copy and save the recovery link. If the update was unsuccessful, follow the copied link, the CMS will be restored.", "update.mod.php" ) . " " . $cms["url"]["scheme"] . "://" . $cms["url"]["host"] . $cms["base_path"] . $fn . "</p>";
    }
}

function cms_update_check( $f ) {
    global $cms;

    // prevent warning
    if ( empty( $cms["config"]["update.mod.php"]["last_check"] ) ) {
        $cms["config"]["update.mod.php"]["last_check"] = 0;
    }

    $t = time();
    $dt = $t - $cms["config"]["update.mod.php"]["last_check"];
    if ( $dt < 10 * 60 ) {
        $n = ceil( ( 10 * 60 - $dt ) / 60 );
        $msg = __( "Try it in xxx min", "update.mod.php" );
        $msg = str_replace( "xxx", $n, $msg );
        exit( json_encode( array(
            "info_text"  => $msg,
            "info_class" => "info-error",
            "info_time"  => 5000,
        ) ) );
    }

    $url = "{$cms['modules']['update.mod.php']['site']}{$f}";
    if ( ! $update = file_get_contents( $url ) ) {
        exit( json_encode( array(
            "info_text"  => __( "Can't get file", "update.mod.php" ) . " " . $url,
            "info_class" => "info-error",
            "info_time"  => 5000,
        ) ) );
    }

    if ( ! $update = json_decode( $update, true ) ) {
        exit( json_encode( array(
            "info_text"  => __( "JSON error", "update.mod.php" ) . " " . $url,
            "info_class" => "info-error",
            "info_time"  => 5000,
        ) ) );
    }

    $cms["config"]["update.mod.php"]["last_check"] = $t;
    $cms["config"]["update.mod.php"]["update"] = $update;
    cms_save_config();

    $tr_current = __( "Current version:", "update.mod.php" );
    $tr_last_v  = __( "Last version:",    "update.mod.php" );
    $tr_last_ch = __( "Last check:",      "update.mod.php" );
    $last_check = strftime( "%c", $cms["config"]["update.mod.php"]["last_check"] );

    $last_version = $cms["config"]["update.mod.php"]["update"]["last_version"];

    if ( $cms["modules"]["update.mod.php"]["compat"] !== $cms["config"]["update.mod.php"]["update"]["compat"] ) {
        $last_version .= ". " . __( "Incompatible version. Please visit", "update.mod.php" ) . " <a href='{$cms['modules']['update.mod.php']['site']}' target=_blank>{$cms['modules']['update.mod.php']['site']}</a>";
    }

    $update_info  = "<p>{$tr_current} {$cms['modules']['update.mod.php']['version']}</p>";
    $update_info .= "<p>{$tr_last_v} {$last_version}</p>";
    $update_info .= "<p>{$tr_last_ch} {$last_check}</p>";

    exit( json_encode( array(
        "info_text"  => __( "Ok", "update.mod.php" ),
        "info_class" => "info-success",
        "info_time"  => 5000,
        "answer"     => $update_info,
    ) ) );

}

function cms_update_cms_update() {
    global $cms;

    if ( empty( $cms["config"]["update.mod.php"]["update"] ) ) {
        exit( json_encode( array(
            "info_text"  => __( "Please check updates", "update.mod.php" ),
            "info_class" => "info-error",
            "info_time"  => 5000,
        ) ) );
    }

    $update = $cms["config"]["update.mod.php"]["update"];

    if ( $update["compat"] !== $cms["modules"]["update.mod.php"]["compat"] ) {
        exit( json_encode( array(
            "info_text"  => __( "The update is not possible. Install the new version manually.", "update.mod.php" ),
            "info_class" => "info-error",
            "info_time"  => 5000,
        ) ) );
    }

    // Check Current Version
    $fl = "{$cms['cms_dir']}/filelist.php";
    if ( ! file_exists( $fl ) ) {
        exit( json_encode( array(
            "info_text"  => __( "File list missing", "update.mod.php" ) . " " . $fl,
            "info_class" => "info-error",
            "info_time"  => 5000,
        ) ) );
    }
    require( $fl );
    foreach( $list as $fn => $file ) {
        $f = $cms["site_dir"] . "/" . $fn;
        if ( ! file_exists( $f ) ) {
            exit( json_encode( array(
                "info_text"  => __( "File not exists:", "update.mod.php" ) . " " . $f,
                "info_class" => "info-error",
                "info_time"  => 5000,
            ) ) );
        }
        $sha1 = sha1_file( $f );
        $size = filesize( $f );
        if ( ! empty( $file["sha1"] ) ) {
            if ( $file["sha1"] !== $sha1 ) {
                exit( json_encode( array(
                    "info_text"  => __( "Changed file:", "update.mod.php" ) . " " . $f,
                    "info_class" => "info-error",
                    "info_time"  => 5000,
                ) ) );
            }
        }
        if ( ! empty( $file["size"] ) ) {
            if ( $file["size"] !== $size ) {
                exit( json_encode( array(
                    "info_text"  => __( "Changed file:", "update.mod.php" ) . " " . $f,
                    "info_class" => "info-error",
                    "info_time"  => 5000,
                ) ) );
            }
        }
    }

    // Check Rights
    $rights = true;
    foreach( $list as $fn => $file ) {
        $from = $cms["site_dir"] . "/" . $fn;
        $r = is_writable( $from );
        $rights = $rights && $r;
        if ( ! $rights ) { break; }
    }

    if ( ! $rights ) {
        exit( json_encode( array(
            "info_text"  => __( "Update error when test current files rights. Please check files rights.", "update.mod.php" ) . " " . $from,
            "info_class" => "info-error",
            "info_time"  => 5000,
        ) ) );
    }

    if ( $update["last_version"] > $cms["modules"]["update.mod.php"]["version"] ) {

        if ( empty( $cms["config"]["update.mod.php"]["last_update"] ) ) {
            $cms["config"]["update.mod.php"]["last_update"] = 0;
        }

        $t = time();
        $dt = $t - $cms["config"]["update.mod.php"]["last_update"];
        if ( $dt < 10 * 60 ) {
            $n = ceil( ( 10 * 60 - $dt ) / 60 );
            $msg = __( "Try it in xxx min", "update.mod.php" );
            $msg = str_replace( "xxx", $n, $msg );
            exit( json_encode( array(
                "info_text"  => $msg,
                "info_class" => "info-error",
                "info_time"  => 5000,
            ) ) );
        }

        // Download New Version
        if ( ! $content = file_get_contents( $update["download"] ) ) {
            exit( json_encode( array(
                "info_text"  => __( "Download error", "update.mod.php" ),
                "info_class" => "info-error",
                "info_time"  => 5000,
            ) ) );
        }
        $tmp = $cms["site_dir"] . "/.tmp";
        if ( is_dir( $tmp ) ) {
            recurse_rm( $tmp );
        }
        if ( ! mkdir( $tmp ) ) {
            exit( json_encode( array(
                "info_text"  => __( "Can't create tmp dir", "update.mod.php" ) . " " . $tmp,
                "info_class" => "info-error",
                "info_time"  => 5000,
            ) ) );
        }
        $fn = preg_replace( "/.*\//", "", $update["download"] );
        $file = $tmp . "/" . $fn;
        if ( ! file_put_contents( $file, $content ) ) {
            exit( json_encode( array(
                "info_text"  => __( "Can't write file", "update.mod.php" ) . " " . $file,
                "info_class" => "info-error",
                "info_time"  => 5000,
            ) ) );
        }

        // Unpack New Version
        // Object Oriented Style for future compability with PHP 8
        $zip = new ZipArchive;
        if ( $zip->open( $file ) === TRUE ) {
            $zip->extractTo( $tmp );
            $zip->close();
        } else {
            exit( json_encode( array(
                "info_text"  => __( "Can't unzip", "update.mod.php" ) . " " . $file,
                "info_class" => "info-error",
                "info_time"  => 5000,
            ) ) );
        }

        // Check New Version
        $fl = "{$tmp}/.cms/filelist.php";
        if ( ! file_exists( $fl ) ) {
            exit( json_encode( array(
                "info_text"  => __( "File list missing", "update.mod.php" ) . " " . $fl,
                "info_class" => "info-error",
                "info_time"  => 5000,
            ) ) );
        }
        $oldlist = $list;
        require( $fl );
        foreach( $list as $fn => $file ) {
            $f = $tmp . "/" . $fn;
            if ( ! file_exists( $f ) ) {
                exit( json_encode( array(
                    "info_text"  => __( "File not exists:", "update.mod.php" ) . " " . $f,
                    "info_class" => "info-error",
                    "info_time"  => 5000,
                ) ) );
            }
            $sha1 = sha1_file( $f );
            $size = filesize( $f );
            if ( ! empty( $file["sha1"] ) ) {
                if ( $file["sha1"] !== $sha1 ) {
                    exit( json_encode( array(
                        "info_text"  => __( "Changed file:", "update.mod.php" ) . " " . $f,
                        "info_class" => "info-error",
                        "info_time"  => 5000,
                    ) ) );
                }
            }
            if ( ! empty( $file["size"] ) ) {
                if ( $file["size"] !== $size ) {
                    exit( json_encode( array(
                        "info_text"  => __( "Changed file:", "update.mod.php" ) . " " . $f,
                        "info_class" => "info-error",
                        "info_time"  => 5000,
                    ) ) );
                }
            }
        }

        // Remove Current Version
        $removed = true;
        foreach( $oldlist as $fn => $file ) {
            $from = $cms["site_dir"] . "/" . $fn;
            $c = unlink( $from );
            $removed = $removed && $c;
            // try remove dir
            $rdir = preg_replace( "/\/[^\/]+$/u", "", $from );
            @rmdir( $rdir );
        }

        // Move New Version to Current Location
        $moved = true;
        foreach( $list as $fn => $file ) {
            $from = $tmp . "/" . $fn;
            $to   = $cms["site_dir"] . "/" . $fn;
            $ndir = preg_replace( "/\/[^\/]+$/u", "", $to );
            if ( ! is_dir( $ndir ) ) {
                mkdir( $ndir, 0777, true );
            }
            $c = copy( $from, $to );
            $moved = $moved && $c;
        }

        // Remove tmp
        recurse_rm( $tmp );

        if ( ! $moved ) {
            exit( json_encode( array(
                "info_text"  => __( "Update error when moving new files. Please check files rights.", "update.mod.php" ),
                "info_class" => "info-error",
                "info_time"  => 5000,
            ) ) );
        }

        $cms["config"]["update.mod.php"]["last_update"] = time();
        cms_save_config();

        cms_clear_cache();

        exit( json_encode( array(
            "info_text"  => __( "Successfull update.", "update.mod.php" ),
            "info_class" => "info-success",
            "info_time"  => 5000,
            "reload"     => true,
        ) ) );
    } else {

        exit( json_encode( array(
            "info_text"  => __( "Already updated", "update.mod.php" ),
            "info_class" => "info-success",
            "info_time"  => 5000,
        ) ) );

    }

}

function cms_update_remove_backup() {
    global $cms;

    $ok = true;
    $logs = "";
    
    $old = $cms["site_dir"] . "/.old";
    if ( file_exists( $old ) ) {
        if ( recurse_rm( $old ) ) {
            $logs .= "<p>" . __( "Removed", "update.mod.php" ) . " {$old}</p>";
        } else {
            $logs .= "<p>" . __( "Can't remove", "update.mod.php" ) . " {$old}</p>";
            $ok = false;
        }
    }
    
    $tmp = $cms["site_dir"] . "/.tmp";
    if ( file_exists( $tmp ) ) {
        if ( recurse_rm( $tmp ) ) {
            $logs .= "<p>" . __( "Removed", "update.mod.php" ) . " {$tmp}</p>";
        } else {
            $logs .= "<p>" . __( "Can't remove", "update.mod.php" ) . " {$tmp}</p>";
            $ok = false;
        }
    }
    
    foreach( glob( "{$cms['site_dir']}/revert_to_*.php" ) as $file ) {
        if ( unlink( $file ) ) {
            $logs .= "<p>" . __( "Removed", "update.mod.php" ) . " {$file}</p>";
        } else {
            $logs .= "<p>" . __( "Can't remove", "update.mod.php" ) . " {$file}</p>";
            $ok = false;
        }
    }

    if ( $ok ) {
        $m = "Removed";
        $c = "info-success";
    } else {
        $m = "Have errors";
        $c = "info-error";
    }

    return array(
        "ok" => $ok,
        "logs" => $logs,
        "message" => __( $m, "update.mod.php" ),
        "class" => $c,
    );
}

function cms_update_create_zip() {
    global $cms;
    if ( $cms["url"]["host"] === "dev.coffee-cms.ru" ) {
        $dir = "/var/www/coffee-cms.com/html";
        $url = "https://coffee-cms.com/";
    } else {
        $dir = $cms["site_dir"];
        $url = "{$cms['url']['scheme']}://{$cms['url']['host']}{$cms['base_path']}";
    }

    $files = cms_update_create_filelist();

    require( "{$cms['cms_dir']}/filelist.php" );
    $zip = new ZipArchive();
    $v = date( "y.m.dHi" );
    $name = "coffee-cms-{$v}.zip";
    $zipname = "{$dir}/{$name}";

    if ( $zip->open( $zipname, ZipArchive::CREATE ) !== true ) {
        return "Can't create {$zipname}";
    }

    $zipped = true;
    foreach( $list as $fn => $file ) {
        $from = "{$cms['site_dir']}/{$fn}";
        $c = $zip->addFile( $from, $fn );
        $zipped = $zipped && $c;
    }

    $zip->close();

    file_put_contents( "{$dir}/update_dev.json", json_encode( array( 
        "last_version" => $v,
        "compat"       => $cms["modules"]["update.mod.php"]["compat"],
        "download"     => "{$url}{$name}"
    ) ) );

    return $files . "<p>" . __( "Archive created", "update.mod.php" ) . ": <a href='{$url}{$name}'>{$url}{$name}</a></p>";
}
