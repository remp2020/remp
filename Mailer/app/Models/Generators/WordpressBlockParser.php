<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\Generators;

use Remp\MailerModule\Models\ContentGenerator\Engine\EngineFactory;
use Remp\MailerModule\Models\ContentGenerator\Engine\IEngine;
use Remp\MailerModule\Models\ContentGenerator\Engine\TwigEngine;

class WordpressBlockParser
{
    public const BLOCK_CORE_GROUP = 'core/group';
    public const BLOCK_CORE_HEADING = 'core/heading';
    public const BLOCK_CORE_PARAGRAPH = 'core/paragraph';
    public const BLOCK_CORE_LIST = 'core/list';
    public const BLOCK_CORE_COLUMN = 'core/column';
    public const BLOCK_CORE_COLUMNS = 'core/columns';
    public const BLOCK_CORE_IMAGE = 'core/image';
    public const BLOCK_CORE_LIST_ITEM = 'core/list-item';
    public const BLOCK_CORE_EMBED = 'core/embed';

    public const BLOCK_DN_MINUTE = 'dn-newsletter/r5m-minute';
    public const BLOCK_DN_GROUP = 'dn-newsletter/r5m-group';
    public const BLOCK_DN_HEADER = 'dn-newsletter/r5m-header';
    public const BLOCK_DN_ADVERTISEMENT = 'dn-newsletter/r5m-ad';

    /** @var TwigEngine $twig */
    private IEngine $twig;

    private int $minuteOrderCounter = 1;

    private bool $isFirstDNMinuteInGroup = true;

    private bool $isFirstDnHeaderInMinuteGroup = true;

    public function __construct(
        EngineFactory $engineFactory,
        private EmbedParser $embedParser
    ) {
        $this->twig = $engineFactory->engine('twig');
    }

    public function parseJson(string $json)
    {
        $data = preg_replace('/[[:cntrl:]]/', '', $json);
        $data = json_decode($data);

        $isFirstBlock = true;
        $htmlResult = '';
        $textResult = '';
        foreach ($data as $block) {
            $parsedBlock = $this->parseBlock($block);
            $htmlResult .= $parsedBlock;

            $textBlock = html_entity_decode(strip_tags($parsedBlock));
            $textBlock = preg_replace('/\n(\s*)\n(\s*)\n/', "\n\n", $textBlock);
            $textBlock = preg_replace('/^\s/', "", $textBlock);
            $textBlock = str_replace('  ', " ", $textBlock);

            $textResult .= $textBlock;

            // put advertisement snippet into template after first main block
            if ($isFirstBlock) {
                $htmlResult .= "{{ include('r5m-advertisement') }}";
                $textResult .= "\n\n{{ include('r5m-advertisement') }}\n\n";
            }
            $isFirstBlock = false;
        }

        return [$htmlResult, $textResult];
    }

    public function getBlockTemplateData(object $block, array $innerBlockParams = []): array
    {
        $data = [
            'originalContent' => $block->originalContent ?? null,
            'content' => $block->attributes->content ?? null,
            'verticalAlignment' => $block->attributes->verticalAlignment ?? null,
            'fontSize' => $block->attributes->style->typography->fontSize ?? null,
            'width' => $block->attributes->width ?? null,
            'url' => $block->attributes->url ?? null,
            'alt' => $block->attributes->alt ?? null,
            'caption' => $block->attributes->caption ?? null,
            'href' => $block->attributes->href ?? null,
            'isInMinute' => $innerBlockParams['isInMinute'] ?? false,
            'minuteListItem' => $innerBlockParams['minuteListItem'] ?? false,
        ];

        if ($block->name === self::BLOCK_CORE_GROUP
            && isset($block->attributes->className)
            && str_contains($block->attributes->className, 'wp-block-dn-newsletter-group-grey')
        ) {
            $data['group_grey'] = true;
        }

        if ($block->name === self::BLOCK_CORE_LIST) {
            $data['list_type'] = $block->attributes->ordered ? 'ol' : 'ul';
        }

        if ($block->name === self::BLOCK_CORE_GROUP
            && isset($block->attributes->className)
            && str_contains($block->attributes->className, 'wp-block-dn-newsletter-group-ordered')
        ) {
            $data['group_ordered'] = true;
        }

        if ($block->name === self::BLOCK_CORE_EMBED) {
            $data['embed_html'] = $this->embedParser->parse($block->attributes->url);
        }

        if ($block->name === self::BLOCK_DN_MINUTE) {
            $data['isFirstDNMinuteInGroup'] = $this->isFirstDNMinuteInGroup;
            if (isset($block->attributes->bullet) && !empty($block->attributes->bullet)) {
                $data['bullet'] = $block->attributes->bullet;
            }
        }

        if ($block->name === self::BLOCK_CORE_HEADING) {
            $data['level'] = $block->attributes->level;
            // Override content - get the width of image from style and add as html attribute to fix Outlook
            $data['content'] = preg_replace('/(\N*width:\\s?)(\d*)(px\N*")(\N*)/', '$1$2$3 width=$2 $4', $block->attributes->content);
        }

        if (isset($innerBlockParams['groupOrdered']) && $innerBlockParams['groupOrdered'] && $block->name === self::BLOCK_DN_MINUTE) {
            if ($this->minuteOrderCounter < 6) {
                $data['minuteOrderCounter'] = $this->minuteOrderCounter;
            }
            $this->minuteOrderCounter++;
        }

        if ($block->name === self::BLOCK_CORE_PARAGRAPH
            && isset($block->attributes->className)
            && str_contains($block->attributes->className, 'wp-block-dn-newsletter-paragraph-hr')
        ) {
            $data['paragraph_hr'] = true;
        }

        if ($block->name === self::BLOCK_DN_HEADER) {
            $data['isFirstDnHeaderInMinuteGroup'] = $this->isFirstDnHeaderInMinuteGroup;
        }

        if ($block->name === self::BLOCK_CORE_IMAGE) {
            $imageSize = getimagesize($block->attributes->url)[0];
            $data['imageWidth'] = min($imageSize, 660);
        }

        return $data;
    }

    public function parseBlock(object $block, array $innerBlockParams = []): string
    {
        $params = [
            'contents' => ''
        ];

        $params += $this->getBlockTemplateData($block, $innerBlockParams);

        if ($block->name === self::BLOCK_CORE_GROUP) {
            $this->minuteOrderCounter = 1;
            $this->isFirstDNMinuteInGroup = true;
        } elseif ($block->name === self::BLOCK_CORE_HEADING) {
            $this->isFirstDNMinuteInGroup = true;
        } elseif ($block->name === self::BLOCK_DN_MINUTE) {
            $this->isFirstDNMinuteInGroup = false;
            $this->isFirstDnHeaderInMinuteGroup = false;
        } elseif ($block->name === self::BLOCK_DN_GROUP) {
            $this->isFirstDNMinuteInGroup = true;
            $this->isFirstDnHeaderInMinuteGroup = true;
        } elseif ($block->name === self::BLOCK_DN_HEADER) {
            $this->isFirstDnHeaderInMinuteGroup = false;
        }

        $template = $this->getTemplate($block->name);
        if (isset($block->innerBlocks) && !empty($block->innerBlocks)) {
            foreach ($block->innerBlocks as $innerBlock) {
                $params['contents'] .= $this->parseBlock($innerBlock, [
                    'isInMinute' => $block->name === self::BLOCK_DN_MINUTE,
                    'groupOrdered' => $params['group_ordered'] ?? false,
                    'minuteListItem' => isset($innerBlockParams['groupOrdered']) && $innerBlockParams['groupOrdered'] && $block->name === self::BLOCK_DN_MINUTE && $this->minuteOrderCounter > 6
                ]);
            }
        }
        return $this->twig->render($template, $params);
    }

    public function getTemplate(string $blockName): string
    {
        $templateFile = match ($blockName) {
            self::BLOCK_CORE_GROUP => __DIR__ . '/resources/templates/WordpressBlockParser/core-group.twig',
            self::BLOCK_CORE_PARAGRAPH => __DIR__ . '/resources/templates/WordpressBlockParser/core-paragraph.twig',
            self::BLOCK_CORE_HEADING => __DIR__ . '/resources/templates/WordpressBlockParser/core-heading.twig',
            self::BLOCK_CORE_IMAGE => __DIR__ . '/resources/templates/WordpressBlockParser/core-image.twig',
            self::BLOCK_CORE_COLUMN => __DIR__ . '/resources/templates/WordpressBlockParser/core-column.twig',
            self::BLOCK_CORE_COLUMNS => __DIR__ . '/resources/templates/WordpressBlockParser/core-columns.twig',
            self::BLOCK_CORE_LIST => __DIR__ . '/resources/templates/WordpressBlockParser/core-list.twig',
            self::BLOCK_CORE_LIST_ITEM => __DIR__ . '/resources/templates/WordpressBlockParser/core-list-item.twig',
            self::BLOCK_CORE_EMBED => __DIR__ . '/resources/templates/WordpressBlockParser/core-embed.twig',
            self::BLOCK_DN_MINUTE => __DIR__ . '/resources/templates/WordpressBlockParser/dn-minute.twig',
            self::BLOCK_DN_GROUP => __DIR__  . '/resources/templates/WordpressBlockParser/dn-group.twig',
            self::BLOCK_DN_HEADER =>  __DIR__  . '/resources/templates/WordpressBlockParser/dn-header.twig',
            self::BLOCK_DN_ADVERTISEMENT => __DIR__ . '/resources/templates/WordpressBlockParser/dn-advertisement.twig',

            default => throw new \Exception("not existing block template: '{$blockName}'"),
        };

        return file_get_contents($templateFile);
    }
}
