# Pipeline

Simple PHP pipelines to use for things like middlewares.

This is just a modified version of the `illuminate/pipeline` repository, without the need for the illuminate container class.

```php
(new Pipeline)
	->send($object)
    ->through($middleware)
    ->then(function(){
    	// middleware is finished
    });
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License

Pipeline is free software distributed under the terms of the MIT license.
