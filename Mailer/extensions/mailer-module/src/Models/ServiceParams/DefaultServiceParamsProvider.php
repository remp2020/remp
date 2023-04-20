<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\ServiceParams;

use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Remp\MailerModule\Repositories\BatchesRepository;

class DefaultServiceParamsProvider implements ServiceParamsProviderInterface
{
    private const CACHE_PREFIX = 'batch_to_variant_code_';
    private const NO_ASSIGNED_VARIANT_CODE = '__NO_ASSIGNED_VARIANT_CODE__';

    public function __construct(
        private BatchesRepository $batchesRepository,
        private Storage $cacheStorage
    ) {
    }

    public function provide($template, string $email, ?int $batchId = null, ?string $autoLogin = null): array
    {
        $variantCode = null;
        if ($batchId) {
            $variantCode = $this->loadVariantCode($batchId);
        }

        $params = [];
        if (isset($_ENV['UNSUBSCRIBE_URL'])) {
            $unsubscribe = str_replace('%type%', $template->mail_type->code, $_ENV['UNSUBSCRIBE_URL']) . $autoLogin;
            if ($variantCode) {
                $unsubscribe .= '&variantCode=' . $variantCode;
            }
            $params['unsubscribe'] = $unsubscribe;
        }
        if (isset($_ENV['SETTINGS_URL'])) {
            $params['settings'] = $_ENV['SETTINGS_URL'] . $autoLogin;
        }
        return $params;
    }

    private function loadVariantCode(int $batchId): ?string
    {
        $variantCode = $this->cacheStorage->read(self::CACHE_PREFIX . ((string) $batchId));

        if (!$variantCode) {
            $batch = $this->batchesRepository->find($batchId);
            $variantCode = $batch->mail_job->mail_type_variant->code ?? self::NO_ASSIGNED_VARIANT_CODE;
            $this->cacheStorage->write(self::CACHE_PREFIX . ((string) $batchId), $variantCode, [
                Cache::Expire => 60*20 // 20 minutes
            ]);
        }

        // convert NO_ASSIGNED_VARIANT_CODE to null
        return $variantCode === self::NO_ASSIGNED_VARIANT_CODE ? null : $variantCode;
    }
}
