function toggleMenu() {
    document.getElementById("sidebar").classList.toggle("active");
    document.getElementById("menuBtn").classList.toggle("active");
}

// SUBMENU
function toggleSubmenu(element) {
    element.parentElement.classList.toggle("active");
}