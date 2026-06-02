<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\Generators;

use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;
use Nette\Utils\Json;
use Remp\Mailer\Components\GeneratorWidgets\Widgets\EuobserverWidget\EuobserverWidget;
use Remp\MailerModule\Models\ContentGenerator\Engine\EngineFactory;
use Remp\MailerModule\Models\Generators\IGenerator;
use Remp\MailerModule\Models\Generators\InvalidUrlException;
use Remp\MailerModule\Models\Generators\PreprocessException;
use Remp\MailerModule\Repositories\SourceTemplatesRepository;
use Tomaj\NetteApi\Params\PostInputParam;

class EuobserverGenerator implements IGenerator
{
    public $onSubmit;

    public function __construct(
        private SourceTemplatesRepository $sourceTemplatesRepository,
        private EngineFactory $engineFactory,
        private EuobserverWordpressBlockParser $wordpressBlockParser,
    ) {
    }

    public function generateForm(Form $form): void
    {
        // disable CSRF protection as external sources could post the params here
        $form->offsetUnset(Form::ProtectorId);

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

        $form->addTextArea('settings_json', 'Settings JSON')
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
        return [EuobserverWidget::class];
    }

    public function apiParams(): array
    {
        return [
            (new PostInputParam('url'))->setRequired(),
            (new PostInputParam('subject'))->setRequired(),
            (new PostInputParam('blocks_json')),
            (new PostInputParam('settings_json')),
            (new PostInputParam('from')),
        ];
    }

    public function process(array $values): array
    {
        [$html, $text] = $this->wordpressBlockParser->parseJson($values['blocks_json'], $values['settings_json']);

        $engine = $this->engineFactory->engine();
        $sourceTemplate = $this->sourceTemplatesRepository->find($values['source_template_id']);

        return [
            'htmlContent' => $engine->render($sourceTemplate->content_html, ['html' => $html]),
            'textContent' => $engine->render($sourceTemplate->content_text, ['text' => $text]),
            'from' => $values['from'],
            'subject' => $values['subject'],
            'errors' => []
        ];
    }

    public function preprocessParameters($data): ?ArrayHash
    {
        $output = new ArrayHash();

        if (!isset($data->blocks) && !isset($data->post_blocks)) {
            throw new PreprocessException("WP json object does not contain required attribute 'post_blocks'");
        }

        if (!isset($data->settings)) {
            throw new PreprocessException("WP json object does not contain required attribute 'settings'");
        }

        if (!isset($data->subject) && !isset($data->post_title)) {
            throw new PreprocessException("WP json object does not contain required attribute 'post_title'");
        }

        if (!isset($data->url) && !isset($data->post_url)) {
            throw new PreprocessException("WP json object does not contain required attribute 'post_url'");
        }

        if (isset($data->blocks)) {
            // wp-based frontend scenario
            $output->blocks_json = $data->blocks;
            $output->settings_json = $data->settings;
        } else {
            // hub-based backend scenario
            $output->blocks_json = Json::encode($data->post_blocks);
            $output->settings_json = Json::encode($data->settings);
        }

        $output->subject = $data->subject ?? $data->post_title;
        $output->url = $data->url ?? $data->post_url;

        return $output;
    }
}
