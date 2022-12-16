<?php
declare(strict_types=1);

namespace Remp\Mailer\Models\Generators;

class EmbedParser extends \Remp\MailerModule\Models\Generators\EmbedParser
{
    public function createEmbedMarkup(string $link, string $title, ?string $image = null, bool $isVideo = false): string
    {
        $html = "<br>";

        if ($isVideo) {
            $html .= "<p style='text-align: center; font-weight: normal;'><i>{$this->videoLinkText}</i></p><br>";
        }

        $html .= "<a href='{$link}' target='_blank' style='color:#181818;padding:0;margin:0;Margin:0;line-height:1.3;text-decoration:none;text-align: center; display: block;'>";

        if (!is_null($image)) {
            $html .= "<img src='{$image}' alt='{$title}' style='outline:none;text-decoration:none;-ms-interpolation-mode:bicubic;width:auto;max-width:100%;clear:both;display:inline;'>";
        } elseif (preg_match('/twitt/', $link)) {
            $html .= "<a style=\"display: block;margin: 0 0 20px;padding: 7px 10px;text-decoration: none;text-align: center;font-weight: bold;font-family:'Helvetica Neue', Helvetica, Arial;color: #249fdc; background: #ffffff; border: 3px solid #249fdc;margin: 16px 0 16px 0\" href=\"{$link}\" target=\"_blank\">Zobraziť na Twitteri</a>";
        } else {
            $html .= "<span style='text-decoration: underline; color: #1F3F83;'>" . $link . "</span>";
        }

        return $html . "</a>" . PHP_EOL;
    }
}
