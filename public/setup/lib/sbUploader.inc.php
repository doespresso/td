<?php
/**
 * Реализация класса, отвечающего за загрузку файлов с локального компьютера на сервер.
 *
 * @author Казбек Елекоев <elekoev@binn.ru>
 * @version 4.0
 * @package SB_FileSystem
 * @copyright Copyright (c) 2007, OOO "СИБИЭС Групп"
 */

define ('SB_UPLOADER_NO_FILE', 'Файл не был загружен!');
define ('SB_UPLOADER_SIZE_ERROR', 'Размер файла не должен превышать %s Кб! Это ограничение можно изменить в настройках системы.');
define ('SB_UPLOADER_IMAGE_SIZE_ERROR', 'Размеры загружаемого изображения не должны быть больше %s пикселей по ширине и %s пикселей по высоте! Это ограничение можно изменить в настройках системы.');
define ('SB_UPLOADER_TYPE_ERROR', 'Запрещено закачивать файлы данного типа!');
define ('SB_UPLOADER_MKPATH_ERROR', 'Ошибка при создании директории <i>%s</i> ! Возможно у Вас недостаточно прав доступа.');
define ('SB_UPLOADER_FILE_EXIST_ERROR', 'Файл с именем <i>%s</i> уже существует!');
define ('SB_UPLOADER_FILE_SAVE_ERROR', 'Ошибка при сохранении файла <i>%s</i> !');

/**
 * Класс, отвечающий за загрузку файлов из HTML-формы.
 *
 * @author Казбек Елекоев <elekoev@binn.ru>
 * @version 4.0
 * @package SB_FileSystem
 * @copyright Copyright (c) 2007, OOO "СИБИЭС Групп"
 */
class sbUploader
{
    /**
     * Массив, содержащий информацию о загружаемом файле
     *
     * @var array
     */
	public $mFile = false;

	/**
	 * Текст ошибки
	 *
	 * @var string
	 */
	private $mError = '';

	/**
	 * Максимально разрешенный размер файла в байтах (0 - без ограничений)
	 *
	 * @var int
	 */
	private $mMaxFilesize = 0;

	/**
	 * Максимально разрешенная ширина картинки в пикселах (0 - без ограничений)
	 *
	 * @var int
	 */
	private $mMaxImageWidth = 0;

	/**
	 * Максимально разрешенная высота картинки в пикселах (0 - без ограничений)
	 *
	 * @var int
	 */
	private $mMaxImageHeight = 0;

	/**
	 * Устанавливает максимально разрешенный размер файла (0 - без ограничений)
	 *
	 * @param int $size Максимально разрешенный размер файла.
	 */
	public function setMaxFileSize($size)
	{
		$this->mMaxFilesize = $size;
	}

	/**
	 * Устанавливает максимально разрешенные ширину и высоту картинки (в случае загрузки графических файлов)
	 *
	 * @param int $width Максимально разрешенная ширина картинки.
	 * @param int $height Максимально разрешенная высота картинки.
	 */
	public function setMaxImageSize($width, $height)
	{
		$this->mMaxImageWidth  = $width;
		$this->mMaxImageHeight = $height;
	}

	/**
	 * Возвращает текст ошибки или пустую строку, если ошибок не было
	 *
	 * @return string Текст ошибки.
	 */
	public function getError()
	{
	    return $this->mError;
	}

	/**
	 * Обнуляет текст ошибки для нового файла
	 */
	public function init()
	{
	    $this->mError = '';
	}

	/**
	 * Инициализирует переменную mFile информацией о загружаемом файле
	 *
	 * Здесь же происходит проверка, не превышает ли размер файла максимально допустимый и, если
	 * файл является графическим изображением, не превышают ли его размеры (высота и ширина)
	 * максимально допустимые.
	 *
	 * @param string $file_field_name Имя поля в форме (параметр name) для файла.
	 * @param array $accept_types Массив разрешенных для закачки расширений файлов.
	 * @return bool TRUE, если все проверки прошли успешно и ошибок нет, FALSE в ином случае
	 *              (текст ошибки можно получить с помощью метода getError).
	 */
	public function upload($file_field_name='', $accept_types=array())
	{
		if (!is_array($_FILES[$file_field_name]) || !$_FILES[$file_field_name]['name'])
		{
		    // поле с заданным именем не найдено или файл не указан
			$this->mError = SB_UPLOADER_NO_FILE;
			return false;
		}

		$this->mFile = $_FILES[$file_field_name];
		$this->mFile['file'] = $file_field_name;

		if($this->mMaxFilesize && ($this->mFile['size'] > $this->mMaxFilesize))
		{
		    // размер файла превышает максимально допустимый
			$this->mError = sprintf(SB_UPLOADER_SIZE_ERROR, sprintf('%01.2f', $this->mMaxFilesize / 1024));
			return false;
		}

	 	if(strstr($this->mFile['type'], 'image'))
	 	{
	 	    // загружаемый файл является изображением, проверяем его размеры
	 		$image = @getimagesize($this->mFile['tmp_name']);

	 		if (is_array($image))
	 		{
	 		    $this->mFile['width']  = $image[0];
	 		    $this->mFile['height'] = $image[1];
	 		}
	 		else
	 		{
	 		    $this->mFile['width']  = 0;
	 		    $this->mFile['height'] = 0;
	 		}

			if(($this->mMaxImageWidth || $this->mMaxImageHeight) && (($this->mFile['width'] > $this->mMaxImageWidth) || ($this->mFile['height'] > $this->mMaxImageHeight)))
			{
			    // размеры изображения превышают максимально допустимые
				$this->mError = sprintf(SB_UPLOADER_IMAGE_SIZE_ERROR, $this->mMaxImageWidth, $this->mMaxImageHeight);
				return false;
			}
	 	}

		if(count($accept_types) != 0)
		{
		    // проверяем, разрешен ли данный тип файла для закачки
		    $ext = @pathinfo($this->mFile['name']); 
		    $ext = isset($ext['extension']) ? strtolower($ext['extension']) : 'undefined';       
            if(!in_array($ext, $accept_types))
            {
                $this->mError = SB_UPLOADER_TYPE_ERROR;
                return false;
            }
		}

		return true;
	}

    /**
     * Перемещает загруженный файл в указанное место
     *
     * @param string $dir_name Директория, в которую будет перемещен файл.
     * @param string $file_name Имя файла, под которым будет сохранен загруженный файл.
     * @param string $folder_rights Права на создаваемые папки.
     * @param string $file_rights Права на создаваемые файлы.
     * @param int $overwrite_mode Режим перезаписи (1 - перезаписывать, 2 - сгенерировать новое имя, 3 - выдать ошибку).
     * 
     * @return bool TRUE, если файл был перемещен успешно, FALSE в ином случае.
     */
	public function move($dir_name, $file_name = '', $folder_rights = '755', $file_rights = '644', $overwrite_mode = 1)
	{
	    if ($dir_name != '/')
	       $dir_name = rtrim($dir_name, '/\\');

		if (!@is_dir($dir_name))
		{
		    if (!@mkdir($dir_name, octdec($folder_rights), true))
		    {
		        $this->mError = sprintf(SB_UPLOADER_MKPATH_ERROR, $dir_name);
		        return false;
		    }
		}

		if($this->mError == '')
		{
		    if ($file_name != '')
		    {
			     $this->mFile['name'] = $file_name;
		    }

		    //только малые буквы, цифры, знак подчеркивания, точка и тире
			$this->mFile['name'] = preg_replace('[^a-z0-9._-]', '', str_replace(array(' ', '%20'), array('_', '_'), strtolower($this->mFile['name'])));
			
			$ext = @pathinfo($this->mFile['name']);
			$this->mFile['extension'] = isset($ext['extension']) ? $ext['extension'] : '';
			       
			if ($this->mFile['extension'] != '')
			{
			    $pos = strrpos($this->mFile['name'], '.'.$this->mFile['extension']);
				$this->mFile['raw_name'] = substr($this->mFile['name'], 0, $pos);
			}
			else
			{
				$this->mFile['raw_name'] = $this->mFile['name'];
			}

			$result = false;
			switch($overwrite_mode)
			{
				case 1:
				    // перезаписать
					$result = @move_uploaded_file($this->mFile['tmp_name'], $dir_name.'/'.$this->mFile['name']);
					break;

				case 2:
				    if (@file_exists($dir_name.'/'.$this->mFile['name']))
				    {
    				    $n = 0;
    				    $copy = '_copy'.$n;
    				    // создать новый с индексом
    					while(@file_exists($dir_name.'/'.$this->mFile['raw_name'].$copy.'.'.$this->mFile['extension']))
    					{
    					    $n++;
    						$copy = '_copy' . $n;
    					}
    
    					$this->mFile['name'] = $this->mFile['raw_name'].$copy.'.'.$this->mFile['extension'];
				    }

					$result = @move_uploaded_file($this->mFile['tmp_name'], $dir_name.'/'.$this->mFile['name']);
					break;

				case 3:
				    // ничего не делать
					if(@file_exists($dir_name.'/'.$this->mFile['name']))
					{
						$this->error = sprintf(SB_UPLOADER_FILE_EXIST_ERROR, $this->mFile['name']);
						return false;
					}
					else
					{
						$result = @move_uploaded_file($this->mFile['tmp_name'], $dir_name.'/'.$this->mFile['name']);
					}
					break;

				default:
					break;
			}

			@chmod($dir_name.'/'.$this->mFile['name'], octdec($file_rights));
			
			if(!$result)
			{
			    $this->mError = sprintf(SB_UPLOADER_FILE_SAVE_ERROR, $this->mFile['name']);
			    return false;
			}

			return $this->mFile['name'];
		}

		return false;
	}
	
	/**
     * Копирует загруженный файл в указанное место
     *
     * @param string $dir_name Директория, в которую будет скопирован файл.
     * @param string $file_name Имя файла, под которым будет сохранен загруженный файл.
     * @param string $folder_rights Права на создаваемые папки.
     * @param string $file_rights Права на создаваемые файлы.
     * @param int $overwrite_mode Режим перезаписи (1 - перезаписывать, 2 - сгенерировать новое имя, 3 - выдать ошибку).
     * 
     * @return bool TRUE, если файл был скопирован успешно, FALSE в ином случае.
     */
	public function copy($dir_name, $file_name = '', $folder_rights = '755', $file_rights = '644', $overwrite_mode=1)
	{
	    if ($dir_name != '/')
	       $dir_name = rtrim($dir_name, '/\\');

		if (!@is_dir($dir_name))
		{
		    if (!@mkdir($dir_name, octdec($folder_rights), true))
		    {
		        $this->mError = sprintf(SB_UPLOADER_MKPATH_ERROR, $dir_name);
		        return false;
		    }
		}

		if($this->mError == '')
		{
		    if ($file_name != '')
		    {
			     $this->mFile['name'] = $file_name;
		    }

		    //только малые буквы, цифры, знак подчеркивания, точка и тире
			$this->mFile['name'] = preg_replace('[^a-z0-9._-]', '', str_replace(array(' ', '%20'), array('_', '_'), strtolower($this->mFile['name'])));
			
			$ext = @pathinfo($this->mFile['name']);
			$this->mFile['extension'] = isset($ext['extension']) ? $ext['extension'] : '';
			       
			if ($this->mFile['extension'] != '')
			{
			    $pos = strrpos($this->mFile['name'], '.'.$this->mFile['extension']);
				$this->mFile['raw_name'] = substr($this->mFile['name'], 0, $pos);
			}
			else
			{
				$this->mFile['raw_name'] = $this->mFile['name'];
			}

			$result = false;
			switch($overwrite_mode)
			{
				case 1:
				    // перезаписать
					$result = @copy($this->mFile['tmp_name'], $dir_name.'/'.$this->mFile['name']);
					break;

				case 2:
				    if (@file_exists($dir_name.'/'.$this->mFile['name']))
				    {
    				    $n = 0;
    				    $copy = '_copy'.$n;
    				    // создать новый с индексом
    					while(@file_exists($dir_name.'/'.$this->mFile['raw_name'].$copy.'.'.$this->mFile['extension']))
    					{
    					    $n++;
    						$copy = '_copy' . $n;
    					}
    
    					$this->mFile['name'] = $this->mFile['raw_name'].$copy.'.'.$this->mFile['extension'];
				    }

					$result = @copy($this->mFile['tmp_name'], $dir_name.'/'.$this->mFile['name']);
					break;

				case 3:
				    // ничего не делать
					if(@file_exists($dir_name.'/'.$this->mFile['name']))
					{
						$this->error = sprintf(SB_UPLOADER_FILE_EXIST_ERROR, $this->mFile['name']);
						return false;
					}
					else
					{
						$result = @copy($this->mFile['tmp_name'], $dir_name.'/'.$this->mFile['name']);
					}
					break;

				default:
					break;
			}

			@chmod($dir_name.'/'.$this->mFile['name'], octdec($file_rights));
			
			if(!$result)
			{
			    $this->mError = sprintf(SB_UPLOADER_FILE_SAVE_ERROR, $this->mFile['name']);
			    return false;
			}

			return $this->mFile['name'];
		}

		return false;
	}
}
?>