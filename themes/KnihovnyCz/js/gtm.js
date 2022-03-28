/**
 * Specification:
 * https://docs.google.com/document/d/1Z0BOH1kbeUvsbVGyVe92rw0l462vwCd1_6Dz3RPyRZI/edit
 */

/*
 * Vyhledávání – fulltext
 * Spouští se po odeslání formulářového pole.
 */
// on submit search form
$(document).on('submit', $('form#searchForm'), function (event) {
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

$input.on('keyup', function () {
  clearTimeout(typingTimer);
  typingTimer = setTimeout(doneTyping, 2000);
});

$input.on('keydown', function () {
  clearTimeout(typingTimer);
});

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

/*
 * Vyhledávání – uložení vyhledávání
 * Spouští se po kliku na tlačítko k uložení vyhledávání.
 */
// $(document).on('click', '#btnSearchSave', function (event) {
//   console.debug('uložení vyhledávání');
//   dataLayer.push({
//     'event': 'action.search',
//     'actionContext': {
//       'eventCategory': 'search',
//       'eventAction': 'saveSearch',
//       'eventLabel': response.data.searchTerms.join(), //@todo where to get?
//       'eventValue': undefined,
//       'nonInteraction': false
//     }
//   });
// });

/*
 * Použití facetů
 * Spouští se s každou zaškrtnutou položkou.
 */
// $('body').on('click', '.facet-filter', function (event) {
//   dataLayer.push({
//     'event': 'action.facet',
//     'actionContext': {
//       'eventCategory': 'facet',
//       'eventAction': $(this).attr('data-facet').split(':')[0],
//       'eventLabel': $(this).attr('data-facet').split(':')[1],
//       'eventValue': useFacet,
//       'nonInteraction': false
//     }
//   });
// });

// $(document).off("click", ".jstree-anchor").on("click", ".jstree-anchor", function () {
//   var useFacet = 0;
//   if ($(this).hasClass('jstree-clicked')) {
//     useFacet = 1;
//   }
//   dataLayer.push({
//     'event': 'action.facet',
//     'actionContext': {
//       'eventCategory': 'facet',
//       'eventAction': $(this).find('.main').attr('data-facet').split(':')[0],
//       'eventLabel': $(this).find('.main').attr('data-facet').split(':')[1],
//       'eventValue': useFacet,
//       'nonInteraction': false
//     }
//   });
// });

/*
 * Přihlášení
 * Posílá se po úspěšném přihlášení.
 */
// //TODO: We can't distinguish between social and library login, we can only detect general login action
// //TODO: add event listener
// //TODO: get idp name
// dataLayer.push({
//   'event': 'action.login',
//   'actionContext': {
//     'eventCategory': 'login',
//     'eventAction': typeof idp !== 'undefined'
//       ? (idp.name == "MojeID | Google+ | Facebook | LinkedIn" ? 'social' : 'library')
//       : undefined,
//     'eventLabel': typeof idp !== 'undefined' ? idp.name : undefined,
//     'eventValue': undefined,
//     'nonInteraction': false
//   }
// });

/*
 * Akce se záznamem
 */

/*
 * favourite = uložení do oblíbených
 * (pozor, jde to i z vyhledávání)
 * //TODO: z vyhledávání
 */
// detail záznamu
/*
 * favourite = uložení do oblíbených
 * (pozor, jde to i z vyhledávání)
 * //TODO: z vyhledávání
 */
// detail záznamu
$('form.form-record-save input[type=submit]').on('click', function (event) {
//$(document).on('click', $('form.form-record-save input[type=submit]'), function (event) {
  console.debug('Record saved to favourites');
  event.preventDefault();
  dataLayer.push({
    'event': 'action.record',
    'actionContext': {
      'eventCategory': 'record',
      'eventAction': 'favourite',
      'eventLabel': $('input[name=id]').val(),
      'eventValue': undefined,
      'nonInteraction': false
    }
  });
});

// $('.record-toolbar').on('click', '#save-record', function () {
//   pushRecordEventToGTM('favourite');
// });


/*
 * sendEmail = poslat e-mailem
 */
// $('#mail-record').on('click', function () {
//   dataLayer.push({
//     'event': 'action.record',
//     'actionContext': {
//       'eventCategory': 'record',
//       'eventAction': 'sendEmail',
//       'eventLabel': $('input.hiddenId').val(),
//       'eventValue': undefined,
//       'nonInteraction': false
//     }
//   });
// });
//
// $('.record-toolbar').on('click', '#mail-record', function () {
//   pushRecordEventToGTM('sendEmail');
// });
//
// /*
//  * permalink = zobrazení trvalého odkazu
//  */
// $('#permalinkAnchor').on('click', function () {
//   dataLayer.push({
//     'event': 'action.record',
//     'actionContext': {
//       'eventCategory': 'record',
//       'eventAction': 'permalink',
//       'eventLabel': $('input.hiddenId').val(),
//       'eventValue': undefined,
//       'nonInteraction': false
//     }
//   });
// });
//
// $('#permalinkItem').on('click', '#permalinkAnchor', function () {
//   pushRecordEventToGTM('permalink');
// });

/*
 * showCitation = zobrazení citačního záznamu
 */
// $('#citace-pro').on('click', function () {
//   dataLayer.push({
//     'event': 'action.record',
//     'actionContext': {
//       'eventCategory': 'record',
//       'eventAction': 'showCitation',
//       'eventLabel': $('input.hiddenId').val(),
//       'eventValue': undefined,
//       'nonInteraction': false
//     }
//   });
// });
//
// $('#citace-pro').on('click', '.citations-link', function () {
//   pushRecordEventToGTM('showCitation');
// });

/*
 * export = export citačního záznamu
 */
// $('.export-toggle').on('click', function () {
//   dataLayer.push({
//     'event': 'action.record',
//     'actionContext': {
//       'eventCategory': 'record',
//       'eventAction': 'export',
//       'eventLabel': $('input.hiddenId').val(),
//       'eventValue': undefined,
//       'nonInteraction': false
//     }
//   });
// });
//
// $('.record-toolbar').on('click', '.export-toggle', function () {
//   pushRecordEventToGTM('export');
// });

/*
 * comment = přidání komentáře (dole v části Půjčit / E-verze / Komentáře / metadata)
 */
//TODO: probably remove, as we don't support comments
// $('form[name=commentRecordObalkyKnih] .btn-primary').on('click', function () {
//   dataLayer.push({
//     'event': 'action.record',
//     'actionContext': {
//       'eventCategory': 'record',
//       'eventAction': 'comment',
//       'eventLabel': '<?=$this->escapeHtmlAttr($this->driver->getUniqueId())?>', //TODO: get id using js
//       'eventValue': undefined,
//       'nonInteraction': false
//     }
//   });
// });

/*
 * ebook = odchod na e-verzi
 * (bude se nám lehce dublovat s externím odkazem, ale IMHO to není takový problém)
 */
// $('#e-version-table a').on('click', function () {
//   dataLayer.push({
//     'event': 'action.record',
//     'actionContext': {
//       'eventCategory': 'record',
//       'eventAction': 'ebook',
//       'eventLabel': $('input.hiddenId').val(),
//       'eventValue': undefined,
//       'nonInteraction': false
//     }
//   });
// });

/*
 * Akce v uživatelském účtu
 */

/*
 * payment = platba
 */
// $(document).on('click', '#pay-button', function () {
//   dataLayer.push({
//     'event': 'action.account',
//     'actionContext': {
//       'eventCategory': 'account',
//       'eventAction': 'payment',
//       'eventLabel': 'fines',
//       'eventValue': undefined,
//       'nonInteraction': false
//     }
//   });
// });

/*
 * connectedAccount = propojený účet
 */
//TODO: add event listener
// Account access
// dataLayer.push({
//   'event': 'action.account',
//   'actionContext': {
//     'eventCategory': 'account',
//     'eventAction': 'connectedAccount',
//     'eventLabel': typeof idp !== 'undefined' ? idp.name : undefined,
//     'eventValue': undefined,
//     'nonInteraction': false
//   }
// });

/*
 * register = klik na (před)registraci
 */
// $(document).ready(function () {
//   $('#preregistration a').on('click', function () {
//     dataLayer.push({
//       'event': 'action.account',
//       'actionContext': {
//         'eventCategory': 'account',
//         'eventAction': 'register',
//         'eventLabel': $(this).attr('href'),
//         'eventValue': undefined,
//         'nonInteraction': false
//       }
//     });
//   })
// });

/*
 * prolongItem = prodloužení záznamu
 * //TODO ???
 */
// $(document).on('click', 'input[name=renewSelected],input[name=renewAll]', function () {
//   dataLayer.push({
//     'event': 'action.account',
//     'actionContext': {
//       'eventCategory': 'account',
//       'eventAction': 'prolongItem',
//       'eventLabel': undefined,
//       'eventValue': undefined,
//       'nonInteraction': false
//     }
//   });
// });

/*
 * order = objednat/rezervovat
 */
// $('input[name=placeHold]').on('click', function () {
//   dataLayer.push({
//     'event': 'action.account',
//     'actionContext': {
//       'eventCategory': 'account',
//       'eventAction': 'order',
//       'eventLabel': $('input.hiddenId').val(), //FIXME: Is this right selector?
//       'eventValue': undefined,
//       'nonInteraction': false
//     }
//   });
// });

/*
 * Odeslání formuláře s feedbackem
 */
// $('#help .btn-primary').on('click', function () {
//   dataLayer.push({
//     'event': 'action.contact',
//     'actionContext': {
//       'eventCategory': 'contact',
//       'eventAction': 'feedback',
//       'eventLabel': 'help',
//       'eventValue': undefined,
//       'nonInteraction': false
//     }
//   });
// });

// $('#bugreport .btn-primary').on('click', function () {
//   dataLayer.push({
//     'event': 'action.contact',
//     'actionContext': {
//       'eventCategory': 'contact',
//       'eventAction': 'feedback',
//       'eventLabel': 'bugreport',
//       'eventValue': undefined,
//       'nonInteraction': false
//     }
//   });
// });

/*
 * Kliky na otazníky s nápovědou
 */
// $('.questionmark-help').on('click', function helpTag() {
//   dataLayer.push({
//     'event': 'action.help',
//     'actionContext': {
//       'eventCategory': 'help',
//       'eventAction': 'click',
//       'eventLabel': $(this).attr('data-target'),
//       'eventValue': undefined,
//       'nonInteraction': false
//     }
//   });
// });

/*
 * common functions
 */
// function pushRecordEventToGTM(eventAction) {
//   dataLayer.push({
//     'event': 'action.record',
//     'actionContext': {
//       'eventCategory': 'record',
//       'eventAction': eventAction,
//       'eventLabel': '<?=$this->escapeHtmlAttr($this->driver->getUniqueId())?>', //TODO: How to get record id using only JS?
//       'eventValue': undefined,
//       'nonInteraction': false
//     }
//   });
// }
