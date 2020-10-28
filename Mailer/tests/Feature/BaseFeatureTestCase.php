<?php
declare(strict_types=1);

namespace Tests\Feature;

use Nette\Database\Context;
use PDOException;
use PHPUnit\Framework\TestCase;
use Remp\MailerModule\Repository\BatchesRepository;
use Remp\MailerModule\Repository\BatchTemplatesRepository;
use Remp\MailerModule\Repository\JobQueueRepository;
use Remp\MailerModule\Repository\JobsRepository;
use Remp\MailerModule\Repository\LayoutsRepository;
use Remp\MailerModule\Repository\ListCategoriesRepository;
use Remp\MailerModule\Repository\ListsRepository;
use Remp\MailerModule\Repository\ListVariantsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;
use Remp\MailerModule\Repository\UserSubscriptionsRepository;
use Remp\MailerModule\Repository\UserSubscriptionVariantsRepository;

class BaseFeatureTestCase extends TestCase
{
    private $container;

    /** @var  Context */
    protected $database;

    /** @var  JobsRepository */
    protected $jobsRepository;

    /** @var JobQueueRepository */
    protected $jobQueueRepository;

    /** @var BatchesRepository */
    protected $batchesRepository;

    /** @var LayoutsRepository */
    protected $layoutsRepository;

    /** @var TemplatesRepository */
    protected $templatesRepository;

    /** @var BatchTemplatesRepository */
    protected $batchTemplatesRepository;

    /** @var ListsRepository */
    protected $listsRepository;

    /** @var ListVariantsRepository */
    protected $listVariantsRepository;

    /** @var ListCategoriesRepository */
    protected $listCategoriesRepository;

    /** @var UserSubscriptionsRepository */
    protected $userSubscriptionsRepository;

    /** @var UserSubscriptionVariantsRepository */
    protected $userSubscriptionVariantsRepository;

    protected function setUp(): void
    {
        $this->container = $GLOBALS['container'];
        $this->database = $this->inject(Context::class);

        $this->jobsRepository = $this->inject(JobsRepository::class);
        $this->batchTemplatesRepository = $this->inject(BatchTemplatesRepository::class);
        $this->templatesRepository = $this->inject(TemplatesRepository::class);
        $this->layoutsRepository = $this->inject(LayoutsRepository::class);
        $this->jobQueueRepository = $this->inject(JobQueueRepository::class);
        $this->batchesRepository = $this->inject(BatchesRepository::class);
        $this->listsRepository = $this->inject(ListsRepository::class);
        $this->listVariantsRepository = $this->inject(ListVariantsRepository::class);
        $this->listCategoriesRepository = $this->inject(ListCategoriesRepository::class);
        $this->userSubscriptionsRepository = $this->inject(UserSubscriptionsRepository::class);
        $this->userSubscriptionVariantsRepository = $this->inject(UserSubscriptionVariantsRepository::class);
    }

    protected function tearDown(): void
    {
        $truncateTables = implode(' ', array_map(function ($repo) {
            $property = (new \ReflectionClass($repo))->getProperty('tableName');
            $property->setAccessible(true);
            return "TRUNCATE `{$property->getValue($repo)}`;";
        }, [
            $this->jobsRepository,
            $this->batchTemplatesRepository,
            $this->templatesRepository,
            $this->layoutsRepository,
            $this->jobQueueRepository,
            $this->batchesRepository,
            $this->listsRepository,
            $this->listVariantsRepository,
            $this->listCategoriesRepository,
            $this->userSubscriptionsRepository,
            $this->userSubscriptionVariantsRepository
        ]));

        $db = $this->database->getConnection()->getPdo();
        $sql = "
SET FOREIGN_KEY_CHECKS=0;
{$truncateTables}
SET FOREIGN_KEY_CHECKS=1;
";
        try {
            $db->exec($sql);
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }

    protected function inject($className)
    {
        return $this->container->getByType($className);
    }

    protected function createBatch($template, $mailTypeVariant = null)
    {
        $mailJob = $this->jobsRepository->add('segment', 'provider', null, $mailTypeVariant);
        $batch = $this->batchesRepository->add($mailJob->id, null, null, BatchesRepository::METHOD_RANDOM);
        $this->batchesRepository->addTemplate($batch, $template);
        $this->batchesRepository->update($batch, ['status' => BatchesRepository::STATUS_READY]);
        return $batch;
    }

    protected function createTemplate($layout, $mailType)
    {
        return $this->templatesRepository->add('name', 'code', '', 'from@sample.com', 'SUBJECT', 'test', 'test', $layout->id, $mailType->id);
    }

    protected function createMailLayout()
    {
        return $this->layoutsRepository->add('layout', '', '');
    }

    protected function createMailTypeWithCategory(string $categoryName = 'category', string $typeCode = 'code', string $typeName = 'name', bool $isPublic = true)
    {
        $listCategory = $this->listCategoriesRepository->add($categoryName, 100);
        return $this->listsRepository->add($listCategory->id, 1, $typeCode, $typeName, 1, true, false, $isPublic, 'XXX');
    }
}
