<?php

namespace Remp\MailerModule\Components\MailLinkStats;

use Nette\Application\UI\Control;
use Nette\Utils\Json;
use Remp\MailerModule\Components\DataTable\DataTableFactory;
use Remp\MailerModule\Models\ContentGenerator\ContentGenerator;
use Remp\MailerModule\Models\ContentGenerator\GeneratorInputFactory;
use Remp\MailerModule\Models\ContentGenerator\Replace\RtmClickReplace;
use Remp\MailerModule\Repositories\ActiveRow;
use Remp\MailerModule\Components\DataTable\DataTable;
use Remp\MailerModule\Repositories\MailTemplateLinksRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;

class MailLinkStats extends Control
{
    private string $templateCode;

    public function __construct(
        private DataTableFactory $dataTableFactory,
        private TemplatesRepository $templatesRepository,
        private ContentGenerator $contentGenerator,
        private GeneratorInputFactory $generatorInputFactory,
        private MailTemplateLinksRepository $mailTemplateLinksRepository
    ) {
    }

    public function create(ActiveRow $mailTemplate): self
    {
        $this->templateCode = $mailTemplate->code;
        return $this;
    }

    public function createComponentDataTableMailLinkStats(): DataTable
    {
        $dataTable = $this->dataTableFactory->create();
        $dataTable
            ->setSourceUrl($this->link('mailLinkStatsJsonData'))
            ->setColSetting('url', [
                'header' => 'url',
                'render' => 'link',
                'priority' => 1,
                'orderable' => false,
                'width' => '70%',
                'class' => 'all table-url',
            ])
            ->setColSetting('text', [
                'header' => 'content',
                'render' => 'raw',
                'priority' => 1,
                'orderable' => false,
                'class' => 'all',
            ])
            ->setColSetting('click_count', [
                'header' => 'clicked',
                'priority' => 1,
                'render' => 'number',
                'class' => 'text-right all',
                'orderable' => false,
            ])
            ->setTableSetting('order', Json::encode([])) // removes sorting arrow from first column
            ->setTableSetting('allowSearch', false)
            ->setTableSetting('scrollX', false);

        return $dataTable;
    }

    public function handleMailLinkStatsJsonData(): void
    {
        $request = $this->getPresenter()->getRequest()->getParameters();
        $length = (int)$request['length'];
        $offset = (int)$request['start'];

        $template = $this->templatesRepository->findBy('code', $this->templateCode);
        $mailContent = $this->contentGenerator->render($this->generatorInputFactory->create($template));

        $parsedLinks = $this->extractUrlContent($mailContent->html());
        $dbLinks = $this->mailTemplateLinksRepository->getLinksForTemplate($template);

        $mailLinks = array_replace_recursive($dbLinks, $parsedLinks);
        $linksCount = count($mailLinks);

        $resultData = [];
        foreach ($mailLinks as $mailLink) {
            $text = isset($mailLink['content']) ? $this->processContent($mailLink['content']) : '';

            $resultData[] = [
                [
                    'url' => $mailLink['url'],
                    'text' => $mailLink['url'],
                ],
                $text,
                $mailLink['clickCount'] ?? 0
            ];
        }

        usort($resultData, static function ($a, $b) {
            return $b[2] <=> $a[2];
        });
        $resultData = array_slice($resultData, $offset, $length);

        $result = [
            'recordsTotal' => $linksCount,
            'recordsFiltered' => $linksCount,
            'data' => $resultData
        ];

        $this->presenter->sendJson($result);
    }

    /**
     * @param string $mailContent
     * @return array{hash: string, array{text: string, url: string}}
     */
    public function extractUrlContent(string $mailContent): array
    {
        $matches = [];
        $rtmClickQueryParam = RtmClickReplace::HASH_PARAM . '=';
        $matched = preg_match_all('/<a(\s[^>]*)href\s*=\s*([\"\']??)(http[^\"\' >]*?' . $rtmClickQueryParam . '.*)\2[^>]*>(.*)<\/a>/isU', $mailContent, $matches);

        if (!$matched) {
            return [];
        }

        $result = [];
        foreach ($matches[3] as $index => $url) {
            $hash = RtmClickReplace::getRtmClickHashFromUrl($url);

            $result[$hash] = [
                'content' => $matches[4][$index],
                'url'=> RtmClickReplace::removeRtmClickHash($url),
            ];
        }

        return $result;
    }

    private function processContent(string $content): string
    {
        $text = trim(html_entity_decode(strip_tags($content)));
        if (empty($text)) {
            $matches = [];
            preg_match('/<img\s[^>]*src\s*=\s*([\"\']??)([^\"\' >]*?)\1[^>]*>/iU', $content, $matches);
            $title = htmlspecialchars($matches[0], ENT_QUOTES);
            return "<i class='table-image-tooltip' title='$title'><a href='$matches[2]' target='_blank'>(image)</a></i>";
        }

        return $text;
    }

    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/mail_link_stats.latte');
        $this->template->render();
    }
}
