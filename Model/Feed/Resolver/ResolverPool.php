<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Feed\Resolver;

/**
 * Built-in mapping sources keyed by code, registered through di.xml.
 */
class ResolverPool
{
    /**
     * @param ResolverInterface[] $resolvers
     */
    public function __construct(
        private readonly array $resolvers = []
    ) {
    }

    /**
     * Resolver for the code, null when it is not registered.
     *
     * @param string $code
     * @return ResolverInterface|null
     */
    public function get(string $code): ?ResolverInterface
    {
        return $this->resolvers[$code] ?? null;
    }

    /**
     * All resolvers keyed by code.
     *
     * @return ResolverInterface[]
     */
    public function getAll(): array
    {
        return $this->resolvers;
    }
}
