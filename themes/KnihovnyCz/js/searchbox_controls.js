/*global VuFind, extractClassParams */

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

  function _showKeyboard() {
    if (_enabled) {
      _keyboard.setOptions({
        theme: `${_defaultTheme} show-keyboard`
      });
    }
  }

  function _hideKeyboard() {
    _keyboard.setOptions({
      theme: _defaultTheme
    });
  }

  function _onChange(input) {
    _textInput.value = input;
    _textInput.dispatchEvent(new Event("input"));
  }

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

  function _updateKeyboardLayout(layoutName) {
    $('.keyboard-selection-item').each(function deactivateItems() {
      $(this).parent().removeClass("active");
    });
    $(".keyboard-selection-item[data-value='" + layoutName + "']").parent().addClass("active");
    window.Cookies.set("keyboard", layoutName);
    if (layoutName === "none") {
      $("#keyboard-selection-button").removeClass("activated");
      _enabled = false;
      _hideKeyboard();
    } else {
      $("#keyboard-selection-button").addClass("activated");
      _enabled = true;
      const keyboardLayout = new _KeyboardLayoutClass().get(layoutName);
      _keyboard.setOptions({layout: keyboardLayout.layout});
      _showKeyboard();
    }
  }

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
      function hasClass(el, className) {
        return el.className !== undefined && el.className.includes(className);
      }
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

    let layout = window.Cookies.get("keyboard");
    if (layout == null) {
      layout = "none";
    }
    _updateKeyboardLayout(layout);
    _hideKeyboard();
  }

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
        var searcher = extractClassParams(input.get(0));
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

    _resetButton.addEventListener("click", function resetOnClick() {
      requestAnimationFrame(() => {
        _textInput.value = "";
        _textInput.dispatchEvent(new Event("input"));
        _textInput.focus();
      });
    });
  }

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

