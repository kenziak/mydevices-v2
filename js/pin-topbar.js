(function () {
  const targetPath = '/plugins/mydevices/front/mydevices.php';

  function rootDoc() {
    try {
      if (window.CFG_GLPI && typeof CFG_GLPI.root_doc === 'string') {
        return CFG_GLPI.root_doc || '';
      }
    } catch (_) {}
    return '';
  }

  function ensurePinned() {
    const nav = document.querySelector('.navbar .navbar-nav');
    if (!nav) return;

    if (nav.querySelector(`.nav-link[href$="${targetPath}"]`)) return;

    const li = document.createElement('li');
    li.className = 'nav-item';
    const a = document.createElement('a');
    a.className = 'nav-link';
    a.href = rootDoc() + targetPath;
    a.innerHTML = '<i class="fas fa-flask"></i> <span>Moje urządzenia</span>';
    li.appendChild(a);

    const more = nav.querySelector('.nav-item.dropdown, .dropdown');
    more ? nav.insertBefore(li, more) : nav.insertBefore(li, nav.firstChild);
  }

  function init() {
    ensurePinned();
    const mo = new MutationObserver(ensurePinned);
    mo.observe(document.body, { childList: true, subtree: true });
    const ro = new ResizeObserver(ensurePinned);
    ro.observe(document.body);
    window.addEventListener('orientationchange', ensurePinned);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
