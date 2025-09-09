<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\Generators;

use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime;
use Remp\Mailer\Components\GeneratorWidgets\Widgets\DailyMinuteWidget\DailyMinuteWidget;
use Remp\MailerModule\Models\ContentGenerator\Engine\EngineFactory;
use Remp\MailerModule\Models\Generators\IGenerator;
use Remp\MailerModule\Models\Generators\InvalidUrlException;
use Remp\MailerModule\Models\Generators\PreprocessException;
use Remp\MailerModule\Repositories\SnippetsRepository;
use Remp\MailerModule\Repositories\SourceTemplatesRepository;
use Tomaj\NetteApi\Params\PostInputParam;

class DailyMinuteGenerator implements IGenerator
{
    private string $nameDaySourceFile;

    private string $from;

    public $onSubmit;

    public function __construct(
        private WordpressBlockParser $wordpressBlockParser,
        private SourceTemplatesRepository $sourceTemplatesRepository,
        private EngineFactory $engineFactory,
        private SnippetsRepository $snippetsRepository,
    ) {
    }

    public function setNameDaySourceFile(string $nameDaySourceFile): void
    {
        $this->nameDaySourceFile = $nameDaySourceFile;
    }

    public function setFrom(string $from): void
    {
        $this->from = $from;
    }

    public function generateForm(Form $form): void
    {
        // disable CSRF protection as external sources could post the params here
        $form->offsetUnset(Form::PROTECTOR_ID);

        $form->addText('subject', 'Subject')
            ->setHtmlAttribute('class', 'form-control')
            ->setRequired();

        $form->addText('from', 'From')
            ->setHtmlAttribute('class', 'form-control');

        $form->addText('url', 'URL')
            ->setHtmlAttribute('class', 'form-control');

        $form->addTextArea('blocks_json', 'Blocks JSON')
            ->setHtmlAttribute('rows', 8)
            ->setHtmlAttribute('class', 'form-control')
            ->setRequired();

        $form->onSuccess[] = [$this, 'formSucceeded'];
    }

    public function formSucceeded(Form $form, ArrayHash $values): void
    {
        try {
            $output = $this->process((array)$values);

            $addonParams = [
                'render' => true,
                'from' => $values->from,
                'subject' => $values->subject,
                'errors' => $output['errors'],
            ];

            $this->onSubmit->__invoke($output['htmlContent'], $output['textContent'], $addonParams);
        } catch (InvalidUrlException $e) {
            $form->addError($e->getMessage());
        }
    }

    public function onSubmit(callable $onSubmit): void
    {
        $this->onSubmit = $onSubmit;
    }

    public function getWidgets(): array
    {
        return [DailyMinuteWidget::class];
    }

    public function apiParams(): array
    {
        return [
            (new PostInputParam('url'))->setRequired(),
            (new PostInputParam('subject'))->setRequired(),
            (new PostInputParam('blocks_json'))->setRequired(),
            (new PostInputParam('from')),
        ];
    }

    public function process(array $values): array
    {
        [$html, $text] = $this->wordpressBlockParser->parseJson($values['blocks_json']);

        $adSnippet = $this->snippetsRepository->all()->where([
            'code' => 'r5m-advertisement',
            'html <> ?' => '',
            'mail_type_id' => null,
        ])->fetch();

        $now = new DateTime();
        $additionalParams = [
            'date' => $now,
            'nameDay' => $this->getNameDayNamesForDate($now),
            'url' => $values['url'],
            'adSnippetHtml' => $adSnippet?->html,
            'adSnippetText' => $adSnippet?->text,
        ];

        $engine = $this->engineFactory->engine();
        $sourceTemplate = $this->sourceTemplatesRepository->find($values['source_template_id']);
        return [
            'htmlContent' => $engine->render($sourceTemplate->content_html, ['html' => $html] + $additionalParams),
            'textContent' => $engine->render($sourceTemplate->content_text, ['text' => $text] + $additionalParams),
            'from' => $values['from'],
            'subject' => $values['subject'],
            'errors' => []
        ];
    }

    public function preprocessParameters($data): ?ArrayHash
    {
        $output = new ArrayHash();

        if (!isset($data->blocks)) {
            throw new PreprocessException("WP json object does not contain required attribute 'blocks'");
        }

        if (!isset($data->subject)) {
            throw new PreprocessException("WP json object does not contain required attribute 'subject'");
        }

        if (!isset($data->url)) {
            throw new PreprocessException("WP json object does not contain required attribute 'url'");
        }

        $output->from = $this->from;
        $output->blocks_json = $data->blocks;
        $output->subject = $data->subject;
        $output->url = $data->url;

        return $output;
    }

    private function getNameDayNamesForDate(DateTime $date): string
    {
        if (!isset($this->nameDaySourceFile)) {
            throw new \Exception("No value set for configurable value 'nameDaySourceFile'. Provide file name in configuration file through 'setNameDaySourceFile()' method.");
        }
        $json = file_get_contents(__DIR__ . '/resources/' . $this->nameDaySourceFile);
        $nameDays = json_decode($json, true);

        // javascript array of months in namedays.json starts from 0
        $month = ((int) $date->format('m')) - 1;
        $day = (int) $date->format('d');

        return $nameDays[$month][$day] ?? '';
    }
}
