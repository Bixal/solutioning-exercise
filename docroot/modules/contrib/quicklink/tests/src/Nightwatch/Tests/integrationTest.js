module.exports = {
  '@tags': ['quicklink'],
  before: function (browser) {
    browser.drupalInstall({
      setupFile: 'modules/contrib/quicklink/tests/src/Nightwatch/TestSiteInstallTestScript.php',
    });
  },
  after: function (browser) {
    browser
      .drupalUninstall();
  },
  'Visit a test page': (browser) => {
    browser
      .drupalRelativeURL('/user/login')
      .assert.quicklinkExists();
  },

};
