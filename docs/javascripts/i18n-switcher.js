// Sélecteur de langue FR / EN compatible avec navigation.instant.
//
// Le plugin i18n calcule les liens du sélecteur au chargement de la page, mais la
// navigation instantanée de Material ne recharge pas l'en-tête : sans ce script,
// le sélecteur pointerait toujours vers la première page ouverte.
// Les pages françaises sont servies à la racine du site, les pages anglaises sous en/.
(function () {
  var root = null;

  // Racine du site, déduite des liens FR et EN de la première page chargée :
  // /edu-framework/boost/route/ et /edu-framework/en/boost/route/ → /edu-framework/
  function detectRoot() {
    var fr = document.querySelector('a[hreflang="fr"]');
    var en = document.querySelector('a[hreflang="en"]');
    if (!fr || !en) return null;
    var frPath = new URL(fr.getAttribute("href"), location.href).pathname;
    var enPath = new URL(en.getAttribute("href"), location.href).pathname;
    var i = 0;
    while (i < frPath.length && frPath[i] === enPath[i]) i++;
    return enPath.slice(i, i + 3) === "en/" ? enPath.slice(0, i) : null;
  }

  function update() {
    if (root === null) root = detectRoot();
    if (root === null) return;
    var path = location.pathname;
    if (path.indexOf(root) !== 0) return;
    var rest = path.slice(root.length);
    if (rest.indexOf("en/") === 0) rest = rest.slice(3);
    var targets = { fr: root + rest, en: root + "en/" + rest };
    document.querySelectorAll("a[hreflang]").forEach(function (link) {
      var lang = link.getAttribute("hreflang");
      if (targets[lang]) link.setAttribute("href", targets[lang]);
    });
  }

  if (typeof document$ !== "undefined") {
    document$.subscribe(update);
  } else {
    update();
  }
})();
