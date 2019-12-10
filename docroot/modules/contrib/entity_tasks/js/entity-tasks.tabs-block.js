window.EntityTasks = window.EntityTasks || {};
(function (drupalSettings, namespace) {
  'use strict';
  namespace.TabControl = (function (module) {
    var moveContainerToBottomOfBody;
    var getSVGHtml;
    var addIconToButton;
    var addDefaultIcons;
    var types = [
      'view',
      'shortcuts',
      'add',
      'edit',
      'translations',
      'webform',
      'webform-results',
      'delete'
    ];
    var left = false;

    moveContainerToBottomOfBody = function (containerEl) {
      document.body.appendChild(containerEl[0]);
    };

    getSVGHtml = function (text, type) {
      var html = '';
      if (left === true) {
        html += '<p>';
        html += text;
        html += '</p>';
        html += '<div class="entity-tasks-svg">';
        html += drupalSettings['entity-tasks-' + type];
        html += '</div>';
      }
      else {
        html += '<div class="entity-tasks-svg">';
        html += drupalSettings['entity-tasks-' + type];
        html += '</div>';
        html += '<p>';
        html += text;
        html += '</p>';
      }

      return html;
    };

    addIconToButton = function (container, type) {
      var svg = document.querySelector('.' + container + ' a[href*="' + type + '"] .entity-tasks-svg');

      if (svg !== null) {
        svg.innerHTML = drupalSettings['entity-tasks-' + type];
      }
    };

    addDefaultIcons = function (container) {
      var buttons = document.querySelectorAll('.' + container + ' a');

      for (var i = 0, l = buttons.length; i < l; i++) {
        var newHtml = getSVGHtml(buttons[i].innerHTML, 'view');
        buttons[i].innerHTML = newHtml;
      }

    };

    module.init = function (container) {
      var containerEl = document.getElementsByClassName(container);

      if (containerEl.length > 0) {
        moveContainerToBottomOfBody(containerEl);
        left = containerEl[0].classList.contains('entity-tasks--left');

        addDefaultIcons(container);

        for (var i = 0, l = types.length; i < l; i++) {
          addIconToButton(container, types[i]);
        }
        containerEl[0].style['opacity'] = '1';
      }
    };

    document.addEventListener('DOMContentLoaded', function (event) {
      module.init('entity-tasks');
    });

  }({}));
}(drupalSettings, window.EntityTasks));
