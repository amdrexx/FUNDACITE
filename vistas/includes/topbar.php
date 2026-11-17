<div class="topbar">
    <button
        type="button"
        class="menu-btn"
        id="menuToggle"
        aria-label="Abrir menú de navegación"
        aria-controls="sidebar"
        aria-expanded="false"
    >
        <div></div>
        <div></div>
        <div></div>
    </button>

    <img src="/FUNDACITE/vistas/img/logo.png" alt="Logo FUNDACITE">

    <div class="topbar-user">
        Bienvenido,
        <strong><?php echo htmlspecialchars($_SESSION["usuario"] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
    </div>
</div>
