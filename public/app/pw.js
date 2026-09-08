// Casilla «mostrar contraseña»: solo aparece si hay JS (la CSP no permite scripts en línea).
document.querySelectorAll('.showpw').forEach(function (s) {
  s.hidden = false;
  s.querySelector('input').addEventListener('change', function () {
    var t = this.checked ? 'text' : 'password';
    this.form.querySelectorAll('input[data-pw]').forEach(function (i) { i.type = t; });
  });
});
