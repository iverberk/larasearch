Larasearch
==========

---

**Caution: This package is under heavy development. Most of the functionaly is there but there might be some refactoring
as the package matures. Be careful if you decide to use it in production at this point.**

---

Introduction
---


Larasearch is a Laravel package that aims to seamlessly integrate Elasticsearch functionality with the Eloquent ORM.

Features
--------

  - Plug 'n Play searching functionality for Eloquent models
  - Automatic creation/indexing based on Eloquent model properties and relations
  - Aggregations, Suggestions, Autocomplete, Highlighting, etc. It's all there!
  - Load Eloquent models based on Elasticsearch queries
  - Automatic reindexing on updates of (related) Eloquent models

Usage
-----

Larasearch unlocks its functionality through PHP Traits. There are three Traits that you can include in your models:

**Searchable**

Use it on the 'base' models that you wish to create indices from.

**Transformable**

Use it on (related) models that you wish to have indexed.

**Callable**

Use it on (related) models to let the 'base' model know that something has changed and that a reindex of the Elasticsearch document should be performed.

Indexing
---

Larasearch exposes two Artisan command for indexing your models. Suppose we have the following Eloquent models:

```PHP
class Husband extends Eloquent {

    /**
     * @return \Illuminate\Database\Eloquent\Relations
     */
    public function wife()
    {
        return $this->hasOne('Wife');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations
     */
    public function children()
    {
        return $this->hasMany('Child', 'father_id');
    }

}

class Wife extends Eloquent {

    /**
     * @return \Illuminate\Database\Eloquent\Relations
     */
    public function husband()
    {
        return $this->belongsTo('Husband');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations
     */
    public function children()
    {
        return $this->hasMany('Child', 'mother_id');
    }

}

class Child extends Eloquent {

    /**
     * @return \Illuminate\Database\Eloquent\Relations
     */
    public function mother()
    {
        return $this->belongsTo('Wife');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations
     */
    public function father()
    {
        return $this->belongsTo('Husband');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations
     */
    public function toys()
    {
        return $this->belongsToMany('Toy');
    }

}

class Toy extends Eloquent {

    /**
     * @return \Illuminate\Database\Eloquent\Relations
     */
    public function children()
    {
        return $this->belongsToMany('Child');
    }

}
```
**Related Models**

Larasearch can automatically include related models as nested objects during indexing. It achieves this functionality by introspecting the models and finding relations. Every model has full control over its serialization by overriding the transform function (see below). Sometimes you do not want related models to be included from a base model. Larasearch allows you to specify conditions in the comments of the relation function. It knows the following options:

1. @follow NEVER
2. @follow UNLESS {base model}

Use the first annotation to never follow the relation. Use the second annotation to specify that the relation should not be included when you are coming from a specific base model. For example, if you do not want the toys to be included on the Husband model you would specify is as follows:

```PHP
class Child extends Eloquent {

    ...

    /**
     * @follow UNLESS Husband
     * @return \Illuminate\Database\Eloquent\Relations
     */
    public function toys()
    {
        return $this->belongsToMany('Toy');
    }

}
```
Mmake sure that you add the proper annotations to the relation functions so that Larasearch knows when a function defines an Eloquent relation. See the example models for the proper annotations.

**Indexing a model**

Just include the SearchableTrait to enable indexing of the Husband model:

```PHP
class Husband extends Eloquent {

    use Iverberk\Larasearch\SearchableTrait;

    ....
```
To index this model Larasearch needs to know what the paths are to the related models. A path is a dot seperated string of relation methods that are defined on the Eloquent models. It also needs to know the 'inverse' of this path to determine which Searchable model records should be reindexed when a change is made to one of its related models. To this end Larasearch exposes an Artisan 'paths' command. This command will generate the paths for you and optionaly write them to your local larasearch package config. **The paths command needs to be rerun when a change is made to the model relationships.** Also, the relationships need to be implemented properly with the relations defined in both directions.

Run the following Artisan command to generate the paths for the model:

```php artisan larasearch:paths Husband```

To generate paths for relations you should add the ```--relations``` flag.

Run the following Artisan command to index the model:

```php artisan larasearch:reindex Husband```

Use the ```--force``` flag to force recreation of the index. To automatically add all related models to the indexed documents you can use the ```--relations``` flag. By default Larasearch will use the database table as index name and the model name as type.

**TransformableTrait**

Include the TransformableTrait to get more control over the indexing proces:
```PHP
class Husband extends Eloquent {

    use Iverberk\Larasearch\TransformableTrait;

    ....
```
It provides a basic transform function to convert attributes to an indexable property array. You can override this function in your model to gain full control over how the property array is built.

**Indexing Configuration**

You can specify how certain fields are analyzed by Elasticsearch during the indexing proces. This is done by declaring a public variable on your model:

```PHP
class Husband extends Eloquent {

    public $__es_config = [

    ];

    ....
```
This array contains analysis settings for different fields. It is possible to specify fields from related models using dot notation.

- **Autocomplete or Partial matches**
```PHP
class Husband extends Eloquent {

    public $__es_config = [
        'autocomplete' => ['field1', 'field2', 'relation.field1', 'relation.field2', ...]
    ];

    ...
```
Instead of the generic 'autocomplete' analyzer you can also use more specific variants:

  * text_start
  * text_middle
  * text_end
  * word_start
  * word_middle
  * word_end

The _text_ types use the keyword tokenizer and the _word_ types use the standard tokenizer.

- **Suggestions**
```PHP
class Husband extends Eloquent {

    public $__es_config = [
        'suggest' => ['field1', 'field2', 'relation.field1', 'relation.field2', ...]
    ];

    ...
```

Searching
---

Easy, just call the search function on the model and grab the results:
```PHP
$results = Husband::search('query_string')->getResults();
```
This will search through all available fields. The results variable is a rich wrapper around the hits that Elasticsearch returned. It extends from the Laravel Collection class and thus supports all the collection methods.

```PHP
foreach($results as $result)
{
    // Convenience functions
    $result->getId();
    $result->getType();
    $result->getIndex();
    $result->getScore();
    $result->getSource();
    $result->getHit();

    // Get results directly from the hit
    // Object notation
    $result->wife

    // Array notation
    $result['_source.wife.name']
}
```
- **Scoping**

It is possible to specify which fields to search on:
```PHP
$results = Husband::search('query_string', ['fields' => ['wife.name']])->getResults();
```
And also which fields to return in the response:
```PHP
$results = Husband::search('query_string', [
        'fields' => ['wife.name'],
        'select' => ['name']
    ])->getResults();

$name = $results->first()->getFields(['name']);
```

- **Highlighting**

```PHP
$results = Husband::search('query_string', ['fields' => ['name'], 'highlight' => true])->getResults();

$highlights = $results->first()->getHighlights(['name']);
```

- **Suggestions**

```PHP
$results = Husband::search('query_string', ['fields' => ['name'], 'suggest' => true])->getResults();

$suggestios = $results->first()->getSuggestions(['name']);
```

- **Aggregations**

```PHP
$results = Husband::search('query_string',
        'aggs' => [
            'agg_name' => [
                'type' => 'terms',
                'field' => 'name'
            ]
        ]
    )->getResults();

$suggestios = $results->first()->getAggregations('agg_name');
```

- **Autocomplete**

```PHP
// Autocomplete on all fields
$results = Husband::search('query_string', ['autocomplete' => true])->getResults();

// Autocomplete on specific fields
// IMPORTANT: you need to index the model with the 'word_start' analyzer on the correct field, otherwise // you won't get any results
$results = Husband::search('query_string', ['fields' => ['wife.name' => 'word_start']])->getResults();
```

- **Loading Eloquent models**

Larasearch can load Eloquent models directly based on Elasticsearch queries. Just ask for records instead of results:
```PHP
$results = Husband::search('query_string')->getRecords();
```

Todo
---
* write tests!!
* add additional functionality (boosting, etc.)
* Polish, polish, polish...

Credits
-------
This package is very much inspired by these excellent packages that already exist for the Ruby/Rails ecosystem.

* [Searchkick](https://github.com/ankane/searchkick)
* [Elasticsearch Rails](https://github.com/elasticsearch/elasticsearch-rails)

A lot of their ideas have been reused to work within a PHP/Laravel environment.