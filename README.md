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

Add Larasearch to your composer.json file:

```"iverberk/larasearch": "0.6.*"```

Add the service provider to your Laravel application config:

```PHP
'Iverberk\Larasearch\LarasearchServiceProvider'
```

Wiki
----
Please see the Github [wiki](https://github.com/iverberk/larasearch/wiki/Introduction) for the most up-to-date documentation.

Credits
-------
This package is very much inspired by these excellent packages that already exist for the Ruby/Rails ecosystem.

* [Searchkick](https://github.com/ankane/searchkick)
* [Elasticsearch Rails](https://github.com/elasticsearch/elasticsearch-rails)

A lot of their ideas have been reused to work within a PHP/Laravel environment.
