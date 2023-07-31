/* exported setupAutocomplete, buildFacetNodes */
/* global VuFind, extractClassParams, htmlEncode */

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

function setupAutocomplete() {
  // If .autocomplete class is missing, autocomplete is disabled and we should bail out.
  var searchbox = $('#searchForm_lookfor.autocomplete');
  if (searchbox.length < 1) {
    return;
  }
  // Auto-submit based on config
  var acCallback = function ac_cb_noop() {};
  if (searchbox.hasClass("ac-auto-submit")) {
    acCallback = function autoSubmitAC(item, input) {
      var element = $(window.event.target.parentNode);
      if (element.hasClass('autocomplete-link')) {
        item.href = element.attr('href');
        return true;
      }
      input.val(item.value);
      $("#searchForm").trigger("submit");
      return false;
    };
  }
  var requestId = 0;
  var ajaxCalls = [];
  // Search autocomplete
  searchbox.autocomplete({
    rtl: $(document.body).hasClass("rtl"),
    maxResults: 10,
    highlight: false,
    loadingString: VuFind.translate('loading_ellipsis'),
    // Auto-submit selected item
    callback: acCallback,
    // AJAX call for autocomplete results
    handler: function vufindACHandler(input, cb) {
      ajaxCalls.forEach(function forEach(ajaxCall) {
        ajaxCall.abort();
      });
      ajaxCalls = [];
      var query = input.val();
      var searcher = extractClassParams(input);
      var hiddenFilters = [];
      $('#searchForm').find('input[name="hiddenFilters[]"], input[name="filter[]"]').each(
        function hiddenFiltersEach() {
          hiddenFilters.push($(this).val());
        });
      const type = searcher.type ? searcher.type : $('#searchForm_type').val();
      const searchTypes = {
        'AllFields': ['Title', 'Author', 'Subject'],
        'AllLibraries': ['Name', 'Town'],
      };
      var types = searchTypes[type] ? searchTypes[type] : [type];
      if (type === 'AllFields' && query.trim().split(/\s+/).length > 1) {
        types.push('AuthorTitle');
      }
      const limit = (types.length > 1) ? 6 : 10;
      types.forEach(function forEach(searchType) {
        var ajaxCall = $.ajax({
          url: VuFind.path + '/AJAX/JSON',
          data: {
            q: query,
            method: 'getACSuggestions',
            searcher: searcher.searcher,
            type: searchType,
            limit: limit,
            hiddenFilters: hiddenFilters
          },
          context: {
            type: searchType
          },
          dataType: 'json',
        });
        ajaxCalls.push(ajaxCall);
      });
      var onSuccess = function onSuccess(currentRequestId) {
        return function newFunction() {
          if (currentRequestId !== requestId) {
            return;
          }
          var contexts = Array.isArray(this) ? this : [this];
          var results = Array.isArray(this) ? Array.from(arguments) : [arguments];
          var data = {
            groups: []
          };
          var suggestions = new Map();
          results.forEach(function forEach(result, index) {
            var hasSuggestions = result[0].data.groups.length > 0;
            if (hasSuggestions) {
              suggestions.set(contexts[index].type, result[0].data.groups[0]);
            }
          });
          if (type === 'AllFields' && suggestions.has('Author')
            && suggestions.has('Title')) {
            suggestions.delete('AuthorTitle');
          }
          for (const [, value] of suggestions.entries()) {
            data.groups.push(value);
          }
          cb(data);
        };
      };
      $.when.apply($, ajaxCalls).then(onSuccess(++requestId));
    }
  });
  $('#searchForm_lookfor').on("ac:select", function onSelect(event, item) {
    $('#searchForm_type').val(item.type);
  });
  // Update autocomplete on type change
  $('#searchForm_type').on("change", function searchTypeChange() {
    searchbox.autocomplete().clearCache();
  });
}

function setupOpenUrl() {
  $('.openurl').each(function onEachOpenUrl() {
    var element = this;
    var ajaxCall = {
      dataType: "json",
      url: "/AJAX/JSON?method=sfx&" + $(element).data('openurl'),
      method: "GET",
      success: function sfx(json){
        $(element).empty();
        $(element).off('click');
        let links = json.data;
        let header = 'Fulltext is available for users of these institutions';
        if (links.length === 0) {
          header = 'Fulltext not found';
        } else if (links.default) {
          header = 'Fulltext is free';
        } else if (links.length === 1) {
          header = 'Fulltext is available for users of this institution';
        }
        $(element).append($('<div>', {
          class: 'records-in-libraries-title',
          html: $('<strong>', { text: VuFind.translate(header) }),
        }));
        let list = $('<ul>', {
          class: 'list-unstyled'
        });
        let dropDownMenu = null;
        let dropDownList = null;
        let dropdown = $(element).data('dropdown');
        if (dropdown && Object.keys(links).length > 3) {
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

function setLibraryAutoComplete(element, data) {
  const facetFilter = element.data('facet');
  const paramSep = window.location.href.includes('?') ? '&' : '?';
  var libraries = new Map();
  function traverse(item) {
    if (Array.isArray(item)) {
      item.forEach(it => traverse(it));
    } else if ('children' in item && item.children.length > 0) {
      traverse(item.children);
    } else {
      const value = item.value;
      const filter = paramSep + "filter[]=~" + facetFilter + ':"' + value + '"';
      libraries.set(value, {
        label: item.displayText,
        value: value,
        href: window.location.href + filter,
      });
    }
  }
  traverse(data);

  var input = $('<input></input>').addClass('autocomplete-institutions form-control')
    .attr('placeholder', VuFind.translate('Autocomplete institutions placeholder'));
  function normalizeString(str) {
    return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
  }
  // Search autocomplete
  input.autocomplete({
    rtl: $(document.body).hasClass("rtl"),
    maxResults: 10,
    loadingString: VuFind.translate('loading') + '...',
    // AJAX call for autocomplete results
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
  element.parent().prepend(input);
}

VuFind.listen('VuFind.sidefacets.buildtree', function onLoaded(event){
  var facet = event.detail.node[0].id;
  const facets = [
    'facet_region_institution_facet_mv',
    'facet_local_region_institution_facet_mv'
  ];
  if (!facets.includes(facet)) {
    return;
  }
  var data = event.detail.data;
  var element = $(document.getElementById(facet));
  setLibraryAutoComplete(element, data);
});

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

/* eslint-disable no-undef */
if (typeof buildFacetTree === 'function') {
  buildFacetTree = (function wrapper(_super) {
    return function callback() {
      var treeNode = arguments[0];
      var facetData = arguments[1];
      VuFind.emit('VuFind.sidefacets.buildtree', {node: treeNode, data: facetData});
      return _super.apply(this, arguments);
    };
  })(buildFacetTree);
}

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
