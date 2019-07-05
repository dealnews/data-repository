# Repository Library

This library provides a generic data repository which is capable of loading
data on demand through registered callbacks and ensures the data is only
loaded once while the repository object exists. This is useful if different
parts of an application will need to use the same data for very different
purposes.

It also supports writing data through the repository. See TODO.

## Usage

### Basic Example
```php
<?php

$repo = new \DealNews\Repository\Repository();
$dao = new My_Data_Object();
// assuming $dao has a load() method that takes
// an array of ids
$repo->register("my_data", [$dao, "load"]);


$data = $repo->get("my_data", [1,2,3]);
// $data is returned as an array with keys matching the ids passed
// in to the get() method
```

## Writing
```php
<?php

$repo = new \DealNews\Repository\Repository();
$dao = new My_Data_Object();
// assuming $dao has a load() method that takes
// an array of ids
$repo->register("my_data", [$dao, "load"], [$dao, "save"]);

$foo = new Foo();
$foo->name = "example";

// save returns the data (possibly updated by the storage later)
// that it is sent
$foo = $repo->save("my_data", $foo);
```

### Best Practice

An applcation should use a singleton of the Repository with the needed handlers
registered for the application. To take best advantage of PHP auto-loading,
the singleton should use a builder pattern for creating the object.

Below is an example of how a Repository object could be built for the web-apps
project.

```php
<?php

namespace DealNews;

class DataRepository {
    public function init() {
        static $repo;
        if (empty($repo)) {
            $repo = new \DealNews\Repository\Repository();

            // All of these handlers are only setting read callbacks
            // There is no writing for this repository.

            $ro = new \RO_Category();
            $repo->register("category", [$ro, "fetch"]);

            $ro = new \RO_Stores();
            $repo->register("vendor", [$ro, "fetch"]);

            $ro = new \RO_Manufacturer();
            $repo->register("manufacturer", [$ro, "fetch"]);

            $ro = new \RO_Facet();
            $repo->register("facet", [$ro, "fetch"]);

            $ro = new \RO_FacetGroup();
            $repo->register("facet_group", [$ro, "fetch"]);

            $ro = new \RO_FacetRange();
            $repo->register("facet_range", [$ro, "fetch"]);

            $dt = new \DealNews\Shared\Content\Filter\DealType;
            $repo->register("deal_type", [$dt, "ids_to_data"]);
        }
        return $repo;
    }
}

```
