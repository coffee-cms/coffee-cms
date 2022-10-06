<?php

$cms["modules"]["update.mod.php"] = array(
    "name"        => __( "Обновления" ),
    "description" => __( "Модуль обновления" ),
    "version"     => "22.10.1",
    "locale"      => "ru_RU.UTF-8",
    "files"       => array(
        ".cms/mod/update.mod.php",
        ".cms/css/update.css",
        ".cms/js/update.js",
        ".cms/lang/en_US.UTF-8/update.mod.php",
        ".cms/lang/uk_UA.UTF-8/update.mod.php",
    ),
    "compat"      => "22",
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

    $backup_link = ""; // prevent warning
    $del_hide    = " class=hidden";
    foreach( glob( "{$cms['site_dir']}/revert_to_*.php" ) as $file ) {
        $file = preg_replace( "/.*\//", "", $file );
        $backup_link = "<p>" . __( "URL восстановления:" ) . " {$cms["url"]["scheme"]}://{$cms["url"]["host"]}{$cms['base_path']}{$file}</p>";
        $del_hide    = "";
    }

    if ( empty( $cms["config"]["update.mod.php"]["update"]["last_version"] ) ) {
        $last_version = __( "Неизвестно" );
    } else {
        $last_version = $cms["config"]["update.mod.php"]["update"]["last_version"];
        if ( $cms["modules"]["update.mod.php"]["compat"] !== $cms["config"]["update.mod.php"]["update"]["compat"] ) {
            $last_version .= ". " . __( "Несовместимая версия. Посетите" ) . " <a href='https://coffee-cms.ru/' target=_blank>https://coffee-cms.ru/</a>";
        }
    }

    if ( empty( $cms["config"]["update.mod.php"]["last_check"] ) ) {
        $last_check = __( "Никогда" );
    } else {
        $last_check = date( "d F Y H:i:s", $cms["config"]["update.mod.php"]["last_check"] );
        $last_check = strtr( $last_check, $cms["tr_mon"] );
    }

    $page = "
<div class=backup-window>
    <div>" . __( "Резервная копия файлов CMS" ) . "</div>
    <div class=buttons>
        <button data-fn=create_backup>" . __( "Создать" ) . "</button>
        <button data-fn=remove_backup{$del_hide}>" . __( "Удалить" ) . "</button>
    </div>
    {$backup_link}
</div>

<div class=update-window>
    <div>" . __( "Обновления" ) . "</div>
    <div class=check-answer>
        <p>" . __( "Текущая версия:" ) . " {$cms['modules']['update.mod.php']['version']}</p>
        <p>" . __( "Последняя версия:" ) . " {$last_version}</p>
        <p>" . __( "Проверялось:" ) . " {$last_check}</p>
    </div>
    <div class=buttons>
        <button data-fn=cms_check_update>" . __( "Проверить" ) . "</button>
        <button data-fn=cms_update>" . __( "Обновить" ) . "</button>
        <button data-show-dev>" . __( "Дополнительно" ) . "</button>
    </div>
</div>

<div class='dev-window developers_only'>
    <div>" . __( "Кнопки разработчиков" ) . "</div>
    <div class=buttons>
        <button data-fn=create_zip style='display:none'>" . __( "Создать Zip" ) . "</button>
        <button data-fn=cms_check_dev_update>" . __( "Проверить Dev" ) . "</button>
    </div>
</div>
";

    // Create menu item if not exists
    if ( empty( $cms["config"]["update.mod.php"]["menu"]["update"] ) ) {
        $cms["config"]["update.mod.php"]["menu"]["update"] = array(
            "title"    => "Обновления",
            "sort"     => 20,
            "class"    => "",
            "section"  => "Настройки",
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
                cms_update_create_zip();
            break;

            case "create_backup":
                cms_update_create_backup();
            break;

            case "cms_check_update":
                cms_update_check( "https://coffee-cms.ru/update.json" );
            break;

            case "cms_check_dev_update":
                cms_update_check( "https://dev.coffee-cms.ru/update_dev.json" );
            break;

            case "cms_update":
                cms_update_cms_update();
            break;
            
            case "remove_backup":
                cms_update_remove_backup();
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
        "{$cms['site_dir']}/.cms/lang/ru_RU.UTF-8/translit.php",
        "{$cms['site_dir']}/.cms/lang/en_US.UTF-8/admin.mod.php",
        "{$cms['site_dir']}/.cms/lang/en_US.UTF-8/base.mod.php",
        "{$cms['site_dir']}/.cms/lang/en_US.UTF-8/menu.mod.php",
        "{$cms['site_dir']}/.cms/lang/en_US.UTF-8/pages.mod.php",
        "{$cms['site_dir']}/.cms/lang/en_US.UTF-8/sitemap.mod.php",
        "{$cms['site_dir']}/.cms/lang/en_US.UTF-8/template.mod.php",
        "{$cms['site_dir']}/.cms/lang/en_US.UTF-8/update.mod.php",
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
        "{$cms['site_dir']}/.cms/update.sql",
        "{$cms['site_dir']}/.cms/.unlicense.txt",
    );
    $no_sha1 = array(
        "{$cms['site_dir']}/.cms/filelist.php",
    );
    
    $list  = array();

    while ( $cur = array_shift( $queue ) ) {
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
            $logs .= "<p>" . __( "Файл или каталог не существует" ) . " '{$cur}'</p>";
        }
    }

    // Список файлов которые разрешено редактировать.
    // И если они отредактированы, то не будут обновляться.
    $allow_change = array(
        ".htaccess",
    );
    
    file_put_contents( "{$cms['cms_dir']}/filelist.php", 
        "<?php\n\$allow_change = " . var_export( $allow_change, true ) . ";\n" .
        '$list = ' . var_export( $list, true ) . ";\n"
    );

    $logs .= "<p>".__( "Файлы:" )." " . count( $list ) . "</p>";

    return $logs;
    
}


function cms_update_create_backup() {
    global $cms;

    if ( $_SERVER["SERVER_NAME"] === "dev.coffee-cms.ru" ) {
        exit( json_encode( array(
            "ok"     => "false",
            "answer" => "<p>Нельзя делать на dev.coffee-cms.ru</p>",
        ) ) );
    }

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

        exit( json_encode( array(
            "ok"     => "false",
            "answer" => "<p>" . __( "Ошибка создания бекапа" ) . " {$from}</p>",
        ) ) );

    } else {
        $fn = "revert_to_{$cms['modules']['update.mod.php']['version']}_" . date( "YmdHis" ) . ".php";
        $w = file_put_contents( "{$cms["site_dir"]}/{$fn}", "
<?php
require( '{$cms["site_dir"]}/.old/.cms/filelist.php' );
\$oldlist = \$list;

require( '{$cms["cms_dir"]}/filelist.php' );
foreach( \$list as \$fn => \$file ) {
    \$from = \"{$cms["site_dir"]}/{\$fn}\";
    unlink( \$from );
}

\$success = true;
foreach( \$oldlist as \$fn => \$file ) {
    \$from = \"{$cms["site_dir"]}/.old/{\$fn}\";
    \$to   = \"{$cms["site_dir"]}/{\$fn}\";
    \$dir  = preg_replace( '/\/[^\/]+$/u', '', \$to );
     if ( ! is_dir( \$dir ) ) {
        mkdir( \$dir, 0777, true );
    }
    \$c = copy( \$from, \$to );
    \$success = \$success && \$c;
}
if ( \$success ) {
    echo 'success';
    recurse_rm( '{$cms["site_dir"]}/.old' );
    unlink( __FILE__ );
}

function recurse_rm( \$src ) /* bool */ {
    if ( ! file_exists( \$src ) ) {
        return false;
    }
    \$dir = opendir( \$src );
    while( false !== ( \$file = readdir( \$dir ) ) ) {
        if ( ( \$file != '.' ) && ( \$file != '..' ) ) {
            if ( is_dir( \"{\$src}/{\$file}\" ) ) {
                recurse_rm( \"{\$src}/{\$file}\" );
            } else {
                if ( ! unlink( \"{\$src}/{\$file}\" ) ) {
                    return false;
                }
            }
        }
    }
    closedir( \$dir );
    return rmdir( \$src );
}
" );
        $url = $cms["url"]["scheme"] . "://" . $cms["url"]["host"] . $cms["base_path"] . $fn;

        if ( $w > 0 ) {
            exit( json_encode( array(
                "ok"     => "true",
                "answer" => "<p>" . __( "Скопируйте и сохраните ссылку восстановления. Если обновление прошло неуспешно перейдите по скопированной ссылке, CMS восстановится." ) . " " . $url . "</p>",
            ) ) );
        } else {
            exit( json_encode( array(
                "ok"     => "false",
                "answer" => "<p>" . __( "Ошибка создания файла:" ) . " {$url}</p>",
            ) ) );
        }

    }
}


function cms_update_check( $url ) {
    global $cms;

    // prevent warning
    if ( empty( $cms["config"]["update.mod.php"]["last_check"] ) ) {
        $cms["config"]["update.mod.php"]["last_check"] = 0;
    }

    $t = time();
    $dt = $t - $cms["config"]["update.mod.php"]["last_check"];
    if ( $dt < 10 * 60 ) {
        $n = ceil( ( 10 * 60 - $dt ) / 60 );
        $msg = __( "Попробуйте через xxx мин" );
        $msg = str_replace( "xxx", $n, $msg );
        exit( json_encode( array(
            "info_text"  => $msg,
            "info_class" => "info-error",
            "info_time"  => 5000,
        ) ) );
    }

    if ( ! $update = file_get_contents( $url ) ) {
        exit( json_encode( array(
            "info_text"  => __( "Не могу скачать файл" ) . " " . $url,
            "info_class" => "info-error",
            "info_time"  => 5000,
        ) ) );
    }

    if ( ! $update = json_decode( $update, true ) ) {
        exit( json_encode( array(
            "info_text"  => __( "Ошибка файла JSON" ) . " " . $url,
            "info_class" => "info-error",
            "info_time"  => 5000,
        ) ) );
    }

    $cms["config"]["update.mod.php"]["last_check"] = $t;
    $cms["config"]["update.mod.php"]["update"] = $update;
    cms_save_config();

    $last_check = date( "d F Y H:i:s", $cms["config"]["update.mod.php"]["last_check"] );
    $last_check = strtr( $last_check, $cms["tr_mon"] );

    $last_version = $cms["config"]["update.mod.php"]["update"]["last_version"];

    if ( $cms["modules"]["update.mod.php"]["compat"] !== $cms["config"]["update.mod.php"]["update"]["compat"] ) {
        $last_version .= ". " . __( "Несовместимая версия. Посетите" ) . " <a href='https://coffee-cms.ru/' target=_blank>https://coffee-cms.ru/</a>";
    }

    $update_info  = "<p>" . __( "Текущая версия:" ) . " {$cms['modules']['update.mod.php']['version']}</p>";
    $update_info .= "<p>" . __( "Последняя версия:" ) . " {$last_version}</p>";
    $update_info .= "<p>" . __( "Проверялось:" ) . " {$last_check}</p>";

    exit( json_encode( array(
        "info_text"  => __( "OK" ),
        "info_class" => "info-success",
        "info_time"  => 5000,
        "answer"     => $update_info,
    ) ) );

}

function cms_update_cms_update() {
    global $cms;

    if ( empty( $cms["config"]["update.mod.php"]["update"] ) ) {
        exit( json_encode( array(
            "info_text"  => __( "Пожалуйста, проверьте обновления" ),
            "info_class" => "info-error",
            "info_time"  => 5000,
        ) ) );
    }

    $update = $cms["config"]["update.mod.php"]["update"];

    if ( $update["compat"] !== $cms["modules"]["update.mod.php"]["compat"] ) {
        exit( json_encode( array(
            "info_text"  => __( "Обновление невозможно. Установите новую версию вручную." ),
            "info_class" => "info-error",
            "info_time"  => 5000,
        ) ) );
    }

    // Check Current Version
    $fl = "{$cms['cms_dir']}/filelist.php";
    if ( ! file_exists( $fl ) ) {
        exit( json_encode( array(
            "info_text"  => __( "Отсутствует список файлов" ) . " " . $fl . " " . __LINE__,
            "info_class" => "info-error",
            "info_time"  => 5000,
        ) ) );
    }
    require( $fl );
    foreach( $list as $fn => $file ) {
        $f = $cms["site_dir"] . "/" . $fn;
        if ( ! file_exists( $f ) ) {
            exit( json_encode( array(
                "info_text"  => __( "Отсутствует файл:" ) . " " . $f . " " . __LINE__,
                "info_class" => "info-error",
                "info_time"  => 5000,
            ) ) );
        }
        $sha1 = sha1_file( $f );
        $size = filesize( $f );
        if ( ! empty( $file["sha1"] ) ) {
            if ( $file["sha1"] !== $sha1 ) {
                if ( ! in_array( $fn, $allow_change ) ) {
                    exit( json_encode( array(
                        "info_text"  => __( "Файл изменен:" ) . " " . $f . " " . __LINE__,
                        "info_class" => "info-error",
                        "info_time"  => 5000,
                    ) ) );
                }
            }
        }
        if ( ! empty( $file["size"] ) ) {
            if ( $file["size"] !== $size ) {
                if ( ! in_array( $fn, $allow_change ) ) {
                    exit( json_encode( array(
                        "info_text"  => __( "Файл изменен:" ) . " " . $f . " " . __LINE__,
                        "info_class" => "info-error",
                        "info_time"  => 5000,
                    ) ) );
                }
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
            "info_text"  => __( "Ошибка обновления при проверке прав на текущие файлы. Пожалуйста, проверьте права файлов." ) . " " . $from,
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
            $msg = __( "Попробуйте через xxx мин" );
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
                "info_text"  => __( "Ошибка скачивания" ),
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
                "info_text"  => __( "Не могу создать tmp папку" ) . " " . $tmp,
                "info_class" => "info-error",
                "info_time"  => 5000,
            ) ) );
        }
        $fn = preg_replace( "/.*\//", "", $update["download"] );
        $file = $tmp . "/" . $fn;
        if ( ! file_put_contents( $file, $content ) ) {
            exit( json_encode( array(
                "info_text"  => __( "Не могу записать файл" ) . " " . $file,
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
                "info_text"  => __( "Не могу разархивировать" ) . " " . $file,
                "info_class" => "info-error",
                "info_time"  => 5000,
            ) ) );
        }

        // Check New Version
        $fl = "{$tmp}/.cms/filelist.php";
        if ( ! file_exists( $fl ) ) {
            exit( json_encode( array(
                "info_text"  => __( "Отсутствует список файлов" ) . " " . $fl . " " . __LINE__,
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
                    "info_text"  => __( "Отсутствует файл:" ) . " " . $f . " " . __LINE__,
                    "info_class" => "info-error",
                    "info_time"  => 5000,
                ) ) );
            }
            $sha1 = sha1_file( $f );
            $size = filesize( $f );
            if ( ! empty( $file["sha1"] ) ) {
                if ( $file["sha1"] !== $sha1 ) {
                    exit( json_encode( array(
                        "info_text"  => __( "Файл изменен:" ) . " " . $f . " " . __LINE__,
                        "info_class" => "info-error",
                        "info_time"  => 5000,
                    ) ) );
                }
            }
            if ( ! empty( $file["size"] ) ) {
                if ( $file["size"] !== $size ) {
                    exit( json_encode( array(
                        "info_text"  => __( "Файл изменен:" ) . " " . $f . " " . __LINE__,
                        "info_class" => "info-error",
                        "info_time"  => 5000,
                    ) ) );
                }
            }
        }

        // Удаление файлов текущей цмс
        $removed = true;
        foreach( $oldlist as $fn => $file ) {
            $from = $cms["site_dir"] . "/" . $fn;
            
            if ( in_array( $fn, $allow_change ) and
            sha1_file( $from ) !== $oldlist[$fn]["sha1"] ) {
                // Если файл в списке разрешенных на изменение
                // и sha1 не совпадает, то оставляем его не тронутым
            } else {
                $c = unlink( $from );
                $removed = $removed && $c;
            }
            // try remove dir
            $rdir = preg_replace( "/\/[^\/]+$/u", "", $from );
            @rmdir( $rdir );
        }

        // Перемещение новой версии в текущую
        $moved = true;
        foreach( $list as $fn => $file ) {
            if ( in_array( $fn, $allow_change ) and
            sha1_file( $cms["site_dir"] . "/" . $fn ) !== $oldlist[$fn]["sha1"] ) {
                // Если файл в списке разрешенных на изменение
                // и sha1 не совпадает, то оставляем его не тронутым
            } else {
                $from = $tmp . "/" . $fn;
                $to   = $cms["site_dir"] . "/" . $fn;
                $ndir = preg_replace( "/\/[^\/]+$/u", "", $to );
                if ( ! is_dir( $ndir ) ) {
                    mkdir( $ndir, 0777, true );
                }
                $c = copy( $from, $to );
                $moved = $moved && $c;
            }
        }

        if ( ! $moved ) {
            exit( json_encode( array(
                "info_text"  => __( "Ошибка обновления при перемещении новых файлов. Пожалуйста, проверьте права файлов." ),
                "info_class" => "info-error",
                "info_time"  => 5000,
            ) ) );
        }

        recurse_rm( $tmp );

        // Выполнить запросы в БД, находящиеся в файле update.sql
        // Не будем обращать внимания на результат запроса,
        // ведь если идет многократное обновление из dev-ветки,
        // то скорее всего будут ошибки его выполнения.
        $update_sql = "{$cms["cms_dir"]}/update.sql";
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

        $cms["config"]["update.mod.php"]["last_update"] = time();
        cms_save_config();

        cms_clear_cache();

        exit( json_encode( array(
            "info_text"  => __( "Успешное обновление." ),
            "info_class" => "info-success",
            "info_time"  => 5000,
            "reload"     => true,
        ) ) );
    } else {

        exit( json_encode( array(
            "info_text"  => __( "Уже обновлено" ),
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
            $logs .= "<p>" . __( "Удалено" ) . " {$old}</p>";
        } else {
            $logs .= "<p>" . __( "Не могу удалить" ) . " {$old}</p>";
            $ok = false;
        }
    }
    
    $tmp = $cms["site_dir"] . "/.tmp";
    if ( file_exists( $tmp ) ) {
        if ( recurse_rm( $tmp ) ) {
            $logs .= "<p>" . __( "Удалено" ) . " {$tmp}</p>";
        } else {
            $logs .= "<p>" . __( "Не могу удалить" ) . " {$tmp}</p>";
            $ok = false;
        }
    }
    
    foreach( glob( "{$cms['site_dir']}/revert_to_*.php" ) as $file ) {
        if ( unlink( $file ) ) {
            $logs .= "<p>" . __( "Удалено" ) . " {$file}</p>";
        } else {
            $logs .= "<p>" . __( "Не могу удалить" ) . " {$file}</p>";
            $ok = false;
        }
    }

    if ( $ok ) {
        exit( json_encode( array(
            "info_text"  => __( "Удалено" ),
            "info_class" => "info-success",
            "info_time"  => 5000,
            "answer"     => $logs,
            "ok"         => $ok ? "true" : "false",
        ) ) );
    } else {
        exit( json_encode( array(
            "info_text"  => __( "Есть ошибки" ),
            "info_class" => "info-error",
            "info_time"  => 5000,
            "answer"     => $logs,
            "ok"         => $ok ? "true" : "false",
        ) ) );
    }
    
}

function cms_update_create_zip() {
    global $cms;
    
    // Данная функция сообщит об отсутствующих файлах
    $files = cms_update_create_filelist();
    
    require( "{$cms['cms_dir']}/filelist.php" );
    $zip = new ZipArchive();
    $v = $cms["modules"]["update.mod.php"]["version"] . date( "-dHi" );
    $name = "coffee-cms-{$v}.zip";
    $url = "https://dev.coffee-cms.ru/{$name}";
    $dir = "/var/www/dev.coffee-cms.ru/html";
    $zipname = "{$dir}/{$name}";

    if ( $zip->open( $zipname, ZipArchive::CREATE ) !== true ) {
        exit( json_encode( array(
            "answer" => __( "Не могу создать" ) . " {$zipname}",
        ) ) );
    }

    foreach( $list as $fn => $file ) {
        $from = "{$cms['site_dir']}/{$fn}";
        $zip->addFile( $from, $fn );
    }

    $zip->close();

    file_put_contents( "{$dir}/update_dev.json", json_encode( array( 
        "last_version" => $v,
        "compat"       => $cms["modules"]["update.mod.php"]["compat"],
        "download"     => $url
    ) ) );

    exit( json_encode( array(
        "answer"     => $files . "<p>" . __( "Создан архив" ) . ": <a href='{$url}'>{$url}</a></p>",
    ) ) );
}
