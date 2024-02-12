# Repository Library

This library provides a generic data repository which is capable of loading
data on demand through registered callbacks and ensures the data is only
loaded once while the repository object exists. This is useful if different
parts of an application will need to use the same data for different
purposes.

It also supports writing data through the repository.

## Usage

### Basic Example
```php
<?php

$repo = new \DealNews\Repository\Repository();
$dao = new My_Data_Object();

// assuming $dao has a load() method that takes
// an array of ids
$repo->register("my_data", [$dao, "load"]);

// $data is returned as an array with keys matching the ids passed
// in to the getMulti() method
$data = $repo->getMulti("my_data", [1,2,3]);

// Or, the get method can be used to return just on object
$obj = $repo->get("my_data", 1);
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

### Our Use Case

At DealNews, we use a singleton of the Repository with the needed handlers
registered for the application.

Below is an example of how a Repository object could be built.

```php
<?php

namespace MyAPP;

class DataRepository {
    public static function init() {
        static $repo;
        if (empty($repo)) {
            $repo = new \DealNews\Repository\Repository();

            // All of these handlers are only setting read callbacks
            // There is no writing for this repository.

            $book = new Book();
            $repo->register("book", [$book, "fetch"]);

            $author = new Author();
            $repo->register("author", [$author, "fetch"]);

        }
        return $repo;
    }
}
```
