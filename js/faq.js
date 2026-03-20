/* ============================================================
   faq.js
   FAQ accordion — only one item open at a time
   ============================================================ */
(function () {
  var items = document.querySelectorAll('.faq__item');
  if (!items.length) return;

  function closeItem(item) {
    item.classList.remove('-open');
    item.querySelector('.faq__question').setAttribute('aria-expanded', 'false');
    item.querySelector('.faq__answer').style.maxHeight = '0';
  }

  function openItem(item) {
    item.classList.add('-open');
    item.querySelector('.faq__question').setAttribute('aria-expanded', 'true');
    item.querySelector('.faq__answer').style.maxHeight =
      item.querySelector('.faq__answer').scrollHeight + 'px';
  }

  /* Init: open the item marked with -open class by PHP */
  items.forEach(function (item) {
    if (item.classList.contains('-open')) {
      openItem(item);
    }
  });

  /* Click handler */
  items.forEach(function (item) {
    var btn = item.querySelector('.faq__question');
    if (!btn) return;

    btn.addEventListener('click', function () {
      var isOpen = item.classList.contains('-open');

      /* Close all open items */
      items.forEach(function (other) {
        if (other.classList.contains('-open')) closeItem(other);
      });

      /* Open clicked if it was closed */
      if (!isOpen) openItem(item);
    });
  });
})();
