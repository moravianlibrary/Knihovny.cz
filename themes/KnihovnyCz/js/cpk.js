/* exported initDatePicker */
/* global VuFind, extractClassParams, htmlEncode */

// We only need to observe change of type childList
const config = { attributes: false, childList: true, subtree: false };

// Function to hide or show cart badge
const toggleCartBadge = function toggleCartBadge(targetNode) {
  targetNode.parentNode.style.display = targetNode.innerText === '0' ? 'none' : 'block';
};

// Callback function to execute when mutations are observed
const observeCartHandler = function observeCartHandler(mutationsList) {
  // Use traditional 'for loops' for IE 11
  for (const mutation of mutationsList) {
    if (mutation.type === 'childList') {
      toggleCartBadge(mutation.target);
    }
  }
};

const cartObserver = new MutationObserver(observeCartHandler);

document.addEventListener('DOMContentLoaded', function runObserver() {
  const targetNode = document.querySelector('#cartItems strong');
  toggleCartBadge(targetNode);
  cartObserver.observe(targetNode, config);
}, false);

$(function jQueryReady($) {

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

function setupOpenUrl() {
  $('.openurl').each(function onEachOpenUrl() {
    var element = this;
    var hasFulltext = $(element).data('fulltext');
    var ajaxCall = {
      dataType: "json",
      url: "/AJAX/JSON?method=sfx&" + $(element).data('openurl'),
      method: "GET",
      success: function sfx(json){
        $(element).empty();
        $(element).off('click');
        let links = json.data;
        let header = null;
        let noOfLinks = Object.keys(links).length;
        if (links.default) {
          header = (!hasFulltext) ? 'Fulltext is free' : null;
        } else if (noOfLinks === 1) {
          header = 'Fulltext is available for users of this institution';
        } else if (noOfLinks > 1) {
          header = 'Fulltext is available for users of these institutions';
        } else if (noOfLinks === 0 && !hasFulltext) {
          header = 'Fulltext not found';
        }
        if (header != null) {
          $(element).append($('<div>', {
            class: 'records-in-libraries-title',
            html: $('<strong>', { text: VuFind.translate(header) }),
          }));
        }
        let list = $('<ul>', {
          class: 'list-unstyled'
        });
        let dropDownMenu = null;
        let dropDownList = null;
        let dropdown = $(element).data('dropdown');
        if (dropdown && noOfLinks > 3) {
          dropDownMenu = $('<div>', {class: 'dropdown'});
          let button = $('<a>', {
            type: 'button',
            class: 'dropdown-toggle',
            'data-toggle': 'dropdown',
            'aria-expanded': false,
            html: '<strong>' + htmlEncode(VuFind.translate('Show next links'))
              + "<strong/><span class='caret'/>",
          });
          button.appendTo(dropDownMenu);
          dropDownList = $('<ul>', {
            class: 'dropdown-menu dropdown-menu-right',
          });
          dropDownList.appendTo(dropDownMenu);
        }
        let index = 0;
        $.each(links, function onEachLink(key, value) {
          let link = $('<a>', {
            text: value.label,
            title: value.label,
            href: value.url,
            target: '_blank',
          });
          let li = $('<li>', {
            class: 'otherSource',
          });
          link.appendTo(li);
          index++;
          li.appendTo((dropDownList == null || index < 3) ? list : dropDownList);
        });
        list.appendTo(element);
        if (dropDownMenu != null) {
          dropDownMenu.appendTo(element);
        }
      }
    };
    var lazy = $(element).data('lazy');
    if (lazy) {
      $(element).on("click", function onClick() {
        $(element).empty().append(VuFind.spinner('fa-3x fa-fw'));
        $.ajax(ajaxCall);
      });
    } else {
      $.ajax(ajaxCall);
    }
  });
}

$(function openUrl() {
  setupOpenUrl();
});

function setLibraryAutoComplete(element) {
  var libraries = new Map();
  const list = element.querySelectorAll('ul li:not(.facet-tree__parent) a');
  for (const item of list) {
    const value = item.dataset.value;
    libraries.set(value, {
      label: item.dataset.title,
      value: value,
      href: item.attributes.href.value,
    });
  }

  var input = $('<input></input>').addClass('autocomplete-institutions form-control')
    .attr('placeholder', VuFind.translate('Autocomplete institutions placeholder'));
  function normalizeString(str) {
    return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
  }
  // Search autocomplete
  input.autocomplete({
    rtl: $(document.body).hasClass("rtl"),
    maxResults: 10,
    loadingString: VuFind.translate('loading_ellipsis'),
    // AJAX call for autocomplete institutions
    handler: function vufindACHandler(inputField, cb) {
      const query = inputField.val();
      const terms = normalizeString(query).split(' ');
      var searcher = extractClassParams(inputField);
      $.fn.autocomplete.ajax({
        url: VuFind.path + '/AJAX/JSON',
        data: {
          q: query,
          method: 'getLibrariesACSuggestions',
          searcher: searcher.searcher
        },
        dataType: 'json',
        success: function autocompleteJSON(json) {
          var results = new Map();
          json.data.forEach(function onEach(item) {
            const library = libraries.get(item.value);
            if (typeof library !== "undefined") {
              results.set(item.value, library);
            }
          });
          for (const [key, item] of libraries.entries()) {
            const searchValue = normalizeString(item.label);
            const add = terms.every(function hasTerm(term) {
              return searchValue.startsWith(term) || searchValue.includes(' ' + term);
            });
            if (add) {
              results.set(key, item);
            }
          }
          var ac = [];
          for (const item of results.values()) {
            ac.push(item);
          }
          cb(ac);
        }
      });
    }
  });
  $(element).prepend(input);
}

// We only need to observe change of type childList
const institutionFacetConfig = { attributes: false, childList: true, subtree: false };

// Callback function to execute when mutations are observed
const observeInstitutionFacetHandler = function observeInstitutionFacetHandler(mutationsList, observer) {
  // Use traditional 'for loops' for IE 11
  for (const mutation of mutationsList) {
    if (mutation.target) {
      observer.disconnect();
      setLibraryAutoComplete(mutation.target);
    }
  }
};

const institutionFacetObserver = new MutationObserver(observeInstitutionFacetHandler);

document.addEventListener('DOMContentLoaded', function runObserver() {
  const targetNode = document.querySelector('#side-collapse-region_institution_facet_mv, #side-collapse-local_region_institution_facet_mv');
  if (targetNode !== null) {
    institutionFacetObserver.observe(targetNode, institutionFacetConfig);
  }
}, false);

$(function saveInstitutionFilter() {
  var element = $('#my-institution-filter-save');
  var hidePopover = function hidePopover(){
    var callback = null;
    return function onTimeout() {
      if (callback != null) {
        clearTimeout(callback);
      }
      callback = setTimeout(function hidePopoverInner() {
        element.popover('hide');
      }, 5000);
    };
  }();
  element.on('click', function onClick() {
    event.stopPropagation();
    element.popover('show');
    element.data('bs.popover').options.content = element.data('content-progress');
    element.popover('show');
    var institutions = element.data('institutions').split(';');
    var url = VuFind.path + '/AJAX/JSON?method=saveInstitutionFilter';
    $.ajax({
      dataType: "json",
      url: url,
      method: "POST",
      data: {'institutions[]': institutions},
      success: function onSuccess(){
        $('#my-institution-filter').attr('href', window.location);
        element.data('bs.popover').options.content = element.data('content-ok');
        element.popover('show');
        hidePopover();
      },
      error: function onError(json){
        var message = element.data('content-error');
        if ('responseJSON' in json) {
          message = json.responseJSON.data;
        }
        element.data('bs.popover').options.content = message;
        element.popover('show');
        hidePopover();
      },
    });
    return true;
  });
  element.show();
});

$(function coverImageLoadError($) {
  $('.ajaxcover img').on('error', function showCoverIconOnError() {
    const format = $(this).data('format');
    $(this).parent().append('<i class="cover-icon ' + format + '"></i>');
    $(this).hide();
  });
});

$(function registerAccountAjax() {
  VuFind.account.register("profile", {
    selector: ".profile-status",
    ajaxMethod: "getUserProfile",
    render: function render($element, status, ICON_LEVELS) {
      if (status.expired === false) {
        $element.addClass("hidden");
        return ICON_LEVELS.NONE;
      }
      $element.html('<span class="badge account-alert">!</span>');
      return ICON_LEVELS.DANGER;
    },
    updateNeeded: function updateNeeded(currentStatus, status) {
      return status.expired !== currentStatus.expired;
    }
  });
});

function initDatePicker(form) {
  const target = form || document;
  $(target).find("input[data-type='date']").each(function onEach() {
    let options = { changeMonth: true, changeYear: true };
    const min = $(this).attr('min');
    if (min != null) {
      options.minDate = new Date(Date.parse(min));
    }
    const max = $(this).attr('max');
    if (max != null) {
      options.maxDate = new Date(Date.parse(max));
    }
    $(this).datepicker(options);
  });
}

$(function cartPopoverReinit() {
  $('#updateCart, #bottom_updateCart')
    .popover('destroy')
    .popover({
      title: VuFind.translate('bookbag'),
      content: '',
      html: true,
      trigger: 'manual',
      placement: 'top',
      viewport: {
        'selector': '.action-toolbar',
        'padding': 0,
      },
    });
});

$(function initCastTruncate() {
  VuFind.truncate.initTruncate('.truncate-cast', '.cast-line');
});
