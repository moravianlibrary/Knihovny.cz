// obtain plugin
var cc = initCookieConsent();

// run plugin with your configuration
cc.run({
  revision: 0,
  autorun: true,
  delay: 0,
  mode: 'opt-out',
  cookie_expiration: 182,

  theme_css: '/themes/KnihovnyCz/css/cookieconsent.css',
  autoclear_cookies: true,
  page_scripts: true,

  auto_language: 'document',
  remove_cookie_tables: true,

  onFirstAction: function (user_preferences, cookie) {
    // callback triggered only once
  },

  onAccept: function (cookie) {
    // ...
  },

  onChange: function (cookie, changed_preferences) {
    // ...
  },

  languages: {
    'cs': {
      consent_modal: {
        title: 'Souhlas s použitím cookies',
        description: 'Tento web používá následující soubory cookies:<br><strong>funkční cookies</strong> (vždy) k&nbsp;zajištění správného fungování webu a&nbsp;<strong>analytické cookies</strong> (na&nbsp;základě souhlasu) k&nbsp;analýze návštěvnosti webu. Žádné z těchto cookies nelze použít k identifikaci konkrétní osoby.',
        primary_btn: {
          text: 'Přijmout vše',
          role: 'accept_all'              // 'accept_selected' or 'accept_all'
        },
        secondary_btn: {
          text: 'Nastavit cookies',
          role: 'settings'        // 'settings' or 'accept_necessary'
        }
      },
      settings_modal: {
        title: 'Nastavení cookies',
        save_settings_btn: 'Uložit nastavení',
        accept_all_btn: 'Přijmout vše',
        reject_all_btn: 'Odmítnout vše',
        close_btn_label: 'Zavřít',
        blocks: [
          {
            title: 'Souhlas s použitím cookies',
            description: '<p>Tento web používá následující soubory cookies:<br>-&nbsp;<strong>funkční cookies</strong> (vždy) k&nbsp;zajištění správného fungování webu,<br>-&nbsp;<strong>analytické cookies</strong> (na&nbsp;základě souhlasu) k&nbsp;analýze návštěvnosti webu.</p>' +
              '<p>Žádné z těchto cookies nelze použít k identifikaci konkrétní osoby.</p>' +
              '<p>Další informace jsou dostupné na stránce <a class="cc-link" href="/Content/ochrana-osobnich-udaju?lng=cs" target="_blank" title="Zásady ochrany osobních údajů">Zásady ochrany osobních údajů</a>.</p>'
          },
          {
            title: 'Funkční cookies',
            description: '<p>Funkční cookies jsou zapotřebí k zajištění základních funkcí webu.</p>' +
              '<p>Vzhledem k&nbsp;jejich podstatě a&nbsp;účelu jsou povoleny vždy a&nbsp;souhlas s jejich použitím není vyžadován.</p>',
            toggle: {
              value: 'necessary',
              enabled: true,
              readonly: true,          // cookie categories with readonly=true are all treated as "necessary cookies"
            },
          },
          {
            title: 'Analytické cookies',
            description: '<p>Analytické cookies umožňují sledovat souhrnné informace o návštěvnosti stránek.<br>Díky těmto informacím jsme schopni lépe přizpůsobit stránky uživatelům webu.</p>' +
              '<p>Jsou povoleny pouze na základě vašeho souhlasu.</p>',
            toggle: {
              value: 'analytics',
              enabled: true,
              readonly: false
            },
          }
        ]
      }
    },
    'en': {
      consent_modal: {
        title: 'Cookie consent',
        description: 'This website uses the following cookies:<br><strong>essential cookies</strong> (always) to ensure its proper operation and <strong>analytics cookies</strong> (only after your consent) to understand how you interact with the site. None of these cookies can be used to identify a specific person.',
        primary_btn: {
          text: 'Accept all',
          role: 'accept_all'              // 'accept_selected' or 'accept_all'
        },
        secondary_btn: {
          text: 'Cookie settings',
          role: 'settings'        // 'settings' or 'accept_necessary'
        }
      },
      settings_modal: {
        title: 'Cookie preferences',
        save_settings_btn: 'Save settings',
        accept_all_btn: 'Accept all',
        reject_all_btn: 'Reject all',
        close_btn_label: 'Close',
        blocks: [
          {
            title: 'Cookie usage',
            description: '<p>This website uses the following cookies:<br>-&nbsp;<strong>essential cookies</strong> (always) to ensure its proper operation,<br>-&nbsp;<strong>analytics cookies</strong> (only with your consent) to understand how you interact with the website.</p>' +
              '<p>All of the data is anonymized and cannot be used to identify a specific person.</p>' +
              '<p>For more details relative to cookies and other sensitive data, please read the full <a class="cc-link" href="/Content/ochrana-osobnich-udaju?lng=en" target="_blank" title="Privacy Policy">Privacy Policy</a>.</p>'
          }, {
            title: 'Strictly necessary cookies',
            description: '<p>These cookies are essential for the proper functioning of the website. Without these cookies, the website would not work properly</p>',
            toggle: {
              value: 'necessary',
              enabled: true,
              readonly: true          // cookie categories with readonly=true are all treated as "necessary cookies"
            }
          },
          {
            title: 'Analytics cookies',
            description: '<p>These cookies collect information about how you use the website.<br>Based on this data, we are able to make better user experience of the website.</p>' +
              '<p>Only allowed with your consent.</p>',
            toggle: {
              value: 'analytics',
              enabled: true,
              readonly: false
            }
          }
        ]
      }
    }
  }
});
