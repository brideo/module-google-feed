<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Management;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Phrase;
use UpturnStudio\GoogleFeed\Model\Feed;
use UpturnStudio\GoogleFeed\Model\Feed\DefaultMappingProvider;
use UpturnStudio\GoogleFeed\Model\FeedFactory;
use UpturnStudio\GoogleFeed\Model\FeedRepository;
use UpturnStudio\GoogleFeed\Model\Queue\Publisher;

/**
 * Creates, changes, copies and removes feeds. The admin screens, REST, GraphQL and the MCP tools all go through here,
 * so a feed is validated the same way whichever way it arrives.
 *
 * Every method takes and returns plain arrays with snake_case keys, as described by FeedMapper::toArray().
 */
class FeedService
{
    /**
     * @param FeedRepository $feedRepository
     * @param FeedFactory $feedFactory
     * @param FeedInputNormalizer $normalizer
     * @param FeedValidator $validator
     * @param FeedMapper $mapper
     * @param DefaultMappingProvider $defaultMappingProvider
     * @param Publisher $publisher
     */
    public function __construct(
        private readonly FeedRepository $feedRepository,
        private readonly FeedFactory $feedFactory,
        private readonly FeedInputNormalizer $normalizer,
        private readonly FeedValidator $validator,
        private readonly FeedMapper $mapper,
        private readonly DefaultMappingProvider $defaultMappingProvider,
        private readonly Publisher $publisher
    ) {
    }

    /**
     * All feeds.
     *
     * @return array[]
     */
    public function getList(): array
    {
        return array_map([$this->mapper, 'toArray'], $this->feedRepository->getAll());
    }

    /**
     * One feed.
     *
     * @param int $feedId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get(int $feedId): array
    {
        return $this->mapper->toArray($this->feedRepository->getById($feedId));
    }

    /**
     * Create a feed. A name and a store view are required; everything else has a default, including the same
     * starting mapping the admin form offers. New feeds are not scheduled until they are switched on.
     *
     * @param array $data
     * @return array
     * @throws InputException
     */
    public function create(array $data): array
    {
        $input = $this->normalizer->normalize($data);
        $storable = $input + [
            'name' => '',
            'store_id' => 0,
            'is_active' => false,
            'target_country' => null,
            'price_tax' => Feed::PRICE_TAX_AUTO,
            'cron_expression' => null,
            'mapping' => $this->normalizer->normalizeMappingRows($this->defaultMappingProvider->get()),
        ];
        $storable['filters'] = array_replace(
            ['category_ids' => [], 'product_types' => [], 'visibility' => [], 'attribute_set_ids' => [],
                'exclude_out_of_stock' => false],
            $input['filters'] ?? []
        );
        $this->assertValid($storable);

        $feed = $this->feedFactory->create();
        $this->mapper->apply($feed, $storable);
        $this->feedRepository->save($feed);

        return $this->mapper->toArray($feed);
    }

    /**
     * Change a feed. Only the fields supplied change; supplying filters changes just the filter fields given, and
     * supplying mapping replaces the whole mapping.
     *
     * @param int $feedId
     * @param array $changes
     * @return array
     * @throws InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function update(int $feedId, array $changes): array
    {
        $feed = $this->feedRepository->getById($feedId);
        $current = $this->mapper->toStorable($feed);
        $input = $this->normalizer->normalize($changes);

        $storable = $input + $current;
        $storable['filters'] = array_replace($current['filters'], $input['filters'] ?? []);
        $this->assertValid($storable);

        $this->mapper->apply($feed, $storable);
        $this->feedRepository->save($feed);

        return $this->mapper->toArray($feed);
    }

    /**
     * Delete a feed and its generated file.
     *
     * @param int $feedId
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function delete(int $feedId): void
    {
        $this->feedRepository->delete($this->feedRepository->getById($feedId));
    }

    /**
     * Copy a feed into a new, unscheduled one.
     *
     * @param int $feedId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function duplicate(int $feedId): array
    {
        $source = $this->feedRepository->getById($feedId);
        $storable = $this->mapper->toStorable($source);
        $storable['name'] = (string) __('%1 (copy)', $source->getName());
        $storable['is_active'] = false;

        $copy = $this->feedFactory->create();
        $this->mapper->apply($copy, $storable);
        $this->feedRepository->save($copy);

        return $this->mapper->toArray($copy);
    }

    /**
     * Queue a generation run. The file is written on the next cron run; read the feed to follow its status.
     *
     * @param int $feedId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function generate(int $feedId): array
    {
        $feed = $this->feedRepository->getById($feedId);
        $this->publisher->publishGenerate((int) $feed->getId());

        return $this->get($feedId);
    }

    /**
     * Add or replace mapping rows by Google attribute, leaving the other rows as they are.
     *
     * @param int $feedId
     * @param array[] $rows
     * @return array
     * @throws InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function setMappingRows(int $feedId, array $rows): array
    {
        if (!$rows) {
            throw new InputException(new Phrase('At least one mapping row is required.'));
        }
        $merged = $this->mapper->toStorable($this->feedRepository->getById($feedId))['mapping'];
        foreach ($this->normalizer->normalizeMappingRows($rows) as $row) {
            $position = array_search($row['google_attribute'], array_column($merged, 'google_attribute'), true);
            if ($position === false) {
                $merged[] = $row;
            } else {
                $merged[$position] = $row;
            }
        }

        return $this->update($feedId, ['mapping' => $merged]);
    }

    /**
     * Remove mapping rows by Google attribute. Attributes that are not mapped are ignored.
     *
     * @param int $feedId
     * @param string[] $attributes
     * @return array
     * @throws InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function removeMappingRows(int $feedId, array $attributes): array
    {
        if (!$attributes) {
            throw new InputException(new Phrase('At least one Google attribute is required.'));
        }
        $remove = array_map('strval', $attributes);
        $kept = array_values(array_filter(
            $this->mapper->toStorable($this->feedRepository->getById($feedId))['mapping'],
            static fn (array $row): bool => !in_array($row['google_attribute'], $remove, true)
        ));

        return $this->update($feedId, ['mapping' => $kept]);
    }

    /**
     * Reject a feed definition that is not valid, reporting every problem.
     *
     * @param array $storable
     * @return void
     * @throws InputException
     */
    private function assertValid(array $storable): void
    {
        $errors = $this->validator->validate($storable);
        if (!$errors) {
            return;
        }
        $exception = new InputException(new Phrase('The feed is not valid.'));
        foreach ($errors as $error) {
            $exception->addError(new Phrase('%1', [$error]));
        }

        throw $exception;
    }
}
