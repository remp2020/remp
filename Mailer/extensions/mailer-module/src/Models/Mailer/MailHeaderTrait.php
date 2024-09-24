<?php

namespace Remp\MailerModule\Models\Mailer;

trait MailHeaderTrait
{
    /**
     * Get parameter from header string
     *
     * <code>
     * $headerValue = 'Content-Disposition: attachment; filename="invoice-2024-09-24.pdf"';

     * // $filename will contain string "invoice-2024-09-24.pdf"
     * $filename = $this->getHeaderParameter($headerValue, 'filename');
     * </code>
     */
    public function getHeaderParameter(string $headerValue, string $parameter): ?string
    {
        preg_match('/.*' . $parameter . '="(?<value>([^"]*))"/', $headerValue, $result);
        return $result['value'] ?? null;
    }
}
