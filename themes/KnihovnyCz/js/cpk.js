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
    loadingString: VuFind.translate('loading') + '...',
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
