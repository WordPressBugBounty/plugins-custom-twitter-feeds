<?php


namespace Smashballoon\TwitterFeed\Vendor\DI;

use Smashballoon\TwitterFeed\Vendor\Psr\Container\ContainerExceptionInterface;
/**
 * Exception for the Container.
 */
class DependencyException extends \Exception implements ContainerExceptionInterface
{
}
