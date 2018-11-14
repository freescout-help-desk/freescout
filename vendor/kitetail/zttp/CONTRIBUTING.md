# Contributing

Most important:

**This library does not use type-hints, follow PSR-2, or apply most other "best practices."**

This is intentional, and mostly just to make life a little more fun. Life's too short to take everything so seriously.

The library is still high quality and has an awesome test suite; it's just written more like Ruby than like Java :)

## Code Style

This library borrows most structural choices from PSR-2 in terms of tabs vs. spaces, where there should/shouldn't be whitespace, and where to put braces.

It deviates significantly from PSR-1 and PSR-2 in the following ways:

- Instance properties **must not be declared** (unfortunately PHP won't let you omit static properties), instead assign them dynamically in `__construct`
- Visibility keywords (public, protected, private) **must not be used**

For example:

```php
// Bad! 😒
class Foo
{
    private $bar;
    private $baz = 'hello world';

    public function __construct()
    {
        $this->bar = new Collection;
    }

    private function qux()
    {
        // ...
    }
}

// Fantastic! 😍
class Foo
{
    function __construct()
    {
        $this->bar = new Collection;
        $this->baz = 'hello world';
    }

    function qux()
    {
        // ...
    }
}
```

Additionally, code must also follow these constraints:

- Type-hints **must not be used**
- Temporary variables **must not be used**
- Each method **must return immediately**; methods cannot have more than one expression

## Pull Requests

We accept contributions via Pull Requests on [GitHub](https://github.com/kitetail/zttp).

- **Add tests!** - Your patch won't be accepted if it doesn't have tests.
- **One pull request per feature** - If you want to do more than one thing, send multiple pull requests.
- **Send coherent history** - Make sure each individual commit in your pull request is meaningful. If you had to make multiple intermediate commits while developing, please squash them before submitting.
