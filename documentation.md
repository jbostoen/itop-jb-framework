# Components

## CMDBChangeHelper
Offers some methods to handle changes (delete changes and change operations).

## HelpContainer
Adds a help container (help text) to the iTop UI.

* `Add()`: renders a custom template, positioned relative to any selector.
* `AddForAttCode()`: shows the description (tooltip) of an attribute next to that attribute. For an enum, the descriptions of the values are listed too.
* `AddFullWidth()`: shows a help text that spans the full width of the object details (all columns), above (or below) the row that contains a given attribute. Optionally with action links (http / https only), which open in a new tab.

Example (e.g. in `DisplayBareProperties()`):

```php
HelpContainer::AddFullWidth($oPage, $this, 'enabled', Dict::S('Class:SomeClass/Help'), [
	[
		'label' => Dict::S('Class:SomeClass/Help:Create'),
		'url' => 'https://example.org/create',
		'icon' => 'fas fa-plus',
	],
]);
```

## ormCustomCaseLog
Offers additional methods to case logs, such as setting the author.

New features:
* add a new log entry where both another user (ID) and timestamp can be set.
* add logs from a provided ormCaseLog.
* sort case log chronologically

