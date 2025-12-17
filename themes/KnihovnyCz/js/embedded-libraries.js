$(function libraryListHandlers() {
  function showElement(e) {
    e.removeClass('hidden');
    e.addClass('show');
  }

  function hideElement(e) {
    e.removeClass('show');
    e.addClass('hidden');
  }

  function searchDirectoryOnKeyup(searchedText, isProfessional, isNonProfessional, isRegional) {
    $('.card-library').filter(function filterDirectoryByText() {
      let card = $(this);
      let isContains = card.text().toLowerCase().indexOf(searchedText) > -1;

      if (!isRegional && !isProfessional && !isNonProfessional) {
        if (isContains) {
          showElement(card);
        } else {
          hideElement(card);
        }
      } else if (!isRegional && isProfessional && !isNonProfessional) {
        if (isContains && card.data('professional')) {
          showElement(card);
        } else {
          hideElement(card);
        }
      } else if (!isRegional && !isProfessional && isNonProfessional) {
        if (isContains && !card.data('professional')) {
          showElement(card);
        } else {
          hideElement(card);
        }
      } else if (isRegional && !isProfessional && !isNonProfessional) {
        if (isContains && card.data('regional')) {
          showElement(card);
        } else {
          hideElement(card);
        }
      } else if (isRegional && isProfessional && !isNonProfessional) {
        if (isContains && card.data('regional') && card.data('professional')) {
          showElement(card);
        } else {
          hideElement(card);
        }
      } else if (isRegional && !isProfessional && isNonProfessional) {
        if (isContains && card.data('regional') && !card.data('professional')) {
          showElement(card);
        } else {
          hideElement(card);
        }
      }

      let row = card.closest('.row');
      let numOfDisplayedColumns = row.children('.card-library.show').length;
      let panel = row.closest('.card');
      if (numOfDisplayedColumns > 0) {
        showElement(panel);
      } else {
        hideElement(panel);
      }
    });
  }

  $('#btn_filter').on('click', function btnFilterClicked() {
    let searchedText = $('input#search').val().toLowerCase();
    let isProfessional = $('input#is_professional').is(':checked');
    let isNonProfessional = $('input#is_nonprofessional').is(':checked');
    let isRegional = $('input#is_regional').is(':checked');
    searchDirectoryOnKeyup(searchedText, isProfessional, isNonProfessional, isRegional);
  });

  $('input#is_professional').on('click', function isProfessionalClicked() {
    if ($('input#is_professional').is(':checked')) {
      $('input#is_nonprofessional').attr('disabled', true);
    } else {
      $('input#is_nonprofessional').attr('disabled', false);
    }
    $('#btn_filter').trigger('click');
  });
  $('input#is_nonprofessional').on('click', function isNonProfessionalClicked() {
    if ($('input#is_nonprofessional').is(':checked')) {
      $('input#is_professional').attr('disabled', true);
    } else {
      $('input#is_professional').attr('disabled', false);
    }
    $('#btn_filter').trigger('click');
  });
  $('input#is_regional').on('click', function isRegionalClicked() {
    $('#btn_filter').trigger('click');
  });
  $('input#search').on('keyup', function searchInputKeyup() {
    $('#btn_filter').trigger('click');
  });
});
