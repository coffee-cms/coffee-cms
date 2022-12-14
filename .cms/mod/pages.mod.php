<?php

$cms["modules"]["pages.mod.php"] = array(
    "name"        => __( "module_name" ),
    "description" => __( "module_description" ),
    "version"     => "",
    "files"       => array(
        ".cms/mod/pages.mod.php",
        ".cms/js/pages.js",
        ".cms/css/pages.css",
        ".cms/lang/ru_RU.UTF-8/pages.mod.php",
        ".cms/lang/en_US.UTF-8/pages.mod.php",
        ".cms/lang/uk_UA.UTF-8/pages.mod.php",
    ),
);

// Return if module disabled
if ( ! empty( $cms["config"]["pages.mod.php"]["disabled"] ) ) {

    return;

} else {
    
    if ( empty( $_COOKIE["pages_pager"] ) ) {
        if ( PHP_VERSION_ID < 70300 ) {
            setcookie( "pages_pager", 100 );
        } else {
            setcookie( "pages_pager", 100, array( "SameSite" => "Lax" ) );
        }
    }

    if ( is_admin() ) {
        cms_add_function( "create_tables", "cms_pages_create_tables" );
        cms_add_function( "admin", "cms_pages_admin" );
        cms_add_function( "admin_header", "cms_pages_admin_header" );
        cms_add_function( "api", "cms_pages_api" );
    }
    cms_add_function( "query", "cms_pages_query" );

}
    
function cms_pages_admin_header() {
    global $cms;
    echo "<link rel=stylesheet href='{$cms['base_path']}css/pages.css'>";
    echo "<link rel=stylesheet href='{$cms['base_path']}lib/codemirror/lib/codemirror.css'>";
    echo "<link rel=stylesheet href='{$cms['base_path']}lib/codemirror/addon/hint/show-hint.css'>";
    echo "<link rel=stylesheet href='{$cms['base_path']}lib/codemirror/addon/dialog/dialog.css'>";
    echo "<script src='{$cms['base_path']}lib/codemirror/lib/codemirror.js'></script>";
    echo "<script src='{$cms['base_path']}lib/codemirror/addon/hint/show-hint.js'></script>";
    echo "<script src='{$cms['base_path']}lib/codemirror/addon/hint/xml-hint.js'></script>";
    echo "<script src='{$cms['base_path']}lib/codemirror/addon/hint/html-hint.js'></script>";
    echo "<script src='{$cms['base_path']}lib/codemirror/addon/hint/javascript-hint.js'></script>";
    echo "<script src='{$cms['base_path']}lib/codemirror/addon/hint/anyword-hint.js'></script>";
    echo "<script src='{$cms['base_path']}lib/codemirror/addon/hint/css-hint.js'></script>";
    echo "<script src='{$cms['base_path']}lib/codemirror/addon/selection/active-line.js'></script>";
    echo "<script src='{$cms['base_path']}lib/codemirror/addon/search/search.js'></script>";
    echo "<script src='{$cms['base_path']}lib/codemirror/addon/search/searchcursor.js'></script>";
    echo "<script src='{$cms['base_path']}lib/codemirror/addon/search/jump-to-line.js'></script>";
    echo "<script src='{$cms['base_path']}lib/codemirror/addon/dialog/dialog.js'></script>";
    echo "<script src='{$cms['base_path']}lib/codemirror/addon/edit/matchbrackets.js'></script>";
    echo "<script src='{$cms['base_path']}lib/codemirror/addon/edit/matchtags.js'></script>";
    echo "<script src='{$cms['base_path']}lib/codemirror/addon/edit/closebrackets.js'></script>";
    echo "<script src='{$cms['base_path']}lib/codemirror/addon/edit/closetag.js'></script>";
    echo "<script src='{$cms['base_path']}lib/codemirror/mode/htmlmixed/htmlmixed.js'></script>";
    echo "<script src='{$cms['base_path']}lib/codemirror/mode/xml/xml.js'></script>";
    echo "<script src='{$cms['base_path']}lib/codemirror/mode/javascript/javascript.js'></script>";
    echo "<script src='{$cms['base_path']}lib/codemirror/mode/css/css.js'></script>";
    echo "<script src='{$cms['base_path']}lib/codemirror/mode/clike/clike.js'></script>";
    echo "<script src='{$cms['base_path']}lib/codemirror/mode/php/php.js'></script>";
    echo "<script src='{$cms['base_path']}js/pages.js'></script>";
}


function cms_pages_api() {
    global $cms;
    
    if ( ! empty( $_POST["fn"] ) ) {
        
        switch ( $_POST["fn"] ) {

            case "create_page":
                
                // Read template settings
                $settings_file = "{$cms['cms_dir']}/{$cms['template']}/settings.php";
                if ( file_exists( $settings_file ) ) {
                    include( $settings_file );
                }

                $tpl = "page";
                
                // Default text for new page from template
                if ( ! empty( $cms["templates"][ $cms["template"] ]["page_templates"][$tpl] ) ) {
                    $text = mysqli_real_escape_string( $cms["base"], $cms["templates"][ $cms["template"] ]["page_templates"][$tpl] );
                } else {
                    $text = "";
                }
                
                $created = date( "Y-m-d H:i:s" );
                
                $q = "INSERT INTO pages SET created='{$created}', tpl='{$tpl}', text='{$text}'";
                if ( $r = mysqli_query( $cms["base"], $q ) ) {
                    $id = mysqli_insert_id( $cms["base"] );
                } else {
                    echo( json_encode( array(
                        "info_text"  => __( "error_creating_page" ) . mysqli_error( $cms["base"] ),
                        "info_class" => "info-error",
                        "info_time"  => 5000,
                    ) ) );
                    return;
                }

                // Create title, url and update in database
                $title     = __( "page_default_title" );
                $url       = "/{$id}";
                
                $q = "UPDATE pages SET title='{$title}', url='{$url}' WHERE id={$id}";
                if ( $r = mysqli_query( $cms["base"], $q ) ) {
                    $_POST["where"] = "id={$id}";
                    $r = cms_pages_get_pages_list();
                    $r = array_merge( $r,
                        array(
                            "info_text"  => __( "page_created" ),
                            "info_class" => "info-success",
                            "info_time"  => 5000,
                        )
                    );
                    echo( json_encode( $r ) );
                    return;
                } else {
                    echo( json_encode( array(
                        "info_text"  => __( "error_creating_page" ) . mysqli_error( $cms["base"] ),
                        "info_class" => "info-error",
                        "info_time"  => 5000,
                    ) ) );
                    return;
                }
            break;

            case "save_prop":

                // Read template settings
                $settings_file = "{$cms['cms_dir']}/{$cms['template']}/settings.php";
                if ( file_exists( $settings_file ) ) {
                    include( $settings_file );
                }
                
                $id          = (int) $_POST["id"];
                $title       = mysqli_real_escape_string( $cms["base"], $_POST["title"] );
                $seo_title   = mysqli_real_escape_string( $cms["base"], $_POST["seo_title"] );
                $description = mysqli_real_escape_string( $cms["base"], $_POST["description"] );
                $tags        = mysqli_real_escape_string( $cms["base"], $_POST["tags"] );

                // get page text
                $q    = "SELECT `text` FROM `pages` WHERE `id`={$id}";
                $r    = mysqli_query( $cms["base"], $q );
                $row  = mysqli_fetch_assoc( $r );
                $text = $row["text"];

                $update_text = "";
                // Default text for old_template
                if ( ! empty( $cms["templates"][ $cms["template"] ]["page_templates"][ $_POST["old_template"] ] ) ) {
                    $text2 = $cms["templates"][ $cms["template"] ]["page_templates"][ $_POST["old_template"] ];
                } else {
                    $text2 = "";
                }

                // Change template text
                if ( $_POST["old_template"] !== $_POST["template"] and $text === $text2 ) {
                    // Default text for template
                    if ( ! empty( $cms["templates"][ $cms["template"] ]["page_templates"][ $_POST["template"] ] ) ) {
                        $text = mysqli_real_escape_string( $cms["base"], $cms["templates"][ $cms["template"] ]["page_templates"][ $_POST["template"] ] );
                        $update_text = ", `text`='{$text}'";
                    }
                }

                // if empty url then create from title
                if ( empty( $_POST["url"] ) ) {
                    $_POST["url"] = "/" . strtolower( cms_translit( $_POST["title"] ) );
                } elseif ( substr( $_POST["url"], 0, 1 ) !== "/" ) {
                    $_POST["url"]  = "/" . $_POST["url"];
                }
                $url = mysqli_real_escape_string( $cms["base"], $_POST["url"] );

                // Check dupl
                if ( $r = mysqli_query( $cms["base"], "SELECT COUNT(*) FROM `pages` WHERE `url`='{$url}' AND `id`<>{$id}" ) ) {
                    if ( $cnt = mysqli_fetch_assoc( $r ) ) {
                        if ( $cnt["COUNT(*)"] > 0 ) {
                            $_POST["url"] = "/" . cms_admin_pass_gen();
                            $url = mysqli_real_escape_string( $cms["base"], $_POST["url"] );
                        }
                    }
                }

                if ( empty( $_POST["date"] ) ) { $_POST["date"] = "0000-00-00"; }
                if ( empty( $_POST["time"] ) ) { $_POST["time"] = "00:00"; }
                $created = mysqli_real_escape_string( $cms["base"], $_POST["date"] . " " . $_POST["time"] . ":00" );
                $tpl = mysqli_real_escape_string( $cms["base"], $_POST["template"] );
                $modified = str_replace( ",", ".", microtime( true ) );
                $q = "UPDATE `pages` SET 
                        `title`='{$title}', 
                        `seo_title`='{$seo_title}', 
                        `description`='{$description}', 
                        `tags`='{$tags}', 
                        `created`='{$created}',
                        `modified`='{$modified}',
                        `tpl`='{$tpl}', 
                        `url`='{$url}'
                        {$update_text}
                      WHERE id={$id}";

                // clear cache before change url
                if ( function_exists( "cms_clear_cache" ) ) {
                    cms_clear_cache();
                }
                if ( $r = mysqli_query( $cms["base"], $q ) ) {
                    if ( strtotime( $created ) > time() ) {
                        $planned = true;
                    } else {
                        $planned = false;
                    }
                    if ( $created === "0000-00-00 00:00:00" ) {
                        $created = __( "no_date" );
                    } else {
                        $time = strtotime( $created );
                        $created = date( "d.m.Y", $time )."<br>".date( "H:i", $time );
                    }
                    $r = array(
                        "info_text"  => __( "updated" ),
                        "info_class" => "info-success",
                        "info_time"  => 1000,
                        "title"      => htmlspecialchars( $_POST["title"] ),
                        "url"        => $_POST["url"],
                        "created"    => $created,
                        "planned"    => $planned,
                        "ok"         => "true",
                    );
                    if ( function_exists( "cms_sitemap_update" ) ) {
                        cms_sitemap_update();
                    }

                    // search page in menu
                    $q = "SELECT * FROM `menu` WHERE `id`={$id}";
                    if ( mysqli_query( $cms["base"], $q ) and mysqli_affected_rows( $cms["base"] ) ) {
                        $r["update_menu"] = "true";
                    } else {
                        $r["update_menu"] = "false";
                    }

                    echo( json_encode( $r ) );
                    return;
                }
            break;

            case "save_page":

                // Read template settings
                $settings_file = "{$cms['cms_dir']}/{$cms['template']}/settings.php";
                if ( file_exists( $settings_file ) ) {
                    include( $settings_file );
                }
                
                // hook for save page
                cms_do_stage( "save_page" );
                
                $id           = (int) $_POST["id"];
                $old_modified = str_replace( ",", ".", (float) $_POST["modified"] );
                $modified     = str_replace( ",", ".", microtime( true ) );
                $text         = mysqli_real_escape_string( $cms["base"], $_POST["text"] );

                // Default text for old_template
                if ( ! empty( $cms["templates"][ $cms["template"] ]["page_templates"][ $_POST["old_template"] ] ) ) {
                    $text2 = mysqli_real_escape_string( $cms["base"], $cms["templates"][ $cms["template"] ]["page_templates"][ $_POST["old_template"] ] );
                } else {
                    $text2 = "";
                }

                // Change template text
                $new_text = "";
                if ( $_POST["old_template"] !== $_POST["template"] and $text === $text2 ) {
                    // Default text for template
                    if ( ! empty( $cms["templates"][ $cms["template"] ]["page_templates"][ $_POST["template"] ] ) ) {
                        $new_text = $cms["templates"][ $cms["template"] ]["page_templates"][ $_POST["template"] ];
                        $text = mysqli_real_escape_string( $cms["base"], $new_text );
                    }
                }


                $title       = mysqli_real_escape_string( $cms["base"], $_POST["title"] );
                $seo_title   = mysqli_real_escape_string( $cms["base"], $_POST["seo_title"] );
                $description = mysqli_real_escape_string( $cms["base"], $_POST["description"] );
                $tags        = mysqli_real_escape_string( $cms["base"], $_POST["tags"] );

                // if empty url then create from title
                if ( empty( $_POST["url"] ) ) {
                    $_POST["url"] = "/" . strtolower( cms_translit( $_POST["title"] ) );
                } elseif ( substr( $_POST["url"], 0, 1 ) !== "/" ) {
                    $_POST["url"]  = "/" . $_POST["url"];
                }
                $url = mysqli_real_escape_string( $cms["base"], $_POST["url"] );

                // Check dupl
                if ( $r = mysqli_query( $cms["base"], "SELECT COUNT(*) FROM `pages` WHERE `url`='{$url}' AND `id`<>{$id}" ) ) {
                    if ( $cnt = mysqli_fetch_assoc( $r ) ) {
                        if ( $cnt["COUNT(*)"] > 0 ) {
                            $_POST["url"] = "/" . cms_admin_pass_gen();
                            $url = mysqli_real_escape_string( $cms["base"], $_POST["url"] );
                        }
                    }
                }

                if ( empty( $_POST["date"] ) ) { $_POST["date"] = "0000-00-00"; }
                if ( empty( $_POST["time"] ) ) { $_POST["time"] = "00:00"; }
                $created = mysqli_real_escape_string( $cms["base"], $_POST["date"] . " " . $_POST["time"] . ":00" );
                $tpl = mysqli_real_escape_string( $cms["base"], $_POST["template"] );

                $q = "UPDATE pages SET 
                        text='{$text}', 
                        modified={$modified},
                        title='{$title}', 
                        seo_title='{$seo_title}', 
                        description='{$description}',
                        tags='{$tags}',
                        created='{$created}',
                        tpl='{$tpl}', 
                        url='{$url}'
                      WHERE id={$id} AND modified={$old_modified}";
                
                // clear cache before change url
                if ( function_exists( "cms_clear_cache" ) ) {
                    cms_clear_cache();
                }

                if ( mysqli_query( $cms["base"], $q ) ) {
                    
                    if ( ! mysqli_affected_rows( $cms["base"] ) ) {
                        echo( json_encode( array(
                            "ok"        => "false",
                            "info_text" => __( "page_changed" ),
                            "info_class" => "info-error",
                            "info_time"  => 5000,
                        ) ) );
                        return;
                    }

                    echo( json_encode( array(
                        "ok"          => "true",
                        "info_text"   => __( "saved" ),
                        "info_class"  => "info-success",
                        "info_time"   => 5000,
                        "modified"    => $modified,
                        "title"       => htmlspecialchars( $_POST["title"] ),
                        "url"         => $_POST["url"],
                        "new_text"    => $new_text,
                    ) ) );
                    return;
                } else {
                    echo( json_encode( array(
                        "ok"         => "false",
                        "info_text"  => mysqli_error( $cms["base"] ),
                        "info_class" => "info-error",
                        "info_time"  => 5000,
                    ) ) );
                    return;
                }
            break;

            case "page_pin":
                $id  = (int) $_POST['id'];
                $pin = (int) $_POST['pin'];
                $q   = "UPDATE pages SET pin={$pin} WHERE id={$id}";
                if ( $res = mysqli_query( $cms["base"], $q) ) {
                    echo( json_encode( array(
                        "ok" => "true",
                    ) ) );
                    return;
                }
            break;

            case "get_page":
                if ( $id = (int) $_POST['id'] and $res = mysqli_query( $cms["base"], "SELECT * FROM `pages` WHERE `id`={$id}" ) ) {
                    if ( $page = mysqli_fetch_assoc( $res ) ) {

                        // date and time
                        $date = date( "Y-m-d", strtotime( $page["created"] ) );
                        $time = date( "H:i", strtotime( $page["created"] ) );

                        // template
                        $telplates_list = array();
                        $expr = $cms['cms_dir'] . "/" . $cms['config']['template.mod.php']['template'] . "/" . $cms["template_prefix"] . "*" . $cms["template_suffix"];
                        foreach ( glob( $expr ) as $tpl ) {
                            $name = preg_replace( "/.+\/{$cms['template_prefix']}(.+)" . str_replace( '.', '\.', $cms["template_suffix"] ) . "/", "$1", $tpl );
                            array_push( $telplates_list, $name );
                        }
                        // <option> for <select>
                        $options = "";
                        $option  = "";
                        $found   = false;
                        $tNoTemplate = __( "no_template" );
                        if ( $page["tpl"] === "" ) {
                            $options .= "<option value selected2>{$tNoTemplate}</option>";
                            $found    = true;
                        } else {
                            $options .= "<option value>{$tNoTemplate}</option>";
                        }
                        foreach( $telplates_list as $tpl ) {
                            if ( $tpl === $page["tpl"] ) {
                                $options .= "<option value='{$tpl}' selected2>{$tpl}</option>";
                                $found    = true;
                                $option   = $tpl;
                            } else {
                                $options .= "<option value='{$tpl}'>{$tpl}</option>";
                            }
                        }
                        if ( ! $found ) {
                            $options .= "<option value='{$page['tpl']}' selected2>{$page['tpl']}</option>";
                        }
                        
                        // files
                        $farr = array();
                        foreach ( glob( "{$cms['site_dir']}/uploads/{$page['id']}/*", GLOB_NOSORT ) as $path ) {
                            if ( is_file( $path ) ) {
                                $farr[] = array( "path" => $path, "sort" => filemtime( $path ) );
                            }
                        }
                        cms_asort( $farr );

                        $flist = "";
                        foreach ( $farr as $f ) {
                            // cut path
                            $path_name = str_replace( $cms["site_dir"], "", $f["path"] );
                            $name      = preg_replace( "/.*\//", "", $path_name );
                            $ext       = strtolower( preg_replace( "/.*\./", ".", $path_name ) ); // .jpg
                            // icon file
                            if ( file_exists( "{$cms['cms_dir']}/img/icon{$ext}.svg" ) ) {
                                $icon = "/img/icon{$ext}.svg";
                            } else {
                                $icon = "/img/icon.default.svg";
                            }
                            // no need icon for image
                            switch ( $ext ) {
                                case ".webp":
                                case ".tiff":
                                case ".jpeg":
                                case ".jpg":
                                case ".png":
                                case ".svg":
                                case ".gif":
                                case ".bmp":
                                case ".ico":
                                    $size = getimagesize( $f["path"] );
                                    if ( ! empty( $size[3] ) ) {
                                        $size = $size[3];
                                    } else {
                                        $size = "";
                                    }
                                    $flist = "<div class=file-block><div class=media-name>{$name}</div><img src='{$path_name}' data-src='{$path_name}' {$size}><input type=checkbox><div class=ext>{$ext}</div></div>{$flist}";
                                break;
                                
                                default:
                                    $flist = "<div class=file-block><div class=media-name>{$name}</div><img src='{$icon}' data-src='{$path_name}'><input type=checkbox><div class=ext>{$ext}</div></div>{$flist}";
                                break;
                            }
                        }
                        echo( json_encode( array(
                            "result"  => "ok",
                            "page"    => $page,
                            "flist"   => $flist,
                            "date"    => $date,
                            "time"    => $time,
                            "options" => $options,
                            "option"  => $option,
                        ) ) );
                        return;
                    }
                }
            break;

            case "del_files":
                foreach ( $_POST["flist"] as $path_name ) {
                    $f = $cms["site_dir"] . $path_name;
                    if ( is_file( $f ) ) {
                        unlink( $f );
                    }
                }
                @rmdir( dirname( $f ) ); // delete folder if empty
                echo( json_encode( array(
                    "info_text"  => __( "files_deleted" ),
                    "info_class" => "info-success",
                    "info_time"  => 5000,
                ) ) );
                return;
            break;

            case "get_pages_list":
                echo( json_encode( cms_pages_get_pages_list() ) );
                return;
            break;

            case "del_pages":
                // Clear cache
                if ( function_exists( "cms_clear_cache" ) ) {
                    cms_clear_cache();
                }
                foreach( $_POST["ids"] as $id ) {
                    if ( $id = (int) $id ) {

                        // Delete page from base
                        mysqli_query( $cms["base"], "DELETE FROM `pages` WHERE `id`={$id}" );

                        // Delete page files
                        foreach ( glob( "{$cms['site_dir']}/uploads/{$id}/*", GLOB_NOSORT ) as $f ) {
                            if ( is_file( $f ) ) {
                                unlink( $f );
                            }
                        }
                        @rmdir( dirname( $f ) ); // delete folder if empty

                    }
                }

                // update sitemap
                if ( function_exists( "cms_sitemap_update" ) ) {
                    cms_sitemap_update();
                }

                echo( json_encode( array(
                    "info_text"  => __( "pages_deleted" ),
                    "info_class" => "info-success",
                    "info_time"  => 5000,
                ) ) );
                return;
            break;

            case "upload_files":
                $id   = (int) $_POST["id"];
                $path = "/uploads/{$id}";
                $dir  = $cms["site_dir"] . $path;
                // create dir if not exists
                if ( ! is_dir( $dir ) && ! mkdir( $dir, 0777, true ) ) {
                    echo( json_encode( array(
                        "info_text"  => __( "error_create_folder" ) . " " . $dir,
                        "info_class" => "info-error",
                        "info_time"  => 5000,
                    ) ) );
                    return;
                }
                $flist = "";
                $success = true;
                foreach ( $_FILES["myfile"]["name"] as $n => $name ) {
                    if ( $_FILES["myfile"]["error"][$n] ) {
                        $success = false;
                        $text = __( "error_upload_file" ) . " " . $name;
                        break;
                    }
                    $ext  = strtolower( preg_replace( "/.*\./", ".", $name ) );
                    $name = substr( $name, 0, strlen( $name ) - strlen( $ext ) );
                    $name = strtolower( cms_translit_file( $name ) );
                    $name = "{$name}{$ext}";
                    if ( ! move_uploaded_file( $_FILES["myfile"]["tmp_name"][$n], "{$dir}/{$name}" ) ) {
                        $success = false;
                        $text = __( "file_move_error" ) . " {$dir}/{$name}";
                        break;
                    }
                    if ( file_exists( "{$cms['cms_dir']}/img/icon{$ext}.svg" ) ) {
                        $icon = "/img/icon{$ext}.svg";
                    } else {
                        $icon = "/img/icon.default.svg";
                    }
                    if ( $success ) {
                        switch ( $ext ) {
                            case ".webp":
                            case ".tiff":
                            case ".jpeg":
                            case ".jpg":
                            case ".png":
                            case ".svg":
                            case ".gif":
                            case ".bmp":
                            case ".ico":
                                $size = getimagesize( "{$cms['site_dir']}{$path}/{$name}" );
                                if ( ! empty( $size[3] ) ) {
                                    $size = $size[3];
                                } else {
                                    $size = "";
                                }
                                $upd = time();
                                $flist .= "<div class='file-block'><div class=media-name>{$name}</div><img src='{$path}/{$name}?upd={$upd}' data-src='{$path}/{$name}' {$size}><input type=checkbox><div class=ext>{$ext}</div></div>";
                            break;
                            
                            default:
                                $flist .= "<div class='file-block'><div class=media-name>{$name}</div><img src='{$icon}' data-src='{$path}/{$name}'><input type=checkbox><div class=ext>{$ext}</div></div>";
                            break;
                        }
                        
                    }
                }

                if ( $success ) {
                    $text = __( "files_uploaded" );
                    echo( json_encode( array(
                        "info_text"  => $text,
                        "info_class" => "info-success",
                        "info_time"  => 5000,
                        "flist"      => $flist,
                    ) ) );
                    return;
                } else {
                    echo( json_encode( array(
                        "info_text"  => $text,
                        "info_class" => "info-error",
                        "info_time"  => 5000,
                    ) ) );
                    return;
                }
            break;
        }
    }
}

// Create pages list
// $_POST["where"] = "id=123";
// $_POST["limit"] = "LIMIT 1000";
// $_POST["search"] = "test";
// $_COOKIE["pages_pager"] = 10;
function cms_pages_get_pages_list() {
    global $cms;

    if ( empty( $cms["base"] ) ) {
        echo( json_encode( array(
            "no_database"  => "<span class=no-database>" . __( "no_connect_db" ) . "</span>",
        ) ) );
        return;
    }
    
    $telplates_list = array();
    $expr = "{$cms['cms_dir']}/{$cms['config']['template.mod.php']['template']}/{$cms['template_prefix']}*{$cms['template_suffix']}";
    foreach ( glob( $expr ) as $tpl ) {
        $name = preg_replace( "/.+\/{$cms['template_prefix']}(.+)" . str_replace( '.', '\.', $cms["template_suffix"] ) . "/", "$1", $tpl );
        array_push( $telplates_list, $name );
    }

    if ( ! empty( $_POST["where"] ) ) {
        $where = $_POST["where"];
    } else {
        $where = "1";
    }

    if ( empty( $_COOKIE["pages_pager"] ) ) {
        $pager = 100;
    } else {
        $pager = (int) $_COOKIE["pages_pager"];
    }

    if ( empty( $_POST["offset"] ) ) {
        $_POST["offset"] = 0;
    }
    $offset = $_POST["offset"] * $pager;

    if ( ! empty( $_POST["limit"] ) ) {
        $limit = $_POST["limit"];
    } else {
        $limit = "LIMIT {$offset}, {$pager}";
    }

    // For var count only
    if ( ! empty( $_POST["search"] ) ) {
        $s = preg_replace( "/\s/u", "", $_POST["search"] );
        $s = preg_split( '//u', $s, -1, PREG_SPLIT_NO_EMPTY );
        foreach( $s as $n => $ch ) {
            $s[$n] = mysqli_real_escape_string( $cms["base"], $ch );
        }
        $s = implode( "%", $s );
        $search = "(title LIKE '%{$s}%' OR url LIKE '%{$s}%' OR tpl LIKE '%{$s}%')";
        $q = "SELECT COUNT(*) FROM `pages` WHERE {$search}";
    } else {
        $search = "1";
        $q = "SELECT COUNT(*) FROM `pages`";
    }
    // count pages
    $res = mysqli_query( $cms["base"], $q );
    $res = mysqli_fetch_assoc( $res );
    $count = $res["COUNT(*)"];

    $pages = array();
    $start = microtime( true );
    $overload = false;
    $q = "SELECT `id`, `pin`, `title`, `seo_title`, `description`, `created`, `url`, `tpl`, `tags` FROM `pages` WHERE {$search} AND {$where} ORDER BY ( `id` + `pin` * 1000000000 ) DESC {$limit}";
    if ( $res = mysqli_query( $cms["base"], $q ) ) {

        $tTitle           = __( "title" );
        $tDescription     = __( "description" );
        $tSeoTitle        = __( "seo_title" );
        $tTemplate        = __( "template" );
        $tNoTemplate      = __( "no_template" );
        $tPublished       = __( "published" );
        $tPlanned         = __( "planned" );
        $tDate            = __( "date" );
        $tTime            = __( "time" );
        $tSave            = __( "save" );
        $tProperties      = __( "properties" );
        $tEdit            = __( "edit" );
        
        while ( $page = mysqli_fetch_assoc( $res ) ) {
            if ( $page["created"] === "0000-00-00 00:00:00" ) {
                $created = __( "no_date" );
                $date    = "";
                $time    = "";
                $published = $tPublished;
                $date_class = "zero";
            } else {
                $time = strtotime( $page["created"] );
                if ( time() >= $time ) {
                    $published = $tPublished;
                    $date_class     = "published";
                } else {
                    $published = $tPlanned;
                    $date_class     = "planned";
                }
                $created = date( "d.m.Y", $time ) . "<br>" . date( "H:i", $time );
                $date    = date( "Y-m-d", $time );
                $time    = date( "H:i", $time );
            }

            // <option> for <select>
            $options = "";
            $found   = false;
            if ( $page["tpl"] === "" ) {
                $options .= "<option value selected2>{$tNoTemplate}</option>";
                $found    = true;
            } else {
                $options .= "<option value>{$tNoTemplate}</option>";
            }
            foreach( $telplates_list as $tpl ) {
                if ( $tpl === $page["tpl"] ) {
                    $options .= "<option value='{$tpl}' selected2>{$tpl}</option>";
                    $found    = true;
                } else {
                    $options .= "<option value='{$tpl}'>{$tpl}</option>";
                }
            }
            if ( ! $found ) {
                $options .= "<option value='{$page['tpl']}' selected>{$page['tpl']}</option>";
            }
            if ( empty( $page["tpl"] ) ) {
                $tpl = $tNoTemplate;
            } else {
                $tpl = $page["tpl"];
            }

            $html = "
<div data-id={$page['id']} data-pin={$page['pin']}>

    <a class=page-name href='{$page['url']}' target=_blank title='id={$page['id']}'>{$page['title']}</a>
    <div class=pin></div>
    <div class=page-buttons>
        <div class=page-edit-btn>{$tEdit}</div>
        <div class=page-prop-btn>{$tProperties}</div>
        <div class=page-prop-save-btn>{$tSave}</div>
    </div>
    <div class='page-date {$date_class}'>{$created}</div>
    <input type=checkbox>
    
    <div class=page-prop>

        <div class=page-title>{$tTitle}:</div>
        <input name=title type=text value='{$page['title']}'>

        <div class=url>URL:</div>
        <input name=url type=text value='{$page['url']}'>

        <div class=seo-title>{$tSeoTitle}:</div>
        <input name=seo_title type=text value='{$page['seo_title']}'>

        <div class=description>{$tDescription}</div>
        <textarea name=description rows=3>{$page['description']}</textarea>

        <div class=template>{$tTemplate}:</div>
        <div class=template-select-grid>
            <div class=field-select data-template='{$page['tpl']}' data-old-template='{$page['tpl']}'>{$tpl}</div>
            <div class=field-options>
                {$options}
            </div>
        </div>

        <div class=date>{$tDate}:</div>
        <input name=date type=date value='{$date}'>
        <div class=time>{$tTime}:</div>
        <input name=time type=time value='{$time}'>

        <div class=tags>" . __( "tags" ) . ":</div>
        <textarea name=tags rows=3>" . htmlspecialchars( $page["tags"] ) . "</textarea>
    </div>

</div>";
            array_push( $pages, array( "id" => (int) $page["id"], "html" => $html ) );
            if ( microtime( true ) - $start > 1 ) {
                $overload = true;
                break;
            }
        }
    }
    return array(
        "pages"            => $pages, 
        "offset"           => $_POST["offset"], 
        "count"            => $count, 
        "overload"         => $overload,
    );
}


function cms_pages_query() {
    global $cms;
    
    // Skip SQL query if file exists
    if ( is_file( $cms["cms_file"] ) ) {
        return;
    }
    
    if ( empty( $cms["base"] ) ) return; // fix 500 error
    $url = mysqli_real_escape_string( $cms["base"], $cms["url"]["path"] );
    if ( $res = mysqli_query( $cms["base"], "SELECT * FROM pages WHERE url!='' AND ( url = '{$url}' OR CONCAT( url, '/' ) = '{$url}' OR url = CONCAT( '{$url}', '/' ) )" ) ) {
        if ( $page = mysqli_fetch_assoc( $res ) ) {
            $cms["page"] = $page;
            if ( $cms["page"]["created"] <= date( "Y-m-d H:i:s" ) ) {
                $cms["status"] = "200";
            } else {
                $cms["status"] = "404"; // disable write to disk but echo if admin
            }
        }
    }
}

function cms_pages_create_tables() {
    global $cms;

    mysqli_query( $cms["base"], "
    CREATE TABLE IF NOT EXISTS `pages` (
        `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `pin` tinyint(1) NOT NULL DEFAULT 1,
        `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
        `modified` double NOT NULL DEFAULT 0,
        `tpl` varchar(64) DEFAULT NULL,
        `title` varchar(255) DEFAULT NULL,
        `seo_title` varchar(255) DEFAULT NULL,
        `text` longtext DEFAULT NULL,
        `url` varchar(255) DEFAULT NULL,
        `keywords` varchar(1024) NOT NULL DEFAULT '',
        `description` varchar(2048) NOT NULL DEFAULT '',
        `tags` varchar(2048) NOT NULL DEFAULT '',
        UNIQUE KEY `id` (`id`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

}

function cms_pages_admin() {
    global $cms;

    // Read template settings
    $settings_file = "{$cms['cms_dir']}/{$cms['template']}/settings.php";
    if ( file_exists( $settings_file ) ) {
        include( $settings_file );
    }
    
    $tMaxUploadSize  = __( "max_size" ) . number_format( file_upload_max_size(), 0, ".", " " ) . __( "bytes" );

    $help_file = "/{$cms['config']['template.mod.php']['template']}/instruction.{$cms['config']['locale']}.html";
    if ( file_exists( $cms["cms_dir"] . $help_file ) ) {
        $help = "<a target=_blank href='{$help_file}'>" . __( "instruction" ) . "</a>";
    } else {
        $help = "";
    }

    $files_panel = "
<div class=mediateka-grid>
    <div class=mediateka-files-hscroll>
        <div class=mediateka-files-grid>
            
        </div>
    </div>
    
    <div class=mediateka-buttons>
        <div class=upload-files>
            <input id=upload-btn type=file name='myfile[]' multiple class=inputfile>
            <label for=upload-btn title='{$tMaxUploadSize}'>" . __( "upload" ) . "</label>
        </div>
        <div class=link-file>
            <span class=link-file-tag></span>
            <span class=link-file-copy-btn>" . __( "copy" ) . "</span>
        </div>
        <div class='del-uploaded-files disabled'>" . __( "delete" ) . "</div>
    </div>
    
</div>";

    $buttons  = "<div class=save-page-button>" . __( "save" ) . "</div>";
    $buttons .= "<div class=open-properties>" . __( "properties" ) . "</div>";
    $buttons .= "<div class=open-mediateka>" . __( "mediateka" ) . "</div>";
    $buttons .= "<div class=tags-helper>" . __( "tags" ) . "</div>";
    $buttons .= "<div class=codemirror-replace>" . __( "replace" ) . "</div>";
    $buttons .= $help;

    $page = "
<div class=main-header>
    
    <div class=search-wrapper>
        <input class=page-search type=text placeholder='" . __( "search" ) . "' autocomplete=off>
        <div class=reset></div>
        <button class=page-search-button></button>
    </div>

    <div class=add-page-btn>
        <div class=x1></div>
        <div class=x2></div>
    </div>
    
    <div> </div>

    <a class=del-pages-btn>
        <svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 777 777'><defs><style>.a-trash,.b-trash,.c-trash{fill:none;}.a-trash,.b-trash{stroke-miterlimit:10;stroke-width:33px;}.a{stroke-linecap:round;}</style></defs><line class='a-trash' x1='207.22' y1='260.51' x2='569.78' y2='260.51'/><line class='a-trash' x1='315.1' y1='333.69' x2='315.1' y2='501.19'/><line class='a-trash' x1='390.76' y1='333.69' x2='390.76' y2='501.19'/><line class='a-trash' x1='461.75' y1='333.69' x2='461.75' y2='501.19'/><path class='b-trash' d='M539.89,261.38V550.17a37.14,37.14,0,0,1-37.17,37.17H274.28a37.14,37.14,0,0,1-37.17-37.17V261.38'/><path class='b-trash' d='M321.22,261.38V207.73a19.38,19.38,0,0,1,19.39-19.38H446.88a19.51,19.51,0,0,1,19.54,19.38h0v53.65'/><rect class='c-trash' width='777' height='777'/></svg>
    </a>
    
</div>

<div class=main-main>
    
    <div class=pages-grid>
    </div>
    
</div>

<div class=main-footer>
    <div class=pager></div>
    <div class=counters><input class=loaded value=0 autocomplete=off> <span>" . __( "of" ) . "</span> <span class=count>0</span></div>
</div>

<div class='page-editor-bg hidden'>
    <div class=page-editor-grid>

        <div class=page-editor-header>
            <div class=close-page-button></div>
            <a data-page-title class=page-editor-title target=_blank></a>
        </div>

        <div class=page-editor-buttons>{$buttons}</div>
        
        <div class='page-properties hidden'>
            <div class=page-title>" . __( "title" ) . ":</div>
            <input name=title type=text>

            <div class=url>URL:</div>
            <input name=url type=text>

            <div class=seo-title>" . __( "seo_title" ) . ":</div>
            <input name=seo_title type=text>

            <div class=description>{$tDescription}</div>
            <textarea name=description rows=3></textarea>

            <div class=template>" . __( "template" ) . ":</div>
            <div class=template-select-grid>
                <div class=field-select data-template data-old-template></div>
                <div class=field-options>
                    
                </div>
            </div>

            <div class=date>" . __( "date" ) . ":</div>
            <input name=date type=date>
            <div class=time>" . __( "time" ) . ":</div>
            <input name=time type=time>

            <div class=tags>" . __( "tags" ) . ":</div>
            <textarea name=tags rows=3></textarea>
        </div>

        <div class='page-editor-panel hidden'>
            <div class=upload-progress></div>
            {$files_panel}
        </div>

        <div class=page-editor>
            <textarea data-modified class=coffee-editorpage-area name=add_editorpage></textarea>
        </div>

        <div class=tags>
            <div class=tags-grid>
                <div data-type=wrap data-otag='<h1>' data-ctag='</h1>' data-len=4><span>" . __( "h1" ) . "</span><span class=tag>&lt;h1></span></div>
                <div data-type=wrap data-otag='<p>' data-ctag='</p>' data-len=3><span>" . __( "p" ) . "</span><span class=tag>&lt;p></span></div>
                <div data-type=wrap data-otag='<div>' data-ctag='</div>' data-len=5><span>" . __( "div" ) . "</span><span class=tag>&lt;div></span></div>
                <div data-type=wrap data-otag='<a href=\"\" target=_blank>' data-ctag='</a>' data-len=25><span>" . __( "link" ) . "</span><span class=tag>&lt;a></span></div>
                <div data-type=wrap data-otag='<pre><code>' data-ctag='</code></pre>' data-len=11><span>" . __( "code" ) . "</span><span class=tag>&lt;pre>&lt;code></span></div>
                <div data-type=wrap data-otag='<span>' data-ctag='</span>' data-len=6><span>" . __( "span" ) . "</span><span class=tag>&lt;span></span></div>
                <div data-type=wrap data-otag='<blockquote>' data-ctag='</blockquote>' data-len=12><span>" . __( "cite" ) . "</span><span class=tag>&lt;blockquote></span></div>
                <div data-type=wrap data-otag='<ul>\n  <li>' data-ctag='</li>\n</ul>' data-ch=6 data-line=1><span>" . __( "ul" ) . "</span><span class=tag>&lt;ul></span></div>
                <div data-type=wrap data-otag='<ol>\n  <li>' data-ctag='</li>\n</ol>' data-ch=6 data-line=1><span>" . __( "ol" ) . "</span><span class=tag>&lt;ol></span></div>
                <div data-type=wrap data-otag='<li>' data-ctag='</li>' data-len=4><span>" . __( "li" ) . "</span><span class=tag>&lt;li></span></div>
                <div data-type=wrap data-otag='<!-- ' data-ctag=' -->' data-len=5><span class=tag>&lt;!--</span>&nbsp;<span>" . __( "comment" ) . "</span>&nbsp;<span class=tag>--></span></div>
            </div>
        </div>
    </div>
</div>";

    // Create menu item if not exists
    if ( empty( $cms["config"]["pages.mod.php"]["menu"]["pages"] ) ) {
        $cms["config"]["pages.mod.php"]["menu"]["pages"] = array(
            "title"    => "module_name",
            "sort"     => 10,
            "section"  => "content",
        );
        cms_save_config();
    }

    $cms["admin_pages"]["pages"] = $page;
}
