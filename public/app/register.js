/* Deltha — registro del service worker (JS opcional: sin él la web funciona igual). */
if ('serviceWorker' in navigator) {
  var v = document.currentScript && document.currentScript.dataset.v || '0';
  navigator.serviceWorker.register('/app/sw.js?v=' + v, { scope: '/' }).catch(function () {});
}
