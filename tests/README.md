# Repo Tests Directory

This is the directory where your PHPUnit test source code should live. The autoload-dev namespace in `../composer.json` will point to this directory. The files should be autoloaded using the [PSR-4](http://www.php-fig.org/psr/psr-4/) style.

Test files should end in `Test.php`. For example, a test for a class named `\DealNews\Template\Foo`, would be in the the file `FooTest.php` in this directory.

The `tests` directory layout should follow the same structure as the `src` directory.

Any [fixtures](https://en.wikipedia.org/wiki/Test_fixture) or configuration files that your tests need should be organized logically in this directory.

Here is an example directory structure:

```
tests/
    AbstractTemplateTest.php
    Interfaces/
        FooTest.php
    Template/
        SubClassTest.php
    TemplateTest.php
    Traits/
        BarTest.php
    fixtures/
        test_object.json
    etc/
        some_ini_file.ini
```
