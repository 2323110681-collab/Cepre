<header class="site-header">
    <a class="brand" href="/cepre_untels/public/" aria-label="CEPRE UNTELS inicio">
        <span class="brand__mark">C</span>
        <span><strong>CEPRE</strong><b>UNTELS</b></span>
    </a>
    <nav aria-label="Navegación principal">
        <span class="user-label"><?= htmlspecialchars(currentUser()['nombre'] ?? 'Usuario', ENT_QUOTES, 'UTF-8') ?></span>
        <a href="/cepre_untels/public/fichas.php">Ver ficha de estudiante</a>
        <a href="/cepre_untels/public/reportes.php">Reportes</a>
        <a href="/cepre_untels/public/">Registrar matrícula</a>
        <a href="/cepre_untels/public/logout.php">Salir</a>
    </nav>
</header>
