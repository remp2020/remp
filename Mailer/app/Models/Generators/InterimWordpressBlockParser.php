<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\Generators;

use Remp\MailerModule\Models\ContentGenerator\Engine\EngineFactory;
use Remp\MailerModule\Models\ContentGenerator\Engine\IEngine;
use Remp\MailerModule\Models\ContentGenerator\Engine\TwigEngine;

class InterimWordpressBlockParser
{
    public const BLOCK_CORE_GROUP = 'core/group';
    public const BLOCK_CORE_HEADING = 'core/heading';
    public const BLOCK_CORE_PARAGRAPH = 'core/paragraph';
    public const BLOCK_CORE_LIST = 'core/list';
    public const BLOCK_CORE_LIST_ITEM = 'core/list-item';
    public const BLOCK_CORE_IMAGE = 'core/image';

    public const BLOCK_EO_POST = 'eo/post';
    public const BLOCK_EO_ADVERT = 'eo/advert';

    /** @var TwigEngine $twig */
    private IEngine $twig;

    public function __construct(
        EngineFactory $engineFactory
    ) {
        $this->twig = $engineFactory->engine('twig');
    }

    public function parseJson(string $blocksJson, string $settingsJson): array
    {
        $blocksData = preg_replace('/[[:cntrl:]]/', '', $blocksJson);
        $blocksData = json_decode($blocksData);

        $settings = preg_replace('/[[:cntrl:]]/', '', $settingsJson);
        $settings = json_decode($settings);

        $colorPalette = $this->extractColorPalette($settings);

        $htmlResult = '';
        $textResult = '';

        foreach ($blocksData as $block) {
            $parsedBlock = $this->parseBlock($block, $colorPalette);
            $htmlResult .= $parsedBlock;

            $textBlock = html_entity_decode(strip_tags($parsedBlock));
            $textBlock = preg_replace('/\n(\s*)\n(\s*)\n/', "\n\n", $textBlock);
            $textBlock = preg_replace('/^\s/', "", $textBlock);
            $textBlock = str_replace('  ', " ", $textBlock);

            $textResult .= $textBlock;
        }

        return [$htmlResult, $textResult];
    }

    public function parseBlock(object $block, array $colorPalette): string
    {
        $params['contents'] = '';
        $params += $this->getBlockTemplateData($block, $colorPalette);

        $template = $this->getTemplate($block->name);
        if (!empty($block->innerBlocks)) {
            foreach ($block->innerBlocks as $innerBlock) {
                $params['contents'] .= $this->parseBlock($innerBlock, $colorPalette);
            }
        }
        return $this->twig->render($template, $params);
    }

    public function getBlockTemplateData(object $block, array $colorPalette): array
    {
        $data = [
            'originalContent' => $block->originalContent ?? null,
            'content' => $block->attributes->content ?? null,
            'url' => $block->attributes->url ?? null,
            'href' => $block->attributes->href ?? null,
            'alt' => $block->attributes->alt ?? null,
        ];

        if ($block->name === self::BLOCK_CORE_GROUP) {
            $backgroundColor = $block->attributes->backgroundColor ?? null;
            $data['backgroundColor'] = $colorPalette[$backgroundColor] ?? $backgroundColor ?? '#ffffff';
        }

        if ($block->name === self::BLOCK_CORE_PARAGRAPH) {
            $data['textAlign'] = $block->attributes->align ?? null;
            $textColor = $block->attributes->textColor ?? null;
            $data['textColor'] = $colorPalette[$textColor] ?? $textColor;
        }

        if ($block->name === self::BLOCK_CORE_HEADING) {
            $data['level'] = $block->attributes->level;
            $data['fontSize'] = match ($data['level']) {
                1 => '20px',
                2 => '24px',
                default => '16px',
            };
            $data['color'] = $data['level'] === 2 ? '#f0523c' : '#32353a';
        }

        return $data;
    }

    private function extractColorPalette(object $settings): array
    {
        $palette = [];
        foreach ($settings->color->palette->default ?? [] as $entry) {
            $palette[$entry->slug] = $entry->color;
        }
        foreach ($settings->color->palette->theme ?? [] as $entry) {
            $palette[$entry->slug] = $entry->color;
        }
        return $palette;
    }

    public function getTemplate(string $blockName): string
    {
        $templateFile = match ($blockName) {
            self::BLOCK_CORE_GROUP => __DIR__ . '/resources/templates/InterimWordpressBlockParser/core-group.twig',
            self::BLOCK_CORE_HEADING => __DIR__ . '/resources/templates/InterimWordpressBlockParser/core-heading.twig',
            self::BLOCK_CORE_PARAGRAPH => __DIR__ . '/resources/templates/InterimWordpressBlockParser/core-paragraph.twig',
            self::BLOCK_CORE_IMAGE => __DIR__ . '/resources/templates/InterimWordpressBlockParser/core-image.twig',
            self::BLOCK_CORE_LIST => __DIR__ . '/resources/templates/InterimWordpressBlockParser/core-list.twig',
            self::BLOCK_CORE_LIST_ITEM => __DIR__ . '/resources/templates/InterimWordpressBlockParser/core-list-item.twig',
            self::BLOCK_EO_POST => __DIR__ . '/resources/templates/InterimWordpressBlockParser/eo-post.twig',
            self::BLOCK_EO_ADVERT => __DIR__ . '/resources/templates/InterimWordpressBlockParser/eo-advert.twig',

            default => throw new \Exception("not existing block template: '{$blockName}'"),
        };

        return file_get_contents($templateFile);
    }
}
