/*global VuFind */
function loadObalkyKnihByElement(data, element) {
  var url = VuFind.path + '/AJAX/JSON?method=' + 'getObalkyKnihCoverWithoutSolr';
  var img = element.find('img');
  var spinner = element.find('div.spinner');
  var container = element.find('div.cover-container');
  var source = $('<p class="cover-source">' + VuFind.translate('cover_source_label') + ' </p>');
  function coverCallback(response) {
    if (typeof response.data.url !== 'undefined' && response.data.url !== false) {
      img.attr("src", response.data.url);
      var inlink = element.parent().is('a.record-cover-link');
      var medium = img.parents('.media-left, .media-right, .carousel-item');
      if (inlink === true) {
        img.detach();
        medium.children('a').prepend(img);
        container.parents('.ajaxcoverobalkyknih').remove();
      }
    } else {
      img.remove();
      source.remove();
      if (typeof response.data.html !== 'undefined') {
        container.html(response.data.html);
      } else {
        container.html();
      }
    }
    spinner.hide();
    container.show();
  }
  $.ajax({
    dataType: "json",
    url: url,
    method: "GET",
    data: data,
    element: element,
    success: coverCallback
  });
}

function loadObalkyKnih() {
  $('.ajaxcoverobalkyknih').each(function getDataAndLoadCovers() {
    var img = $(this).find('img');

    var data = {
      recordId: img.data('id'),
      size: img.data('coversize'),
    };
    ['isbn', 'ismn', 'issn', 'ean', 'cnb', 'format'].forEach(function createData(key) {
      let value = img.data(key);
      if (value) {
        data[key] = value;
      }
    });
    loadObalkyKnihByElement(data, $(this));
  });
}
$(loadObalkyKnih);
