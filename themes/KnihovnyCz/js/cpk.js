// We only need to observe change of type childList
const config = { attributes: false, childList: true, subtree: false };

// Callback function to execute when mutations are observed
const observeCartHandler = function observeCartHandler(mutationsList) {
  // Use traditional 'for loops' for IE 11
  for (const mutation of mutationsList) {
    if (mutation.type === 'childList') {
      if (mutation.target.innerText === '0') {
        mutation.target.parentNode.style.display = 'none';
      } else {
        mutation.target.parentNode.style.display = '';
      }
    }
  }
};

const observer = new MutationObserver(observeCartHandler);

document.addEventListener('DOMContentLoaded', function runObserver() {
  const targetNode = document.querySelector('#cartItems strong');
  observer.observe(targetNode, config);
}, false);

jQuery(document).ready(function jQueryReady($) {

  // Scroll to target by data attribute
  $('*[data-scrollto-target]').on('click', function scrollToTarget() {
    const target = $(this).data('scrollto-target');
    const interval = typeof $(this).data('scrollto-interval') === 'number'
      ? $(this).data('scrollto-interval')
      : 500;
    $('html,body').animate({
      scrollTop: $(target).offset().top
    }, interval);
  });

  // Remove truncated class
  $('.btn-show-full-text').on('click', function showFullDescription() {
    $('.btn-show-full-text').addClass('hidden');
    $('.text-last').removeClass('hidden');
  });    

});
