window.EntityTasks = window.EntityTasks || {};
(function (namespace) {
  'use strict';
  namespace.Dropdown = (function (module) {
    module.init = function (selector) {
      var el = document.getElementsByClassName(selector);

      if (el.length > 0) {
        el[0].children[0].classList.remove('trigger');
      }
    };

    document.addEventListener('DOMContentLoaded', function (event) {
      module.init('entity-tasks-toolbar-dropdown');
    });

  }({}));
}(window.EntityTasks));
