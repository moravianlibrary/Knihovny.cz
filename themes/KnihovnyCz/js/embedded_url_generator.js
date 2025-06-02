/*global VuFind */

const baseUrl = "https://www.knihovny.cz/Search/embedded?";
const allowedFilterKeys = [
  'record_format_facet_mv',
  'region_institution_facet_mv',
  'statuses_facet_mv',
  'subject_facet_mv',
  'ziskej_facet_mv',
  'source_title_facet',
  'conspectus_facet_mv',
  'publisher_facet_mv',
  'author_facet_mv',
  'language_facet_mv',
  'genre_facet_mv',
  'country_facet_mv',
];

$(function jQueryReady() {

  /**
   * Do we use facet filters?
   *
   * @returns {boolean}
   */
  function useFilterParams() {
    const db = $('input[name="database"]:checked').val() || '';

    return db !== 'libraries';
  }

  /**
   * Parse URL with search parameters.
   *
   * @param url
   * @param allowedKeys
   * @returns {{params: string[], usedFilterKeys: string[]}}
   */
  function extractAllowedFilterKeysFromUrl(url, allowedKeys) {
    let params = [];
    let usedFilterKeys = [];

    let a = document.createElement('a');
    a.href = url;

    let query = a.search.substring(1);
    if (!query) return '';

    let pairs = query.split('&');

    pairs.forEach(function parseFilterParam(pair) {
      if (!pair) return;

      let parts = pair.split('=');
      let rawKey = decodeURIComponent(parts[0] || '');
      let rawVal = decodeURIComponent(parts[1] || '');

      if (rawKey !== 'filter[]') return;

      // Extract and check key (e.g. record_format_facet_mv): ~key:"value"
      let keyMatch = rawVal.match(/^~?([^:]+):".*"$/);
      if (!keyMatch) return;

      let key = keyMatch[1];

      if (allowedKeys.indexOf(key) === -1) return;

      usedFilterKeys.push(key);
      params.push('filter[]=' + rawVal);
    });

    let usedFilterKeysUnique = [];

    usedFilterKeys.forEach(function filterUnique(item) {
      if (usedFilterKeysUnique.indexOf(item) === -1) {
        usedFilterKeysUnique.push(item);
      }
    });

    return {
      params: params, // valid parameters (e.g. "filter[]=~record_format_facet_mv:...")
      usedFilterKeys: usedFilterKeysUnique // keys used in parameters (e.g. ["record_format_facet_mv", "conspectus_facet_mv"])
    };
  }

  /**
   * Print facet filters names in HTML.
   * @param usedFilterKeys
   */
  function updateUsedFilterKeysOutput(usedFilterKeys) {
    var output = usedFilterKeys.map(function buildFilterInfoBubble(key) {
      return '<span class="btn btn-default btn-s disabled">' + (VuFind.translate(key) || key) + '</span>';
    });

    $('#usedFiltersOutput').html(output.length ? output.join(' ') : '—');
  }

  /**
   * Process inputs and print result in html div.
   */
  function updateUrl() {
    const params = [];
    let usedFilterKeys = [];

    // database
    const db = $('input[name="database"]:checked').val();

    // lookfor
    const lookfor = $('#lookfor').val().trim();
    if (lookfor) {
      params.push('lookfor=' + encodeURIComponent(lookfor));
    }

    // position
    const positionInput = $('#position');
    if (positionInput.is(':checked')) {
      params.push('position=' + encodeURIComponent(positionInput.val()));
    }

    // lng
    const lng = $('input[name="lng"]:checked').val();
    if (lng) {
      params.push('lng=' + encodeURIComponent(lng));
    }

    // database
    if (db) {
      params.push('database=' + encodeURIComponent(db));
    }

    // facets
    const urlToParse = $('#urlToParse').val().trim();
    if (urlToParse && useFilterParams()) {
      var parsedData = extractAllowedFilterKeysFromUrl(urlToParse, allowedFilterKeys);

      params.push(parsedData.params.join('&'));
      usedFilterKeys = parsedData.usedFilterKeys;
    }

    // print used filters
    updateUsedFilterKeysOutput(usedFilterKeys);

    // print final url
    const finalUrl = encodeURI(baseUrl + params.join('&'));
    $('#embeddedUrl').text(finalUrl);
  }

  updateUrl(); // fill initial url value

  // Trigger on change
  $('#filter-form').on('change input', 'select, input', function filterFormModified() {
    const urlToParse = $('#urlToParse');

    if (useFilterParams()) {
      urlToParse.removeAttr('disabled');
    } else {
      urlToParse.attr('disabled', 'disabled');
    }

    updateUrl();
  });

  // Copy to clipboard button
  $('#copyUrlBtn').on('click', function copyUrlButtonClicked() {
    const textToCopy = $('#embeddedUrl').text().trim();
    const $success = $('#copySuccess');
    const $fail = $('#copyFail');

    function showMessage($el) {
      $success.hide();
      $fail.hide();
      $el.show();
      setTimeout(() => $el.hide(), 600);
    }

    if (navigator.clipboard) {
      navigator.clipboard.writeText(textToCopy).then(() => {
        showMessage($success);
      }).catch(() => {
        showMessage($fail);
      });
    } else {
      const tempInput = $('<input>');
      $('body').append(tempInput);
      tempInput.val(textToCopy).select();
      const successful = document.execCommand('copy');
      tempInput.remove();

      if (successful) {
        showMessage($success);
      } else {
        showMessage($fail);
      }
    }
  });
});
