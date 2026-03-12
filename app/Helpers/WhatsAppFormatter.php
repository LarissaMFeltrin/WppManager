<?php

namespace App\Helpers;

class WhatsAppFormatter
{
    /**
     * Converte formatação do WhatsApp para HTML
     * - *texto* → negrito
     * - _texto_ → itálico
     * - ~texto~ → tachado
     * - ```texto``` → código em bloco
     * - `texto` → código inline
     */
    public static function format(?string $text): string
    {
        if (empty($text)) {
            return '';
        }

        // Escapar HTML primeiro
        $text = e($text);

        // Bloco de código (``` ... ```) - processar primeiro
        $text = preg_replace('/```(.*?)```/s', '<pre class="wa-code-block">$1</pre>', $text);

        // Código inline (` ... `)
        $text = preg_replace('/`([^`]+)`/', '<code class="wa-code">$1</code>', $text);

        // Negrito (*texto*)
        $text = preg_replace('/\*([^\*]+)\*/', '<strong>$1</strong>', $text);

        // Itálico (_texto_)
        $text = preg_replace('/\_([^\_]+)\_/', '<em>$1</em>', $text);

        // Tachado (~texto~)
        $text = preg_replace('/\~([^\~]+)\~/', '<s>$1</s>', $text);

        // Converter quebras de linha
        $text = nl2br($text);

        return $text;
    }
}
