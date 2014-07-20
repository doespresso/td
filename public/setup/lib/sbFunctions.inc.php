<?php
/**
 * Разархивирует ZIP-архив в указанную папку
 *
 * @param string $file Полный путь к ZIP-архиву.
 * @param string $path Путь, куда будет разархивирован архив.
 * @param int $folder_rights Права на создаваемые папки.
 * @param int $file_rights Права на создаваемые файлы.
 * 
 * @return mixed Массив файлов, извлеченных из архива (с путями) или FALSE, в случае возникновения ошибки.
 */
function sb_unzip($file, $path, $folder_rights, $file_rights)
{
    $path = rtrim($path, '/');
    $zip = zip_open($file);

    if (!$zip)
        return false;

    $result = array();
    while ($entry = @zip_read($zip))
    {
        $entry_name = @zip_entry_name($entry);
        if (@zip_entry_filesize($entry))
        {
            $dir = dirname($entry_name);
            if ($dir != '' && $dir != '.' && $dir != '..')
            {
            	$dir = preg_replace('/[^a-zA-Z0-9\._\-\/]/', '', str_replace(array(' ', '%20', '\\'), array('_', '_', '/'), $dir));
            	if (!@file_exists($path.'/'.$dir))
                {
                    if (!@mkdir($path.'/'.$dir, octdec($folder_rights), true))
                        return false;
                }
            }
            else
            {
                $dir = '';
            }

            $entry_name = iconv('CP866', 'WINDOWS-1251', $entry_name);
            $file_name = basename($entry_name);
            
		    //только буквы, цифры, знак подчеркивания, точка и тире
			$file_name = preg_replace('/[^a-zA-Z0-9\._-]/', '', str_replace(array(' ', '%20'), array('_', '_'), $file_name));
            $file_name = str_replace('//', '/', '/'.$dir.'/'.$file_name);

            if (!@zip_entry_open($zip, $entry))
            {
                return false;
            }

            $fh = @fopen($path.$file_name, 'w');
            if (!$fh)
            {
                return false;
            }

            if (!@fwrite($fh, @zip_entry_read($entry, @zip_entry_filesize($entry))))
            {
                return false;
            }

            @fclose($fh);
            @chmod($path.$file_name, octdec($file_rights));
            
            @zip_entry_close($entry);

            $result[] = $file_name;
        }
    }
    return $result;
}

/**
 * Определяет домен, на котором была запущена система
 *
 * @return string Домен.
 */
function sb_get_host()
{
    $url = array();

    // сначала пытаемся отпарсить REQUEST_URI, возможно там есть полный путь
    if ( !empty($_SERVER['REQUEST_URI']) )
    {
        $pos = strpos($_SERVER['REQUEST_URI'], '?');
        if ($pos)
            $request_uri = substr($_SERVER['REQUEST_URI'], 0, $pos);
        else
            $request_uri = $_SERVER['REQUEST_URI'];

        $url = @parse_url($request_uri);
    }
    if (!$url) $url = array();
    // Если протокола нет, значит путь не полный
    // копаем глубже
    if ( !isset($url['scheme']) || empty($url['scheme']) )
    {
        if ( !empty($_SERVER['HTTP_HOST']) )
        {
            if ( strpos($_SERVER['HTTP_HOST'], ':') !== false )
            {
                list( $url['host'], $url['port'] ) = explode(':', $_SERVER['HTTP_HOST']);
            }
            else
            {
                $url['host'] = $_SERVER['HTTP_HOST'];
            }
        }
        elseif ( !empty($_SERVER['SERVER_NAME']) )
        {
            $url['host'] = $_SERVER['SERVER_NAME'];
        }
        else
        {
            return array();
        }
    }

    return $url;
}

/* Копирует одну директорию в другую.
 *
 * Может вызываться рекурсивно.
 *
 * @param string $src  Путь к директории, которую копируем.
 * @param string $dest Путь к директории, в которую копируем.
 * @param int $folder_rights Права на создаваемые папки.
 * @param int $file_rights Права на создаваемые файлы.
 * 
 * @return bool TRUE, если копирование прошло успешно и FALSE в противном случае.
 */
function sb_copy($src, $dest, $folder_rights, $file_rights)
{
    if (!@is_dir($dest))
    {
        /**
         * Если директории, в которую производится копирование, не существует, то создаем ее
         */
        if (!@mkdir($dest, octdec($folder_rights), true))
        {
            /**
             * Если создать не удалось, то возвращаем FALSE
             */
            return false;
        }
    }

    /**
     * Считываем содержимое директории, которую копируем
     */
    $dir_contents = @scandir($src);

    if (is_array($dir_contents))
    {
        foreach ($dir_contents as $item)
        {
        	if ($item == 'dump.sql')
        		continue;
        		
            /**
             * Производим копирование каждого файла и директории из считанного списка
             */
            $src_file = $src.'/'.$item;
            $dest_file = $dest.'/'.$item;

            if ($item != '.' && $item != '..' && @is_dir($src_file))
            {
                /**
                 * Если копируем директорию, то вызываем рекурсию
                 */
                $res = sb_copy($src_file, $dest_file, $folder_rights, $file_rights);
                if (!$res)
                {
                    /**
                     * Если скопировать не удалось, то возвращаем FALSE
                     */
                    return false;
                }
            }
            elseif ($item != '.' && $item != '..')
            {
                /**
                 * Копирование файла
                 */
                $res = @copy($src_file, $dest_file);
                if (!$res && @filesize($src_file) != 0)
                {
                    /**
                     * Иногда файлы с нулевым размером могут приводить к ошибке, игнорируем их
                     */
                    return false;
                }

                @chmod($dest_file, octdec($file_rights));
            }
        }
	}
	
    return true;
}

function sb_symlink ($target, $link) 
{
	if (isset($_SERVER['WINDIR']) && !empty($_SERVER['WINDIR']))
	{
    	$target = str_replace('/', '\\', $target);
    	$link = str_replace('/', '\\', $link);
    	return exec('mklink /j "' . $link . '" "' . $target . '"');
	}
	else 
	{
		return symlink($target, $link);
	}
}

?>