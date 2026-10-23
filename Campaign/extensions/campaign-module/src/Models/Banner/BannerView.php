<?php

namespace Remp\CampaignModule\Models\Banner;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Remp\CampaignModule\Banner;

/**
 * Banner with its template as it's cached, usable without the database.
 */
readonly class BannerView
{
    public function __construct(
        private array $banner,
        private ?array $template,
    ) {
    }

    public static function fromModel(Banner $banner): self
    {
        $template = $banner->loadTemplate()->getTemplate();

        return new self(self::attributes($banner), $template === null ? null : self::attributes($template));
    }

    public static function fromCache(array $banner): self
    {
        return new self($banner['banner'], $banner['template']);
    }

    public function toCache(): array
    {
        return [
            'banner' => $this->banner,
            'template' => $this->template,
        ];
    }

    public function toModel(): Banner
    {
        $banner = new Banner();
        $banner->setRawAttributes($this->banner);
        $banner->exists = true;

        $relation = $banner->getTemplateRelationName();
        $template = null;
        if ($this->template !== null) {
            $templateClass = Banner::TEMPLATE_RELATIONS[$relation];
            $template = new $templateClass();
            $template->setRawAttributes($this->template);
            $template->exists = true;
        }
        $banner->setRelation($relation, $template);

        return $banner;
    }

    /**
     * Raw attributes, so the model created from them casts them as if loaded from database. Appended attributes
     * (e.g. config of the newsletter banner) are evaluated now, they may need Laravel. Timestamps are left out,
     * banner.js doesn't use them and converting them is the most expensive part of rendering the banner.
     */
    private static function attributes(Model $model): array
    {
        return Arr::except($model->getAttributes() + $model->only($model->getAppends()), ['created_at', 'updated_at']);
    }
}
