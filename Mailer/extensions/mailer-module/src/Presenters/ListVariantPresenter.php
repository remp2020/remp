<?php
declare(strict_types=1);

namespace Remp\MailerModule\Presenters;

use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Http\IResponse;
use Nette\Utils\Json;
use Remp\MailerModule\Components\DataTable\DataTable;
use Remp\MailerModule\Components\DataTable\DataTableFactory;
use Remp\MailerModule\Forms\ListVariantFormFactory;
use Remp\MailerModule\Forms\VariantSubscribersImportFormFactory;
use Remp\MailerModule\Repositories\ActiveRow;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\ListVariantsRepository;
use Remp\MailerModule\Repositories\UserSubscriptionVariantsRepository;

final class ListVariantPresenter extends BasePresenter
{
    public function __construct(
        private readonly ListsRepository $listsRepository,
        private readonly ListVariantsRepository $listVariantsRepository,
        private readonly ListVariantFormFactory $listVariantFormFactory,
        private readonly VariantSubscribersImportFormFactory $variantSubscribersImportFormFactory,
        private readonly UserSubscriptionVariantsRepository $userSubscriptionVariantsRepository,
        private readonly DataTableFactory $dataTableFactory,
    ) {
        parent::__construct();
    }

    public function renderShow($id): void
    {
        $variant = $this->listVariantsRepository->find($id);
        if (!$variant || $variant->deleted_at !== null) {
            throw new BadRequestException("List variant not found", IResponse::S404_NotFound);
        }

        $this->template->variant = $variant;
        $this->template->mailType = $variant->mail_type;
    }

    public function renderEdit($id): void
    {
        $variant = $this->listVariantsRepository->find($id);
        if (!$variant || $variant->deleted_at !== null) {
            throw new BadRequestException("List variant not found", IResponse::S404_NotFound);
        }

        $this->template->variant = $variant;
    }

    public function renderNew($listId): void
    {
        $mailType = $this->listsRepository->find($listId);
        if (!$mailType) {
            throw new BadRequestException("Mail type [{$listId}] not found", IResponse::S404_NotFound);
        }

        $this->template->mailType = $mailType;
    }

    public function renderImport($id): void
    {
        $variant = $this->listVariantsRepository->find($id);
        if (!$variant || $variant->deleted_at !== null) {
            throw new BadRequestException();
        }

        $mailType = $variant->mail_type;
        if (!$mailType->is_external) {
            throw new BadRequestException();
        }

        $this->template->variant = $variant;
        $this->template->mailType = $mailType;
    }

    public function createComponentImportForm(): Form
    {
        $form = $this->variantSubscribersImportFormFactory->create((int) $this->getParameter('id'));

        $this->variantSubscribersImportFormFactory->onImport = function ($variant, int $importedCount) {
            if ($importedCount > 0) {
                $this->flashMessage("{$importedCount} email(s) were imported successfully.");
            } else {
                $this->flashMessage("No emails were imported.");
            }
            $this->redirect('show', $variant->id);
        };

        return $form;
    }

    public function createComponentDataTableSubscribers(): DataTable
    {
        $dataTable = $this->dataTableFactory->create();
        $dataTable
            ->setSourceUrl($this->link('subscribersJsonData'))
            ->setColSetting('user_email', [
                'header' => 'user email',
                'priority' => 1,
                'render' => 'text',
            ])
            ->setColSetting('created_at', [
                'header' => 'subscribed at',
                'render' => 'date',
                'priority' => 1,
            ])
            ->setTableSetting('add-params', Json::encode(['variantId' => $this->getParameter('id')]))
            ->setTableSetting('order', Json::encode([[1, 'DESC']]));

        return $dataTable;
    }

    public function renderSubscribersJsonData(): void
    {
        $request = $this->request->getParameters();

        $variantId = (int)$request['variantId'];

        $subscribersCount = $this->userSubscriptionVariantsRepository
            ->tableFilter(
                $request['search']['value'],
                $request['columns'][$request['order'][0]['column']]['name'],
                $request['order'][0]['dir'],
                $variantId
            )
            ->count('*');

        $subscribers = $this->userSubscriptionVariantsRepository
            ->tableFilter(
                $request['search']['value'],
                $request['columns'][$request['order'][0]['column']]['name'],
                $request['order'][0]['dir'],
                $variantId,
                (int)$request['length'],
                (int)$request['start']
            )
            ->fetchAll();

        $result = [
            'recordsTotal' => $subscribersCount,
            'recordsFiltered' => $subscribersCount,
            'data' => []
        ];

        /** @var ActiveRow $subscriber */
        foreach ($subscribers as $subscriber) {
            $result['data'][] = [
                $subscriber->user_email,
                $subscriber->created_at,
            ];
        }
        $this->presenter->sendJson($result);
    }

    public function createComponentListVariantForm(): Form
    {
        $id = isset($this->params['id']) ? (int)$this->params['id'] : null;
        $mailTypeId = isset($this->params['listId']) ? (int)$this->params['listId'] : null;

        $variant = $mailType = null;
        if ($id) {
            $variant = $this->listVariantsRepository->find($id);
        }
        if ($mailTypeId) {
            $mailType = $this->listsRepository->find($mailTypeId);
        }

        $form = $this->listVariantFormFactory->create($variant, $mailType);

        $this->listVariantFormFactory->onCreate = function ($variant) {
            $this->flashMessage('Variant was created');
            $this->redirect('show', $variant->id);
        };
        $this->listVariantFormFactory->onUpdate = function ($variant) {
            $this->flashMessage('Variant was updated');
            $this->redirect('edit', $variant->id);
        };

        return $form;
    }
}
