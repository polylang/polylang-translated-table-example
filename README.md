# Polylang translated table example

This is an example plugin meant for developers and demonstrating how to translate a custom DB table with [Polylang](https://github.com/polylang/polylang/) 3.4.

This plugin doesn't show everything but only some basics + some UI to ease the demonstration:

- How and when to register your table in Polylang.
- How to assign a language to an item being created.
- A way to filter items in the admin area according to the language selector from the admin bar.
- How to display language columns in a `WP_List_Table` listing your items.
- How to create a translation of your items.

## How to set up this plugin

- Download the zip file from the [tags section](https://github.com/polylang/polylang-translated-table-example/tags) and install it like any other WordPress plugin.
Or,
- Clone this repository and `composer install`.

## How to use this plugin

Once the plugin activated, you will get a "Events" entry at the bottom of the admin menu. Going there will display an empty list of events and a button to create an event. This button will create a random event (randomized title, date, duration, type, etc). Those events are sorted by starting date in the list. Events cannot be edited, but can be deleted.

Once Polylang 3.4 is activated, several language columns will appear in the list, allowing you to see the language assigned to the event, and giving you the ability to create translations.
If you created events before activating Polylang, you can assign a language to all of them in one click by going to a Polylang's settings page (you will see an admin notice).
The plugin allows to translate only 3 event types out of 5: Event, Conference, and Seminar. Other and Unknown are not translatable for the sake of the demonstration.
Note that, now that Polylang is activated, the "Add" button will create an event in the language set in the language switcher from the admin bar (the default language is used when display all languages).

## Where to look in the code

`polylang-translated-table-example.php` and `src/TranslatedEvents.php` are the files you're looking for.
