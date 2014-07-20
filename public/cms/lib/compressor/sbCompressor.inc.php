<?php
/**
 * Реализация классов, отвечающих за сжатие HTML, CSS и JavaScript-кода
 *
 * @version 1.0
 * @package SB_Compressor
 * @copyright Copyright (c) 2012, OOO "ЭсБилдер"
 */

/**
 * Класс, отвечающий за сжатие HTML-кода страницы
 *
 * Из HTML-кода вырезаются пробелы, табуляции, ненужные комментарии и пр.
 *
 * @version 1.0
 * @package SB_Compressor
 * @copyright Copyright (c) 2012, OOO "ЭсБилдер"
 */
class sbCompressorHTML
{
    /**
     * HTML-код в формате XHTML 1.0 или нет
     *
     * @var bool
     */
    protected $_isXhtml = null;

    /**
     * Уникальный хэш для плейсхолдера
     *
     * @var string
     */
    protected $_replacementHash = null;

    /**
     * Массив плейсхолдеров
     *
     * @var array
     */
    protected $_placeholders = array();

    /**
     * Callback-функция для обработки содержимого тегов STYLE
     *
     * @var string
     */
    protected $_cssCompressor = null;

    /**
     * Callback-функция для обработки содержимого тегов SCRIPT
     *
     * @var string
     */
    protected $_jsCompressor = null;

    /**
     * Сжимает HTML-код
     *
     * @param string $html HTML-код для сжатия.
     * @param array $options Набор параметров.
     *
     * 'cssCompressor' : callback-функция для обработки содержимого тегов STYLE.
     *
     * 'jsCompressor' : callback-функция для обработки содержимого тегов SCRIPT.
     *
     * 'xhtml' : HTML-код в формате XHTML 1.0 или нет. Если параметр не передается, то смотрим XHTML doctype.
     *
     * @return string
     */
    public static function compress($html, $options = array())
    {
        $html = trim($html);
        if ($html == '')
            return '';

        $min = new sbCompressorHTML($options);
        return $min->process($html);
    }

    /**
     * Конструктор класса
     *
     * @param array $options Набор параметров.
     *
     * 'cssCompressor' : callback-функция для обработки содержимого тегов STYLE.
     *
     * 'jsCompressor' : callback-функция для обработки содержимого тегов SCRIPT.
     *
     * 'xhtml' : HTML-код в формате XHTML 1.0 или нет. Если параметр не передается, то смотрим XHTML doctype.
     *
     * @return object
     */
    public function __construct($options = array())
    {
        if (isset($options['xhtml']))
        {
            $this->_isXhtml = (bool)$options['xhtml'];
        }
        if (isset($options['cssCompressor'])) {
            $this->_cssCompressor = $options['cssCompressor'];
        }
        if (isset($options['jsCompressor'])) {
            $this->_jsCompressor = $options['jsCompressor'];
        }
    }


    /**
     * Сжимает HTML-код
     *
     * @param string $html Код для сжатия.
     *
     * @return string
     */
    public function process($html)
    {
        $html = str_replace("\r\n", "\n", trim($html));

        if ($this->_isXhtml === null)
        {
            $this->_isXhtml = (false !== sb_strpos($html, '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML'));
        }

        $this->_replacementHash = 'SB_HTML' . md5($_SERVER['REQUEST_TIME']);
        $this->_placeholders = array();

        // убираем содержимое тегов SCRIPT
        $html = preg_replace_callback(
                '/(\\s*)<script(\\b[^>]*?>)([\\s\\S]*?)<\\/script>(\\s*)/i'.SB_PREG_MOD
                ,array($this, '_removeScriptCB')
                ,$html);

        // убираем содержимое тегов STYLE
        $html = preg_replace_callback(
                '/\\s*<style(\\b[^>]*>)([\\s\\S]*?)<\\/style>\\s*/i'.SB_PREG_MOD
                ,array($this, '_removeStyleCB')
                ,$html);

        // убираем HTML-комментарии
        $html = preg_replace_callback(
                '/<!--([\\s\\S]*?)-->/'.SB_PREG_MOD
                ,array($this, '_commentCB')
                ,$html);

        // убираем содержимое тегов PRE
        $html = preg_replace_callback('/\\s*<pre(\\b[^>]*?>[\\s\\S]*?<\\/pre>)\\s*/i'.SB_PREG_MOD
                ,array($this, '_removePreCB')
                ,$html);

        // уббираем содержимое тегов TEXTAREA
        $html = preg_replace_callback(
                '/\\s*<textarea(\\b[^>]*?>[\\s\\S]*?<\\/textarea>)\\s*/i'.SB_PREG_MOD
                ,array($this, '_removeTextareaCB')
                ,$html);

        // убираем пробелы и переводы строк
        $html = preg_replace('/^\\s+|\\s+$/m'.SB_PREG_MOD, ' ', $html);

        // убираем пробелы между блочными и непоказываемыми элементами
        $html = preg_replace('/\\s+(<\\/?(?:area|base(?:font)?|blockquote|body'
                .'|caption|center|cite|col(?:group)?|dd|dir|dt|fieldset|form'
                .'|br|frame(?:set)?|h[1-6]|head|hr|html|legend|link|map|menu|meta'
                .'|ol|opt(?:group|ion)|param|t(?:able|body|head|d|h||r|foot|itle)'
                .'|ul)\\b[^>]*>)/i'.SB_PREG_MOD, '$1', $html);

        // убираем пробелы после любых элементов
        $html = preg_replace(
                '/>(\\s(?:\\s*))?([^<]+)(\\s(?:\s*))?</'.SB_PREG_MOD
                ,'>$1$2$3<'
                ,$html);

        //$html = preg_replace('/(<[a-z\\-]+)\\s+([^>]+>)/i'.SB_PREG_MOD, "$1\n$2", $html);

        // возвращаем плейсхолдеры
        $html = sb_str_replace(
                array_keys($this->_placeholders)
                ,array_values($this->_placeholders)
                ,$html
        );

        // еще раз для скриптов внутри textarea
        $html = sb_str_replace(
                array_keys($this->_placeholders)
                ,array_values($this->_placeholders)
                ,$html
        );

        return $html;
    }

    /**
     * Вырезаем HTML-комментарии, сохраняем условные комментарии IE.
     *
     * @param array $m Массив замен.
     *
     * @return string
     */
    protected function _commentCB($m)
    {
        return (0 === strpos($m[1], '[') || false !== strpos($m[1], '<![') || 0 === strpos($m[1], 'sb_index') || 0 === strpos($m[1], 'noindex') || 0 === strpos($m[1], '/noindex')) ? $m[0] : '';
    }

    /**
     * Вырезаем код тега, создаем соотв. плейсхолдер.
     *
     * @param string $content Код для создания плейсхолдера.
     *
     * @return string Имя плейсхолдера.
     */
    protected function _reservePlace($content)
    {
        $placeholder = '%' . $this->_replacementHash . count($this->_placeholders) . '%';
        $this->_placeholders[$placeholder] = $content;
        return $placeholder;
    }

    /**
     * Вырезаем код pre, создаем соотв. плейсхолдер.
     *
     * @param array $m Массив замен.
     *
     * @return string
     */
    protected function _removePreCB($m)
    {
        return $this->_reservePlace("<pre{$m[1]}");
    }

    /**
     * Вырезаем код textarea, создаем соотв. плейсхолдер.
     *
     * @param array $m Массив замен.
     *
     * @return string
     */
    protected function _removeTextareaCB($m)
    {
        return $this->_reservePlace("<textarea{$m[1]}");
    }

    /**
     * Вырезаем CSS-код, создаем соотв. плейсхолдер.
     *
     * @param array $m Массив замен.
     *
     * @return string
     */
    protected function _removeStyleCB($m)
    {
        $openStyle = "<style{$m[1]}";
        $css = $m[2];

        // вырезаем комментарии
        $css = preg_replace('/(?:^\\s*<!--|-->\\s*$)/', '', $css);

        // вырезаем разметку CDATA
        $css = $this->_removeCdata($css);

        // сжимаем код
        $func = $this->_cssCompressor ? $this->_cssCompressor : 'trim';
        $css = call_user_func($func, $css);

        return $this->_reservePlace($this->_needsCdata($css) ? "{$openStyle}/*<![CDATA[*/{$css}/*]]>*/</style>" : "{$openStyle}{$css}</style>");
    }

    /**
     * Вырезаем JavaScript-код, создаем соотв. плейсхолдер.
     *
     * @param array $m Массив замен.
     *
     * @return string
     */
    protected function _removeScriptCB($m)
    {
        $openScript = "<script{$m[2]}";
        $js = $m[3];

        // есть проблемы перед и после? оставляем как минимум один пробел
        $ws1 = ''; //($m[1] === '') ? '' : ' ';
        $ws2 = ''; //($m[4] === '') ? '' : ' ';

        // вырезаем комментарии
        $js = preg_replace('/(?:^\\s*<!--\\s*|\\s*(?:\\/\\/)?\\s*-->\\s*$)/', '', $js);

        // вырезаем разметку CDATA
        $js = $this->_removeCdata($js);

        // сжимаем код
        $func = $this->_jsCompressor ? $this->_jsCompressor : 'trim';
        $js = call_user_func($func, $js);

        return $this->_reservePlace($this->_needsCdata($js) ? "{$ws1}{$openScript}/*<![CDATA[*/{$js}/*]]>*/</script>{$ws2}" : "{$ws1}{$openScript}{$js}</script>{$ws2}");
    }

    /**
     * Вырезает разметку CDATA
     *
     * @param string $str Код для анализа.
     *
     * @return Сгенерированный код.
     */
    protected function _removeCdata($str)
    {
        return (false !== sb_strpos($str, '<![CDATA[')) ? str_replace(array('<![CDATA[', ']]>'), '', $str) : $str;
    }

    /**
     * Опеределяет, нужна разметка CDATA или нет
     *
     * @param string $str Код для анализа.
     *
     * @return boolean
     */
    protected function _needsCdata($str)
    {
        return ($this->_isXhtml && preg_match('/(?:[<&]|\\-\\-|\\]\\]>)/', $str));
    }
}

/**
 * Класс, отвечающий сжатие CSS-кода страницы
 *
 * Из CSS-кода вырезаются пробелы, табуляции, ненужные комментарии и пр.
 *
 * @version 1.0
 * @package SB_Compressor
 * @copyright Copyright (c) 2012, OOO "ЭсБилдер"
 */
class sbCompressorCSS
{
    /**
     * Мы внутри "хака"?
     *
     * @var bool
     */
    protected $_inHack = false;

    /**
     * Сжимает CSS-код
     *
     * @param string $css CSS-код для сжатия.
     * @param array $options Набор парамеров.
     *
     * @return string
     */
    public static function compress($css)
    {
        $obj = new sbCompressorCSS();
        return $obj->process($css);
    }

    /**
     * Сжимает CSS-код.
     *
     * @param string $css CSS-код.
     *
     * @return string
     */
    public function process($css)
    {
        $css = str_replace("\r\n", "\n", $css);

        // оставляем пустой комментарий после '>'
        // http://www.webdevout.net/css-hacks#in_css-selectors
        $css = preg_replace('@>/\\*\\s*\\*/@'.SB_PREG_MOD, '>/*keep*/', $css);

        // оставляем пустой комментарий между свойством и значением
        // http://css-discuss.incutio.com/?page=BoxModelHack
        $css = preg_replace('@/\\*\\s*\\*/\\s*:@'.SB_PREG_MOD, '/*keep*/:', $css);
        $css = preg_replace('@:\\s*/\\*\\s*\\*/@'.SB_PREG_MOD, ':/*keep*/', $css);

        // вырезаем комментарии
        $css = preg_replace_callback('@\\s*/\\*([\\s\\S]*?)\\*/\\s*@'.SB_PREG_MOD
                ,array($this, '_commentCB'), $css);

        // убираем пробелы рядом с { } и после последней ;
        $css = preg_replace('/\\s*{\\s*/'.SB_PREG_MOD, '{', $css);
        $css = preg_replace('/;?\\s*}\\s*/'.SB_PREG_MOD, '}', $css);

        // убираем пробелы рядом с ;
        $css = preg_replace('/\\s*;\\s*/'.SB_PREG_MOD, ';', $css);

        // убираем пробелы рядом с URL
        $css = preg_replace('/
                url\\(      # url(
                \\s*
                ([^\\)]+?)  # 1 = the URL (really just a bunch of non right parenthesis)
                \\s*
                \\)         # )
            /x'.SB_PREG_MOD, 'url($1)', $css);

        // убираем пробелы между правилами и :
        $css = preg_replace('/
                \\s*
                ([{;])              # 1 = beginning of block or rule separator
                \\s*
                ([\\*_]?[\\w\\-]+)  # 2 = property (and maybe IE filter)
                \\s*
                :
                \\s*
                (\\b|[#\'"-])        # 3 = first character of a value
            /x'.SB_PREG_MOD, '$1$2:$3', $css);

        // убираем пробелы в селекторах
        $css = preg_replace_callback('/
                (?:              # non-capture
                    \\s*
                    [^~>+,\\s]+  # selector part
                    \\s*
                    [,>+~]       # combinators
                )+
                \\s*
                [^~>+,\\s]+      # selector part
                {                # open declaration block
            /x'.SB_PREG_MOD
                ,array($this, '_selectorsCB'), $css);

        // сжимаем коды цветов
        $css = preg_replace('/([^=])#([a-f\\d])\\2([a-f\\d])\\3([a-f\\d])\\4([\\s;\\}])/i'.SB_PREG_MOD
                , '$1#$2$3$4$5', $css);

        // убираем пробелы между названиями шрифтов
        $css = preg_replace_callback('/font-family:([^;}]+)([;}])/'.SB_PREG_MOD
                ,array($this, '_fontFamilyCB'), $css);

        $css = preg_replace('/@import\\s+url/'.SB_PREG_MOD, '@import url', $css);
        $css = preg_replace('/[ \\t]*\\n+\\s*/'.SB_PREG_MOD, "\n", $css);

        /*$css = preg_replace('/([\\w#\\.\\*]+)\\s+([\\w#\\.\\*]+){/'.SB_PREG_MOD, "$1\n$2{", $css);
        $css = preg_replace('/
            ((?:padding|margin|border|outline):\\d+(?:px|em)?) # 1 = prop : 1st numeric value
            \\s+
            /x'.SB_PREG_MOD
                ,"$1\n", $css);*/

        // Баг IE6: http://www.crankygeek.com/ie6pebug/
        $css = preg_replace('/:first-l(etter|ine)\\{/'.SB_PREG_MOD, ':first-l$1 {', $css);

        return trim($css);
    }

    /**
     * Обработка списка селекторов
     *
     * @param array $m Массив совпадений.
     *
     * @return string
     */
    protected function _selectorsCB($m)
    {
        return preg_replace('/\\s*([,>+~])\\s*/', '$1', $m[0]);
    }

    /**
     * Обработка комментариев
     *
     * @param array $m Массив совпадений.
     *
     * @return string
     */
    protected function _commentCB($m)
    {
        $hasSurroundingWs = (trim($m[0]) !== $m[1]);
        $m = $m[1];

        if ($m === 'keep')
        {
            return '/**/';
        }

        if ($m === '" "')
        {
            return '/*" "*/';
        }

        if (preg_match('@";\\}\\s*\\}/\\*\\s+@', $m))
        {
            return '/*";}}/* */';
        }

        if ($this->_inHack)
        {
            if (preg_match('@
                    ^/               # comment started like /*/
                    \\s*
                    (\\S[\\s\\S]+?)  # has at least some non-ws content
                    \\s*
                    /\\*             # ends like /*/ or /**/
                @x'.SB_PREG_MOD, $m, $n))
                {
                $this->_inHack = false;
                return "/*/{$n[1]}/**/";
                }
            }

            if (substr($m, -1) === '\\')
            {
                $this->_inHack = true;
                return '/*\\*/';
            }

            if ($m !== '' && $m[0] === '/')
            {
                $this->_inHack = true;
                return '/*/*/';
            }

            if ($this->_inHack)
            {
                $this->_inHack = false;
                return '/**/';
            }

            return $hasSurroundingWs ? ' ' : '';
    }

    /**
     * Сжимает перечисление имен шрифтов
     *
     * @param array $m Массив совпадений.
     *
     * @return string
     */
    protected function _fontFamilyCB($m)
    {
        $pieces = preg_split('/(\'[^\']+\'|"[^"]+")/', $m[1], null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $out = 'font-family:';
        while (null !== ($piece = array_shift($pieces)))
        {
            if ($piece[0] !== '"' && $piece[0] !== "'")
            {
                $piece = preg_replace('/\\s+/', ' ', $piece);
                $piece = preg_replace('/\\s?,\\s?/', ',', $piece);
            }
            $out .= $piece;
        }
        return $out . $m[2];
    }
}

/**
 * Класс, отвечающий сжатие JS-кода страницы
 *
 * Из JS-кода вырезаются пробелы, табуляции, ненужные комментарии и пр.
 *
 * @version 1.0
 * @package SB_Compressor
 * @copyright Copyright (c) 2012, OOO "ЭсБилдер"
 */
class sbCompressorJS
{
    const ORD_LF            = 10;
    const ORD_SPACE         = 32;
    const ACTION_KEEP_A     = 1;
    const ACTION_DELETE_A   = 2;
    const ACTION_DELETE_A_B = 3;

    protected $a           = "\n";
    protected $b           = '';
    protected $input       = '';
    protected $inputIndex  = 0;
    protected $inputLength = 0;
    protected $lookAhead   = null;
    protected $output      = '';
    protected $lastByteOut  = '';

    /**
     * Сжатие Javascript-кода.
     *
     * @param string $js Javascript-код
     *
     * @return string
     */
    public static function compress($js)
    {
        $js = str_replace("\r\n", "\n", $js);
        $js = preg_replace('/\h+/m'.SB_PREG_MOD, ' ', $js);
	    $js = preg_replace('/\v+/m'.SB_PREG_MOD, "\n", $js);

        return $js;

        /*$obj = new sbCompressorJS($js);
        return $obj->process();*/
    }

    /**
     * Конструктор класса
     *
     * @param string $input JS-код для сжатия.
     */
    public function __construct($input)
    {
        $this->input = $input;
    }

    /**
     * Сжимает JS-код
     *
     * @return string
     */
    public function process()
    {
        if ($this->output !== '')
        {
            return $this->output;
        }

        $this->input = str_replace("\r\n", "\n", $this->input);
        $this->inputLength = sb_strlen($this->input);

        $this->action(self::ACTION_DELETE_A_B);

        while ($this->a !== null)
        {
            // следующая команда
            $command = self::ACTION_KEEP_A;
            if ($this->a === ' ')
            {
                if (($this->lastByteOut === '+' || $this->lastByteOut === '-') && ($this->b === $this->lastByteOut))
                {
                    // Не убираем этот пробел, так как можем сломать постинкремент
                }
                elseif (!$this->isAlphaNum($this->b))
                {
                    $command = self::ACTION_DELETE_A;
                }
            }
            elseif ($this->a === "\n")
            {
                if ($this->b === ' ')
                {
                    $command = self::ACTION_DELETE_A_B;
                }
                elseif ($this->b === null || (false === strpos('{[(+-', $this->b) && !$this->isAlphaNum($this->b)))
                {
                    $command = self::ACTION_DELETE_A;
                }
            }
            elseif (!$this->isAlphaNum($this->a))
            {
                if ($this->b === ' ' || ($this->b === "\n" && (false === sb_strpos('}])+-"\'', $this->a))))
                {
                    $command = self::ACTION_DELETE_A_B;
                }
            }
            $this->action($command);
        }
        $this->output = trim($this->output);

        return $this->output;
    }

    /**
     * ACTION_KEEP_A = Выводит A. Копирует B в A. Берет следующую B.
     * ACTION_DELETE_A = Копирует B в A. Берет следующую B.
     * ACTION_DELETE_A_B = Берет следующую B.
     *
     * @param int $command
     */
    protected function action($command)
    {
        if ($command === self::ACTION_DELETE_A_B && $this->b === ' ' && ($this->a === '+' || $this->a === '-'))
        {
            if ($this->input[$this->inputIndex] === $this->a)
            {
                $command = self::ACTION_KEEP_A;
            }
        }

        switch ($command)
        {
            case self::ACTION_KEEP_A:
                $this->output .= $this->a;
                $this->lastByteOut = $this->a;

            case self::ACTION_DELETE_A:
                $this->a = $this->b;
                if ($this->a === "'" || $this->a === '"')
                {
                    $str = $this->a;
                    while (true)
                    {
                        $this->output .= $this->a;
                        $this->lastByteOut = $this->a;

                        $this->a = $this->get();
                        if ($this->a === $this->b)
                        {
                            break;
                        }

                        if (ord($this->a) <= self::ORD_LF)
                        {
                            // ошибка!!!
                        }

                        $str .= $this->a;

                        if ($this->a === '\\')
                        {
                            $this->output .= $this->a;
                            $this->lastByteOut = $this->a;

                            $this->a       = $this->get();
                            $str .= $this->a;
                        }
                    }
                }

            case self::ACTION_DELETE_A_B:
                $this->b = $this->next();
                if ($this->b === '/' && $this->isRegexpLiteral())
                {
                    $this->output .= $this->a . $this->b;
                    $pattern = '/';
                    while (true)
                    {
                        $this->a = $this->get();
                        $pattern .= $this->a;
                        if ($this->a === '/')
                        {
                            break;
                        }
                        elseif ($this->a === '\\')
                        {
                            $this->output .= $this->a;
                            $this->a       = $this->get();
                            $pattern      .= $this->a;
                        }
                        elseif (ord($this->a) <= self::ORD_LF)
                        {
                            // ошибка!!!
                        }
                        $this->output .= $this->a;
                        $this->lastByteOut = $this->a;
                    }
                    $this->b = $this->next();
                }
        }
    }

    /**
     * @return bool
     */
    protected function isRegexpLiteral()
    {
        if (false !== sb_strpos("\n{;(,=:[!&|?", $this->a))
        {
            return true;
        }

        if (' ' === $this->a)
        {
            $length = sb_strlen($this->output);
            if ($length < 2)
            {
                return true;
            }

            if (preg_match('/(?:case|else|in|return|typeof)$/'.SB_PREG_MOD, $this->output, $m))
            {
                if ($this->output === $m[0])
                {
                    return true;
                }

                $charBeforeKeyword = sb_substr($this->output, $length - sb_strlen($m[0]) - 1, 1);
                if (! $this->isAlphaNum($charBeforeKeyword))
                {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Берем следующий символ.
     *
     * @return string
     */
    protected function get()
    {
        $c = $this->lookAhead;
        $this->lookAhead = null;
        if ($c === null)
        {
            if ($this->inputIndex < $this->inputLength)
            {
                $c = sb_substr($this->input, $this->inputIndex, 1);
                $this->inputIndex += 1;
            }
            else
            {
                return null;
            }
        }
        if ($c === "\r" || $c === "\n")
        {
            return "\n";
        }
        if (ord($c) < self::ORD_SPACE)
        {
            return ' ';
        }
        return $c;
    }

    /**
     * Берем следующий символ.
     *
     * @return string
     */
    protected function peek()
    {
        $this->lookAhead = $this->get();
        return $this->lookAhead;
    }

    /**
     * Является ли $c символом?
     *
     * @param string $c
     *
     * @return bool
     */
    protected function isAlphaNum($c)
    {
        return (preg_match('/^[0-9a-zA-Z_\\$\\\\]$/'.SB_PREG_MOD, $c) || ord($c) > 126);
    }

    /**
     * @return string
     */
    protected function singleLineComment()
    {
        $comment = '';
        while (true)
        {
            $get = $this->get();
            $comment .= $get;
            if (ord($get) <= self::ORD_LF)
            {
                if (preg_match('/^\\/@(?:cc_on|if|elif|else|end)\\b/'.SB_PREG_MOD, $comment))
                {
                    return "/{$comment}";
                }
                return $get;
            }
        }
    }

    /**
     * @return string
     */
    protected function multipleLineComment()
    {
        $this->get();
        $comment = '';
        while (true)
        {
            $get = $this->get();
            if ($get === '*')
            {
                if ($this->peek() === '/')
                {
                    $this->get();

                    if (0 === strpos($comment, '!'))
                    {
                        return "\n/*!" . substr($comment, 1) . "*/\n";
                    }

                    if (preg_match('/^@(?:cc_on|if|elif|else|end)\\b/'.SB_PREG_MOD, $comment))
                    {
                        return "/*{$comment}*/";
                    }
                    return ' ';
                }
            }
            elseif ($get === null)
            {
                // ошибка
            }
            $comment .= $get;
        }
    }

    /**
     * Берем следующий символ, пропускаем комментарии.
     *
     * @return string
     */
    protected function next()
    {
        $get = $this->get();
        if ($get !== '/')
        {
            return $get;
        }
        switch ($this->peek())
        {
            case '/': return $this->singleLineComment();
            case '*': return $this->multipleLineComment();
            default: return $get;
        }
    }
}

/**
 * Класс, отвечающий за формирование сжатых CSS и JS-файлов по коду HTML-страницы
 *
 * Из HTML-кода вырезаются пробелы, табуляции, ненужные комментарии и пр.
 *
 * @version 1.0
 * @package SB_Compressor
 * @copyright Copyright (c) 2012, OOO "ЭсБилдер"
 */
class sbCompressor
{
    /**
     * Массив с путями к CSS-файлам.
     *
     * @var array
     */
    protected $_css = array();

    /**
     * Массив с путями к JS-файлам.
     *
     * @var array
     */
    protected $_js = array();

    /**
     * Уникальный хэш для плейсхолдера
     *
     * @var string
     */
    protected $_replacementHash = null;

    /**
     * Массив плейсхолдеров
     *
     * @var array
     */
    protected $_placeholders = array();

    /**
     * Формирование сжатых CSS и JS-файлов по коду HTML-страницы
     *
     * @param string $html HTML-код
     *
     * @return string
     */
    public static function compress($html)
    {
        $obj = new sbCompressor();
        return $obj->process($html);
    }

    /**
     * Формирование сжатых CSS и JS-файлов по коду HTML-страницы
     *
     * @param string $html HTML-код для анализа.
     */
    public function process($html)
    {
        $this->_replacementHash = 'SB_HTML' . md5($_SERVER['REQUEST_TIME']);
        $this->_placeholders = array();

        // убираем условные подключения
        $html = preg_replace_callback(
                '/(<!--\s*\[\s*if)(.*?)(<!--\s*)?(<!\[\s*endif\s*\]\s*-->)/ms'.SB_PREG_MOD
                ,array($this, '_conditionCB')
                ,$html);

        // вырезаем подключение JS, формируем массив с URL
        $html = preg_replace_callback(
                '/(<script[^>]+?src=["\'])([^"\']*?\.js)(["\'].*?<\/script>)/ms'.SB_PREG_MOD
                ,array($this, '_getJS')
                ,$html);

        // вырезаем подключение CSS, формируем массив с URL
        $html = preg_replace_callback(
                '/(<link[^>]+?href=["\'])([^"\']*?\.css)(["\'][^>]*?>)/'.SB_PREG_MOD
                ,array($this, '_getCSS')
                ,$html);

        $html = $this->_compressFiles($html);

        // возвращаем плейсхолдеры
        $html = sb_str_replace(
                array_keys($this->_placeholders)
                ,array_values($this->_placeholders)
                ,$html
        );

        return $html;
    }

    protected function _conditionCB($m)
    {
        $placeholder = '%' . $this->_replacementHash . count($this->_placeholders) . '%';
        $this->_placeholders[$placeholder] = $m[0];

        return $placeholder;
    }

    /**
     * Вырезает подключение JS
     *
     * @param string $html HTML-код.
     */
    protected function _getJS($m)
    {
        $src = trim($m[2]);
        if ((sb_stripos($src, '//') === 0 || sb_stripos($src, 'http://') === 0 || sb_stripos($src, 'https://') === 0) && sb_stripos($src, SB_COOKIE_DOMAIN) === false)
        {
            return $m[0];
        }

        // убираем имя домена из ссылок
        $src = sb_str_replace(array('http://', 'www.', SB_COOKIE_DOMAIN), '', $src);

        if ($src[0] != '/')
        {
            // абсолютный путь к файлу
            $src = dirname($_SERVER['SCRIPT_FILENAME']).'/'.trim($src, '/');
        }
        else
        {
            $src = SB_BASEDIR.$src;
        }

	if (sb_strpos('./', $src) !== false || sb_strpos('../', $src) !== false)
	{
        	// распарсиваем . и ..
	        $real_src = realpath($src);
        	if ($real_src !== false)
	        {
        	    $src = strtr($real_src, '\\', '/');
        	}
	}
        // делаем относительный путь
        $src = str_replace(SB_BASEDIR, '', $src);

        $this->_js['files['.count($this->_js).']'] = $src;

        return '';
    }

    /**
     * Вырезает подключение CSS
     *
     * @param string $html HTML-код.
     */
    protected function _getCSS($m)
    {
        $src = trim($m[2]);
        if ((sb_stripos($src, '//') === 0 || sb_stripos($src, 'http://') === 0 || sb_stripos($src, 'https://') === 0) && sb_stripos($src, SB_COOKIE_DOMAIN) === false)
        {
            return $m[0];
        }

        // убираем имя домена из ссылок
        $src = sb_str_replace(array('http://', 'www.', SB_COOKIE_DOMAIN), '', $src);

        if ($src[0] != '/')
        {
            // абсолютный путь к файлу
            $src = dirname($_SERVER['SCRIPT_FILENAME']).'/'.trim($src, '/');
        }
        else
        {
            $src = SB_BASEDIR.$src;
        }

	if (sb_strpos('./', $src) !== false || sb_strpos('../', $src) !== false)
	{
        	// распарсиваем . и ..
	        $real_src = realpath($src);
        	if ($real_src !== false)
	        {
        	    $src = strtr($real_src, '\\', '/');
	        }
	}

        // делаем относительный путь
        $src = str_replace(SB_BASEDIR, '', $src);

        $this->_css['files['.count($this->_css).']'] = $src;

        return '';
    }

    protected function _compressFiles($html)
    {
        $isXhtml = (false !== sb_strpos($html, '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML'));

        $m = array();
        if (!preg_match('/<head(.*?)>(.*?)<\/head>/msi'.SB_PREG_MOD, $html, $m))
            return $html;

        $head = $m[0];
        $attr_head = $m[1];
        $in_head = $m[2];

        $script = sb_stripos($in_head, '<script');

        if (count($this->_css) > 0)
        {
            if ($script !== false)
                $in_head = preg_replace('/<script/i', '<link rel="stylesheet" type="text/css" href="/cms/assets/css/mini/?'.http_build_query($this->_css, '', '&amp;').'"'.($isXhtml ? ' />' : '>').'<script', $in_head, 1);
            else
                $in_head .= '<link rel="stylesheet" type="text/css" href="/cms/assets/css/mini/?'.http_build_query($this->_css, '', '&amp;').'"'.($isXhtml ? ' />' : '>');
        }

        if (count($this->_js) > 0)
        {
            if ($script !== false)
                $in_head = preg_replace('/<script/i', '<script src="/cms/assets/js/mini/?'.http_build_query($this->_js, '', '&amp;').'" type="text/javascript"></script><script', $in_head, 1);
            else
                $in_head .= '<script src="/cms/assets/js/mini/?'.http_build_query($this->_js, '', '&amp;').'" type="text/javascript"></script>';
        }

        return sb_str_replace($head, '<head'.$attr_head.'>'.$in_head.'</head>', $html);
    }
}

/**
 * Заменяет относительные URI на абсолютные в CSS-файлах
 *
 * @version 1.0
 * @package SB_Compressor
 * @copyright Copyright (c) 2012, OOO "ЭсБилдер"
 */
class sbCompressorCSSUriRewriter
{
    /**
     * Директория, в которой располагается CSS-файл
     *
     * @var string
     */
    private static $_currentDir = '';

    /**
     * Заменяет относительные URI на абсолютные в CSS-коде
     *
     * @param string $css CSS-код.
     * @param string $currentDir Директория, в которой располагается CSS-файл.
     *
     * @return string
     */
    public static function rewrite($css, $currentDir)
    {
        self::$_currentDir = $currentDir;

        $css = self::_trimUrls($css);

        // Замена
        $css = preg_replace_callback('/@import\\s+([\'"])(.*?)[\'"]/'.SB_PREG_MOD
                ,array('sbCompressorCSSUriRewriter', '_processUriCB'), $css);

        $css = preg_replace_callback('/url\\(\\s*([^\\)\\s]+)\\s*\\)/'.SB_PREG_MOD
                ,array('sbCompressorCSSUriRewriter', '_processUriCB'), $css);

        return $css;
    }

    /**
     * Убирает лишние пробелы из всех URI
     *
     * @param string $css CSS-код.
     *
     * @return string
     */
    private static function _trimUrls($css)
    {
        return preg_replace('/
                url\\(          # url(
                \\s*
                ([^\\)]+?)      # 1 = URI (assuming does not contain ")")
                \\s*
                \\)             # )
                /x'.SB_PREG_MOD, 'url($1)', $css);
    }

    /**
     * @param array $m Массив соответствий.
     *
     * @return string
     */
    private static function _processUriCB($m)
    {
        // $m соотв. '/@import\\s+([\'"])(.*?)[\'"]/' или '/url\\(\\s*([^\\)\\s]+)\\s*\\)/'
        $isImport = ($m[0][0] === '@');

        // определяем URI и кавычку (если есть)
        if ($isImport)
        {
            $quoteChar = $m[1];
            $uri = $m[2];
        }
        else
        {
            $quoteChar = ($m[1][0] === "'" || $m[1][0] === '"') ? $m[1][0] : '';
            $uri = ($quoteChar === '') ? $m[1] : sb_substr($m[1], 1, strlen($m[1]) - 2);
        }

        $uri = trim($uri);
        if ((sb_stripos($uri, '//') === 0 || sb_stripos($uri, 'http://') === 0 || sb_stripos($uri, 'https://') === 0) && sb_stripos($uri, SB_COOKIE_DOMAIN) === false)
        {
            // директива импортирует внешний файл, оставляем как есть
            return $m[0];
        }

        $uri = sb_str_replace(array('http://', 'www.', SB_COOKIE_DOMAIN), '', $uri);

        // анализ URI
        if ('/' !== $uri[0]                      // относительно root
                && false === strpos($uri, '//')  // протокол (не-data)
                && 0 !== strpos($uri, 'data:')   // data протокол
        )
        {
            // URI указан относительно CSS-файла, переписываем
            $uri = self::rewriteRelative($uri, self::$_currentDir);
        }

        return $isImport ? "@import {$quoteChar}{$uri}{$quoteChar}" : "url({$quoteChar}{$uri}{$quoteChar})";
    }

    /**
     * Делает абсолютный URI из относительного
     *
     * @param string $uri Относительный URI.
     * @param string $realCurrentDir Директория, в которой располагается CSS-файл.
     *
     * @return string
     */
    public static function rewriteRelative($uri, $realCurrentDir)
    {
        $path = strtr($realCurrentDir, '/', DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . strtr($uri, '/', DIRECTORY_SEPARATOR);
        // убираем DOC_ROOT
        $path = sb_substr($path, sb_strlen(SB_BASEDIR));

        // делаем абсолютный URI
        $uri = strtr($path, '/\\', '//');
        $uri = self::removeDots($uri);

        return $uri;
    }

    /**
    * Убираем "./" и "../" там, где есть такая возможность
    *
    * @param string $uri
    *
    * @return string
    */
    public static function removeDots($uri)
    {
        $uri = str_replace('/./', '/', $uri);
        do
        {
            $uri = preg_replace('@/[^/]+/\\.\\./@'.SB_PREG_MOD, '/', $uri, 1, $changed);
        } while ($changed);

        return $uri;
    }
}

/**
 * Класс, отвечающий за обработку import-директив CSS-файлов
 *
 * @version 1.0
 * @package SB_Compressor
 * @copyright Copyright (c) 2012, OOO "ЭсБилдер"
 */
class sbCompressorCSSImport
{
    /**
     * Массив с импортированными файлами
     *
     * @var array
     */
    public static $files = array();

    /**
     * Текущая директория для callback-функций
     *
     * @var string
     */
    private $_currentDir = null;

    /**
     * Текущая директория файла, который импортирует обрабатываемый файл
     *
     * @var string
     */
    private $_previewsDir = null;

    /**
     * Проимпортированный контент для callback-функций
     *
     * @var string
     */
    private $_importedContent = '';

    /**
     * Заменяет import-директивы на содержимое файлов
     *
     * @param string $css CSS-код.
     * @param string $file Путь к CSS-файлу относительно DOC_ROOT.
     *
     * @return string
     */
    public static function process($css, $file)
    {
        self::$files = array($file);

        $obj = new sbCompressorCSSImport(dirname(SB_BASEDIR.$file));
        return $obj->_getContent($css, $file);
    }

    /**
     * Конструктор класса
     *
     * @param string $currentDir Директория, в которой располагается CSS-файл.
     * @param string $previewsDir Директория файла, который импортирует обрабатываемый файл.
     */
    private function __construct($currentDir, $previewsDir = '')
    {
        $this->_currentDir = $currentDir;
        $this->_previewsDir = $previewsDir;
    }

    /**
     * Заменяет import-директивы на содержимое файлов
     *
     * @param string $css CSS-код.
     * @param string $file CSS-файл.
     * @param string $is_imported Рекурсивный вызов?
     *
     * @return string
     */
    private function _getContent($css, $file, $is_imported = false)
    {
        // убираем UTF-8 BOM
        if (pack("CCC", 0xef,0xbb,0xbf) === substr($css, 0, 3))
        {
            $css = substr($css, 3);
        }

        // делаем все пути в коде абсолютными
        $css = sbCompressorCSSUriRewriter::rewrite($css, dirname(SB_BASEDIR.$file), SB_BASEDIR);

        $css = str_replace("\r\n", "\n", $css);

        // обрабатываем @imports
        $css = preg_replace_callback(
                '/
                @import\\s+
                (?:url\\(\\s*)?      # maybe url(
                [\'"]?               # maybe quote
                (.*?)                # 1 = URI
                [\'"]?               # maybe end quote
                (?:\\s*\\))?         # maybe )
                ([a-zA-Z,\\s]*)?     # 2 = media list
                ;                    # end token
            /x'.SB_PREG_MOD
                ,array($this, '_importCB')
                ,$css
        );

        return $this->_importedContent . $css;
    }

    private function _importCB($m)
    {
        $url = trim($m[1]);
        if ((sb_stripos($url, 'http://') === 0 || sb_stripos($url, 'https://') === 0) && sb_stripos($url, SB_COOKIE_DOMAIN) === false)
        {
            // директива импортирует внешний файл, оставляем как есть
            return $m[0];
        }

        if (in_array($url, self::$files))
        {
            return '';
        }

        $mediaList = preg_replace('/\\s+/', '', $m[2]);

        ob_start();
        include_once(SB_BASEDIR.$url);
        $css = ob_get_clean();

        if (trim($css) == '')
        {
            return '';
        }

        $obj = new sbCompressorCSSImport(dirname(SB_BASEDIR.$url), $this->_currentDir);
        $css = $obj->_getContent($css, $url, true);

        return (preg_match('@(?:^$|\\ball\\b)@', $mediaList)) ? $css : "@media {$mediaList} {\n{$css}\n}\n";
    }
}

?>