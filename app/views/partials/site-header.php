<?php $publicStudentHeader = $publicStudentHeader ?? false; ?>
<header class="site-header <?= $publicStudentHeader || isStudent() ? 'site-header--student' : 'site-header--admin' ?>">
    <a class="brand" href="/cepre_untels/public/" aria-label="CEPRE UNTELS inicio">
        <img class="brand__logo" src="/cepre_untels/public/img/cepre.png" alt="CEPRE UNTELS">
    </a>
    <?php if ($publicStudentHeader): ?>
        <nav aria-label="Navegación principal">
            <a href="/cepre_untels/public/">Inicio</a>
            <a href="/cepre_untels/public/sobre-nosotros.php">Sobre Nosotros</a>
            <a href="/cepre_untels/public/docentes.php">Docentes</a>
            <a href="/cepre_untels/public/contacto.php">Contacto</a>
            <a href="/cepre_untels/public/preguntas-frecuentes.php">Preguntas Frecuentes</a>
            <a class="button button--gold" href="/cepre_untels/public/login_alumnos.php">MATRICULATE</a>
        </nav>
    <?php else: ?>
        <nav aria-label="Navegación principal">
            <span class="user-label"><?= htmlspecialchars(currentUser()['nombre'] ?? 'Usuario', ENT_QUOTES, 'UTF-8') ?></span>
            <?php if (isAdmin()): ?>
            <a href="/cepre_untels/public/fichas.php">Ver ficha de estudiante</a>
            <a href="/cepre_untels/public/reportes.php">Reportes</a>
            <?php endif; ?>
            <a href="/cepre_untels/public/">Registrar matrícula</a>
            <a href="/cepre_untels/public/logout.php">Salir</a>
        </nav>
    <?php endif; ?>
</header>
