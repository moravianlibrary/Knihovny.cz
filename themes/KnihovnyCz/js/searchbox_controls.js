/*global Autocomplete, VuFind, extractClassParams */

VuFind.register('searchbox_controls', function SearchboxControls() {
  let _KeyboardClass;
  let _KeyboardLayoutClass;

  let _textInput;
  let _resetButton;

  let _enabled = false;
  let _keyboard;
  const _defaultTheme = "hg-theme-default";
  const _display = {
    "{bksp}": "&#10229;",
    "{enter}": "&#8626;",
    "{shift}": "&#8679;",
    "{tab}": "&#8633;",
    "{lock}": "&#8681;",
  };

  /**
   * Show the virtual keyboard by applying a CSS class.
   */
  function _showKeyboard() {
    if (_enabled) {
      _keyboard.setOptions({
        theme: `${_defaultTheme} show-keyboard`
      });
    }
  }

  /**
   * Hide the virtual keyboard.
   */
  function _hideKeyboard() {
    _keyboard.setOptions({
      theme: _defaultTheme
    });
  }

  /**
   * Handle changes from the virtual keyboard, updating the search input and dispatching an 'input' event.
   * @param {string} input The new input value.
   */
  function _onChange(input) {
    _textInput.value = input;
    _textInput.dispatchEvent(new Event("input"));
  }

  /**
   * Handle button presses on the virtual keyboard.
   * @param {string} button The button pressed.
   */
  function _onKeyPress(button) {
    if (button === "{shift}" || button === "{lock}") {
      let currentLayoutType = _keyboard.options.layoutName;
      _keyboard.setOptions({
        layoutName: currentLayoutType === "default" ? "shift" : "default"
      });
    }

    if (button === "{enter}") {
      document.getElementById("searchForm").submit();
    }

    requestAnimationFrame(() => {
      let caretPos = _keyboard.getCaretPosition();
      if (caretPos) {
        _textInput.setSelectionRange(caretPos, caretPos);
      }
    });
  }

  /**
   * Update the virtual keyboard layout based on user selection.
   * @param {string} layoutName The name of the layout to switch to.
   */
  function _updateKeyboardLayout(layoutName) {
    $('.keyboard-selection-item').each(function deactivateItems() {
      $(this).removeClass("active");
    });
    $(".keyboard-selection-item[data-value='" + layoutName + "']").addClass("active");
    if (layoutName === "none") {
      VuFind.cookie.remove("keyboard");
      $("#keyboard-selection-button").removeClass("activated");
      _enabled = false;
      _hideKeyboard();
    } else {
      VuFind.cookie.set("keyboard", layoutName);
      $("#keyboard-selection-button").addClass("activated");
      _enabled = true;
      const keyboardLayout = new _KeyboardLayoutClass().get(layoutName);
      _keyboard.setOptions({layout: keyboardLayout.layout});
      _showKeyboard();
    }
  }

  /**
   * Set up the virtual keyboard functionality.
   */
  function setupKeyboard() {
    if (!_textInput) {
      return;
    }

    _KeyboardClass = window.SimpleKeyboard.default;
    _KeyboardLayoutClass = window.SimpleKeyboardLayouts.default;

    $('.keyboard-selection-item').on("click", function updateLayoutOnClick(ev) {
      _updateKeyboardLayout($(this).data("value"));
      ev.preventDefault();
    });

    _textInput.addEventListener("focus", () => {
      _showKeyboard();
    });
    _textInput.addEventListener("click", () => {
      _showKeyboard();
    });
    _textInput.addEventListener("input", (event) => {
      _keyboard.setInput(event.target.value);
    });
    _textInput.addEventListener("keydown", (event) => {
      if (event.shiftKey) {
        _keyboard.setOptions({
          layoutName: "shift"
        });
      }
    });
    _textInput.addEventListener("keyup", (event) => {
      if (!event.shiftKey) {
        _keyboard.setOptions({
          layoutName: "default"
        });
      }
    });

    document.addEventListener("click", (event) => {
      if (!_keyboard.options.theme.includes('show-keyboard')) {
        return;
      }
      /**
       * Check if an element has a specific class
       * @param {HTMLElement} el        The element to check. 
       * @param {string}      className The class name to search for.
       * @returns {boolean} Return true of the element has the class name.
       */
      function hasClass(el, className) {
        return el.className !== undefined && el.className.includes(className);
      }
      /**
       * Check if an element has a specific id
       * @param {HTMLElement} el The element to check.
       * @param {string}      id The id to search for
       * @returns {boolean} Return true if the element has the specific id.
       */
      function hasId(el, id) {
        return el.id === id;
      }
      if (
        event.target.parentNode == null ||
        event.target.parentNode.parentNode == null || (
          !hasClass(event.target, 'searchForm_lookfor')
          && !hasClass(event.target, 'keyboard-selection')
          && !hasClass(event.target, 'hg-button')
          && !hasClass(event.target, 'hg-row')
          && !hasClass(event.target, 'simple-keyboard')
          && !hasClass(event.target, 'searchForm-reset')
          && !hasClass(event.target.parentNode, 'keyboard-selection')
          && !hasClass(event.target.parentNode, 'searchForm-reset')
          && !hasClass(event.target.parentNode.parentNode, 'keyboard-selection')
        )
      ) {
        _hideKeyboard();
      } else if (
        event.target.parentNode == null || (
          !hasId(event.target, 'keyboard-selection-button')
          && !hasId(event.target.parentNode, 'keyboard-selection-button')
        )
      ) {
        _textInput.focus();
      }
    });

    _keyboard = new _KeyboardClass({
      onChange: input => _onChange(input),
      onKeyPress: button => _onKeyPress(button),
      display: _display,
      syncInstanceInputs: true,
      mergeDisplay: true,
      physicalKeyboardHighlight: true,
      preventMouseDownDefault: true,
    });

    _keyboard.setInput(_textInput.value);

    let layout = VuFind.cookie.get("keyboard");
    if (layout == null) {
      layout = "none";
    }
    _updateKeyboardLayout(layout);
    _hideKeyboard();
  }

  /**
   * Set up the searchbox autocomplete functionality.
   */
  function setupAutocomplete() {
    // If .autocomplete class is missing, autocomplete is disabled and we should bail out.
    var $searchboxes = $('input.autocomplete');
    $searchboxes.each(function processAutocompleteForSearchbox(i, searchboxElement) {
      const $searchbox = $(searchboxElement);
      const typeFieldSelector = $searchbox.data('autocompleteTypeFieldSelector');
      const typePrefix = $searchbox.data('autocompleteTypePrefix');
      const typeahead = new Autocomplete({
        rtl: $(document.body).hasClass("rtl"),
        limit: 10,
        loadingString: VuFind.translate('loading_ellipsis'),
        delay: 500,
      });
      let requestId = 0;
      let ajaxCalls = [];

      let cache = {};
      const input = $searchbox[0];
      typeahead(input, function vufindACHandler(query, callback) {
        ajaxCalls.forEach(function forEach(ajaxCall) {
          ajaxCall.abort();
        });
        ajaxCalls = [];
        const classParams = extractClassParams(input);
        const searcher = classParams.searcher;
        const selectedType = classParams.type
          ? classParams.type
          : $(typeFieldSelector ? typeFieldSelector : '#searchForm_type').val();
        const type = (typePrefix ? typePrefix : "") + selectedType;

        const cacheKey = searcher + "|" + type;
        if (typeof cache[cacheKey] === "undefined") {
          cache[cacheKey] = {};
        }

        if (typeof cache[cacheKey][query] !== "undefined") {
          callback(cache[cacheKey][query]);
          return;
        }

        var hiddenFilters = [];
        $('#searchForm').find('input[name="hiddenFilters[]"], input[name="filter[]"]').each(
          function hiddenFiltersEach() {
            hiddenFilters.push($(this).val());
          }
        );
        const searchTypes = {
          'AllFields': ['Title', 'Author', 'Subject'],
          'adv_search_without_fulltext': ['Title', 'Author', 'Subject'],
          'AllLibraries': ['Name', 'Town'],
        };
        let types = searchTypes[type] ? searchTypes[type] : [type];
        if (
          (type === 'AllFields' || type === 'adv_search_without_fulltext')
          && query.trim().split(/\s+/).length > 1
        ) {
          types.push('AuthorTitle');
        }
        const limit = (types.length > 1) ? 6 : 10;
        types.forEach(function forEach(searchType) {
          const ajaxCall = $.ajax({
            url: VuFind.path + '/AJAX/JSON',
            data: {
              q: query,
              method: 'getACSuggestions',
              searcher: searcher,
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
        const onSuccess = function onSuccess(currentRequestId) {
          return function onSuccessCallback() {
            if (currentRequestId !== requestId) {
              return;
            }
            const responses = Array.isArray(this) ? Array.from(arguments) : [arguments];
            const result = [];
            responses.forEach(function forEachResponse(response){
              response[0].data.groups.forEach(function processGroup(group) {
                result.push({ _header: group.label });
                group.items.forEach(function processItem(item){
                  result.push({ 'text': item.label, 'value': item.value, 'type': item.type });
                });
              });
            });
            callback(result);
            cache[cacheKey][query] = result;
          };
        };
        $.when.apply($, ajaxCalls).then(onSuccess(++requestId));
      });

      // Bind autocomplete auto submit
      if ($searchbox.hasClass("ac-auto-submit")) {
        input.addEventListener("ac-select", (event) => {
          const type = typeof event.detail === "string" ? null : event.detail.type;
          if (type !== undefined) {
            $('#searchForm_type').val(type);
          }
          input.value = typeof event.detail === "string"
            ? event.detail
            : event.detail.value;
          input.form.submit();
        });
      }
    });
  }

  /**
   * Set up the searchbox reset button.
   */
  function setupSearchResetButton() {
    _resetButton = document.getElementById("searchForm-reset");

    if (!_resetButton || !_textInput) {
      return;
    }

    if (_textInput.value !== "") {
      _resetButton.classList.remove("hidden");
    }

    _textInput.addEventListener("input", function resetOnInput() {
      _resetButton.classList.toggle("hidden", _textInput.value === "");
    });

    _resetButton.addEventListener("click", function resetOnClick(e) {
      e.preventDefault();
      requestAnimationFrame(() => {
        _textInput.value = "";
        _textInput.dispatchEvent(new Event("input"));
        _textInput.focus();
      });
    });
  }

  /**
   * Initialize the searchbox controls module.
   */
  function init() {
    _textInput = document.getElementById("searchForm_lookfor");

    setupAutocomplete();
    setupSearchResetButton();

    // Setup keyboard
    if (typeof window.SimpleKeyboard !== 'undefined') {
      setupKeyboard();
    }
  }

  return {
    init: init
  };
});
