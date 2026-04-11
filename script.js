function irARegistro() {
    document.getElementById('caja-login').style.display = 'none';
    document.getElementById('caja-registro').style.display = 'block';
    document.getElementById('cuerpo-fondo').style.backgroundImage = "url('fondo2.png')";
    // Aquí cambiaremos el fondo cuando proporciones el nombre de la nueva imagen
    // document.getElementById('cuerpo-fondo').style.backgroundImage = "url('NUEVA_IMAGEN.png')";
}

function irALogin() {
    document.getElementById('caja-registro').style.display = 'none';
    document.getElementById('caja-login').style.display = 'block';
    document.getElementById('cuerpo-fondo').style.backgroundImage = "url('fondo.png')";
    
}