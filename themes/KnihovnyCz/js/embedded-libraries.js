$(function libraryListHandlers() {
  function showElement(e) {
    e.removeClass('hidden');
    e.addClass('show');
  }

  function hideElement(e) {
    e.removeClass('show');
    e.addClass('hidden');
  }

  $('#search').on('keyup', function searchDirectoryOnKeyup() {
    let searchedText = $(this).val().toLowerCase();
    $('.card-library').filter(function filterDirectoryByText() {
      let card = $(this);
      let isContains = $(this).text().toLowerCase().indexOf(searchedText) > -1;
      if (isContains) {
        showElement(card);
      } else {
        hideElement(card);
      }
      let row = card.closest('.row');
      let numOfDisplayedColumns = row.children('.card-library.show').length;
      let panel = row.closest('.panel');
      if (numOfDisplayedColumns > 0) {
        showElement(panel);
      } else {
        hideElement(panel);
      }
    });
  });
});
