/* global dataLayer */
/**
 * Specification:
 * https://docs.google.com/document/d/1Z0BOH1kbeUvsbVGyVe92rw0l462vwCd1_6Dz3RPyRZI/edit
 */

/*
 * Vyhledávání – fulltext
 * Spouští se po odeslání formulářového pole.
 */
// on submit search form
$(document).on('submit', $('form#searchForm'), function reportSubmitForm() {
  dataLayer.push({
    'event': 'action.search',
    'actionContext': {
      'eventCategory': 'search',
      'eventAction': 'fulltext',
      'eventLabel': $('input#searchForm_lookfor').val(),
      'eventValue': undefined,
      'nonInteraction': false
    }
  });
});

/*
 * Vyhledávání – našeptávač
 * Spouští se po 2000 ms po posledním stisku klávesy.
 */
let typingTimer;
const $input = $('input#searchForm_lookfor');

function doneTyping() {
  dataLayer.push({
    'event': 'action.search',
    'actionContext': {
      'eventCategory': 'search',
      'eventAction': 'whisperer',
      'eventLabel': $input.val(),
      'eventValue': undefined,
      'nonInteraction': false
    }
  });
}

$input.on('keyup', function createTimeout() {
  clearTimeout(typingTimer);
  typingTimer = setTimeout(doneTyping, 2000);
});

$input.on('keydown', function createTimeout() {
  clearTimeout(typingTimer);
});

/*
 * Vyhledávání – uložení vyhledávání
 * Spouští se po kliku na tlačítko k uložení vyhledávání.
 */
$(document).on('click', '#btnSearchSave', function reportSaveSearch() {
  dataLayer.push({
    'event': 'action.search',
    'actionContext': {
      'eventCategory': 'search',
      'eventAction': 'saveSearch',
      'eventLabel': $('#searchForm_lookfor').val(),
      'eventValue': undefined,
      'nonInteraction': false
    }
  });
});

/* Použití facetů
 * Spouští se s každou zaškrtnutou položkou.
 */
$('.jstree-anchor, .js-facet-item').on('click', function reportFacets() {
  let action = $(this).find('.facet-value').text();
  let type = $(this).parents('.collapse').data('facet');
  let useFacet = 1;
  if ($(this).hasClass('active') || $(this).find('.facet > span').hasClass('applied')) {
    useFacet = 0;
  }
  dataLayer.push({
    'event': 'action.facet',
    'actionContext': {
      'eventCategory': 'facet',
      'eventAction': action,
      'eventLabel': type,
      'eventValue': useFacet,
      'nonInteraction': false
    }
  });
});

/* Přihlášení */
$('#loginOptions a').on('click', function reportLogin() {
  dataLayer.push({
    'event': 'action.login',
    'actionContext': {
      'eventCategory': 'login',
      'eventAction': undefined,
      'eventLabel': undefined,
      'eventValue': undefined,
      'nonInteraction': false
    }
  });
});

/* Favorites from search results */
$('a.save-record').on('click', function reportFavorites() {
  dataLayer.push({
    'event': 'action.record',
    'actionContext': {
      'eventCategory': 'record',
      'eventAction': 'favourite',
      'eventLabel': $(this).data('id'),
      'eventValue': undefined,
      'nonInteraction': false
    }
  });
});

/* Favorites from search results - bulk */
$('#ribbon-save').on('click', function reportFavorites() {
  $('.record-list li.result input[type="checkbox"]:checked').each(function reportFavorite() {
    dataLayer.push({
      'event': 'action.record',
      'actionContext': {
        'eventCategory': 'record',
        'eventAction': 'favourite',
        'eventLabel': $(this).val(),
        'eventValue': undefined,
        'nonInteraction': false
      }
    });
  });
});

/*
 * Akce se záznamem
 */
/* common functions */
function pushRecordEventToGTM(eventAction) {
  dataLayer.push({
    'event': 'action.record',
    'actionContext': {
      'eventCategory': 'record',
      'eventAction': eventAction,
      'eventLabel': $('input.hiddenId').val(),
      'eventValue': undefined,
      'nonInteraction': false
    }
  });
}

/* favourite = uložení do oblíbených */
$('.save-record').on('click', function reportFavorites() {
  pushRecordEventToGTM('favourite');
});

/* sendEmail = poslat e-mailem */
$('.mail-record').on('click', function reportSendEmail() {
  pushRecordEventToGTM('sendEmail');
});

/* permalink = zobrazení trvalého odkazu */
$('.permalink-record').on('click', function reportPermalink() {
  pushRecordEventToGTM('permalink');
});

/* showCitation = zobrazení citačního záznamu */
$('.cite-record').on('click', function reportCiteRecord() {
  pushRecordEventToGTM('showCitation');
});

/* export = export citačního záznamu */
$('.export-toggle').on('click', function reportExportRecord() {
  pushRecordEventToGTM('export');
});

/* ebook = odchod na e-verzi */
$('#e-version-table a').on('click', function reportEversion() {
  dataLayer.push({
    'event': 'action.record',
    'actionContext': {
      'eventCategory': 'record',
      'eventAction': 'ebook',
      'eventLabel': $('input.hiddenId').val(),
      'eventValue': undefined,
      'nonInteraction': false
    }
  });
});

/*
 * Akce v uživatelském účtu
 */

/* payment = platba
 * TODO: implementovat až budeme podporovat platby */
/*
$(document).on('click', '#pay-button', function reportPayment() {
  dataLayer.push({
    'event': 'action.account',
    'actionContext': {
      'eventCategory': 'account',
      'eventAction': 'payment',
      'eventLabel': 'fines',
      'eventValue': undefined,
      'nonInteraction': false
    }
  });
});
*/

/* connectedAccount = propojený účet */
$('#connectCardInitializeBtn').on('click', function reportConnectCard() {
  dataLayer.push({
    'event': 'action.account',
    'actionContext': {
      'eventCategory': 'account',
      'eventAction': 'connectedAccount',
      'eventLabel': undefined,
      'eventValue': undefined,
      'nonInteraction': false
    }
  });
});

/* register = klik na (před)registraci
 * TODO: Implementovat, až budeme podporovat odkazy na registrační formuláře knihoven (pokud vůbec) */
/*
  $('#preregistration a').on('click', function reportRegistration() {
    dataLayer.push({
      'event': 'action.account',
      'actionContext': {
        'eventCategory': 'account',
        'eventAction': 'register',
        'eventLabel': $(this).attr('href'),
        'eventValue': undefined,
        'nonInteraction': false
      }
    });
  })
*/

/* prolongItem = prodloužení výpůjčky */
$(document).on('click', 'input[name=renewSelected],input[name=renewAll]', function reportProlongCheckouts() {
  dataLayer.push({
    'event': 'action.account',
    'actionContext': {
      'eventCategory': 'account',
      'eventAction': 'prolongItem',
      'eventLabel': undefined,
      'eventValue': undefined,
      'nonInteraction': false
    }
  });
});

/* order = objednat/rezervovat */
$('form[name="placeHold"]').on('submit', function reportPlaceHold() {
  dataLayer.push({
    'event': 'action.account',
    'actionContext': {
      'eventCategory': 'account',
      'eventAction': 'order',
      'eventLabel': $('input[name="recordId"]').val(),
      'eventValue': undefined,
      'nonInteraction': false
    }
  });
});

/* Kliky na otazníky s nápovědou
 * TODO: Doplnit až bude implementována nápověda */
/*
$('.questionmark-help').on('click', function reportHelpUsage() {
  dataLayer.push({
    'event': 'action.help',
    'actionContext': {
      'eventCategory': 'help',
      'eventAction': 'click',
      'eventLabel': $(this).attr('data-target'),
      'eventValue': undefined,
      'nonInteraction': false
    }
  });
});
*/
