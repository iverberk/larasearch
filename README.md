Disclaimer
----------

This is a forked repository made for the sole purpose of working on Laravel 5 without errors. All other work is a courtesy of its respectful owner.
[Original repository](https://github.com/iverberk/larasearch)

Introduction
------------

Larasearch is a Laravel package that aims to seamlessly integrate Elasticsearch functionality with the Eloquent ORM.

Features
--------

  - Plug 'n Play searching functionality for Eloquent models
  - Automatic creation/indexing based on Eloquent model properties and relations
  - Aggregations, Suggestions, Autocomplete, Highlighting, etc. It's all there!
  - Load Eloquent models based on Elasticsearch queries
  - Automatic reindexing on updates of (related) Eloquent models

Installation
------------

*Laravel 5*

Add Larasearch to your composer.json file:

```"norgul/larasearch": "0.9.1"```

Add the service provider to your Laravel application config:

```'Iverberk\Larasearch\LarasearchServiceProvider'```

Wiki
----
Please see the Github [wiki](https://github.com/iverberk/larasearch/wiki/Introduction) for the most up-to-date documentation.

Changelog
---------
All releases are tracked and documented in the [changelog](https://github.com/iverberk/larasearch/wiki/Changelog).

Credits
-------
This package is very much inspired by these excellent packages that already exist for the Ruby/Rails ecosystem.

* [Searchkick](https://github.com/ankane/searchkick)
* [Elasticsearch Rails](https://github.com/elasticsearch/elasticsearch-rails)

A lot of their ideas have been reused to work within a PHP/Laravel environment.
