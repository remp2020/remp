<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\ServiceParams;

use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Utils\Json;
use Remp\MailerModule\Repositories\BatchesRepository;

class DefaultServiceParamsProvider implements ServiceParamsProviderInterface
{
    private const CACHE_PREFIX = 'batch_to_variant_code_';
    private const CACHE_PREFIX_VARIANT_DATA = 'batch_to_variant_';
    private const NO_ASSIGNED_VARIANT_DATA = '__NO_ASSIGNED_VARIANT_DATA__';

    public function __construct(
        private BatchesRepository $batchesRepository,
        private Storage $cacheStorage
    ) {
    }

    public function provide($template, string $email, ?int $batchId = null, ?string $autoLogin = null): array
    {
        $variantCode = null;
        if ($batchId) {
            $variantData = $this->loadVariantData($batchId);
            if (isset($variantData['code'])) {
                $variantCode = $variantData['code'];
            }
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

        $params['newsletter_id'] = $template->mail_type->id;
        $params['newsletter_code'] = $template->mail_type->code;
        $params['newsletter_title'] = $template->mail_type->title;

        if (!empty($variantData)) {
            $params['variant_id'] = $variantData['id'];
            $params['variant_code'] = $variantData['code'];
            $params['variant_title'] = $variantData['title'];
        }

        return $params;
    }

    private function loadVariantData(int $batchId): array
    {
        $variantData = $this->cacheStorage->read(self::CACHE_PREFIX_VARIANT_DATA . ((string) $batchId));

        if (!$variantData) {
            $batch = $this->batchesRepository->find($batchId);
            $variant = $batch->mail_job->mail_type_variant;

            $variantData = [];
            if ($variant) {
                $variantData = [
                    'id' => $variant->id,
                    'code' => $variant->code,
                    'title' => $variant->title,
                ];
            }

            $this->cacheStorage->write(self::CACHE_PREFIX_VARIANT_DATA . ((string) $batchId), Json::encode($variantData), [
                Cache::Expire => 60*20, // 20 minutes
            ]);

            return $variantData;
        }

        return Json::decode($variantData, true);
    }
}
