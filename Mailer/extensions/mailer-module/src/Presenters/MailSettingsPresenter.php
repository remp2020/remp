<?php
declare(strict_types=1);

namespace Remp\MailerModule\Presenters;

use Nette\Application\Attributes\Requires;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Presenter;
use Nette\Http\IResponse;
use Nette\Utils\DateTime;
use Remp\MailerModule\Models\Auth\AutoLogin;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\UserSubscriptionsRepository;

class MailSettingsPresenter extends Presenter
{
    public function __construct(
        private readonly ListsRepository $mailListsRepository,
        private readonly UserSubscriptionsRepository $userSubscriptionsRepository,
        private readonly AutoLogin $autoLogin,
    ) {
        parent::__construct();
    }

    public function renderUnSubscribeEmail(string $token, string $listCode): void
    {
        $mailType = $this->mailListsRepository
            ->findByCode($listCode)
            ->where('deleted_at', null)
            ->fetch();
        if (!$mailType || $mailType->locked) {
            throw new BadRequestException("Mail type not found", IResponse::S404_NotFound);
        }

        $tokenRow = $this->autoLogin->getToken($token);
        if (!$tokenRow) {
            throw new BadRequestException("Invalid token", IResponse::S400_BadRequest);
        }

        $now = new DateTime();
        if ($now < $tokenRow->valid_from || $now > $tokenRow->valid_to || $tokenRow->used_count >= $tokenRow->max_count) {
            $this->forward('tokenExpired');
        }

        $isSubscribed = $this->userSubscriptionsRepository->isEmailSubscribed($tokenRow->email, $mailType->id);

        $this->template->token = $token;
        $this->template->email = $tokenRow->email;
        $this->template->mailType = $mailType;
        $this->template->isSubscribed = $isSubscribed;
    }

    #[Requires(methods: 'POST')]
    public function actionUnsubscribe(string $token, string $listCode): void
    {
        $mailType = $this->mailListsRepository
            ->findByCode($listCode)
            ->where('deleted_at', null)
            ->fetch();
        if (!$mailType || $mailType->locked) {
            throw new BadRequestException("Unable to unsubscribe from locked mail type", IResponse::S400_BadRequest);
        }

        $tokenRow = $this->autoLogin->getToken($token);
        if (!$tokenRow) {
            throw new BadRequestException("Invalid token", IResponse::S400_BadRequest);
        }

        $now = new DateTime();
        if ($now < $tokenRow->valid_from || $now > $tokenRow->valid_to || $tokenRow->used_count >= $tokenRow->max_count) {
            $this->forward('tokenExpired');
        }

        $this->autoLogin->useToken($tokenRow);

        $this->userSubscriptionsRepository->unsubscribeEmail($mailType, $tokenRow->email);

        $this->redirect('unSubscribeSuccess', [
            'listCode' => $listCode,
        ]);
    }

    public function renderTokenExpired(): void
    {
    }

    public function renderUnSubscribeSuccess(string $listCode): void
    {
        $mailType = $this->mailListsRepository
            ->findByCode($listCode)
            ->where('deleted_at', null)
            ->fetch();
        if (!$mailType) {
            throw new BadRequestException("Mail type not found", IResponse::S404_NotFound);
        }
        $this->template->mailType = $mailType;
    }
}
