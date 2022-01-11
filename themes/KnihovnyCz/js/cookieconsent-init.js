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
        title: 'Web používá cookies.',
        description: 'Hi, this website uses essential cookies to ensure its proper operation and tracking cookies to understand how you interact with it. The latter will be set only after consent. <button type="button" data-cc="c-settings" class="cc-link">Let me choose</button>',
        primary_btn: {
          text: 'Přijmout vše',
          role: 'accept_all'              // 'accept_selected' or 'accept_all'
        },
        secondary_btn: {
          text: 'Odmítnout vše',
          role: 'accept_necessary'        // 'settings' or 'accept_necessary'
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
            title: 'Použití cookies',
            description: 'I use cookies to ensure the basic functionalities of the website and to enhance your online experience. You can choose for each category to opt-in/out whenever you want. For more details relative to cookies and other sensitive data, please read the full <a href="#" class="cc-link">privacy policy</a>.'
          },
          {
            title: 'Funkční cookies',
            description: 'Funkční cookies jsou zapotřebí k zajištění základních funkcí webu. Tyto druhy cookies jsou vzhledem ke své podstatě a účelu bez povoleny vždy a souhlas subjektu údajů se zde nevyžaduje. Funkční cookies jsou využívány vždy.',
            toggle: {
              value: 'necessary',
              enabled: true,
              readonly: true,          // cookie categories with readonly=true are all treated as "necessary cookies"
            },
          },
          {
            title: 'Analytické cookies',
            description: 'These cookies collect information about how you use the website, which pages you visited and which links you clicked on. All of the data is anonymized and cannot be used to identify you',
            toggle: {
              value: 'analytics',     // your cookie category
              enabled: false,
              readonly: false
            },
          },
          {
            title: 'Další informace',
            description: 'For any queries in relation to our policy on cookies and your choices, please <a class="cc-link" href="#yourcontactpage">contact us</a>.',
          }
        ]
      }
    },
    'en': {
      consent_modal: {
        title: 'We use cookies.',
        description: 'Hi, this website uses essential cookies to ensure its proper operation and tracking cookies to understand how you interact with it. The latter will be set only after consent. <button type="button" data-cc="c-settings" class="cc-link">Let me choose</button>',
        primary_btn: {
          text: 'Accept all',
          role: 'accept_all'              // 'accept_selected' or 'accept_all'
        },
        secondary_btn: {
          text: 'Reject all',
          role: 'accept_necessary'        // 'settings' or 'accept_necessary'
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
            description: 'I use cookies to ensure the basic functionalities of the website and to enhance your online experience. You can choose for each category to opt-in/out whenever you want. For more details relative to cookies and other sensitive data, please read the full <a href="#" class="cc-link">privacy policy</a>.'
          }, {
            title: 'Strictly necessary cookies',
            description: 'These cookies are essential for the proper functioning of my website. Without these cookies, the website would not work properly',
            toggle: {
              value: 'necessary',
              enabled: true,
              readonly: true          // cookie categories with readonly=true are all treated as "necessary cookies"
            }
          },
          {
            title: 'Analytics cookies',
            description: 'These cookies collect information about how you use the website, which pages you visited and which links you clicked on. All of the data is anonymized and cannot be used to identify you',
            toggle: {
              value: 'targeting',
              enabled: false,
              readonly: false
            }
          }, {
            title: 'More information',
            description: 'For any queries in relation to our policy on cookies and your choices, please <a class="cc-link" href="#yourcontactpage">contact us</a>.',
          }
        ]
      }
    }
  }
});
