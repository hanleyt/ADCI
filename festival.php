<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Amateur Drama Council of Ireland</title>

    <link rel="canonical" href="http://adci.ie/">
    <link rel="shortlink" href="http://adci.ie/">
    <link rel="stylesheet" id="fifteen-bootstrap-style-css" href="/css/fifteenBootstrap.css" type="text/css" media="all">
    <link rel="stylesheet" id="fifteen-basic-style-css" href="/css/fifteenStyle.css" type="text/css" media="all">
    <link rel="stylesheet" id="fifteen-main-skin-css" href="/css/fifteenSkins.css" type="text/css" media="all">

</head>
<body>
<div id="parallax-bg"></div>
<div id="foreground">

    <header role="banner">
        <h1 class="site-title">
            <a href="http://adci.ie/" title="Amateur Drama Council of Ireland" rel="home">Amateur Drama Council of
                Ireland</a>
        </h1>

        <div class="social-icons">
            <a target="_blank" href="https://www.facebook.com/adcipage/" title="Facebook"><img
                src="/img/facebook.png"></a>
            <a target="_blank" href="https://twitter.com/ADCI_Forum" title="Twitter"><img src="/img/twitter.png"></a>
        </div>
    </header>

    <div class="default-nav-wrapper">
        <nav id="site-navigation" role="navigation">
            <ul>
                <li><a href="http://adci.ie/">Home</a></li>
                <li><a href="http://adci.ie/index.php/festivals/">Festivals</a></li>
                <li><a href="http://adci.ie/index.php/results/">Results</a></li>
                <li><a href="http://adci.ie/index.php/archives/">Archives</a></li>
                <li><a href="http://adci.ie/index.php/contact/">Contact</a></li>
                <li><a href="http://adci.ie/index.php/about/">About</a></li>
            </ul>
        </nav>
    </div>

    <h1 class="container single-entry-title">Festivals</h1>

    <main id="content" class="container" role="main">
        <?php
            require_once '../php/festivalFunctions.php';

            if ($_GET["oneAct"]){
                $oneAct = "ONE_ACT_";
            }else{
                 $oneAct = "";
            }
            showFestival($_GET["name"], $_GET["year"], $oneAct);
        ?>



    </main>

    <footer class="social-icons">
        <a target="_blank" href="https://www.facebook.com/adcipage/" title="Facebook"><img src="/img/facebook.png"
                                                                                           alt="Facebook"></a>
        <a target="_blank" href="https://twitter.com/ADCI_Forum/" title="Twitter"><img src="/img/twitter.png"
                                                                                       alt="Twitter"></a>
    </footer>


</div>

</body>
</html>