CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Similar Projects
 * Requirements
 * Installation
 * Configuration
 * Troubleshooting
 * Maintainers


INTRODUCTION
------------

The Background Image Field module allows the user to create a field on an entity
type. It requires responsive images mapping in order to offer the best image
quality for the device it is rendering on.

 * For a full description of the module, visit the project page:
   https://drupal.org/project/bg_img_field

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/bg_img_field


SIMILAR PROJECTS
----------------

Formatters that have similar features as the Background Image Field:

 * Picture Background Formatter (base formatter) -
   https://www.drupal.org/project/picture_background_formatter
 * Background Images Formatter -
   https://www.drupal.org/project/bg_image_formatter
 * Simple Background Image Formatter -
   https://www.drupal.org/project/background_image_formatter

The biggest differences you will notice is that the formatters will apply the
setting globally to all content that is being rendered with that formatter.

Using a field-specific solution allows the user to control each individual field
type per entity type i.e. node, paragraph_item, or custom entity. This field
type makes the background image content more dynamic per page and allows more
control over how the background image will render.

Having this as a field also allows the user different ways to apply it to
content, query it in views, and custom personalization per item created with it.


REQUIREMENTS
------------

This module requires the following modules outside of Drupal core.

 * Token - https://www.drupal.org/project/token


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit:
   https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Configuration > Media > and create a
       responsive image style.
       Note: The only responsive image style that will be picked up by the field
       formatter are the ones that have selected a single image style.
    3. Navigate to Administration > Structure > Content types > [Content type to
       edit] > Manage fields and add the field on an entity type such as node,
       paragraph_item or, custom entity.
    4. Define the CSS selector to attach the background image. Select a repeat
       option, background size, and background position.
    5. Save settings.


TROUBLESHOOTING
---------------

If you do not see the background image, please make sure to check that the CSS
selector is actually apart of the HTML. The field will not create the selector
you choose, it already has to exist for it to work.

If you don't see any available responsive image styles in the managed display
setting on the entity type you will most likley need to create one following the
outline configurations above.


MAINTAINERS
-----------

Active Maintainers:
 * Jeffrey Fortune (TheLostCookie) - https://www.drupal.org/u/thelostcookie
 * cchoe1 - https://www.drupal.org/u/cchoe1

Module Development and Maintenance:
 * The Tombras Group - https://www.drupal.org/the-tombras-group

Initial development for the formatter in the Background Image Field:
 * Encore Multimedia - https://www.drupal.org/encore-multimedia
