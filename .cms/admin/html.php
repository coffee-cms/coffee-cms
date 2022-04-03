<!doctype html>
<html lang="<?php echo $cms["config"]["lang"]; ?>" class="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<?php
if ( is_admin() ) {
    echo "<title>" . __( "Admin" ) . " {$cms['url']['host']}</title>";
} elseif ( empty( $cms["config"]["admin.mod.php"]["admin_login"] ) && 
           empty( $cms["config"]["admin.mod.php"]["admin_password"] ) ) {
    echo "<title>" . __( "Install" ) . " {$cms['url']['host']}</title>";
} else {
    echo "<title>" . __( "SignIn" ) . " {$cms['url']['host']}</title>";
} ?>
<link rel="icon" href="<?php echo $cms["base_path"]; ?>img/favicon.png">

<?php
$styles = array();
foreach( glob( $cms['cms_dir'].'/css/admin.*.*.css' ) as $file ) {
    preg_match( "/.*(\/admin\.(.+)\.(.+)\.css)/", $file, $m );
    echo "<link rel=stylesheet href='{$cms['base_path']}css{$m[1]}'>\n";
    array_push($styles,array($m[2],$m[3]));
}
echo "<link rel=stylesheet href='{$cms['base_path']}css/admin.css'>";

echo "<script>\nadmin_styles = " . json_encode( $styles ) . ";\n";
if ( ! empty( $cms["config"]["locale"] ) ) {
    $lang = json_encode( $cms["lang"] );
    $tr   = json_encode( $cms["tr"] );
    echo "cms = {};
cms.locale = '{$cms['config']['locale']}';
cms.lang = {$lang};
cms.tr = {$tr};
cms.api = '{$cms['config']['admin.mod.php']['api_url']}';
</script>\n";
}
?>

<?php cms_do_stage( "admin_header" ); ?>

</head>


<?php if ( is_admin() ) : ?>

<body class=logged>
    <header>
        <div class=burger>
            <div class=menu-icon>
                <span class=line-1></span>
                <span class=line-2></span>
            </div>
        </div>

        <div class=logo>
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 233.34 233.24"><path class="icon-coffee" d="M116.62,0c-1.63,0-3.25,0-4.87.11a112.56,112.56,0,0,0,1.74,44.51c6.09,24.94,16.07,49.17,26.47,72.77,15.52,35.21,24.37,70.68,21.06,107.13A116.63,116.63,0,0,0,116.62,0Z" transform="translate(0.05)"></path><path class="icon-coffee" d="M120.92,233.16V233c7.12-39.29-3.54-75.28-21.28-109.75-19-36.9-30.46-74.6-27.76-114.33a116.64,116.64,0,0,0,44.7,224.36C118.06,233.24,119.49,233.21,120.92,233.16Z" transform="translate(0.05)"></path></svg>
            </div>
            <div>Coffee CMS</div>
        </div>

        <div class=menu>
            <a href="/" data-front target=_blank>
                <?php echo __( "Home" ); ?>
            </a>
            <div class=clear-cache>
                <?php echo __( "Cache" ); ?>
            </div>
            <div class=theme-switcher>
                <?php echo __( "Theme" ); ?>
            </div>
            <div data-logout>
                <?php echo __( "Logout" ); ?>
            </div>
        </div>
    </header>

  <aside>

<?php
foreach( $cms["admin_sections"] as $section_name => $section ) {
    if ( empty( $section["hide"] ) ) {
        echo "<section>";
        echo "<div>{$cms['admin_sections'][$section_name]['title']}</div>";
        // Items
        if ( ! empty( $cms["admin_sections"][$section_name]["items"] ) ) {
            foreach( $cms["admin_sections"][$section_name]["items"] as $page_name => $page ) {
                if ( empty( $page["hide"] ) ) {
                    $title = __( $page["title"], $page["module"] );
                    // for highlite
                    if ( ! empty( $page["class"] ) ) {
                        $class = "class='{$page['class']}'";
                    } else {
                        $class = "";
                    }
                    if ( empty( $page["external_url"] ) ) {
                        echo "<a href=#{$page_name} {$class}>{$title}</a>";
                    } else {
                        echo "<a href='{$page['external_url']}' {$class}>{$title}</a>";
                    }
                }
            }
        }
        echo "</section>";
    }
}
?>

  </aside>


  <main>

<?php
    $hello = __( "Hello!" );
    if ( cms_base_connect() === false ) {
        $base_ok = "<p>" . __( "The first step is to set up your database connection." ) . "</p>";
    } else {
        $base_ok = "";
    }
    //$cookie_expires = __( "You can <a class=cookie_expires>Remember the browser</a> and do not enter your login and password for a month." );
    
echo <<<EOS
<section id=start>
    <div>
        <div>{$hello}</div>
        {$base_ok}
    </div>
</section>
EOS;

    foreach( $cms["admin_pages"] as $name => $page ) {
        echo "<section id={$name}>{$page}</section>";
    }
    
?>

  </main>

  <div class=milk></div>

<?php else : ?>

<body class=login>
  
    <header>
        <div class=logo>
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 233.34 233.24"><path class="icon-coffee" d="M116.62,0c-1.63,0-3.25,0-4.87.11a112.56,112.56,0,0,0,1.74,44.51c6.09,24.94,16.07,49.17,26.47,72.77,15.52,35.21,24.37,70.68,21.06,107.13A116.63,116.63,0,0,0,116.62,0Z" transform="translate(0.05)"></path><path class="icon-coffee" d="M120.92,233.16V233c7.12-39.29-3.54-75.28-21.28-109.75-19-36.9-30.46-74.6-27.76-114.33a116.64,116.64,0,0,0,44.7,224.36C118.06,233.24,119.49,233.21,120.92,233.16Z" transform="translate(0.05)"></path></svg>
            </div>
            <div>Coffee CMS</div>
        </div>
        <div class=menu>
            <div class=theme-switcher>
                <?php echo __( "Theme" ); ?>
            </div>
        </div>
    </header>

    

    <div class=aside-main>
        <div class=center-box>
            <div class=setup-error>
                <?php // config.php not writeable
                if ( isset( $cms["config_writeable"] ) && $cms["config_writeable"] === false ) {
                    echo __( "Can't write" ) . " {$cms['cms_dir']}/config.php";
                }
                ?>
            </div>
            <div class=lang-selector>
                <select id=lang_selector>
                <?php
                    $t = array(
                        "ru_RU.UTF-8" => "Русский",
                        "uk_UA.UTF-8" => "Українська",
                    );
                    // Scan all locales
                    $options = "";
                    $default = "";
                    foreach( glob( "lang/*.UTF-8",  GLOB_ONLYDIR ) as $locale ) {
                        $locale = preg_replace( "/.*\//", "", $locale );

                        // translate
                        if ( isset( $t[$locale] ) ) {
                            $lang = $t[$locale];
                        } else {
                            $lang = $locale;
                        }

                        if ( $cms["config"]["locale"] == $locale ) {
                            $default = $locale;
                            $selected = "selected";
                        } else {
                            $selected = "";
                        }

                        $options .= "<option value='{$locale}' {$selected}>{$lang}</option>";
                    }
                    if ( ! $default ) {
                        $selected = "selected";
                    } else {
                        $selected = "";
                    }
                    echo "<option value='en_US.UTF-8' {$selected}>English</option>{$options}";
                ?>
                </select>
            </div>
            <div class=setup-auth>
                <?php
                if ( empty( $cms["config"]["admin.mod.php"]["admin_login"] ) && empty( $cms["config"]["admin.mod.php"]["admin_password"] ) ) {
                    echo __( "Setup Login and Password" );
                }
                ?>
            </div>
            <div class=login-and-password>
                <div class=login>
                    <input placeholder="<?php echo __( "Login or Email" ); ?>" name=login type=text>
                </div>
                <div class="password">
                    <input placeholder="<?php echo __( "Password" ); ?>" name=password type=password>
                    <div title="<?php echo __( "Enter" ); ?>" type=submit></div>
                </div>
            </div>
            <div class=flatfree></div>
            <div class=support-box>
                <?php echo __( "<a target=_blank href='https://coffee-cms.com/support/'>Support</a>" ); ?>
            </div>
        </div>
    </div>

    
<?php endif; ?>
    
    <div class=log-info-box>
        <!-- div for messages -->
    </div>
</body>
</html>
