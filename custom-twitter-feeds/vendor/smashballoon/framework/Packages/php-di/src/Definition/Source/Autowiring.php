<?php


namespace Smashballoon\TwitterFeed\Vendor\DI\Definition\Source;

use Smashballoon\TwitterFeed\Vendor\DI\Definition\Exception\InvalidDefinition;
use Smashballoon\TwitterFeed\Vendor\DI\Definition\ObjectDefinition;
/**
 * Source of definitions for entries of the container.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
interface Autowiring
{
    /**
     * Autowire the given definition.
     *
     * @throws InvalidDefinition An invalid definition was found.
     * @return ObjectDefinition|null
     */
    public function autowire(string $name, ?ObjectDefinition $definition = null);
}
