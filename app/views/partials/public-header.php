<header class="site-header public-site-header">
    <div class="container header-inner">
        <a class="brand" href="/cepre_untels/public/" aria-label="CEPRE UNTELS inicio">
            <img class="brand-logo" src="/cepre_untels/public/img/cepre.png" alt="CEPRE UNTELS">
        </a>
        <nav class="main-nav" id="mainNav" aria-label="Navegación principal">
            <ul>
                <li><a href="/cepre_untels/public/" class="<?= ($pagina ?? '') === 'inicio' ? 'active' : '' ?>">Inicio</a></li>
                <li><a href="/cepre_untels/public/sobre-nosotros.php" class="<?= ($pagina ?? '') === 'sobre-nosotros' ? 'active' : '' ?>">Sobre Nosotros</a></li>
                <li><a href="/cepre_untels/public/docentes.php" class="<?= ($pagina ?? '') === 'docentes' ? 'active' : '' ?>">Docentes</a></li>
                <li><a href="/cepre_untels/public/contacto.php" class="<?= ($pagina ?? '') === 'contacto' ? 'active' : '' ?>">Contacto</a></li>
                <li><a href="/cepre_untels/public/preguntas-frecuentes.php" class="<?= ($pagina ?? '') === 'faq' ? 'active' : '' ?>">Preguntas Frecuentes</a></li>
            </ul>
        </nav>
        <div class="header-cta">
            <a href="/cepre_untels/public/login_alumnos.php" class="btn btn-secondary btn-campus">MATRICULATE</a>
            <button class="menu-toggle" id="menuToggle" type="button" aria-label="Menú">☰</button>
        </div>
    </div>
</header>
