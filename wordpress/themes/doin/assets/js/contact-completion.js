(function () {
  'use strict';

  document.addEventListener('wpcf7mailsent', function (event) {
    var settings = window.doinContactCompletion;
    if (!settings || !event.detail || Number(event.detail.containerPostId) !== Number(settings.pageId)) {
      return;
    }
    if (!(event.target instanceof Element) || !event.target.closest('.entry-body .wpcf7')) {
      return;
    }

    window.location.assign(settings.url);
  });
}());
