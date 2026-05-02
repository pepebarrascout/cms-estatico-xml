<?php
/**
 * CMS Estático XML - Convertidor Markdown a HTML
 * Parser ligero sin dependencias externas
 */

class MarkdownParser {
    
    public function __construct() {}
    
    /**
     * Convertir Markdown a HTML
     */
    public function parse(string $text): string {
        // Normalizar saltos de línea
        $text = str_replace("\r\n", "\n", $text);
        $text = str_replace("\r", "\n", $text);
        
        // Bloques de código (``` ... ```) - procesar primero
        $text = preg_replace_callback('/```(\w*)\n(.*?)```/s', function($m) {
            $lang = $m[1] ? ' class="language-' . htmlspecialchars($m[1]) . '"' : '';
            return '<pre><code' . $lang . '>' . htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8') . '</code></pre>';
        }, $text);
        
        // Código inline (` ... `)
        $text = preg_replace_callback('/`([^`]+)`/', function($m) {
            return '<code>' . htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8') . '</code>';
        }, $text);
        
        // Imágenes con título opcional
        $text = preg_replace_callback('/!\[([^\]]*)\]\(([^)]+?)(?:\s+"([^"]*)")?\)/', function($m) {
            $alt = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
            $title = isset($m[3]) && $m[3] ? ' title="' . htmlspecialchars($m[3], ENT_QUOTES, 'UTF-8') . '"' : '';
            return '<img src="' . htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8') . '" alt="' . $alt . '"' . $title . ' loading="lazy">';
        }, $text);
        
        // Enlaces con título opcional
        $text = preg_replace_callback('/\[([^\]]+)\]\(([^)]+?)(?:\s+"([^"]*)")?\)/', function($m) {
            $text = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
            $href = htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8');
            $title = isset($m[3]) && $m[3] ? ' title="' . htmlspecialchars($m[3], ENT_QUOTES, 'UTF-8') . '"' : '';
            return '<a href="' . $href . '"' . $title . ' rel="noopener">' . $text . '</a>';
        }, $text);
        
        // Procesar por líneas para encabezados, bloques, listas
        $lines = explode("\n", $text);
        $html = [];
        $inList = false;
        $listType = '';
        $inParagraph = false;
        
        foreach ($lines as $line) {
            // Encabezados
            if (preg_match('/^(#{1,6})\s+(.+)$/', $line, $m)) {
                if ($inParagraph) { $html[] = '</p>'; $inParagraph = false; }
                if ($inList) { $html[] = $listType === 'ul' ? '</ul>' : '</ol>'; $inList = false; }
                $level = strlen($m[1]);
                $content = $this->parseInline(trim($m[2]));
                $html[] = '<h' . $level . '>' . $content . '</h' . $level . '>';
                continue;
            }
            
            // Línea horizontal
            if (preg_match('/^(-{3,}|\*{3,}|_{3,})\s*$/', $line)) {
                if ($inParagraph) { $html[] = '</p>'; $inParagraph = false; }
                if ($inList) { $html[] = $listType === 'ul' ? '</ul>' : '</ol>'; $inList = false; }
                $html[] = '<hr>';
                continue;
            }
            
            // Cierre de lista
            if ($inList && !preg_match('/^[\s]*[-*+]\s+/', $line) && !preg_match('/^[\s]*\d+\.\s+/', $line) && trim($line) === '') {
                $html[] = $listType === 'ul' ? '</ul>' : '</ol>';
                $inList = false;
                continue;
            }
            
            // Lista no ordenada
            if (preg_match('/^[\s]*[-*+]\s+(.+)$/', $line, $m)) {
                if ($inParagraph) { $html[] = '</p>'; $inParagraph = false; }
                if (!$inList || $listType !== 'ul') {
                    if ($inList) $html[] = $listType === 'ul' ? '</ul>' : '</ol>';
                    $html[] = '<ul>';
                    $inList = true;
                    $listType = 'ul';
                }
                $html[] = '<li>' . $this->parseInline($m[1]) . '</li>';
                continue;
            }
            
            // Lista ordenada
            if (preg_match('/^[\s]*\d+\.\s+(.+)$/', $line, $m)) {
                if ($inParagraph) { $html[] = '</p>'; $inParagraph = false; }
                if (!$inList || $listType !== 'ol') {
                    if ($inList) $html[] = $listType === 'ul' ? '</ul>' : '</ol>';
                    $html[] = '<ol>';
                    $inList = true;
                    $listType = 'ol';
                }
                $html[] = '<li>' . $this->parseInline($m[1]) . '</li>';
                continue;
            }
            
            // Blockquote
            if (preg_match('/^>\s?(.+)$/', $line, $m)) {
                if ($inParagraph) { $html[] = '</p>'; $inParagraph = false; }
                if ($inList) { $html[] = $listType === 'ul' ? '</ul>' : '</ol>'; $inList = false; }
                $html[] = '<blockquote>' . $this->parseInline($m[1]) . '</blockquote>';
                continue;
            }
            
            // Línea vacía
            if (trim($line) === '') {
                if ($inParagraph) { $html[] = '</p>'; $inParagraph = false; }
                continue;
            }
            
            // Párrafo
            if ($inList) {
                $html[] = $listType === 'ul' ? '</ul>' : '</ol>';
                $inList = false;
            }
            if (!$inParagraph) {
                $html[] = '<p>';
                $inParagraph = true;
            } else {
                $html[] = '<br>';
            }
            $html[] = $this->parseInline($line);
        }
        
        if ($inParagraph) $html[] = '</p>';
        if ($inList) $html[] = $listType === 'ul' ? '</ul>' : '</ol>';
        
        return implode("\n", $html);
    }
    
    /**
     * Parsear elementos inline (negritas, itálicas, tachado)
     */
    private function parseInline(string $text): string {
        // Negrita + itálica ***texto***
        $text = preg_replace('/\*\*\*(.+?)\*\*\*/', '<strong><em>$1</em></strong>', $text);
        // Negrita **texto** o __texto__
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/__(.+?)__/', '<strong>$1</strong>', $text);
        // Itálica *texto* o _texto_
        $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
        $text = preg_replace('/_(.+?)_/', '<em>$1</em>', $text);
        // Tachado ~~texto~~
        $text = preg_replace('/~~(.+?)~~/', '<del>$1</del>', $text);
        
        return $text;
    }
}

/**
 * Función global para convertir markdown a HTML
 */
function markdownToHtml(string $markdown): string {
    $parser = new MarkdownParser();
    return $parser->parse($markdown);
}
