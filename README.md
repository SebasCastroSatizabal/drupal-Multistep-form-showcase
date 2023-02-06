# Multistep Form Showcase

Showcase of a Drupal Multistep form using Ajax.
The form creates user based on the information submitted. It is composed by three pages, one to get <strong>personal information</strong>, another to get <strong>contact and location</strong> information, and the last one to show if the user was correctly created or if it was an error.

This module generates a unique username using the first and last name provided by the user. The username is composed by the first character of the first name and all the last name (without spaces)., and is dispaly at the end of the form in the third page. In case there is already a user with that username, an number is appended to make it unique.

The module also creates the neccessary user fields on installation to store the requested information (first_name, last_name, gender, birthday, city, phone, and address). The fields are automatically set to be shown on the user edit page. The fields are deleted when the module is uninstalled.

<h2>Installation and use</h2>
To use this module simply install it using composer and enable it. Then you can go to the demo page on <em>/multistep_form_demo</em> and start checking out.


