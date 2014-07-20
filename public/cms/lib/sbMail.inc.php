<?php
/**
* Реализация классов для отправки писем с сайта
*
* Письма могут состоять из нескольких включаемых частей (присоединенных файлов, картинок, альтернативного текста и пр.)
*
* @author Казбек Елекоев <elekoev@binn.ru>
* @version 4.0
* @package SB_Mail
* @copyright Copyright (c) 2007, OOO "СИБИЭС Групп"
*/

/**
 * @ignore
 */
define('SB_MAIL_CRLF', "\n");

/**
* Класс для отправки писем с сайта
*
* Письма могут состоять из нескольких включаемых частей (присоединенных файлов, картинок, альтернативного текста и пр.)
*
* @author Казбек Елекоев <elekoev@binn.ru>
* @version 4.0
* @package SB_Mail
* @copyright Copyright (c) 2007, OOO "СИБИЭС Групп"
*
*/
class sbMail
{
   /**
    * HTML-текст письма
    *
    * @var string
    */
    private $mHtml = '';

   /**
    * Обычный текст письма (используется только для текстовых писем)
    *
    * @var string
    */
    private $mText = '';

   /**
    * Тело письма, после сборки всех частей
    *
    * @var string
    */
    private $mOutput = '';

   /**
    * Альтернативный текст для HTML-текст (используется только для HTML-писем)
    *
    * @var string
    */
    private $mHtmlText = '';

   /**
    * Массив включенных изображений
    *
    * @var array
    */
    private $mHtmlImages = array();

   /**
    * Массив разрешенных для включения типов изображений
    *
    * @see findHtmlImages()
    * @var array
    */
    private $mImageTypes = array();

   /**
    * Параметры письма (кодировка, адрес отправителя и пр.)
    *
    * @var array
    */
    private $mBuildParams = array();

   /**
    * Массив прикрепленных файлов
    *
    * @var array
    */
    private $mAttachments = array();

   /**
    * Массив заголовков письма
    *
    * @var array
    */
    private $mHeaders = array();

   /**
    * Указывает, было письмо полностью собрано или еще нет
    *
    * @var boolean
    */
    private $mIsBuilt = false;

   /**
    * Адрес, куда будет идти возврат
    *
    * Если не указан, то возврат идет на адрес отправителя.
    *
    * @var string
    */
    private $mReturnPath = '';

    /**
     * Кодировка системы управления сайтом
     *
     * @var string
     */
    public $mNativeCharset = 'UTF-8';

	/**
	 * Хост SMTP сервера
	 *
	 * @var string
	 */
	public $mSmtpHost = '';

	/**
	 * Имя пользователя учетной записи smtp сервера
	 *
	 * @var string
	 */
	public $mSmtpUser = '';

	/**
	 * Пароль учетной записи smtp сервера
	 *
	 * @var string
	 */
	public $mSmtpPassword = '';

	/**
	 * Порт
	 *
	 * @var int
	 */
	public $mSmtpPort = 25;

	/**
     * Будет использоваться авторизация или нет? И если да то указывается какой вид авторизации.
     *
     * Может принимать значения (TRUE), (FALSE) или название специального метода авторизации.
     *
     * @var mixed
	*/
	public $mSmtpAuth = true;

	/**
	 * Таймаут ожидания соединения
	 *
	 * @var int
	 */
	public $mSmtpTimeout = null;

	/**
	 * Постоянное соединение или нет
	 *
	 * @var boolean
	 */
	public $mSmtpPersist = false;

	/**
	 * Массив видов авторизации
	 *
	 * @var array()
	 */
	public $mSmtpAuthMethods = array('DIGEST-MD5', 'CRAM-MD5', 'LOGIN', 'PLAIN');

	/**
	 * Конвейерная обработка
	 *
	 * @var boolean
	 */
	public $mSmtpPipelining = false;

	private $mSmtpPipelinedCommands = 0;

	/**
	 * Объект класса sbSoket
	 *
	 * @var resourse
	 */
	private $mSmtpSocket = null;

	/**
	 * Код отклика smtp сервера
	 *
	 * @var int
	 */
	private $mSmtpCode = -1;

	/**
	 * Текст отклика smtp сервера
	 *
	 * @var unknown_type
	 */
	private $mSmtpArguments = array();

	private $mSmtpEsmtp;

   /**
    * Конструктор класса
    *
    * Выставляет заголовки письма по умолчанию.
    */
    public function __construct()
    {
        /**
         * Типы картинок и объектов, разрешенных для включения в письма
         */
        $this->mImageTypes = array(
                                    'gif'	=> 'image/gif',
                                    'jpg'	=> 'image/jpeg',
                                    'jpeg'	=> 'image/jpeg',
                                    'jpe'	=> 'image/jpeg',
                                    'bmp'	=> 'image/bmp',
                                    'png'	=> 'image/png',
                                    'tif'	=> 'image/tiff',
                                    'tiff'	=> 'image/tiff',
                                    'swf'	=> 'application/x-shockwave-flash'
									);

       /**
        * Заголовки по умолчанию
        */
        $this->mBuildParams['html_encoding'] = 'quoted-printable';
        $this->mBuildParams['text_encoding'] = '8bit';

		if (class_exists('sbPlugins'))
        {
			$this->mBuildParams['html_charset'] = sbPlugins::getSetting('sb_letters_charset');
			$this->mBuildParams['text_charset'] = sbPlugins::getSetting('sb_letters_charset');
			$this->mBuildParams['head_charset'] = sbPlugins::getSetting('sb_letters_charset');

	        $site_email = trim(str_replace('{DOMAIN}', SB_COOKIE_DOMAIN, sbPlugins::getSetting('sb_admin_email')));
	        if ($site_email != '')
	        {
	        	$site_name = trim(str_replace('{DOMAIN}', SB_COOKIE_DOMAIN, sbPlugins::getSetting('sb_site_name')));
	        	if ($site_name != '')
	        	{
	        		$from = $site_name.' <'.$site_email.'>';
	        	}
	        	else
	        	{
	        		$from = $site_email;
	        	}

	        	$this->setFrom($from);
	        	$this->setReturnPath($site_email);
	        }

	        $smtpHost = sbPlugins::getSetting('sb_letters_smtp_host');
			if(strpos($smtpHost, ':') !== false)
	        {
				$smtpHost = explode(':', $smtpHost);

                if(count($smtpHost) > 2)
                {
                    $this->mSmtpHost = $smtpHost[0].':'.$smtpHost[1];
                    $this->mSmtpPort = $smtpHost[2];
                }
                else
                {
                    $this->mSmtpHost = $smtpHost[0];
                    $this->mSmtpPort = $smtpHost[1];
                }
			}
			else
			{
				$this->mSmtpHost = $smtpHost;
	        }
			$this->mSmtpUser = sbPlugins::getSetting('sb_letters_smtp_user');
			$this->mSmtpPassword = sbPlugins::getSetting('sb_letters_smtp_password');
        }
        else
        {
        	$this->mBuildParams['html_charset']  = 'UTF-8';
	        $this->mBuildParams['text_charset']  = 'UTF-8';
	        $this->mBuildParams['head_charset']  = 'UTF-8';
        }

        $this->mBuildParams['text_wrap']     = 998;

        /**
        * Заголовок версии MIME должен быть первым.
        */
        $this->mHeaders['MIME-Version'] = '1.0';
        $this->mHeaders['X-Priority']   = '3 (Normal)';
        $this->mHeaders['X-Mailer']     = 'S.Builder';
        $this->mHeaders['Date']         = sb_date("r");

        if (defined('SB_CHARSET'))
        	$this->mNativeCharset = strtoupper(SB_CHARSET);
    }

    /**
     * Защита значений заголовков от инъекций
     *
     * @param string $value Значение заголовка.
     * @return string Значений заголовка, очищенное от инъекций.
     */
    private function preprocessHeaderField($value)
    {
      // Чистим переводы строки
      $ret = str_replace("\r", '', $value);
      $ret = str_replace("\n", '', $ret);

      // Чистим другие заголовки, которые пытаются нам засунуть

      $find = array('/bcc\:/ims',
                    '/content\-type\:/ims',
                    '/mime\-type\:/ims',
                    '/cc\:/ims',
                    '/to\:/ims');

      $ret = preg_replace($find, '', $ret);

      return $ret;
    }

    /**
     * Проверяет массив E-mail-ов на правильность синтаксиса
     *
     * @param array $emails Массив E-mail-ов.
     * @return bool TRUE - если E-mail-ы верные и FALSE в ином случае.
     */
    private function emailCheck($emails)
    {
        $m = array();
        foreach($emails as $email)
        {
            if (preg_match('/<(.+?)>/ui', $email, $m))
            {
                $email = $m[1];
            }
            if (!preg_match('/([0-9a-z][_0-9a-z\.\-]*)@([0-9a-z][_0-9a-z\.\-]+)\.([a-z]{2,4}$)/ui', $email))
                return false;
        }
        return true;
    }

    /**
     * Считывает содержимое файла и возвращает его (используется для включенных изображений и присоединенных файлов).
     *
     * @param string $filename Путь к файлу.
     * @return mixed Текст файла, если удалось прочитать файл, или FALSE в ином случае.
     */
    public function getFile($filename)
    {
        $return = '';
        if ($GLOBALS['sbVfs']->fopen($filename, 'r'))
        {
            while (!$GLOBALS['sbVfs']->feof())
            {
                $return .= $GLOBALS['sbVfs']->fread(1024);
            }
            $GLOBALS['sbVfs']->fclose();

            return $return;

        }
        else
        {
            return false;
        }
    }

   /**
    * Устанавливает кодировку текста письма (7bit, 8bit)
    *
    * @param string $encoding Кодировка.
    */
    public function setTextEncoding($encoding = '8bit')
    {
        $this->mBuildParams['text_encoding'] = $this->preprocessHeaderField($encoding);
    }

   /**
    * Устанавливает кодировку HTML-текста письма (quoted-printable, 7bit, 8bit)
    *
    * @param string $encoding Кодировка.
    */
    public function setHtmlEncoding($encoding = 'quoted-printable')
    {
        $this->mBuildParams['html_encoding'] = $this->preprocessHeaderField($encoding);
    }

   /**
    * Устанавливает кодировку текста письма (WINDOWS-1251, ISO-8859-1 и т.д.)
    *
    * @param string $charset Кодировка.
    */
    public function setTextCharset($charset = '')
    {
        if ($charset == '')
            $charset = (class_exists('sbPlugins') ? sbPlugins::getSetting('sb_letters_charset') : 'UTF-8');

        $this->mBuildParams['text_charset'] = $this->preprocessHeaderField($charset);
    }

   /**
    * Устанавливает кодировку HTML-текста письма (WINDOWS-1251, ISO-8859-1 и т.д.)
    *
    * @param string $charset Кодировка.
    */
    public function setHtmlCharset($charset = '')
    {
        if ($charset == '')
            $charset = (class_exists('sbPlugins') ? sbPlugins::getSetting('sb_letters_charset') : 'UTF-8');

        $this->mBuildParams['html_charset'] = $this->preprocessHeaderField($charset);
    }

   /**
    * Устанавливает кодировку заголовка письма (WINDOWS-1251, ISO-8859-1 и т.д.)
    *
    * @param string $charset Кодировка.
    */
    public function setHeadCharset($charset = '')
    {
        if ($charset == '')
            $charset = (class_exists('sbPlugins') ? sbPlugins::getSetting('sb_letters_charset') : 'UTF-8');

        $this->mBuildParams['head_charset'] = $this->preprocessHeaderField($charset);
    }

   /**
    * Устанавливает кол-во символов в строке для текстовых писем
    *
    * @param int $count Кол-во символов.
    */
    public function setTextWrap($count = 998)
    {
        $this->mBuildParams['text_wrap'] = intval($count);
    }

   /**
    * Устанавливает пользовательские заголовки письма
    *
    * @param string $name Название заголовка.
    * @param string $value Значение заголовка.
    */
    public function setHeader($name, $value)
    {
        $this->mHeaders[$name] = $this->preprocessHeaderField($value);
    }

   /**
    * Устанавливает заголовок письма (Subject)
    *
    * @param string $subject Заголовок письма.
    */
    public function setSubject($subject)
    {
        $subject = trim($this->preprocessHeaderField(strip_tags($subject)));

        if (strtoupper($this->mBuildParams['head_charset']) != $this->mNativeCharset)
            $subject = iconv($this->mNativeCharset, $this->mBuildParams['head_charset'].'//IGNORE', $subject);

        $this->mHeaders['Subject'] = $subject;
    }

   /**
    * Устанавливает E-mail-адрес отправителя (From)
    *
    * @param string $from E-mail-адрес.
    */
    public function setFrom($from)
    {
		$from = $this->preprocessHeaderField($from);

		if ($this->emailCheck(array($from)))
        {
        	$m = array();
        	if (preg_match('/<(.+?)>/ui', $from, $m))
        		$email = $m[1];
        	else
        		$email = $from;

            $this->mHeaders['X-Sender'] = $email;
            $this->mHeaders['From'] = $from;
        }
    }

   /**
    * Устанавливает обратный E-mail-адрес (Return Path)
    *
    * @param string $return_path Обратный E-mail-адрес.
    */
    public function setReturnPath($return_path)
    {
        $return_path = $this->preprocessHeaderField($return_path);

        if ($this->emailCheck(array($return_path)))
        {
        	$m = array();
        	if (preg_match('/<(.+?)>/ui', $return_path, $m))
        		$return_path = $m[1];

            $this->mReturnPath = $return_path;
        }
    }

   /**
    * Устанавливает заголовок Cc
    *
    * Несколько E-mail-адресов передаются через запятую.
    *
    * @param string $cc Один или несколько E-mail-адресов.
    */
    public function setCc($cc)
    {
        $cc = str_replace(';', ',', $this->preprocessHeaderField($cc));

        if ($this->emailCheck(explode(',', $cc)))
            $this->mHeaders['Cc'] = $cc;
    }

   /**
    * Устанавливает заголовок Bcc
    *
    * Несколько E-mail-адресов передаются через запятую.
    *
    * @param string $bcc Один или несколько E-mail-адресов.
    */
    public function setBcc($bcc)
    {
		$bcc = str_replace(';', ',', $this->preprocessHeaderField($bcc));

		if ($this->emailCheck(explode(',', $bcc)))
			$this->mHeaders['Bcc'] = $bcc;
    }

   /**
    * Добавляет обычный текст в письмо
    *
    * Используйте эту функцию, только когда отправляете письма формата plain/text.
    *
    * @param string $text Обычный текст.
    */
    public function setText($text = '')
    {
		$text = strip_tags($text);

		if (strtoupper($this->mBuildParams['text_charset']) != $this->mNativeCharset)
			$text = iconv($this->mNativeCharset, $this->mBuildParams['text_charset'].'//IGNORE', $text);

		$this->mText = $text;
    }

    /**
    * Добавляет HTML-текст в письмо
    *
    * Если параметр $embed_images установлен в TRUE, то все изображения, разрешенные в параметре mImageTypes, будут
    * заменены на ID их контентов.
    *
    * @param string $html HTML-текст.
    * @param string $text Альтернативный обычный текст.
    * @param bool $embed_images Отправлять изображения как включенные файлы или нет.
    */
    public function setHtml($html, $text = '', $embed_images = null)
    {
        if ($embed_images == null)
            $embed_images = (class_exists('sbPlugins') && sbPlugins::getSetting('sb_letters_images') == 1 ? true : false);

        $this->mHtml      = $html;
        $this->mHtmlText  = $text;

        if ($embed_images)
        {
            $this->findHtmlImages();
        }
    }

   /**
    * Вытаскивает изображения из текста сообщения
    *
    * Этот метод анализирует HTML-текст, переданный методу setHtml, и находит все файлы, расширение которых есть в
    * параметре mImageTypes. Если файл существует, то его содержимое включается в тело письма, а ссылка на файл заменяется
    * соотв. идентификатором содержимого.
    */
    private function findHtmlImages()
    {
    	// Разрешенные расширения
        $extensions = array_keys($this->mImageTypes);
		$this->mHtmlImages = array();

        $images = array();
        // Вытаскиваем все изображения
        if (preg_match_all('/(?:"|\')([^"\']+\.('.implode('|', $extensions).'))(?:"|\'|\))/i', $this->mHtml, $images))
        {
            $html_images = array();

            for ($i = 0; $i < count($images[1]); $i++)
            {
                $src = '';
                $pos = stripos($images[1][$i], 'url(');
                if ($pos !== false)
                {
                    // для случая background: url(...);
                    $images[1][$i] = substr($images[1][$i], $pos + 4);
                }

                if (stripos($images[1][$i], SB_COOKIE_DOMAIN) !== false)
                {
                    // Абсолютная ссылка на изображение, делаем ее относительной и проверяем, есть такой файл или нет
                    $src = str_ireplace(array('http://'.SB_COOKIE_DOMAIN, 'http://www.'.SB_COOKIE_DOMAIN), array('', ''), $images[1][$i]);
                    if (!$GLOBALS['sbVfs']->exists($src))
                        $src = '';
                }
                elseif (stripos($images[1][$i], 'http://') === false)
                {
                    // Относительная ссылка, добавляем слэш спереди и проверяем, есть такой файл или нет
                    $src = '/'.trim($images[1][$i], '/');
                    if (!$GLOBALS['sbVfs']->exists($src))
                        $src = '';
                }

                if ($src != '')
                {
                    $name = array_search($src, $html_images);
                    if (!$name)
                    {
                        $name = uniqid(time()).'_'.basename($src);
                        $html_images[$name] = $src;
                    }

                    $this->mHtml = sb_str_replace($images[1][$i], $name, $this->mHtml);
                }
            }

            foreach ($html_images as $name => $src)
            {
                $image = $this->getFile($src);
                if ($image)
                {
                    $ext = substr($src, strrpos($src, '.') + 1);
                    $content_type = $this->mImageTypes[strtolower($ext)];

                    $this->addHtmlImage($image, $name, $content_type);
                }
            }
        }
    }

   /**
    * Добавляет изображение в список включенных изображений
    *
    * @param string $file Текст файла изображения.
    * @param string $name Уникальное имя файла изображения (на него и будет заменена ссылка).
    * @param string $c_type Тип изображения.
    */
    public function addHtmlImage($file, $name = '', $c_type='application/octet-stream')
    {
        $this->mHtmlImages[] = array(
                                        'body'   => $file,
                                        'name'   => $name,
                                        'c_type' => $c_type,
                                        'cid'    => md5(uniqid(time()))
                                    );
    }

   /**
    * Добавляет файл в список присоединенных файлов
    *
    * @param string $file Текст файла.
    * @param string $name Имя файла.
    * @param string $c_type Тип файла.
    * @param string $encoding Кодировка, в которую будет переведен текст файла (base64 и пр.)
    */
    public function addAttachment($file, $name = '', $c_type='application/octet-stream', $encoding = 'base64')
    {
        $this->mAttachments[] = array(
                                    'body'		=> $file,
                                    'name'		=> $name,
                                    'c_type'	=> $c_type,
                                    'encoding'	=> $encoding
                                  );
    }

   /**
    * Очищает список присоединенных файлов
    */
    public function clearAttachments()
    {
        $this->mAttachments = array();
    }

   /**
    * Добавляет текстовый контент в MIME-части письма
    *
    * @param sbMailPart $obj Ссылка на объект типа sbMailPart.
    * @param string $text Текстовый контент.
    *
    * @return sbMailPart Объект добавленной MIME-части.
    */
    private function &addTextPart(&$obj, $text)
    {
        $params['content_type'] = 'text/plain';
        $params['encoding']     = $this->mBuildParams['text_encoding'];
        $params['charset']      = $this->mBuildParams['text_charset'];

        if (strtoupper($params['charset']) != $this->mNativeCharset)
        {
            $text = iconv($this->mNativeCharset, $params['charset'].'//IGNORE', $text);
        }

        if (is_object($obj))
        {
            $return = $obj->addSubpart($text, $params);
        }
        else
        {
            $return = new sbMailPart($text, $params);
        }

        return $return;
    }

   /**
    * Добавляет HTML контент в MIME-части письма
    *
    * @param sbMailPart $obj Ссылка на объект типа sbMailPart.
    *
    * @return sbMailPart Объект добавленной MIME-части.
    */
    private function &addHtmlPart(&$obj)
    {
        $params['content_type'] = 'text/html';
        $params['encoding']     = $this->mBuildParams['html_encoding'];
        $params['charset']      = $this->mBuildParams['html_charset'];

    	if (strtoupper($params['charset']) != $this->mNativeCharset)
        {
            $html = iconv($this->mNativeCharset, $params['charset'].'//IGNORE', $this->mHtml);
        }
        else
        {
        	$html = $this->mHtml;
        }

        if (is_object($obj))
        {
            $return = $obj->addSubpart($html, $params);
        }
        else
        {
            $return = new sbMailPart($html, $params);
        }

        return $return;
    }

   /**
    * Добавляет смешанный контент в MIME-части письма
    *
    * @return sbMailPart Объект добавленной MIME-части.
    */
    private function &addMixedPart()
    {
        $params['content_type'] = 'multipart/mixed';
        $return = new sbMailPart('', $params);

        return $return;
    }

   /**
    * Добавляет альтернативный контент в MIME-части письма
    *
    * @param sbMailPart $obj Ссылка на объект типа sbMailPart.
    *
    * @return sbMailPart Объект добавленной MIME-части.
    */
    private function &addAlternativePart(&$obj)
    {
        $params['content_type'] = 'multipart/alternative';
        if (is_object($obj))
        {
            $return = $obj->addSubpart('', $params);
        }
        else
        {
            $return = new sbMailPart('', $params);
        }

        return $return;
    }

   /**
    * Добавляет HTML подконтент в MIME-части письма
    *
    * @param sbMailPart $obj Ссылка на объект типа sbMailPart.
    *
    * @return sbMailPart Объект добавленной MIME-части.
    */
    private function &addRelatedPart(&$obj)
    {
        $params['content_type'] = 'multipart/related';
        if (is_object($obj))
        {
            $return = $obj->addSubpart('', $params);
        }
        else
        {
            $return = new sbMailPart('', $params);
        }

        return $return;
    }

   /**
    * Добавляет контент внедренного изображения в MIME-части письма
    *
    * @param sbMailPart $obj Ссылка на объект типа sbMailPart.
    * @param array $value Массив, содержащий параметры внедряемого изображения.
    */
    private function addHtmlImagePart(&$obj, $value)
    {
        $params['content_type'] = $value['c_type'];
        $params['encoding']     = 'base64';
        $params['disposition']  = 'inline';
        $params['dfilename']    = $value['name'];
        $params['cid']          = $value['cid'];
        $obj->addSubpart($value['body'], $params);
    }

   /**
    * Добавляет контент присоединенного файла в MIME-части письма
    *
    * @param sbMailPart $obj Ссылка на объект типа sbMailPart.
    * @param array $value Массив, содержащий параметры присоединенного файла.
    */
    private function addAttachmentPart(&$obj, $value)
    {
        $params['content_type'] = $value['c_type'];
        $params['encoding']     = $value['encoding'];
        $params['disposition']  = 'attachment';
        $params['dfilename']    = $value['name'];
        $obj->addSubpart($value['body'], $params);
    }

   /**
    * Строит тело письма, учитывая различные параметры
    *
    * Записывает тело письма в переменную mOutput и заголовки письма в переменную mHeaders.
    *
    * @param array $params Параметры письма (html_encoding, text_encoding и пр.).
    *
    * @return bool TRUE, если не было ошибок и FALSE в ином случае.
    */
    private function buildMessage($params = array())
    {
        if (!empty($params))
        {
            while ((list($key, $value) = each($params)) != false)
            {
                $this->mBuildParams[$key] = $value;
            }
        }

        if (!empty($this->mHtmlImages))
        {
            foreach ($this->mHtmlImages as $value)
            {
                $this->mHtml = sb_str_replace($value['name'], 'cid:'.$value['cid'], $this->mHtml);
            }
        }

        $null        = null;
        $attachments = !empty($this->mAttachments) ? true : false;
        $html_images = !empty($this->mHtmlImages)  ? true : false;
        $html        = !empty($this->mHtml)        ? true : false;
        $text        = !empty($this->mText)        ? true : false;

        $message = '';
        switch (true)
        {
            case $text && !$attachments:
                $message = &$this->addTextPart($null, $this->mText);
                break;

            case !$text && $attachments && !$html:
                $message = &$this->addMixedPart();

                for ($i = 0; $i < count($this->mAttachments); $i++)
                {
                    $this->addAttachmentPart($message, $this->mAttachments[$i]);
                }
                break;

            case $text && $attachments:
                $message = &$this->addMixedPart();
                $this->addTextPart($message, $this->mText);

                for ($i = 0; $i < count($this->mAttachments); $i++)
                {
                    $this->addAttachmentPart($message, $this->mAttachments[$i]);
                }
                break;

            case $html && !$attachments && !$html_images:
                if (!empty($this->mHtmlText))
                {
                    $message = &$this->addAlternativePart($null);
                    $this->addTextPart($message, $this->mHtmlText);
                    $this->addHtmlPart($message);
                }
                else
                {
                    $message = &$this->addHtmlPart($null);
                }
                break;

            case $html && !$attachments && $html_images:
                if (!empty($this->mHtmlText))
                {
                    $message = &$this->addAlternativePart($null);
                    $this->addTextPart($message, $this->mHtmlText);
                    $related = &$this->addRelatedPart($message);
                }
                else
                {
                    $message = &$this->addRelatedPart($null);
                    $related = &$message;
                }
                $this->addHtmlPart($related);
                for ($i = 0; $i < count($this->mHtmlImages); $i++)
                {
                    $this->addHtmlImagePart($related, $this->mHtmlImages[$i]);
                }
                break;

            case $html && $attachments && !$html_images:
                $message = &$this->addMixedPart($null);
                if (!empty($this->mHtmlText))
                {
                    $alt = &$this->addAlternativePart($message);
                    $this->addTextPart($alt, $this->mHtmlText);
                    $this->addHtmlPart($alt);
                }
                else
                {
                    $this->addHtmlPart($message);
                }

                for ($i = 0; $i < count($this->mAttachments); $i++)
                {
                    $this->addAttachmentPart($message, $this->mAttachments[$i]);
                }
                break;

            case $html && $attachments && $html_images:
                $message = &$this->addMixedPart();
                if (!empty($this->mHtmlText))
                {
                    $alt = &$this->addAlternativePart($message);
                    $this->addTextPart($alt, $this->mHtmlText);
                    $rel = &$this->addRelatedPart($alt);
                }
                else
                {
                    $rel = &$this->addRelatedPart($message);
                }
                $this->addHtmlPart($rel);
                for ($i = 0; $i < count($this->mHtmlImages); $i++)
                {
                    $this->addHtmlImagePart($rel, $this->mHtmlImages[$i]);
                }

                for ($i = 0; $i < count($this->mAttachments); $i++)
                {
                    $this->addAttachmentPart($message, $this->mAttachments[$i]);
                }
                break;

        }

        if (!empty($message))
        {
            $output = $message->encode();

            $this->mOutput   = $output['body'];
            $this->mHeaders  = array_merge($this->mHeaders, $output['headers']);

            // Добавляем идентификатор письма
            srand((double)microtime()*10000000);
            $message_id = sprintf('<%s.%s@%s>', base_convert(time(), 10, 36), base_convert(rand(), 10, 36), SB_COOKIE_DOMAIN);
            $this->mHeaders['Message-ID'] = $message_id;

            $this->mIsBuilt = true;
            return true;
        }
        else
        {
            return false;
        }
    }

   /**
    * Кодирует заголовки в соотв. со стандартом RFC2047
    *
    * @param string $input Заголовок для кодирования.
    * @param string $charset Кодировка.
    *
    * @return string Кодированный заголовок.
    */
    private function encodeHeader($input, $charset = '')
    {
        if ($charset == '')
            $charset = (class_exists('sbPlugins') ? sbPlugins::getSetting('sb_letters_charset') : 'UTF-8');

        $matches = array();

        if (preg_match_all('/(\s?\w*[\x80-\xFF]+\w*\s?)/', $input, $matches))
        {
            if (!function_exists('sb_mail_str_sort'))
            {
                function sb_mail_str_sort($a, $b)
                {
                    if (sb_strlen($a) < sb_strlen($b)) { return 1; } elseif (sb_strlen($a) == sb_strlen($b)) { return 0; } else { return -1; }
                }
            }

            usort($matches[1], 'sb_mail_str_sort');

            foreach ($matches[1] as $value)
            {
                $replacement = preg_replace('/([\x20\x80-\xFF])/e', '"=" . strtoupper(dechex(ord("\1")))', $value);
                $input = sb_str_replace($value, '=?' . $charset . '?Q?' . $replacement . '?=', $input, $charset);
            }
        }

        return $input;
    }

   /**
    * Отправляет письмо
    *
    * @param  array $recipients Массив получателей.
    * @param  bool $checkBuild Если true - письмо собирается один раз, если false - письмо собирается перед каждой отправкой.
	*
    * @return bool TRUE, если письмо отправлено и FALSE в ином случае.
    */
    public function send($recipients, $checkBuild = true)
    {
		if (!is_array($recipients))
			return false;

		$tmp = array();
		foreach ($recipients as $value)
		{
    		$value = trim($value);
    		if ($value != '')
    		{
    			$tmp[] = $value;
    		}
    	}

    	if (count($tmp) <= 0)
    		return false;

		$recipients = $tmp;

        if (!$this->emailCheck($recipients))
            return false;

		if($checkBuild)
		{
			if (!$this->mIsBuilt)
			{
				$this->buildMessage();
			}
		}
		else
		{
			$this->buildMessage();
		}

        $subject = '';
        $tmp_subject = '';
        if (isset($this->mHeaders['Subject']) && !empty($this->mHeaders['Subject']))
        {
            $tmp_subject = $this->mHeaders['Subject'];
            $subject = $this->encodeHeader($this->mHeaders['Subject'], $this->mBuildParams['head_charset']);
            unset($this->mHeaders['Subject']);
        }

        // Формируем текст заголовка письма
        $headers = array();
        foreach ($this->mHeaders as $name => $value)
        {
            $headers[] = $name . ': ' . $this->encodeHeader($value, $this->mBuildParams['head_charset']);
        }

        $to = $this->encodeHeader(implode(', ', $recipients), $this->mBuildParams['head_charset']);
        $to = $this->preprocessHeaderField($to);

        if (class_exists('sbPlugins') && !is_null(sbPlugins::getSetting('sb_submit_timeout')))
			usleep(intval(sbPlugins::getSetting('sb_submit_timeout')));

		$sendmail = ini_get('sendmail_path');
		if (trim($this->mSmtpHost) != '' && trim($this->mSmtpUser) != '' && trim($this->mSmtpPassword) != '')
		{
			$hdrs = array();
			foreach($headers as $key => $value)
			{
				$value = explode(':', $value);
				$hdrs[$value[0]] = $value[1];
			}

			$hdrs['Subject'] = $subject;
			$hdrs['Return-Path'] = $this->mReturnPath != '' ? $this->mReturnPath : SB_COOKIE_DOMAIN;
			$hdrs['To'] = $to;

			$result = $this->smtpSend($to, $hdrs, $this->mOutput);
		}
		elseif (!empty($this->mReturnPath) && substr_count($sendmail, '-f') <= 0 && strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN')
        {
			$result = @mail($to, $subject, $this->mOutput, implode(SB_MAIL_CRLF, $headers), '-f' . $this->mReturnPath);
        }
		else
        {
			$result = @mail($to, $subject, $this->mOutput, implode(SB_MAIL_CRLF, $headers));
		}

		$this->mHeaders['Subject'] = $tmp_subject;
		return $result;
	}

	public function smtpPrepareHeaders($headers)
	{
		$lines = array();
		$from = null;

		foreach ($headers as $key => $value)
		{
			if (strcasecmp($key, 'From') === 0)
			{
				$lines[] = $key.': '.$value;
			}
			elseif (strcasecmp($key, 'Received') === 0)
			{
                $received = array();
                if (is_array($value))
                {
					foreach($value as $line)
                    {
                        $received[] = $key.': '.$line;
                    }
                }
                else
                {
					$received[] = $key.': '.$value;
                }

                //	Put Received: headers at the top.  Spam detectors often
                //	flag messages with Received: headers after the Subject:
                //	as spam.
				$lines = array_merge($received, $lines);
            }
            else
            {
                //	If $value is an array (i.e., a list of addresses), convert
                //	it to a comma-delimited string of its elements (addresses).
                if (is_array($value))
                {
                    $value = implode(', ', $value);
                }
                $lines[] = $key.': '.$value;
            }
        }

		return array($from, join("\r\n", $lines));
    }

	public function smtpSend($recipients, $headers, $body)
    {
		if(!is_object($this->mSmtpSocket) || !is_resource($this->mSmtpSocket->mFp))
		{
			require_once(SB_CMS_LIB_PATH.'/sbDownload.inc.php');
			$this->mSmtpSocket = new sbSocket($this->mSmtpHost);

			$res = $this->smtpConnect($this->mSmtpTimeout);
			if($this->mSmtpAuth)
			{
				$method = is_string($this->mSmtpAuth) ? $this->mSmtpAuth : '';
				$res = $this->smtpAuth($this->mSmtpUser, $this->mSmtpPassword, $method);
				if(!$res)
				{
					sb_add_system_message(SB_MAIL_SMTP_AUTH_ERR, SB_MSG_WARNING);
					return false;
				}
			}
		}

		if (!is_array($headers))
        {
			return false;
		}

		$headerElements = $this->smtpPrepareHeaders($headers);
		list($from, $textHeaders) = $headerElements;

        if (isset($headers['Return-Path']) && !empty($headers['Return-Path']))
        {
			$from = $headers['Return-Path'];
        }

		if (!isset($from))
		{
			$this->smtpRset();
			return false;
		}

		$res = $this->smtpMailFrom($from);
		$recipients = explode(',', $recipients);

		foreach($recipients as $recipient)
		{
			$this->smtpRcptTo($recipient);
		}

		$res = $this->smtpData($textHeaders."\r\n\r\n".$body);

		if (!$res)
        {
			$this->smtpRset();
			return false;
        }

		if ($this->mSmtpPersist === false)
		{
			$this->smtpDisconnect();
		}

		return true;
	}

    private function smtpPut($command, $args = '')
    {
    	if (!empty($args))
        {
			$command .= ' '.$args;
        }

		if (strcspn($command, "\r\n") !== strlen($command))
		{
			return false;
		}

		$u = $this->smtpWrite($command."\r\n");
		return $u;
	}

	/**
	 * Записывает в открытый сокет данные
	 *
	 * @param string $data
	 * @param unknown_type $blocksize
	 * @return int кол-во записанных байт
	 */
    public function smtpWrite($data, $blocksize = null)
    {
		if (!is_resource($this->mSmtpSocket->mFp))
        {
			return false;
        }

		if (is_null($blocksize) && strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN')
        {
			return @fwrite($this->mSmtpSocket->mFp, $data);
        }
		else
        {
			if (is_null($blocksize))
			{
				$blocksize = 1024;
            }

			$pos = 0;
			$size = strlen($data);

            while ($pos < $size)
            {
				$written = @fwrite($this->mSmtpSocket->mFp, substr($data, $pos, $blocksize));
				if ($written === false)
				{
					return false;
				}
				$pos += $written;
            }
			return $pos;
        }
	}

	/**
	 * Читает строку из открытого сокета.
	 *
	 * @return string
	 */
    public function smtpReadLine()
    {
		if (!is_resource($this->mSmtpSocket->mFp))
			return false;

		$line = '';
		$timeout = time() + $this->mSmtpTimeout;

        while (!feof($this->mSmtpSocket->mFp) && (!$this->mSmtpTimeout || time() < $timeout))
        {
			$line .= @fgets($this->mSmtpSocket->mFp, 2048);
			if (substr($line, -1) == "\n")
			{
				return rtrim($line, "\r\n");
			}
        }
		return $line;
    }

	public function smtpEnableCrypto($enabled, $type)
	{
		if (version_compare(phpversion(), '5.1.0', '>='))
        {
			if (!is_resource($this->mSmtpSocket->mFp))
				return false;

			return @stream_socket_enable_crypto($this->mSmtpSocket->mFp, $enabled, $type);
		}
		else
		{
			return false;
		}
	}

    private function smtpParseResponse($valid, $later = false)
    {
		$this->mSmtpCode = -1;
		$this->mSmtpArguments = array();

		if ($later)
		{
			$this->mSmtpPipelinedCommands++;
			return true;
		}

		for ($i = 0; $i <= $this->mSmtpPipelinedCommands; $i++)
        {
			while (($line = $this->smtpReadLine()) != false)
			{
                if (empty($line))
                {
					$this->smtpDisconnect();
					return false;
                }

                $code = substr($line, 0, 3);
                $this->mSmtpArguments[] = trim(substr($line, 4));

                if (is_numeric($code))
                {
                    $this->mSmtpCode = (int)$code;
                }
				else
                {
					$this->mSmtpCode = -1;
					break;
                }

				if (substr($line, 3, 1) != '-')
				{
					break;
				}
			}
		}

		$this->mSmtpPipelinedCommands = 0;
		if (is_int($valid) && ($this->mSmtpCode === $valid))
		{
			return true;
        }
        elseif (is_array($valid) && in_array($this->mSmtpCode, $valid, true))
        {
			return true;
		}
    }

    public function smtpConnect($timeout = null, $persistent = false)
    {
		if(!$this->mSmtpSocket->connect($this->mSmtpHost, $this->mSmtpPort, $persistent, $timeout))
			return false;

		if(!$this->smtpParseResponse(220))
			return false;

		if(!$this->smtpNegotiate())
			return false;

		return true;
	}

    public function smtpDisconnect()
    {
		if (!$this->smtpPut('QUIT'))
			return false;

		if (!$this->smtpParseResponse(221))
			return false;

		if (!$this->mSmtpSocket->disconnect())
			return false;

		return true;
	}

    private function smtpNegotiate()
	{
		if(!$this->smtpPut('EHLO', SB_COOKIE_DOMAIN))
		{
			sb_add_system_message(SB_MAIL_SMTP_CONNECT_ERR, SB_MSG_WARNING);
			return false;
		}
		if(!$this->smtpParseResponse(250))
			return false;

		foreach ($this->mSmtpArguments as $argument)
		{
			$verb = strtok($argument, ' ');
			$arguments = substr($argument, strlen($verb) + 1, strlen($argument) - strlen($verb) - 1);
			$this->mSmtpEsmtp[$verb] = $arguments;
        }

		if (!isset($this->mSmtpEsmtp['PIPELINING']))
		{
			$this->mSmtpPipelining = false;
		}
		return true;
    }

	private function smtpGetBestAuthMethod()
	{
		$available_methods = explode(' ', $this->mSmtpEsmtp['AUTH']);
		foreach ($this->mSmtpAuthMethods as $method)
		{
			if (in_array($method, $available_methods))
			{
				return $method;
			}
		}
	}

	public function smtpAuth($uid, $pwd , $method = '', $tls = true)
	{
		if ($tls && version_compare(PHP_VERSION, '5.1.0', '>=') &&
			extension_loaded('openssl') && isset($this->mSmtpEsmtp['STARTTLS']) && strncasecmp($this->mSmtpHost, 'ssl://', 6) !== 0)
		{
			if(!$this->smtpPut('STARTTLS'))
				return false;
			if(!$this->smtpParseResponse(220))
				return false;
			if(!$this->smtpEnableCrypto(true, STREAM_CRYPTO_METHOD_TLS_CLIENT))
				return false;
			if (!$this->smtpNegotiate())
				return false;
        }

		if (empty($this->mSmtpEsmtp['AUTH']))
			return false;

        if (empty($method))
        {
			$method = $this->smtpGetBestAuthMethod();
		}
		else
		{
			$method = strtoupper($method);
			if (!in_array($method, $this->mSmtpAuthMethods))
            {
				return false;
			}
		}

		switch ($method)
		{
			case 'DIGEST-MD5':
				$res = $this->smtpAuthDigestMD5($uid, $pwd);
				break;

			case 'CRAM-MD5':
				$res = $this->smtpAuthCramMD5($uid, $pwd);
				break;

			case 'LOGIN':
				$res = $this->smtpAuthLogin($uid, $pwd);
				break;

			case 'PLAIN':
				$res = $this->smtpAuthPlain($uid, $pwd);
				break;

			default:
				return false;
				break;
		}

		if(!$res)
			return false;

		return true;
    }

    private function smtpAuthDigestMD5($uid, $pwd)
    {
		if(!$this->smtpPut('AUTH', 'DIGEST-MD5'))
			return false;

		if(!$this->smtpParseResponse(334))
			return false;

		$challenge = base64_decode($this->mSmtpArguments[0]);
        $auth_str = base64_encode($this->DigestMD5GetResponse($uid, $pwd, $challenge, $this->mSmtpHost, "smtp"));

		if(!$this->smtpPut($auth_str))
			return false;
		if(!$this->smtpParseResponse(334))
			return false;
		if(!$this->smtpPut(''))
			return false;
		if(!$this->smtpParseResponse(235))
			return false;

		return true;
    }

    private function smtpAuthCramMD5($uid, $pwd)
    {
		if(!$this->smtpPut('AUTH', 'CRAM-MD5'))
			return false;
		if(!$this->smtpParseResponse(334))
			return false;

		$challenge = base64_decode($this->mSmtpArguments[0]);
		$auth_str = base64_encode($this->CramMD5GetResponse($uid, $pwd, $challenge));

		if(!$this->smtpPut($auth_str))
			return false;
		if(!$this->smtpParseResponse(235))
			return false;

		return true;
	}

	private function smtpAuthLogin($uid, $pwd)
	{
		if(!$this->smtpPut('AUTH', 'LOGIN'))
			return false;
		if(!$this->smtpParseResponse(334))
			return false;
		if(!$this->smtpPut(base64_encode($uid)))
			return false;
		if(!$this->smtpParseResponse(334))
			return false;
		if(!$this->smtpPut(base64_encode($pwd)))
			return false;
		if(!$this->smtpParseResponse(235))
			return false;

		return true;
	}

	private function smtpAuthPlain($uid, $pwd)
    {
    	if(!$this->smtpPut('AUTH', 'PLAIN'))
    		return false;
    	if(!$this->smtpParseResponse(334))
    	    return false;

		$auth_str = base64_encode(chr(0).$uid.chr(0).$pwd);
        if(!$this->smtpPut($auth_str))
			return false;

		return true;
    }

    public function smtpMailFrom($sender, $params = null)
    {
		$args = "FROM:<$sender>";
		if (is_string($params))
		{
			$args .= ' '.$params;
		}

		if($this->smtpPut('MAIL', $args))
			return false;

		if($this->smtpParseResponse(250, $this->mSmtpPipelining))
			return false;

		return true;
	}

	public function smtpRcptTo($recipient, $params = null)
    {
		$args = "TO:<$recipient>";
		if (is_string($params))
        {
			$args .= ' '.$params;
		}

		$args = sb_str_replace(' ', '',$args);
		if(!$this->smtpPut('RCPT', $args))
			return false;

		if(!$this->smtpParseResponse(array(250, 251), $this->mSmtpPipelining))
			return false;

		return true;
    }

    public function smtpQuotedata(&$data)
    {
		$data = preg_replace(array('/(?<!\r)\n/', '/\r(?!\n)/'), "\r\n", $data);
		$data = str_replace("\n.", "\n..", $data);
    }

	public function smtpData($data, $headers = null)
	{
		if (!is_string($data) && !is_resource($data))
        {
			return false;
		}

		if (isset($this->mSmtpEsmtp['SIZE']) && ($this->mSmtpEsmtp['SIZE'] > 0))
        {
            $size = (is_null($headers)) ? 0 : strlen($headers) + 4;

            if (is_resource($data))
            {
                $stat = fstat($data);
                if ($stat === false)
                {
					sb_add_system_message(SB_MAIL_SMTP_GET_FILE_ERR, SB_MSG_WARNING);
					return false;
                }
                $size += $stat['size'];
            }
            else
            {
				$size += strlen($data);
            }

            if ($size >= $this->mSmtpEsmtp['SIZE'])
            {
				sb_add_system_message(SB_MAIL_SMTP_MESSAGES_SIZE, SB_MSG_WARNING);
				$this->smtpDisconnect();
				return false;
			}
		}

		if(!$this->smtpParseResponse(250))
			return false;

		if (!$this->smtpPut('DATA'))
			return false;

		if(!$this->smtpParseResponse(354))
			return false;

        if (!is_null($headers))
        {
			$this->smtpQuotedata($headers);
			$result = $this->smtpWrite($headers."\r\n\r\n");
        }
        if (is_resource($data))
        {
            while (($line = fgets($data, 1024)) != false)
            {
				$this->smtpQuotedata($line);
				if(!$this->smtpWrite($line))
				{
					return false;
				}
			}

			if (!$this->smtpWrite("\r\n.\r\n"))
				return false;
		}
		else
        {
			$this->smtpQuotedata($data);
			if (!$this->smtpWrite($data."\r\n.\r\n"))
				return false;
		}

		if(!$this->smtpParseResponse(250, $this->mSmtpPipelining))
			return false;

		return true;
	}

	public function smtpRset()
    {
		if(!$this->smtpPut('RSET'))
			return false;

		if(!$this->smtpParseResponse(250, $this->mSmtpPipelining))
			return false;

		return true;
	}

    /**
    * Provides the (main) client response for DIGEST-MD5
    * requires a few extra parameters than the other
    * mechanisms, which are unavoidable.
    *
    * @param  string $authcid   Authentication id (username)
    * @param  string $pass      Password
    * @param  string $challenge The digest challenge sent by the server
    * @param  string $hostname  The hostname of the machine you're connecting to
    * @param  string $service   The servicename (eg. imap, pop, acap etc)
    * @param  string $authzid   Authorization id (username to proxy as)
    * @return string            The digest response (NOT base64 encoded)
    *
    */
	public function DigestMD5GetResponse($authcid, $pass, $challenge, $hostname, $service, $authzid = '')
	{
		$tokens = array();
		while (preg_match('/^([a-z-]+)=("[^"]+(?<!\\\)"|[^,]+)/i', $challenge, $matches))
        {
            //	Ignore these as per rfc2831
            if ($matches[1] == 'opaque' || $matches[1] == 'domain')
            {
				$challenge = substr($challenge, strlen($matches[0]) + 1);
				continue;
            }

            // Allowed multiple "realm" and "auth-param"
            if (!empty($tokens[$matches[1]]) && ($matches[1] == 'realm' || $matches[1] == 'auth-param'))
            {
                if (is_array($tokens[$matches[1]]))
                {
					$tokens[$matches[1]][] = preg_replace('/^"(.*)"$/', '\\1', $matches[2]);
				}
				else
                {
                    $tokens[$matches[1]] = array($tokens[$matches[1]], preg_replace('/^"(.*)"$/', '\\1', $matches[2]));
                }
			// Any other multiple instance = failure
            }
            elseif (!empty($tokens[$matches[1]]))
            {
                $tokens = array();
                break;
            }
            else
            {
				$tokens[$matches[1]] = preg_replace('/^"(.*)"$/', '\\1', $matches[2]);
            }

            //	Remove the just parsed directive from the challenge
			$challenge = substr($challenge, strlen($matches[0]) + 1);
		}

        if (empty($tokens['realm']))
            $tokens['realm'] = '';

        // Maxbuf
        if (empty($tokens['maxbuf']))
            $tokens['maxbuf'] = 65536;

        // Required: nonce, algorithm
        if (empty($tokens['nonce']) || empty($tokens['algorithm']))
            $tokens = array();

		$challenge = $tokens;

		$authzid_string = '';
		if ($authzid != '')
		{
			$authzid_string = ',authzid="' . $authzid . '"';
        }

        if (!empty($challenge))
        {
			$cnonce = '';
			for ($i = 0; $i < 32; $i++)
			{
				$cnonce .= chr(mt_rand(0, 255));
			}
            $cnonce = base64_encode($cnonce);

            $digest_uri = sprintf('%s/%s', $service, $hostname);
			if ($authzid == '')
	        {
	            $A1 = sprintf('%s:%s:%s', pack('H32', md5(sprintf('%s:%s:%s', $authcid, $challenge['realm'], $pass))), $challenge['nonce'], $cnonce);
	        }
			else
	        {
				$A1 = sprintf('%s:%s:%s:%s', pack('H32', md5(sprintf('%s:%s:%s', $authcid, $challenge['realm'], $pass))), $challenge['nonce'], $cnonce, $authzid);
	        }
			$A2 = 'AUTHENTICATE:'.$digest_uri;

			$response_value = md5(sprintf('%s:%s:00000001:%s:auth:%s', md5($A1), $challenge['nonce'], $cnonce, md5($A2)));

            if ($challenge['realm'])
            {
                return sprintf('username="%s",realm="%s"'.$authzid_string.',nonce="%s",cnonce="%s",nc=00000001,qop=auth,digest-uri="%s",response=%s,maxbuf=%d', $authcid, $challenge['realm'], $challenge['nonce'], $cnonce, $digest_uri, $response_value, $challenge['maxbuf']);
            }
            else
            {
                return sprintf('username="%s"'.$authzid_string.',nonce="%s",cnonce="%s",nc=00000001,qop=auth,digest-uri="%s",response=%s,maxbuf=%d', $authcid, $challenge['nonce'], $cnonce, $digest_uri, $response_value, $challenge['maxbuf']);
            }
        }
        else
        {
			return false;
        }
    }


    public function CramMD5GetResponse($user, $pass, $challenge)
    {
		if (strlen($pass) > 64)
		{
			$pass = pack('H32', md5($pass));
		}

		if (strlen($pass) < 64)
		{
			$pass = str_pad($pass, 64, chr(0));
		}

		$k_ipad = substr($pass, 0, 64) ^ str_repeat(chr(0x36), 64);
		$k_opad = substr($pass, 0, 64) ^ str_repeat(chr(0x5C), 64);

		$inner = pack('H32', md5($k_ipad.$challenge));
		$digest = md5($k_opad.$inner);

		return $user.' '.$digest;
	}

	public function __destruct()
	{
		if($this->mSmtpHost != '' && $this->mSmtpUser != '' && is_object($this->mSmtpSocket))
		{
			$this->smtpDisconnect();
		}
	}
}

/**
*
* Класс для кодирования включаемых в письма частей (присоединенных файлов, картинок, альтернативного текста и пр.)
*
* Этот класс позволяет собирать MIME-письма из различных частей и используется классом sbMail.
*
* @author Казбек Елекоев <elekoev@binn.ru>
* @version 4.0
* @package SB_Mail
* @copyright Copyright (c) 2007, OOO "СИБИЭС Групп"
*/

class sbMailPart
{
   /**
    * Кодировка текущей включаемой части
    * @var string
    */
    private $mEncoding;

   /**
    * Массив подчастей текущей включаемой части
    * @var array
    */
    private $mSubparts;

   /**
    * Кодированная часть (то, что вставляется в письмо)
    * @var string
    */
    private $mEncoded;

   /**
    * Заголовки текущей включаемой части
    * @var array
    */
    private $mHeaders;

   /**
    * Тело текущей включаемой части
    * @var string
    */
    private $mBody;

    /**
     * Кодировка включаемой части
     *
     * @var string
     */
    private $mCharset = 'UTF-8';
    /**
     * Конструктор класса
     *
     * @param string $body  Тело включаемой части, если есть.
     * @param array $params Ассоциативный массив параметров:<pre>
     *                  content_type - Тип контента части (например, multipart/mixed)
     *                  encoding     - Кодировка для использования (7bit, 8bit, base64, или quoted-printable)
     *                  cid          - ID части
     *                  disposition  - Расположение контента части (inline или attachment)
     *                  dfilename    - Необязательное имя файла для attachment
     *                  description  - Описание контента
     *                  charset      - Кодировка (windows-1251 и т.д.)</pre>
     */
    public function __construct($body = '', $params = array())
    {
        if (!defined('SB_MAIL_PART_CRLF'))
        {
            define('SB_MAIL_PART_CRLF', defined('SB_MAIL_CRLF') ? SB_MAIL_CRLF : "\r\n");
        }

        if (defined(SB_CHARSET))
        	$this->mCharset = strtoupper(SB_CHARSET);

        $headers = array();

        foreach ($params as $key => $value)
        {
            switch ($key)
            {
                case 'content_type':
                    $headers['Content-Type'] = $value . (isset($charset) ? '; charset="' . $charset . '"' : '');
                    break;

                case 'encoding':
                    $this->mEncoding = $value;
                    $headers['Content-Transfer-Encoding'] = $value;
                    break;

                case 'cid':
                    $headers['Content-ID'] = '<' . $value . '>';
                    break;

                case 'disposition':
                    $headers['Content-Disposition'] = $value . (isset($dfilename) ? '; filename="' . $dfilename . '"' : '');
                    break;

                case 'dfilename':
                    if (isset($headers['Content-Disposition']))
                    {
                        $headers['Content-Disposition'] .= '; filename="' . $value . '"';
                    }
                    else
                    {
                        $dfilename = $value;
                    }
                    break;

                case 'description':
                    $headers['Content-Description'] = $value;
                    break;

                case 'charset':
                    if (isset($headers['Content-Type']))
                    {
                        $headers['Content-Type'] .= '; charset="' . $value . '"';
                    }
                    else
                    {
                        $charset = $value;
                    }
                    $this->mCharset = $value;
                    break;
            }
        }

        // Тип контента по умолчанию
        if (!isset($headers['Content-Type']))
        {
            $headers['Content-Type'] = 'text/plain';
        }

        // Кодировка по умолчанию
        if (!isset($this->mEncoding))
        {
            $this->mEncoding = '8bit';
        }

        $this->mEncoded  = array();
        $this->mHeaders  = $headers;
        $this->mBody     = $body;
    }

    /**
     * Кодирует и возвращает текст для вставки в письмо
     *
     * Сохраняет текст в переменной класса mEncoded.
     *
     * @return array Ассоциативный массив, содержащий два элемента - body и headers. Элемент headers является также массивом.
     */
    public function encode()
    {
        $encoded =& $this->mEncoded;

        if (!empty($this->mSubparts))
        {
            srand((double)microtime()*1000000);

            $boundary = '=_' . md5(uniqid(rand()) . microtime());

            $this->mHeaders['Content-Type'] .= ';' . SB_MAIL_PART_CRLF . "\t" . 'boundary="' . $boundary . '"';

            // Добавляем к телу части подчасти
            for ($i = 0; $i < count($this->mSubparts); $i++)
            {
                $headers = array();
                $tmp = $this->mSubparts[$i]->encode();
                foreach ($tmp['headers'] as $key => $value)
                {
                    $headers[] = $key . ': ' . $value;
                }
                $subparts[] = implode(SB_MAIL_PART_CRLF, $headers) . SB_MAIL_PART_CRLF . SB_MAIL_PART_CRLF . $tmp['body'];
            }

            $encoded['body'] = '--' . $boundary . SB_MAIL_PART_CRLF .
                               implode('--' . $boundary . SB_MAIL_PART_CRLF, $subparts) .
                               '--' . $boundary.'--' . SB_MAIL_PART_CRLF;
        }
        else
        {
            $encoded['body'] = $this->getEncodedData($this->mBody, $this->mEncoding) . SB_MAIL_PART_CRLF;
        }

        // Добавляем заголовки
        $encoded['headers'] =& $this->mHeaders;

        return $encoded;
    }

    /**
     * Добавляет подчасть к текущей части и возвращает ссылку на нее
     *
     * @param string $body   Тело подчасти.
     * @param array $params Параметры (см. конструктор класса).
     * @return sbMailPart Ссылка на добавленную подчасть.
     */
    public function &addSubPart($body, $params)
    {
        $this->mSubparts[] = new sbMailPart($body, $params);

        return $this->mSubparts[count($this->mSubparts) - 1];
    }

    /**
     * Возвращает кодированный текст
     *
     * @param string $data     Текст для кодирования.
     * @param string $encoding Кодировка (7bit, 8bit, base64 или quoted-printable).
     *
     * @return string Кодированный текст.
     */
    private function getEncodedData($data, $encoding)
    {
        switch ($encoding)
        {
            case '8bit':
            case '7bit':
                return $data;

            case 'quoted-printable':
                return $this->quotedPrintableEncode($data);

            case 'base64':
                return rtrim(chunk_split(base64_encode($data), 76, SB_MAIL_PART_CRLF));

            default:
                return $data;
        }
    }

    /**
     * Кодирует текст в кодировку quoted-printable
     *
     * @param string $input    Текст для кодирования.
     * @param int $line_max Необязательный параметр - максимальное кол-во символов в строке. Не должен превышать 76.
     *
     * @return string Кодированный текст.
     */
    private function quotedPrintableEncode($input , $line_max = 76)
    {
        $lines  = preg_split("/\r?\n/", $input);
        $eol    = SB_MAIL_PART_CRLF;
        $escape = '=';
        $output = '';

        while((list(, $line) = each($lines)) != false)
        {
            $linlen = sb_strlen($line, $this->mCharset);
            $newline = '';

            for ($i = 0; $i < $linlen; $i++)
            {
                $char = sb_str_replace('%', $escape, rawurlencode(sb_substr($line, $i, 1, $this->mCharset)));

                if ((sb_strlen($newline, $this->mCharset) + 1) >= $line_max)
                {
                    $output  .= $newline . $escape . $eol;
                    $newline  = '';
                }
                $newline .= $char;
            }
            $output .= $newline . $eol;
        }

        $output = sb_substr($output, 0, -1 * sb_strlen($eol), $this->mCharset);
        return $output;
    }
}


?>