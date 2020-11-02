# Components

## CMDBChangeHelper
Offers some methods to handle changes (delete changes and change operations).

## DBObjectHelper
Offers a method to convert an object set into a PHP (hashtable) array.

## ormCustomCaseLog
Offers additional methods to case logs, such as setting the author.

New features:
* add a new log entry where both another user (ID) and timestamp can be set.
* add logs from a provided ormCaseLog.
* sort case log chronologically

## ScheduledProcess
Offers a flexible way to integrate a default scheduled process.

Default settings include:

```
enabled
debug_level
time
weekdays
```

## TraceLog
Shows some tracing info.

