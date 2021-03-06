<body>
<?=getOption('google-analytics-code')?>
	<?php if(isClientAdmin()) include(X_PATH."/application/views/admin/client/frontend_admin_panel.php"); ?>
    <!--========== HEADER ==========-->
    <header <?php if($_SERVER['REQUEST_URI'] != '/') echo ' id="auxiliary"';?> id="pageHeader" class="header navbar">
        <div class="container">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#myNavbar" aria-expanded="false">
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="/">
                	<img class="logo-img" src="/img/logo.png" alt="<?=$GLOBALS['site_name']?>">
                </a>
            </div>
            <nav id="myNavbar" class="navbar-collapse collapse">
                <?php if($_SERVER['REQUEST_URI'] == '/'){ ?>
                <ul class="nav navbar-nav">
                    <li><a href="#welcome">Главная</a></li>
                    <li><a href="#about">О нас</a></li>
                    <!-- <li><a href="#services">Направления</a></li> -->
                    <li><a href="#team">Мы-Команда!</a></li>
                    <li><a href="#portfolio">Что мы делаем?</a></li>
                    <li><a href="#specials">Спец. предложения</a></li>
                    <li><a href="#blog">Новости</a></li>
                    <li><a href="/articles/">Статьи</a></li>
                    <li><a href="#testimonials">Отзывы</a></li>
                    <li><a href="#contact">Контакты</a></li>
                </ul>
                <?php } else { ?>
                    <ul class="nav navbar-nav">
                    <li><a href="/">Главная</a></li>
                    <li><a href="/about/">О нас</a></li>
                    <!-- <li><a href="#services">Направления</a></li> -->
                    <li><a href="/our-team/">Мы-Команда!</a></li>
                    <li><a href="/chto-my-delaem/">Что мы делаем?</a></li>
                    <li><a href="/specpredlozhenija/">Спец. предложения</a></li>
                    <li><a href="/news/">Новости</a></li>
                    <li><a href="/articles/">Статьи</a></li>
                    <li><a href="/otzivi/">Отзывы</a></li>
                    <li><a href="/kontakty/">Контакты</a></li>
                    </ul>
                <?php } ?>
            </nav>
        </div>
    </header>
    <!--========== END OF HEADER ==========-->