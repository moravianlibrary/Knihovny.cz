(function initCookieBarHandler() {
  function startObserving() {
    var inserted = false;

    function insertCloseButton() {
      var $cookieBar = $('#cc-main');
      var $body = $cookieBar.find('.cm__body');

      if ($body.length && !inserted) {
        inserted = true;

        var $closeBtn = $('<button id="cc-x-button"><i class="fa fa-times" aria-hidden="true"></i></button>');

        $closeBtn.on('click', function onCloseBtnClick() {
          $body.slideUp(200, function bodySlideUpCallback() {
            setTimeout(function hideCookieBarWithTimeout() {
              $cookieBar.hide();
            }, 100);
          });
        });

        $body.append($closeBtn);
      }
    }

    const observer = new MutationObserver(function observeCookieBarLength() {
      if ($('#cc-main').length) {
        insertCloseButton();
      }
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startObserving);
  } else {
    startObserving();
  }
})();
