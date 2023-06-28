<?php
declare(strict_types=1);

namespace Tests\Feature;

use Nette\Database\Explorer;
use Nette\DI\Container;
use Nette\Utils\Random;
use Nette\Utils\Strings;
use PHPUnit\Framework\TestCase;
use Remp\MailerModule\Models\Job\JobSegmentsManager;
use Remp\MailerModule\Repositories\AutoLoginTokensRepository;
use Remp\MailerModule\Repositories\BatchesRepository;
use Remp\MailerModule\Repositories\BatchTemplatesRepository;
use Remp\MailerModule\Repositories\JobQueueRepository;
use Remp\MailerModule\Repositories\JobsRepository;
use Remp\MailerModule\Repositories\LayoutsRepository;
use Remp\MailerModule\Repositories\ListCategoriesRepository;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\ListVariantsRepository;
use Remp\MailerModule\Repositories\LogConversionsRepository;
use Remp\MailerModule\Repositories\LogsRepository;
use Remp\MailerModule\Repositories\Repository;
use Remp\MailerModule\Repositories\TemplatesRepository;
use Remp\MailerModule\Repositories\UserSubscriptionsRepository;
use Remp\MailerModule\Repositories\UserSubscriptionVariantsRepository;

class BaseFeatureTestCase extends TestCase
{
    private Container $container;
    protected Explorer $database;
    protected AutoLoginTokensRepository $autoLoginTokensRepository;
    protected JobsRepository $jobsRepository;
    protected JobQueueRepository $jobQueueRepository;
    protected BatchesRepository $batchesRepository;
    protected LayoutsRepository $layoutsRepository;
    protected TemplatesRepository $templatesRepository;
    protected BatchTemplatesRepository $batchTemplatesRepository;
    protected ListsRepository $listsRepository;
    protected ListVariantsRepository $listVariantsRepository;
    protected ListCategoriesRepository $listCategoriesRepository;
    protected UserSubscriptionsRepository $userSubscriptionsRepository;
    protected UserSubscriptionVariantsRepository $userSubscriptionVariantsRepository;
    protected LogsRepository $mailLogsRepository;
    protected LogConversionsRepository $mailLogConversionsRepository;

    protected function setUp(): void
    {
        $this->container = $GLOBALS['container'];
        $this->database = $this->inject(Explorer::class);

        $this->autoLoginTokensRepository = $this->inject(AutoLoginTokensRepository::class);
        $this->jobsRepository = $this->inject(JobsRepository::class);
        $this->batchTemplatesRepository = $this->inject(BatchTemplatesRepository::class);
        $this->templatesRepository = $this->inject(TemplatesRepository::class);
        $this->layoutsRepository = $this->inject(LayoutsRepository::class);
        $this->jobQueueRepository = $this->inject(JobQueueRepository::class);
        $this->mailLogsRepository = $this->inject(LogsRepository::class);
        $this->mailLogConversionsRepository = $this->inject(LogConversionsRepository::class);
        $this->batchesRepository = $this->inject(BatchesRepository::class);
        $this->listsRepository = $this->inject(ListsRepository::class);
        $this->listVariantsRepository = $this->inject(ListVariantsRepository::class);
        $this->listCategoriesRepository = $this->inject(ListCategoriesRepository::class);
        $this->userSubscriptionsRepository = $this->inject(UserSubscriptionsRepository::class);
        $this->userSubscriptionVariantsRepository = $this->inject(UserSubscriptionVariantsRepository::class);

        $repositories = [
            $this->autoLoginTokensRepository,
            $this->jobsRepository,
            $this->batchTemplatesRepository,
            $this->templatesRepository,
            $this->layoutsRepository,
            $this->jobQueueRepository,
            $this->batchesRepository,
            $this->listsRepository,
            $this->mailLogsRepository,
            $this->mailLogConversionsRepository,
            $this->listVariantsRepository,
            $this->listCategoriesRepository,
            $this->userSubscriptionsRepository,
            $this->userSubscriptionVariantsRepository,
        ];

        foreach ($repositories as $repository) {
            $this->truncate($repository);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        \Mockery::close();
    }

    protected function inject($className)
    {
        return $this->container->getByType($className);
    }

    protected function createJob($context = null, $mailTypeVariant = null, $includeSegments = [['code' => 'segment', 'provider' => 'provider']], $excludeSegments = [])
    {
        $jobSegmentsManager = new JobSegmentsManager();
        foreach ($includeSegments as $includeSegment) {
            $jobSegmentsManager->includeSegment($includeSegment['code'], $includeSegment['provider']);
        }
        foreach ($excludeSegments as $excludeSegment) {
            $jobSegmentsManager->excludeSegment($excludeSegment['code'], $excludeSegment['provider']);
        }

        return $this->jobsRepository->add($jobSegmentsManager, $context, $mailTypeVariant);
    }

    protected function createBatch($mailJob, $template, $maxEmailsCount = null)
    {
        $batch = $this->batchesRepository->add($mailJob->id, $maxEmailsCount, null, BatchesRepository::METHOD_RANDOM);
        $this->batchesRepository->addTemplate($batch, $template);
        $this->batchesRepository->updateStatus($batch, BatchesRepository::STATUS_READY_TO_PROCESS_AND_SEND);
        return $batch;
    }

    protected function createJobAndBatch($template, $mailTypeVariant = null, $context = null, $maxEmailsCount = null)
    {
        $mailJob = $this->createJob($context, $mailTypeVariant);
        return $this->createBatch($mailJob, $template, $maxEmailsCount);
    }

    protected function createTemplate($layout, $mailType, $code = null)
    {
        if (!$code) {
            $code = 'template_' . Random::generate(15);
        }

        return $this->templatesRepository->add('name', $code, '', 'from@sample.com', 'SUBJECT', 'test', 'test', $layout->id, $mailType->id);
    }

    protected function createMailLayout()
    {
        return $this->layoutsRepository->add('Layout', 'layout', '', '');
    }

    protected function createMailTypeCategory($categoryName)
    {
        $categoryCode = Strings::webalize($categoryName);
        $listCategory = $this->listCategoriesRepository->getByCode($categoryCode)->fetch();
        if (!$listCategory) {
            $listCategory = $this->listCategoriesRepository->add($categoryName, $categoryCode, 100);
        }
        return $listCategory;
    }

    protected function createMailTypeWithCategory(
        string $categoryName = 'category',
        string $typeCode = 'code',
        string $typeName = 'name',
        bool $publicListing = true,
        bool $isMultiVariant = false,
        int $defaultVariantId = null,
    ) {
        $listCategory = $this->createMailTypeCategory($categoryName);

        return $this->listsRepository->add(
            categoryId: $listCategory->id,
            priority: 1,
            code: $typeCode,
            name: $typeName,
            sorting: 1,
            isAutoSubscribe: true,
            isLocked: false,
            description: 'XXX',
            publicListing: $publicListing,
            isMultiVariant: $isMultiVariant,
            defaultVariantId: $defaultVariantId,
        );
    }

    protected function truncate(Repository $repository)
    {
        $truncateTables = implode(' ', array_map(function ($repo) {
            $property = (new \ReflectionClass($repo))->getProperty('tableName');
            $property->setAccessible(true);
            return "DELETE FROM `{$property->getValue($repo)}`;";
        }, [
            $repository,
        ]));

        $db = $this->database->getConnection()->getPdo();
        $sql = "
SET FOREIGN_KEY_CHECKS=0;
{$truncateTables}
SET FOREIGN_KEY_CHECKS=1;
";
        try {
            $db->exec($sql);
        } catch (\PDOException $e) {
            echo $e->getMessage();
        }
    }

    protected function createMailTypeVariant($mailType, string $title = 'variant', string $code = 'variant', int $sorting = 100, bool $isDefaultVariant = false)
    {
        $variant = $this->listVariantsRepository->add($mailType, $title, $code, $sorting);
        if ($isDefaultVariant) {
            $this->listsRepository->update($mailType, ['default_variant_id' => $variant->id]);
        }
        return $variant;
    }

    protected function createMailUserSubscription($mailType, int $userID = 123, string $email = 'example@example.com', int $variantID = null)
    {
        $this->userSubscriptionsRepository->subscribeUser($mailType, $userID, $email, $variantID);
    }
}
