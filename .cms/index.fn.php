<?php

function cms_do_stage( $stage = "" ) {
    global $cms;
    
    if ( empty( $cms["stages"][$stage] ) || ! empty( $cms["stages"][$stage]["disabled"] ) ) {
        return;
    }
    
    ksort( $cms["stages"][$stage] );
    
    foreach ( $cms["stages"][$stage] as $nice => $value ) {
        // skip non numeric keys
        if ( is_array( $cms["stages"][$stage][$nice] ) ) {
            foreach ( $cms["stages"][$stage][$nice] as $function => $enabled ) {
                if ( $enabled ) {
                    $function();
                }
            }
        }
    }
}
/* Stage example
array (
    'query' => array (
        'disabled' => false,
        'next' => 'template',
        5 => array (
            'cms_base_connect' => true, ),
        10 => array (
            'cms_pages_query' => true, ),
    ),
    'template' => array (
        'disabled' => false,
        'next' => 'echo',
        10 => array (
            'cms_template_template' => true, ),
    ),
    'echo' => array (
        'disabled' => false,
        'next' => 'write',
        10 => array (
            'cms_template_echo' => true, ),
    ),
    'write' => array (
        'disabled' => false,
        'next' => '',
        10 => array (
            'cms_template_write' => true, ),
    ),
    ...
*/

function cms_save_config() {
    global $cms;
    @$r = file_put_contents( $cms["config_file"], '<?php
$cms["config"] = ' . var_export( $cms["config"], true) . ";\n", LOCK_EX );
    return $r;
}

// Create new stage
function cms_add_stage( $stage, $next = "" ) {
    global $cms;
    $cms["stages"][$stage]["next"] = $next;
}

// Add function to selected stage with default prioritet = 10
function cms_add_function( $stage, $function, $nice = 10 ) {
    global $cms;
    $cms["stages"][$stage][$nice][$function] = true;
    // fix warning
    if ( ! isset( $cms["stages"][$stage]["next"] ) ) {
        $cms["stages"][$stage]["next"] = "";
    }
}

function cms_email_callback2( $matches ) {
    global $email_images;
    $n = count( $email_images );
    $email_images[$n] = array( "ext" => $matches[1], "base64" => $matches[2] );
    return "cid:{$n}.{$matches[1]}";
}

function cms_email_callback1( $matches ) {
    $r2 = preg_replace_callback( "/data:image\/(\w+);base64,(.+)/", "cms_email_callback2", $matches[1] );
    return "<img src=\"$r2\"";
}

// $data["to_name"]
// $data["to_email"]
// $data["subject"]
// $data["email_body"]
// $data["from_name"]
// $data["from_email"]
// $data["reply_name"]
// $data["reply_to"]
// $data["files"]
// $data["type"] ( text/html or text/plain (default) )
// $data["sender"] if empty, then noreply@$_SERVER["SERVER_NAME"]
// $data["bcc"]
// return "" if success of "error:..." if error
function cms_email( $data ) {
    global $email_images;

    $to_name = @trim( $data["to_name"] );
    if ( ! empty( $to_name ) ) {
        $to = "=?utf-8?b?" . base64_encode( $to_name ) . "?= <" . $data["to_email"] . ">";
    } else {
        $to = $data["to_email"];
    }

    $subject = "=?utf-8?b?" . base64_encode( $data["subject"] ) . "?=";

    $from_name = @trim( $data["from_name"] );
    if ( ! empty( $from_name ) ) {
        $from = "=?utf-8?b?" . base64_encode( $from_name ) . "?= <" . $data["from_email"] . ">";
    } else {
        $from = $data["from_email"];
    }

    if ( empty( $data["reply_to"] ) ) {
        $reply = $from;
    } else {
        $reply = $data["reply_to"];
        if ( ! empty( $data["reply_name"] ) ) {
            $reply = "=?utf-8?b?" . base64_encode( $data["reply_name"] ) . "?= <" . $data["reply_to"] . ">";
        }
    }

    $boundary = md5( uniqid( time() - 25 ) );
    $boundary_id = md5( uniqid( time() ) );

    if ( empty( $data["sender"] ) ) {
        $sender = "noreply@{$_SERVER['SERVER_NAME']}";
    } else {
        $sender = $data["sender"];
    }

    $headers = "Sender: {$sender}\r\nFrom: {$from}\r\nReply-To: {$reply}\r\nContent-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

    if ( ! empty( $data["bcc"] ) ) {
        $headers .= "Bcc: {$data['bcc']}\r\n";
    }

    $email_images = array();
    $body2 = preg_replace_callback( '/<img +src="([^"]+)"/', "cms_email_callback1", $data["email_body"] );

    if ( empty( $data["type"] ) ) {
        $type = "text/plain";
    } else {
        $type = $data["type"];
    }

    $body_all  = "--{$boundary}\r\nContent-Type: multipart/related; boundary=\"{$boundary_id}\"\r\n\r\n";
    $body_all .= "--{$boundary_id}\r\nContent-Type: {$type}; charset=utf-8;\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split( base64_encode( $body2 ) ) . "\r\n\r\n";

    foreach( $email_images as $ind => $image ) {
        $body_all .= "--{$boundary_id}\r\nContent-Type: image/{$image['ext']}; name=\"{$ind}.{$image['ext']}\"\r\nContent-ID: <{$ind}.{$image['ext']}>\r\nContent-Disposition: inline; filename=\"{$ind}.{$image['ext']}\"\r\nContent-transfer-encoding: base64\r\n\r\n" . chunk_split( $image["base64"] ) . "\r\n\r\n";
    }

    if ( isset( $data["files"] ) && is_array( $data["files"] ) )
    foreach( $data["files"] as $file ) {
        $mime = mime_content_type( $file );
        $name = preg_replace( "/.*\//u", "", $file );
        $body_all .= "--{$boundary_id}\r\nContent-Type: {$mime}; name=\"{$name}\"\r\nContent-Transfer-Encoding: base64\r\nContent-Disposition: attachment; filename=\"{$name}\"\r\n\r\n" . chunk_split( base64_encode( file_get_contents( $file ) ) ) . "\r\n\r\n";
    }

    $body_all .= "--{$boundary_id}--\r\n\r\n";
    $body_all .= "--{$boundary}--\r\n\r\n";

    if ( mail( $to, $subject, $body_all, $headers, "-f{$data['from_email']}" ) ) {
        return "";
    } else {
        return "error mail() function";
    }
}

// $data["to_name"]
// $data["to_email"]
// $data["subject"]
// $data["email_body"]
// $data["from_name"]
// $data["from_email"]
// $data["reply_name"]
// $data["reply_to"]
// $data["files"]
// $data["type"] ( text/plain (default) or text/html )
// $data["sender"] if empty, then noreply@$_SERVER["SERVER_NAME"]
// $data["bcc"]
// $data["smtp_host"]
// $data["smtp_port"]
// $data["smtp_login"]
// $data["smtp_password"]
// return "" if success of "error:..." if error
function cms_smtp_email( $data ) {
    global $email_images, $cms;

    $to_name = @trim( $data["to_name"] );
    if ( ! empty( $to_name ) ) {
        $to = "=?utf-8?b?" . base64_encode( $to_name ) . "?= <" . $data["to_email"] . ">";
    } else {
        $to = $data["to_email"];
    }

    $from_name = @trim( $data["from_name"] );
    if ( ! empty( $from_name ) ) {
        $from = "=?utf-8?b?" . base64_encode( $from_name ) . "?= <" . $data["from_email"] . ">";
    } else {
        $from = $data["from_email"];
    }

    if ( empty( $data["reply_to"] ) ) {
        $reply = $from;
    } else {
        $reply = $data["reply_to"];
        if ( ! empty( $data["reply_name"] ) ) {
            $reply = "=?utf-8?b?" . base64_encode( $data["reply_name"] ) . "?= <" . $data["reply_to"] . ">";
        }
    }

    $boundary = md5( uniqid( time() - 25 ) );
    $boundary_id = md5( uniqid( time() ) );

    if ( empty( $data["sender"] ) ) {
        $sender = $data["from_email"];
    } else {
        $sender = $data["sender"];
    }
    
    if ( empty( $data["return_path"] ) ) {
        $return_path = $data["from_email"];
    } else {
        $return_path = $data["return_path"];
    }


    /* start headers */
    $SEND  = "Date: " . date( "r" ) . "\r\n";
    $SEND .= "Sender: {$sender}\r\n";
    $SEND .= "Return-Path: {$return_path}\r\n";
    $SEND .= "Reply-To: {$reply}\r\n";
    $SEND .= "From: {$from}\r\n";
    $SEND .= "To: {$to}\r\n";
    if ( ! empty( $data["bcc"] ) ) { $SEND .= "Bcc: {$data['bcc']}\r\n"; }
	$SEND .= "Subject: =?utf-8?b?" . base64_encode( $data["subject"] ) . "?=\r\n";
    $SEND .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
    $SEND .= "\r\n";
    /* end headers */

    /* Move images to appendix */
    $email_images = array();
    $body2 = preg_replace_callback( '/<img +src="([^"]+)"/', "cms_email_callback1", $data["email_body"] );

    if ( empty( $data["type"] ) ) {
        $type = "text/plain";
    } else {
        $type = $data["type"];
    }

    $SEND .= "--{$boundary}\r\nContent-Type: multipart/related; boundary=\"{$boundary_id}\"\r\n\r\n";
    $SEND .= "--{$boundary_id}\r\nContent-Type: {$type}; charset=utf-8;\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split( base64_encode( $body2 ) ) . "\r\n\r\n";

    foreach( $email_images as $ind => $image ) {
        $SEND .= "--{$boundary_id}\r\n";
        $SEND .= "Content-Type: image/{$image['ext']}; name=\"{$ind}.{$image['ext']}\"\r\n";
        $SEND .= "Content-ID: <{$ind}.{$image['ext']}>\r\n";
        $SEND .= "Content-Disposition: inline; filename=\"{$ind}.{$image['ext']}\"\r\n";
        $SEND .= "Content-transfer-encoding: base64\r\n\r\n";
        $SEND .= chunk_split( $image['base64'] );
        $SEND .= "\r\n\r\n";
    }

    if ( isset( $data["files"] ) && is_array( $data["files"] ) ) {
        foreach( $data["files"] as $file ) {
            $mime = mime_content_type( $file );
            $name = preg_replace( "/.*\//u", "", $file );
            $SEND .= "--{$boundary_id}\r\n";
            $SEND .= "Content-Type: {$mime}; name=\"{$name}\"\r\n";
            $SEND .= "Content-Transfer-Encoding: base64\r\n";
            $SEND .= "Content-Disposition: attachment; filename=\"{$name}\"\r\n\r\n";
            $SEND .= chunk_split( base64_encode( file_get_contents( $file ) ) ) . "\r\n\r\n";
        }
    }

    $SEND .= "--{$boundary_id}--\r\n\r\n";
    $SEND .= "--{$boundary}--\r\n\r\n";

    $debug_file = "{$cms['cms_dir']}/smtp_debug.txt";
    cms_debug_prep_file( $debug_file );
    cms_debug( $debug_file, "\n---" );

    // Connect
    cms_debug( $debug_file, "fsockopen {$data['smtp_host']} {$data['smtp_port']}" );
    $socket = fsockopen( $data["smtp_host"], $data["smtp_port"], $errno, $errstr, 10 ); // 10 sec timeout
    if( $socket === false ) {
        cms_debug( $debug_file, "fsockopen error {$errno} {$errstr}" );
        return "{$errno} {$errstr}";
    }
    cms_debug( $debug_file, "fsockopen ok" );

    stream_set_timeout( $socket, 3 );
 
    // Receive data after connection
    $ok = false;
    while ( $r = fgets( $socket, 1000 ) ) {
        cms_debug( $debug_file, "SERVER: {$r}" );
        if ( substr( $r, 0, 3 ) === "220" ) { $ok = true; }
        if ( substr( $r, 3, 1 ) === " " ) { break; }
    }
	if ( $ok === false ) {
        return $r;
    }

    // HELLO
    cms_debug( $debug_file, "ME: EHLO {$_SERVER['SERVER_NAME']}" );
    fputs( $socket, "EHLO {$_SERVER['SERVER_NAME']}\r\n");

    // Receive data after HELLO
    $ok = false;
    while ( $r = fgets( $socket, 1000 ) ) {
        cms_debug( $debug_file, "SERVER: {$r}" );
        if ( preg_match( '/250.AUTH/', $r ) ) { $ok = true; }
        if ( substr( $r, 3, 1 ) === " " ) { break; }
    }
    if ( $ok === false ) {
        fclose( $socket );
        cms_debug( $debug_file, "The server does not greet us." );
		return $r."\nThe server does not greet us.";
    }
    
    // AUTH
    cms_debug( $debug_file, "ME: AUTH LOGIN" );
	fputs( $socket, "AUTH LOGIN\r\n" );

    // Receive data
    $ok = false;
    while ( $r = fgets( $socket, 1000 ) ) {
        cms_debug( $debug_file, "SERVER: {$r}" );
        if ( substr( $r, 0, 3 ) === "334" ) { $ok = true; }
        if ( substr( $r, 3, 1 ) === " " ) { break; }
    }
	if ( $ok === false ) {
		fclose( $socket );
        cms_debug( $debug_file, "Can't find the answer to the authorization request." );
		return "Can't find the answer to the authorization request.";
    }
    
    // Send login
    cms_debug( $debug_file, "ME: base64_encode( smtp_login )" );
	fputs( $socket, base64_encode( $data["smtp_login"] ) . "\r\n" );

    // Receive data
    $ok = false;
    while ( $r = fgets( $socket, 1000 ) ) {
        cms_debug( $debug_file, "SERVER: {$r}" );
        if ( substr( $r, 0, 3 ) === "334" ) { $ok = true; }
        if ( substr( $r, 3, 1 ) === " " ) { break; }
    }
	if ( $ok === false ) {
		fclose( $socket );
        cms_debug( $debug_file, "The authorization login was not accepted by the server." );
		return "The authorization login was not accepted by the server.";
    }

    // Send password
    cms_debug( $debug_file, "ME: base64_encode( smtp_password )" );
	fputs( $socket, base64_encode( $data["smtp_password"] ) . "\r\n" );

    // Receive data
    $ok = false;
    while ( $r = fgets( $socket, 1000 ) ) {
        cms_debug( $debug_file, "SERVER: {$r}" );
        if ( substr( $r, 0, 3 ) === "235" ) { $ok = true; }
        if ( substr( $r, 3, 1 ) === " " ) { break; }
    }
	if ( $ok === false ) {
		fclose( $socket );
        cms_debug( $debug_file, "The password was not accepted by the server as correct." );
		return "The password was not accepted by the server as correct.";
    }

    // Send FROM
    cms_debug( $debug_file, "ME: MAIL FROM: <{$data['from_email']}>" );
	fputs( $socket, "MAIL FROM: <{$data['from_email']}>\r\n" );

    // Receive data
    $ok = false;
    while ( $r = fgets( $socket, 1000 ) ) {
        cms_debug( $debug_file, "SERVER: {$r}" );
        if ( substr( $r, 0, 3 ) === "250" ) { $ok = true; }
        if ( substr( $r, 3, 1 ) === " " ) { break; }
    }
	if ( $ok === false ) {
		fclose( $socket );
        cms_debug( $debug_file, "Can't send command MAIL FROM" );
		return "Can't send command MAIL FROM";
    }

    // Send recipient
    cms_debug( $debug_file, "ME: RCPT TO: <{$data['to_email']}>" );
	fputs( $socket, "RCPT TO: <{$data['to_email']}>\r\n" );

    // Receive data
    $ok = false;
    while ( $r = fgets( $socket, 1000 ) ) {
        cms_debug( $debug_file, "SERVER: {$r}" );
        if ( substr( $r, 0, 3 ) === "250" ) { $ok = true; }
        if ( substr( $r, 3, 1 ) === " " ) { break; }
    }
	if ( $ok === false ) {
		fclose( $socket );
        cms_debug( $debug_file, "Can't send RCPT TO command" );
		return "Can't send RCPT TO command";
    }

    // BCC
    if ( ! empty( $data["bcc"] ) ) {
        cms_debug( $debug_file, "ME (bcc): RCPT TO: <{$data['bcc']}>" );
        fputs( $socket, "RCPT TO: {$data['bcc']}\r\n" );

        // Receive data
        $ok = false;
        while ( $r = fgets( $socket, 1000 ) ) {
            cms_debug( $debug_file, "SERVER: {$r}" );
            if ( substr( $r, 0, 3 ) === "250" ) { $ok = true; }
            if ( substr( $r, 3, 1 ) === " " ) { break; }
        }
        if ( $ok === false ) {
            fclose( $socket );
            cms_debug( $debug_file, "Can't send RCPT TO (bcc) command" );
            return "Can't send RCPT TO (bcc) command";
        }
    }
    
    // Email body command
    cms_debug( $debug_file, "ME: DATA" );
	fputs( $socket, "DATA\r\n" );

    // Receive data
    $ok = false;
    while ( $r = fgets( $socket, 1000 ) ) {
        cms_debug( $debug_file, "SERVER: {$r}" );
        if ( substr( $r, 0, 3 ) === "354" ) { $ok = true; }
        if ( substr( $r, 3, 1 ) === " " ) { break; }
    }
    if ( $ok === false ) {
        fclose( $socket );
        cms_debug( $debug_file, "Can't send DATA command" );
        return "Can't send DATA command";
    }

    // Send email body
    cms_debug( $debug_file, "ME SEND DATA" );
	fputs( $socket, "{$SEND}\r\n.\r\n" );

    // Receive data
    $ok = false;
    while ( $r = fgets( $socket, 1000 ) ) {
        cms_debug( $debug_file, "SERVER: {$r}" );
        if ( substr( $r, 0, 3 ) === "250" ) { $ok = true; }
        if ( substr( $r, 3, 1 ) === " " ) { break; }
    }
    if ( $ok === false ) {
        fclose( $socket );
        cms_debug( $debug_file, "I can not send the body of the letter." );
        return "I can not send the body of the letter.";
    }

    // Quit command
    cms_debug( $debug_file, "ME: QUIT\n" );
	fputs( $socket, "QUIT\r\n" );
	fclose( $socket );
	return "";
}

function cms_debug( $debug_file, $string ) {
    global $cms;
    if ( empty( $cms["config"]["debug"] ) ) {
        return;
    }
    if ( substr( $string, -1 ) !== "\n" ) { $string .= "\n"; }
    file_put_contents( $debug_file, $string, FILE_APPEND );
}

function cms_debug_prep_file( $debug_file ) {
    if ( ! file_exists( $debug_file ) || filesize( $debug_file ) > 16384 ) {
        file_put_contents( $debug_file, "" );
    }
}

function cms_readfile( $file, $headers = true ) {
    if ( file_exists( $file ) ) {
        /*if (ob_get_level()) {
            ob_end_clean();
        }*/
        if ( $headers ) {
            header( "Content-Description: File Transfer" );
            $mime = mime_content_type( $file );
            header( "Content-Type: {$mime}" );
            $basename = basename( $file );
            header( "Content-Disposition: attachment; filename=\"{$basename}\"" );
            header( "Content-Transfer-Encoding: binary" );
            header( "Expires: 0" );
            header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
            header( "Pragma: public" );
            $filesize = filesize( $file );
            header( "Content-Length: {$filesize}" );
            @ob_clean();
            flush();
        }
        if ( $fd = fopen( $file, "rb" ) ) {
            while ( !feof( $fd ) ) {
              print fread( $fd, 1024 );
            }
            return fclose( $fd );
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function __( $string, $module = "" ) {
    global $cms;
    if ( ! isset( $cms["config"] ) ) {
        error_log( 'Not set $cms["config"]' );
        return $string;
    }

    // Определяем вызвавший файл
    if ( $module === "" ) {
        $backtrace = debug_backtrace();
        $module = pathinfo( $backtrace[0]["file"], PATHINFO_BASENAME );
        // Для шаблона admin
        if ( $module === "html.php" ) {
            $module = "admin.mod.php";
        }
    }

    // Загрузка файла перевода
    if ( ! isset( $cms["lang"][$module][ $cms["config"]["locale"] ] ) ) {
        $file = $cms["cms_dir"] . "/lang/" . $cms["config"]["locale"] . "/" . $module;
        if ( file_exists( $file ) ) {
            include_once( $file );
        } else {
            $cms["lang"][$module][ $cms["config"]["locale"] ] = array();
        }
    }

    // Translate
    if ( isset( $cms["lang"][$module][ $cms["config"]["locale"] ][$string] ) ) {
        return $cms["lang"][$module][ $cms["config"]["locale"] ][$string];
    } else {
        // Чтобы узнать какие переводы отсутствуют, нужно включить отладку в confog.php
        //     $cms["config"]["debug"] = true;
        // Результаты буду в файле .cms/debug.log.php
        // Если известна локаль модуля и она не совпадает с текущей локалью цмс
        // То должен быть перевод
        if ( ! empty( $cms["config"]["debug"] ) && isset( $cms["modules"][$module]["locale"] ) && $cms["config"]["locale"] !== $cms["modules"][$module]["locale"] ) {
            if ( empty( $cms["debug"]["translate"][$string] ) ) {
                $cms["debug"]["translate"][$string] = "Нет перевода для [{$module}] \"{$string}\" на {$cms["config"]["locale"]}";
            }
        }
        return $string;
    }
}

function cms_translit( $string, $trim_dash = true ) {
    global $cms;
    $tr1 = strtr( $string, $cms["tr"] );
    $tr2 = strtr( $tr1, array( " " => "-" ) );
    $tr3 = preg_replace( "/[^-A-Za-z0-9_]+/u", "", $tr2 );
    if ( $trim_dash ) {
        $tr3 = trim( $tr3, "-_" );
    }
    return $tr3;
}

function cms_translit_file( $string ) {
    global $cms;
    $tr1 = strtr( $string, $cms["tr"] );
    $tr2 = strtr( $tr1, array( " " => "_" ) );
    $tr3 = preg_replace( "/[^-A-Za-z0-9_]+/u", "", $tr2 );
    return $tr3;
}

function cms_uasort( $a, $b ) {
    if ( ! isset( $a["sort"] ) || ! isset( $b["sort"] ) ) {
        return 0;
    }
    if ( $a["sort"] <= $b["sort"] ) {
        return -1;
    } else {
        return 1;
    }
}

function cms_asort( &$array ) {
    if ( ! is_array( $array ) ) return false;
    return uasort( $array, "cms_uasort" );
}

function recurse_rm( $src ) /* bool */ {
    if ( ! file_exists( $src ) ) {
        return false;
    }
    $dir = opendir( $src );
    while( false !== ( $file = readdir( $dir ) ) ) {
        if ( ( $file != "." ) && ( $file != ".." ) ) {
            if ( is_dir( "{$src}/{$file}" ) ) {
                recurse_rm( "{$src}/{$file}" );
            } else {
                if ( !unlink( "{$src}/{$file}" ) ) {
                    return false;
                }
            }
        }
    }
    closedir( $dir );
    return rmdir( $src );
}

function is_email( $str ) {
    return preg_match( "/^([a-zA-Z0-9_\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,9})(\]?)$/", $str );
}

// Returns a file size limit in bytes based on the PHP upload_max_filesize
// and post_max_size
function file_upload_max_size() {
    $max_size = -1;
  
    if ( $max_size < 0 ) {
        // Start with post_max_size.
        $post_max_size = parse_size( ini_get( "post_max_size" ) );
        if ( $post_max_size > 0 ) {
            $max_size = $post_max_size;
        }
    
        // If upload_max_size is less, then reduce. Except if upload_max_size is
        // zero, which indicates no limit.
        $upload_max = parse_size( ini_get( "upload_max_filesize" ) );
        if ( $upload_max > 0 && $upload_max < $max_size ) {
            $max_size = $upload_max;
        }
    }
    return $max_size;
}
  
function parse_size( $size ) {
    $unit = preg_replace( "/[^bkmgtpezy]/i", "", $size ); // Remove the non-unit characters from the size.
    $size = preg_replace( "/[^0-9\.]/", "", $size ); // Remove the non-numeric characters from the size.
    if ( $unit ) {
        // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
        return round( $size * pow( 1024, stripos( "bkmgtpezy", $unit[0] ) ) );
    } else {
        return round( $size );
    }
}