document.addEventListener("DOMContentLoaded", function () {

    // Submenu DIRECCION
    var btnDireccion = document.getElementById("btnDireccion");
    var menuDireccion = document.getElementById("menuDireccion");

    if (btnDireccion && menuDireccion) {
        btnDireccion.addEventListener("click", function (e) {
            e.preventDefault();
            menuDireccion.classList.toggle("active");
        });
    }

    // Submenu SOLICITUDES
    var btnSolicitudes = document.getElementById("btnSolicitudes");
    var menuSolicitudes = document.getElementById("menuSolicitudes");

    if (btnSolicitudes && menuSolicitudes) {
        btnSolicitudes.addEventListener("click", function (e) {
            e.preventDefault();
            menuSolicitudes.classList.toggle("active");
        });
    }

});
