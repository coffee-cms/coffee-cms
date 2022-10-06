<!doctype html>
<html lang="<?php echo $cms["config"]["lang"]; ?>" class="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?php echo $cms['url']['host']; ?></title>
<link rel="icon" href="<?php echo $cms["base_path"]; ?>img/favicon.svg">

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
    $modules = json_encode( $cms["modules"] );
    echo "cms = {};
cms.async_api = true;
cms.locale = '{$cms['config']['locale']}';
cms.api = '{$cms['config']['admin.mod.php']['api_url']}';
cms.modules = {$modules};
cms.lang = {$lang};
cms.tr = {$tr};
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

        <div class=menu>
            <a href="/" data-front target=_blank>
                <?php echo __( "Главная" ); ?>
            </a>
            <div class=clear-cache>
                <?php echo __( "Кэш" ); ?>
            </div>
            <div class=theme-switcher>
                <?php echo __( "Тема" ); ?>
            </div>
            <div data-logout>
                <?php echo __( "Выход" ); ?>
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
    $hello = __( "Привет!" );
    if ( cms_base_connect() === false ) {
        $base_ok = "<p>" . __( "Первым делом настройте <a href='#base'>подключение к базе данных</a>." ) . "</p>";
    } else {
        $base_ok = "";
    }
    
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
        <div class=menu>
            <div class=theme-switcher>
                <?php echo __( "Тема" ); ?>
            </div>
        </div>
    </header>

    

    <div class=aside-main>
        <div class=center-box>
            <div class=setup-error>
                <?php // config.php not writeable
                if ( isset( $cms["config_writeable"] ) && $cms["config_writeable"] === false ) {
                    echo __( "Не могу записать" ) . " {$cms['cms_dir']}/config.php";
                }
                ?>
            </div>
            
            <?php
                // Scan all locales
                $options = "";
                foreach( glob( "lang/*.UTF-8",  GLOB_ONLYDIR ) as $locale ) {
                    include( $locale . "/admin.mod.php" );
                    $locale = preg_replace( "/.*\//", "", $locale );

                    // translate
                    $lang = $cms["lang"]["admin.mod.php"][$locale][$locale];

                    $options .= "<option value='{$locale}'>{$lang}</option>";
                }
                //$options = "<option value='ru_RU.UTF-8'>Русский</option>{$options}";
            ?>
            <div class=lang-selector>
                <div class=lang-select-grid>
                    <div class=field-select data-lang='<?php echo $cms["config"]["locale"]; ?>'><?php echo $cms["lang"]["admin.mod.php"][$cms["config"]["locale"]][$cms["config"]["locale"]]; ?></div>
                    <div class=field-options>
                        <?php echo $options; ?>
                    </div>
                </div>
            </div>
            <div class=setup-auth>
                <?php
                if ( empty( $cms["config"]["admin.mod.php"]["admin_login"] ) && empty( $cms["config"]["admin.mod.php"]["admin_password"] ) ) {
                    echo __( "Установите логин и пароль" );
                }
                ?>
            </div>
            <div class=login-and-password>
                <div class=login>
                    <input placeholder="<?php echo __( "Логин или эл.почта" ); ?>" name=login type=text>
                </div>
                <div class="password">
                    <input placeholder="<?php echo __( "Пароль" ); ?>" name=password type=password>
                    <div title="<?php echo __( "Войти" ); ?>" type=submit></div>
                </div>
            </div>
            <div class=flatfree></div>
            <div class=support-box>
                <a target=_blank href='https://coffee-cms.ru/podderzhka/'><?php echo __( "Поддержка" ); ?></a>
            </div>
        </div>
    </div>

    
<?php endif; ?>
    
    <div class=log-info-box>
        <!-- div for messages -->
    </div>
</body>
</html>
