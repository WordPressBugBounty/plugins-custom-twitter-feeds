<?php


namespace Smashballoon\TwitterFeed\Vendor\Invoker;

use Smashballoon\TwitterFeed\Vendor\Invoker\Exception\InvocationException;
use Smashballoon\TwitterFeed\Vendor\Invoker\Exception\NotCallableException;
use Smashballoon\TwitterFeed\Vendor\Invoker\Exception\NotEnoughParametersException;
/**
 * Invoke a callable.
 */
interface InvokerInterface
{
    /**
     * Call the given function using the given parameters.
     *
     * @param callable|array|string $callable Function to call.
     * @param array $parameters Parameters to use.
     * @return mixed Result of the function.
     * @throws InvocationException Base exception class for all the sub-exceptions below.
     * @throws NotCallableException
     * @throws NotEnoughParametersException
     */
    public function call($callable, array $parameters = []);
}
