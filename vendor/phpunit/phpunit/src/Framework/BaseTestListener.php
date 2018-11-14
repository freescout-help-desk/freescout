<?php
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Framework;

/**
 * Implementation of the TestListener interface that does not do anything.
 *
 * @deprecated Use TestListenerDefaultImplementation trait instead
 */
abstract class BaseTestListener implements TestListener
{
    use TestListenerDefaultImplementation;
}
