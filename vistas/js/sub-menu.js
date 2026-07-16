document.addEventListener("DOMContentLoaded", function () {

    var btnDireccion = document.getElementById("btnDireccion");
    var menuDireccion = document.getElementById("menuDireccion");

    if (btnDireccion && menuDireccion) {

        btnDireccion.addEventListener("click", function (e) {
            e.preventDefault();
            menuDireccion.classList.toggle("active");
        });

    }

});
