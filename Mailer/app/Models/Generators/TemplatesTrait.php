<?php

namespace Remp\Mailer\Models\Generators;

trait TemplatesTrait
{
    public function getArticleLinkTemplateFunction(): callable
    {
        return static function ($title, $url, $image) {
            return <<<HTML
                        <table style="border-spacing:0;border-collapse:collapse;vertical-align:top;text-align:left;font-family:Helvetica, Arial, sans-serif;height:100%;width:100%;color:#181818;padding:0;margin:0;Margin:0;line-height:1.3;background:#f8f8f8 !important;">
                            <tr style="padding:0;vertical-align:top;text-align:left;">
                                <td valign="top" style="vertical-align:top;text-align:left;font-size:14px;line-height:1.3;border-collapse:collapse !important;padding-top:16px;padding-left:16px;padding-bottom:16px;padding-right:16px;">
                                    <a href="{$url}" style="text-decoration: none;color: #181818; display:block;width:100%;max-width:100%;height:auto;border:none;">
                                        <img src="{$image}" style="display:block;width:100%;max-width:100%;height:auto;border:none;" alt="{$title}">
                                    </a>
                                    <small style="margin: 0;padding: 0;margin-bottom: 8px;margin-top:8px; font-size:12px; display: block;">Prečítajte si</small>
                                    <h2 style="margin: 0;padding: 0; font-size: 20px;">
                                        <a href="{$url}" style="text-decoration: none;color: #181818;">{$title}</a>
                                    </h2>
                                </td>
                            </tr>
                        </table>
                HTML;
        };
    }
}
