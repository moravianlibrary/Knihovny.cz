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
      input.val(item.value);
      $("#searchForm").submit();
      return false;
    };
  }
  // Search autocomplete
  searchbox.autocomplete({
    rtl: $(document.body).hasClass("rtl"),
    maxResults: 10,
    loadingString: VuFind.translate('loading_ellipsis'),
    // Auto-submit selected item
    callback: acCallback,
    // AJAX call for autocomplete results
    handler: function vufindACHandler(input, cb) {
      var query = input.val();
      var searcher = extractClassParams(input);
      var hiddenFilters = [];
      $('#searchForm').find('input[name="hiddenFilters[]"]').each(
        function hiddenFiltersEach() {
          hiddenFilters.push($(this).val());
        });
      $.fn.autocomplete.ajax({
        url: VuFind.path + '/AJAX/JSON',
        data: {
          q: query,
          method: 'getACSuggestions',
          searcher: searcher.searcher,
          type: searcher.type ? searcher.type : $('#searchForm_type').val(),
          hiddenFilters: hiddenFilters
        },
        dataType: 'json',
        success: function autocompleteJSON(json) {
          cb(json.data);
        }
      });
    }
  });
  $('#searchForm_lookfor').on("ac:select", function onSelect(event, item) {
    $('#searchForm_type').val(item.type);
  });
  // Update autocomplete on type change
  $('#searchForm_type').change(function searchTypeChange() {
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
        $(element).unbind('click');
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
      $(element).click(function onClick() {
        $(element).empty().append(VuFind.spinner('fa-3x fa-fw'));
        $.ajax(ajaxCall);
      });
    } else {
      $.ajax(ajaxCall);
    }
  });
}

jQuery(document).ready(function openUrl() {
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

  var input = $('<input></input>').addClass('autocomplete-institutions')
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

VuFind.listen('VuFind.sidefacets.loaded', function onLoaded(){
  const facets = [
    'facet_region_institution_facet_mv',
    'facet_local_region_institution_facet_mv'
  ];
  facets.forEach(function forEeach(facet){
    var element = document.getElementById(facet);
    if (element != null) {
      $(element).bind('loaded.jstree', function onLoad() {
        setLibraryAutoComplete(facet);
      });
    }
  });
});

function buildFacetNodes(data, currentPath, allowExclude, excludeTitle, counts)
{
  var json = [];
  var selected = VuFind.translate('Selected');
  var separator = VuFind.translate('number_thousands_separator');

  for (var i = 0; i < data.length; i++) {
    var facet = data[i];
    var html = document.createElement('div');
    html.className = 'facet';

    var url = currentPath + facet.href;
    var item = document.createElement('span');
    item.className = 'main text';
    if (facet.isApplied) {
      item.className += ' applied';
    }
    if (facet.count === 0) {
      item.className += ' emptyFacet';
    }
    item.setAttribute('title', facet.displayText);
    item.setAttribute('role', 'menuitem');
    var icon = document.createElement('i');
    icon.className = 'fa';
    if (facet.operator === 'OR') {
      if (facet.isApplied) {
        icon.className += ' fa-check-square-o';
        icon.title = selected;
      } else {
        icon.className += ' fa-square-o';
        icon.setAttribute('aria-hidden', 'true');
      }
      item.appendChild(icon);
    } else if (facet.isApplied) {
      icon.className += ' fa-check pull-right';
      icon.setAttribute('title', selected);
      item.appendChild(icon);
    }
    var description = document.createElement('span');
    description.className = 'facet-value';
    description.appendChild(document.createTextNode(facet.displayText));
    description.setAttribute('data-filter-value', facet.value);
    item.appendChild(description);
    html.appendChild(item);

    if (!facet.isApplied && counts) {
      var badge = document.createElement('span');
      badge.className = 'badge';
      badge.appendChild(document.createTextNode(facet.count.toString().replace(/\B(?=(\d{3})+\b)/g, separator)));
      html.appendChild(badge);
      if (allowExclude) {
        var excludeUrl = currentPath + facet.exclude;
        var a = document.createElement('a');
        a.className = 'exclude';
        a.setAttribute('href', excludeUrl);
        a.setAttribute('title', excludeTitle);

        var inIcon = document.createElement('i');
        inIcon.className = 'fa fa-times';
        a.appendChild(inIcon);
        html.appendChild(a);
      }
    }

    var children = null;
    if (typeof facet.children !== 'undefined' && facet.children.length > 0) {
      children = buildFacetNodes(facet.children, currentPath, allowExclude, excludeTitle, counts);
    }
    json.push({
      'text': html.outerHTML,
      'children': children,
      'applied': facet.isApplied,
      'state': {
        'opened': facet.hasAppliedChildren
      },
      'li_attr': facet.isApplied ? { 'class': 'active' } : {},
      'data': {
        'url': url.replace(/&amp;/g, '&')
      }
    });
  }

  return json;
}

/* eslint-disable no-undef */
buildFacetTree = (function wrapper(_super) {
  return function callback() {
    var treeNode = arguments[0];
    var facetData = arguments[1];
    VuFind.emit('VuFind.sidefacets.buildtree', {node: treeNode, data: facetData});
    return _super.apply(this, arguments);
  };
})(buildFacetTree);
