<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use UpturnStudio\GoogleFeed\Model\Feed\GenerationService;
use UpturnStudio\GoogleFeed\Model\FeedRepository;
use UpturnStudio\GoogleFeed\Model\FeedUrlProvider;

/**
 * Generate Google Merchant Center feeds now, without waiting for the queue.
 *
 *   bin/magento upturnstudio:google-feed:generate              # all active feeds
 *   bin/magento upturnstudio:google-feed:generate --feed-id=3
 */
class GenerateCommand extends Command
{
    private const OPTION_FEED_ID = 'feed-id';

    /**
     * @param State $state
     * @param FeedRepository $feedRepository
     * @param GenerationService $generationService
     * @param FeedUrlProvider $feedUrlProvider
     * @param string|null $name
     */
    public function __construct(
        private readonly State $state,
        private readonly FeedRepository $feedRepository,
        private readonly GenerationService $generationService,
        private readonly FeedUrlProvider $feedUrlProvider,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->setName('upturnstudio:google-feed:generate');
        $this->setDescription('Generate Google Merchant Center feed files.');
        $this->addOption(
            self::OPTION_FEED_ID,
            null,
            InputOption::VALUE_REQUIRED,
            'Generate only this feed ID (defaults to all active feeds).'
        );

        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->state->setAreaCode(Area::AREA_ADMINHTML);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $output->writeln('<comment>Area code already set; continuing.</comment>', OutputInterface::VERBOSITY_DEBUG);
        }

        $feedId = $input->getOption(self::OPTION_FEED_ID);
        try {
            $feeds = $feedId !== null
                ? [$this->feedRepository->getById((int) $feedId)]
                : $this->feedRepository->getAll(true);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return Command::FAILURE;
        }

        if (!$feeds) {
            $output->writeln('<comment>No active feeds to generate.</comment>');

            return Command::SUCCESS;
        }

        $failed = false;
        foreach ($feeds as $feed) {
            $started = microtime(true);
            try {
                $count = $this->generationService->run((int) $feed->getId());
                $output->writeln(sprintf(
                    '<info>Feed %d "%s": %d item(s) in %.1fs</info> %s',
                    (int) $feed->getId(),
                    $feed->getName(),
                    $count,
                    microtime(true) - $started,
                    $this->feedUrlProvider->getUrl($feed)
                ));
            } catch (\Throwable $e) {
                $failed = true;
                $output->writeln('<error>' . $e->getMessage() . '</error>');
            }
        }

        return $failed ? Command::FAILURE : Command::SUCCESS;
    }
}
