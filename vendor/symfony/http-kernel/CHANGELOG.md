CHANGELOG
=========

3.4.0
-----

 * added a minimalist PSR-3 `Logger` class that writes in `stderr`
 * made kernels implementing `CompilerPassInterface` able to process the container
 * deprecated bundle inheritance
 * added `RebootableInterface` and implemented it in `Kernel`
 * deprecated commands auto registration
 * deprecated `EnvParametersResource`
 * added `Symfony\Component\HttpKernel\Client::catchExceptions()`
 * deprecated the `ChainCacheClearer::add()` method
 * deprecated the `CacheaWarmerAggregate::add()` and `setWarmers()` methods
 * made `CacheWarmerAggregate` and `ChainCacheClearer` classes final
 * added the possibility to reset the profiler to its initial state
 * deprecated data collectors without a `reset()` method
 * deprecated implementing `DebugLoggerInterface` without a `clear()` method

3.3.0
-----

 * added `kernel.project_dir` and `Kernel::getProjectDir()`
 * deprecated `kernel.root_dir` and `Kernel::getRootDir()`
 * deprecated `Kernel::getEnvParameters()`
 * deprecated the special `SYMFONY__` environment variables
 * added the possibility to change the query string parameter used by `UriSigner`
 * deprecated `LazyLoadingFragmentHandler::addRendererService()`
 * deprecated `Extension::addClassesToCompile()` and `Extension::getClassesToCompile()`
 * deprecated `Psr6CacheClearer::addPool()`

3.2.0
-----

 * deprecated `DataCollector::varToString()`, use `cloneVar()` instead
 * changed surrogate capability name in `AbstractSurrogate::addSurrogateCapability` to 'symfony'
 * Added `ControllerArgumentValueResolverPass`

3.1.0
-----
 * deprecated passing objects as URI attributes to the ESI and SSI renderers
 * deprecated `ControllerResolver::getArguments()`
 * added `Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface`
 * added `Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface` as argument to `HttpKernel`
 * added `Symfony\Component\HttpKernel\Controller\ArgumentResolver`
 * added `Symfony\Component\HttpKernel\DataCollector\RequestDataCollector::getMethod()`
 * added `Symfony\Component\HttpKernel\DataCollector\RequestDataCollector::getRedirect()`
 * added the `kernel.controller_arguments` event, triggered after controller arguments have been resolved

3.0.0
-----

 * removed `Symfony\Component\HttpKernel\Kernel::init()`
 * removed `Symfony\Component\HttpKernel\Kernel::isClassInActiveBundle()` and `Symfony\Component\HttpKernel\KernelInterface::isClassInActiveBundle()`
 * removed `Symfony\Component\HttpKernel\Debug\TraceableEventDispatcher::setProfiler()`
 * removed `Symfony\Component\HttpKernel\EventListener\FragmentListener::getLocalIpAddresses()`
 * removed `Symfony\Component\HttpKernel\EventListener\LocaleListener::setRequest()`
 * removed `Symfony\Component\HttpKernel\EventListener\RouterListener::setRequest()`
 * removed `Symfony\Component\HttpKernel\EventListener\ProfilerListener::onKernelRequest()`
 * removed `Symfony\Component\HttpKernel\Fragment\FragmentHandler::setRequest()`
 * removed `Symfony\Component\HttpKernel\HttpCache\Esi::hasSurrogateEsiCapability()`
 * removed `Symfony\Component\HttpKernel\HttpCache\Esi::addSurrogateEsiCapability()`
 * removed `Symfony\Component\HttpKernel\HttpCache\Esi::needsEsiParsing()`
 * removed `Symfony\Component\HttpKernel\HttpCache\HttpCache::getEsi()`
 * removed `Symfony\Component\HttpKernel\DependencyInjection\ContainerAwareHttpKernel`
 * removed `Symfony\Component\HttpKernel\DependencyInjection\RegisterListenersPass`
 * removed `Symfony\Component\HttpKernel\EventListener\ErrorsLoggerListener`
 * removed `Symfony\Component\HttpKernel\EventListener\EsiListener`
 * removed `Symfony\Component\HttpKernel\HttpCache\EsiResponseCacheStrategy`
 * removed `Symfony\Component\HttpKernel\HttpCache\EsiResponseCacheStrategyInterface`
 * removed `Symfony\Component\HttpKernel\Log\LoggerInterface`
 * removed `Symfony\Component\HttpKernel\Log\NullLogger`
 * removed `Symfony\Component\HttpKernel\Profiler::import()`
 * removed `Symfony\Component\HttpKernel\Profiler::export()`

2.8.0
-----

 * deprecated `Profiler::import` and `Profiler::export`

2.7.0
-----

 * added the HTTP status code to profiles

2.6.0
-----

 * deprecated `Symfony\Component\HttpKernel\EventListener\ErrorsLoggerListener`, use `Symfony\Component\HttpKernel\EventListener\DebugHandlersListener` instead
 * deprecated unused method `Symfony\Component\HttpKernel\Kernel::isClassInActiveBundle` and `Symfony\Component\HttpKernel\KernelInterface::isClassInActiveBundle`

2.5.0
-----

 * deprecated `Symfony\Component\HttpKernel\DependencyInjection\RegisterListenersPass`, use `Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass` instead

2.4.0
-----

 * added event listeners for the session
 * added the KernelEvents::FINISH_REQUEST event

2.3.0
-----

 * [BC BREAK] renamed `Symfony\Component\HttpKernel\EventListener\DeprecationLoggerListener` to `Symfony\Component\HttpKernel\EventListener\ErrorsLoggerListener` and changed its constructor
 * deprecated `Symfony\Component\HttpKernel\Debug\ErrorHandler`, `Symfony\Component\HttpKernel\Debug\ExceptionHandler`,
   `Symfony\Component\HttpKernel\Exception\FatalErrorException` and `Symfony\Component\HttpKernel\Exception\FlattenException`
 * deprecated `Symfony\Component\HttpKernel\Kernel::init()`
 * added the possibility to specify an id an extra attributes to hinclude tags
 * added the collect of data if a controller is a Closure in the Request collector
 * pass exceptions from the ExceptionListener to the logger using the logging context to allow for more
   detailed messages

2.2.0
-----

 * [BC BREAK] the path info for sub-request is now always _fragment (or whatever you configured instead of the default)
 * added Symfony\Component\HttpKernel\EventListener\FragmentListener
 * added Symfony\Component\HttpKernel\UriSigner
 * added Symfony\Component\HttpKernel\FragmentRenderer and rendering strategies (in Symfony\Component\HttpKernel\Fragment\FragmentRendererInterface)
 * added Symfony\Component\HttpKernel\DependencyInjection\ContainerAwareHttpKernel
 * added ControllerReference to create reference of Controllers (used in the FragmentRenderer class)
 * [BC BREAK] renamed TimeDataCollector::getTotalTime() to
   TimeDataCollector::getDuration()
 * updated the MemoryDataCollector to include the memory used in the
   kernel.terminate event listeners
 * moved the Stopwatch classes to a new component
 * added TraceableControllerResolver
 * added TraceableEventDispatcher (removed ContainerAwareTraceableEventDispatcher)
 * added support for WinCache opcode cache in ConfigDataCollector

2.1.0
-----

 * [BC BREAK] the charset is now configured via the Kernel::getCharset() method
 * [BC BREAK] the current locale for the user is not stored anymore in the session
 * added the HTTP method to the profiler storage
 * updated all listeners to implement EventSubscriberInterface
 * added TimeDataCollector
 * added ContainerAwareTraceableEventDispatcher
 * moved TraceableEventDispatcherInterface to the EventDispatcher component
 * added RouterListener, LocaleListener, and StreamedResponseListener
 * added CacheClearerInterface (and ChainCacheClearer)
 * added a kernel.terminate event (via TerminableInterface and PostResponseEvent)
 * added a Stopwatch class
 * added WarmableInterface
 * improved extensibility between bundles
 * added profiler storages for Memcache(d), File-based, MongoDB, Redis
 * moved Filesystem class to its own component
