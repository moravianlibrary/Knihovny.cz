/* exported initMap */
/* global google, VuFind, MarkerClusterer */
const LIBRARY_MAP_MAX_ZOOM = 14;

function hideMapLoader() {
  $('#map-loader').addClass('hidden');
}

function showMap() {
  hideMapLoader();
  $('#map').css('height', '600px').removeClass('hidden');
}

function initialize(libraries) {
  let mapCenter = { lat: 49.78, lng: 15.39 };
  let markers = [];
  let map = new google.maps.Map(document.getElementById('map'), {
    zoom: 7,
    center: mapCenter
  });
  let bounds = new google.maps.LatLngBounds();

  let info = [];

  /* jshint -W083 */
  for (let i = 0; i < libraries.length; i++) {
    let library = libraries[i];
    if (isNaN(library.coordinates.lat)
      || library.coordinates.lat === undefined
      || isNaN(library.coordinates.lng)
      || library.coordinates.lng === undefined
    ) {
      continue;
    }
    let contentString = '<div id="content" class="marker-info">' +
      '<div class="marker-title">' + library.title + '</div>' +
      '<div class="marker-subtitle">' + library.address[0] + '</div>' +
      '<div class="marker-link"><strong><a href="/LibraryRecord/' + library.id +
      '">' + VuFind.translate('Library detail') + '</a></strong>' +
    '</div>';
    info[i] = new google.maps.InfoWindow({ content: contentString });

    let marker = new google.maps.Marker({
      position: new google.maps.LatLng(library.coordinates.lat, library.coordinates.lng),
      map: map,
      title: library.title
    });

    bounds.extend(marker.getPosition());
    markers.push(marker);

    marker.addListener("click", () => {
      let isMap = info[i].getMap();
      info.forEach(function closeInfoWindows(infoWindow) {
        infoWindow.close();
      });
      if (!isMap) {
        info[i].open(map, marker);
      }
    });
  }

  if (markers.length !== 0 ) {
    map.fitBounds(bounds);
    let mcOptions = { gridSize: 75, maxZoom: 10, imagePath: '/themes/KnihovnyCz/images/markerclusterer/m' };
    new MarkerClusterer(map, markers, mcOptions);
    google.maps.event.addListenerOnce(map, 'zoom_changed', function fixInitialZoom() {
      if (map.getZoom() > LIBRARY_MAP_MAX_ZOOM) {
        map.setZoom(LIBRARY_MAP_MAX_ZOOM);
      }
    });
    showMap();
  } else {
    hideMapLoader();
  }
}

function loadPage(url, page, records) {
  $.ajax(url, {
    data: { page: page },
    error: function error() {
      console.error('Cannot load data from API: ' + url + ', page: ' + page);
    },
    success: function success(data) {
      if (data.error) {
        hideMapLoader();
        console.error(data.error);
      } else {
        if (data.resultCount === 0) {
          hideMapLoader();
          return;
        }
        records.push(...data.records);
        if (data.resultCount <= page * 1000) {
          initialize(records);
        } else {
          loadPage(url, page + 1, records);
        }
      }
    }
  });
}

function initMap(url) {
  let map = $(
    '<div class="row">' +
    '<div id="map-loader" class="col-xs-12 text-center">' + VuFind.spinner('fa-3x fa-fw') + '</div>' +
    '<div id="map" class="hidden"></div>' +
    '</div>'
  );
  $('.search-header').after(map);
  if (undefined !== url) {
    loadPage(url, 1, []);
  }
}
