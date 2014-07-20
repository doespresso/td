<?php
/**
 * Реализация класса, отвечающего за построение и подгрузку дерева папок в файловой панели.
 *
 * @author Казбек Елекоев <elekoev@binn.ru>
 * @version 4.0
 * @package SB_FileSystem
 * @copyright Copyright (c) 2007, OOO "СИБИЭС Групп"
 */

/**
 * Класс, отвечающий за построение и подгрузку дерева папок в файловой панели.
 *
 * @author Казбек Елекоев <elekoev@binn.ru>
 * @version 4.0
 * @package SB_FileSystem
 * @copyright Copyright (c) 2007, OOO "СИБИЭС Групп"
 */
class sbFolders
{
    /**
     * Директория, которую надо открыть и подсветить
     *
     * Если не указана, то подсвечивается корневая директория.
     *
     * @var string
     */
    public $mFoldersSelectedPath = '/';

    /**
     * Использовать подгрузку или отображать все дерево сразу
     *
     * @var bool
     */
    public $mFoldersAutoloading = true; //

    /**
     * Список директорий, которые показывать не надо
     *
     * @var array
     */
    public $mFoldersNeverShowFolds = array();

    /**
     * Список директорий, только которые и будут показаны
     *
     * @var array
     */
    public $mFoldersOnlyShowFolds = array();

    /**
     * Выводить или нет контекстное меню папок
     *
     * @var bool
     */
    public $mFoldersMenu = true;

    /**
     * Использовать или нет перетаскивание в дереве папок
     *
     * @var bool
     */
    public $mFoldersDad = true;

    /**
     * Выводить или нет пункт меню "Права на папку"
     *
     * @var bool
     */
    public $mFoldersRightsMenu = true;

    /**
     * Выводить или нет пункт меню "Обновить"
     *
     * @var bool
     */
    public $mFoldersRefreshMenu = true;

    /**
     * Выводить или нет пункт меню "Загрузить файлы"
     *
     * @var bool
     */
    public $mFoldersUploadMenu = true;

    /**
     * Выводить или нет пункт меню "Создать папку"
     *
     * @var bool
     */
    public $mFoldersAddMenu = true;

    /**
     * Выводить или нет пункт меню "Редактировать папку"
     *
     * @var bool
     */
    public $mFoldersEditMenu = true;

    /**
     * Выводить или нет пункт меню "Удалить папку"
     *
     * @var bool
     */
    public $mFoldersDeleteMenu = true;

    /**
     * Выводить или нет пункт меню "Копировать папку"
     *
     * @var bool
     */
    public $mFoldersCopyMenu = true;

    /**
     * Выводить или нет пункт меню "Вставить файлы"
     *
     * @var bool
     */
    public $mFoldersPasteFilesMenu = true;

    /**
     * Массив доп. пунктов меню для дерева папок
     *
     * <code>
     * // текст пункта меню
     * $this->mFoldersAdditionalMenu[$i]['item'] = ...;
     * // JavaScript-функция, вызываемая при щелчке по пункту меню
     * $this->mFoldersAdditionalMenu[$i]['func'] = ...;
     * // true - скрывать пункт из списка, если меню вызывается для корневой папки
     * // false - показывать пункт всегда
     * $this->mFoldersAdditionalMenu[$i]['hide'] = ...;
     * </code>
     *
     * @var array
     */
    private $mFoldersAdditionalMenu = array();

    /**
     * Конструктор класса
     */
    public function __construct()
    {
        if (isset($_SESSION['sb_folders_selected_path']))
            $this->mFoldersSelectedPath = $_SESSION['sb_folders_selected_path'];
        else
            $_SESSION['sb_folders_selected_path'] = '/';
    }

    /**
     * Добавляет доп. пункт в контекстное меню папок
     *
     * @param string $title Название пункта меню.
     * @param string $func JavaScript-функция, вызываемая при щелчке по пункту меню.
     * @param bool $hide TRUE - скрывать пункт из списка, если меню вызывается для корневой папки, FALSE - показывать пункт всегда.
     */
    public function addFolderMenuItem($title, $func, $hide=true)
    {
        $i = count($this->mFoldersAdditionalMenu);
        $this->mFoldersAdditionalMenu[$i] = array();
        $this->mFoldersAdditionalMenu[$i]['item'] = $title;
        $this->mFoldersAdditionalMenu[$i]['func'] = $func;
        $this->mFoldersAdditionalMenu[$i]['hide'] = $hide;
    }

    /**
     * Инициализация переменных класса и вывод JavaScript-кода для работы с папками
     */
    public function init()
    {
        if (!is_array($this->mFoldersNeverShowFolds))
            $this->mFoldersNeverShowFolds = array();

        $folders = SbPlugins::getSetting("sb_folder_restricted");
        if (!empty($folders)) {
            $folders = explode(",", $folders);
            foreach ($folders as $folder) {
                $this->mFoldersNeverShowFolds[] = trim($folder);
            }
        }
        unset($folders);

        if (!is_array($this->mFoldersOnlyShowFolds))
            $this->mFoldersOnlyShowFolds = array();

        if (!$GLOBALS['sbVfs']->exists($this->mFoldersSelectedPath))
        {
            $this->mFoldersSelectedPath = '/';
            $_SESSION['sb_folders_selected_path'] = '/';
        }

        if (!$_SESSION['sbPlugins']->isRightAvailable('pl_filelist', 'folders_edit'))
        {
            $this->mFoldersDad = false;
            $this->mFoldersAddMenu = false;
            $this->mFoldersEditMenu = false;
            $this->mFoldersCopyMenu = false;
        }

        if (!$_SESSION['sbPlugins']->isRightAvailable('pl_filelist', 'folders_delete'))
        {
            $this->mFoldersDeleteMenu = false;
        }

        if (!$_SESSION['sbPlugins']->isRightAvailable('pl_filelist', 'folders_rights'))
        {
            $this->mFoldersRightsMenu = false;
        }

        if (!$_SESSION['sbPlugins']->isRightAvailable('pl_filelist', 'folders_upload'))
        {
            $this->mFoldersUploadMenu = false;
        }

        echo '<link rel="stylesheet" type="text/css" href="'.SB_CMS_CSS_URL.'/sbFolders.css">
        <script src="'.SB_CMS_JSCRIPT_URL.'/sbDAD.js.php"></script>
        <script src="'.SB_CMS_JSCRIPT_URL.'/sbCMenu.js.php"></script>
        <script src="'.SB_CMS_JSCRIPT_URL.'/sbXMLLoader.js.php"></script>
        <script src="'.SB_CMS_JSCRIPT_URL.'/sbTree.js.php"></script>
        <script src="'.SB_CMS_JSCRIPT_URL.'/sbFolders.js.php"></script>
        <script src="'.SB_CMS_JSCRIPT_URL.'/sbDialogs.js.php"></script>
        <script>
            var sb_folders_selected_path = "'.addslashes($this->mFoldersSelectedPath).'";
            var sb_folders_autoloading = "'.$this->mFoldersAutoloading.'";
            var sb_folders_never_show = "'.urlencode(implode(',', $this->mFoldersNeverShowFolds)).'";
            var sb_folders_only_show = "'.urlencode(implode(',', $this->mFoldersOnlyShowFolds)).'";
            var sb_folders_need_conf = '.((sbPlugins::getSetting('sb_confirm_move_folders') == 1)? '1' : '0').'

            var sb_folders_add_menu = [];';

        if (count($this->mFoldersAdditionalMenu) > 0)
        {
            for ($i = 0; $i < count($this->mFoldersAdditionalMenu); $i++)
            {
                echo '
                var i = sb_folders_add_menu.length;
                sb_folders_add_menu[i] = [];
                sb_folders_add_menu[i]["item"] = "'.$this->mFoldersAdditionalMenu[$i]['item'].'";
                sb_folders_add_menu[i]["func"] = "'.$this->mFoldersAdditionalMenu[$i]['func'].'";
                sb_folders_add_menu[i]["hide"] = '.($this->mFoldersAdditionalMenu[$i]['hide'] ? '1':'0').';
                ';
            }
        };

        echo '
            function sbFoldersLoad()
            {
                sbFoldersTree=new sbTree(sbGetE("sb_folders_list"), "100%", "100%", 0);
                sbFoldersTree.imPath = sb_cms_img_url + "/tree/";
                sbFoldersTree.checkBoxes=false;'.
                (!$this->mFoldersDad || !$this->mFoldersMenu ? 'sbFoldersTree.dragAndDrop=false;':'sbFoldersTree.dadupdown=0;sbFoldersTree.dadmode=2;sbFoldersTree.dragFunc=sbFoldersPaste;').
                'sbFoldersTree.aFunc=sbFoldersClick;
                sbFoldersTree.cMenu = sbFoldersMenu;
                sbFoldersTree.dadlevel = 2;
                '.($this->mFoldersAutoloading ? 'sbFoldersTree.setXMLAutoLoading(sb_cms_empty_file + "?event=pl_folders_read&folds_never_show="+sb_folders_never_show+"&folds_only_show="+sb_folders_only_show+"&folds_selected_path="+encodeURI(sb_folders_selected_path));' : '').
                'sbFoldersTree.loadXML(sb_cms_empty_file + "?event=pl_folders_read&folds_autoloading="+sb_folders_autoloading+"&folds_never_show="+sb_folders_never_show+"&folds_only_show="+sb_folders_only_show+"&folds_selected_path="+encodeURI(sb_folders_selected_path), sbFoldersAfterLoad);
            }

            sbAddEvent(window, "load", sbFoldersLoad);';

        if ($this->mFoldersMenu)
        {
            echo 'sbFoldersMenu = new sbCMenu("sbFoldersMenu", "15#FF9933", "15#fff5e0", "highItemText", "lowItemText", 90);
            sbFoldersMenu.showFunc = sbFoldersShowMenu;';
            if ($this->mFoldersPasteFilesMenu) echo 'sbFoldersMenu.addItem("paste", "'.SB_FOLDERS_PASTE_FILES_MENU.'", sbFoldersPasteFiles);';
            echo 'sbFoldersMenu.addItem("paste_delimeter");';
            if ($this->mFoldersCopyMenu)
            {
                echo '
                sbFoldersMenu.addItem("copy", "'.SB_FOLDERS_COPY_MENU.'", sbFoldersCopy);
                sbFoldersMenu.addItem("cut", "'.SB_FOLDERS_CUT_MENU.'", sbFoldersCut);
                sbFoldersMenu.addItem("paste_folders", "'.SB_FOLDERS_PASTE_FOLDS_MENU.'", sbFoldersPaste);
                sbFoldersMenu.addItem("paste_folders_delimeter");';
            }
            if ($this->mFoldersAddMenu)
            {
                echo 'sbFoldersMenu.addItem("add", "'.SB_FOLDERS_ADD_MENU.'", sbFoldersAdd);';
            }

            if ($this->mFoldersEditMenu)
            {
                echo 'sbFoldersMenu.addItem("rename", "'.SB_FOLDERS_EDIT_MENU.'", sbFoldersRename);';
            }

            if ($this->mFoldersDeleteMenu)
            {
                echo 'sbFoldersMenu.addItem("delete", "'.SB_FOLDERS_DELETE_MENU.'", sbFoldersDelete);';
            }

            if ($this->mFoldersUploadMenu)
            {
                echo 'sbFoldersMenu.addItem("upload", "'.SB_FOLDERS_UPLOAD_MENU.'", sbFoldersUploadFiles);';
            }

            if ($this->mFoldersRefreshMenu)
            {
                echo 'sbFoldersMenu.addItem("refresh", "'.SB_FOLDERS_REFRESH_MENU.'", sbFoldersRefresh);';
            }

            echo 'sbFoldersMenu.addItem("delimeter");';

            if ($this->mFoldersRightsMenu)
            {
                echo 'sbFoldersMenu.addItem("rights", "'.SB_FOLDERS_RIGHTS_MENU.'", sbFoldersRights);';
            }

            if (count($this->mFoldersAdditionalMenu) > 0)
            {
                for ($i = 0; $i < count($this->mFoldersAdditionalMenu); $i++)
                    echo 'sbFoldersMenu.addItem("additional_'.$i.'", "'.str_replace('"', '\"', $this->mFoldersAdditionalMenu[$i]['item']).'", "'.$this->mFoldersAdditionalMenu[$i]['func'].'");';
            }
        }

        echo '</script>';
    }

    /**
     * Рекурсивно считывает директорию и формирует XML для отображения дерева каталогов
     *
     * @param string $directory Путь к директории для считывания.
     * @param string $result Сформированный XML.
     * @param string $level Уровень вложенности директорий.
     */
    private function readDir($directory, &$result, $level, $prev_show=true)
    {
        static $groups_sql = '';

        $level++;
        // считываем содержимое директорий
        $dirs_and_files = $GLOBALS['sbVfs']->scandir($directory.'/');
        if ($dirs_and_files)
        {
            $str = '';
            foreach ($dirs_and_files as $file)
            {
                if ($file == '.' || $file == '..')
                    continue;

                $path = $directory.'/'.$file;

                // если файл, то ничего не делаем
                if (!$GLOBALS['sbVfs']->is_dir($path) && !$GLOBALS['sbVfs']->is_link($path))
                    continue;

                // если директория в списке неотображаемых, то ничего не делаем
                if (in_array($path, $this->mFoldersNeverShowFolds))
                    continue;

                if (!empty($this->mFoldersOnlyShowFolds))
                {
                    // если есть список директорий, только которые и надо показать
                    $dirs = explode('/', trim($path, '/'));
                    $allow_path = '';
                    $found = false;

                    foreach ($dirs as $value)
                    {
                        // проверяем, является ли текущая директория поддиректорий из списка
                        $allow_path .= '/'.$value;
                        if (in_array($allow_path, $this->mFoldersOnlyShowFolds))
                        {
                            $found = true;
                            break;
                        }
                    }

                    if (!$found)
                    {
                        // если нет, идем глубже, может быть там есть нужная директория
                        $this->readDir($path, $str, $level-1);
                        continue;
                    }
                }

                $show = fFolders_Check_Rights($path);

                if ($this->mFoldersAutoloading && $level >= 3)
                {
                    if ($prev_show)
                    {
                        // так как используется подгрузка, то ниже 3-го уровня не опускаемся
                        $result .= ' ';
                        return;
                    }

                    if (!$show)
                    {
                        // прав доступа к выбранной папке нет, проверяем есть ли в ней папки, для которых указаны права доступа
                        if ($groups_sql == '')
                        {
                            $groups = $_SESSION['sbAuth']->getUserGroups();
                            foreach ($groups as $val)
                            {
                                $groups_sql .= ' OR CONCAT("^", f_ids, "^") LIKE "%^g'.$val.'^%"';
                            }
                        }

                        $res = sql_query('SELECT COUNT(*) FROM sb_folders_rights WHERE f_domain=? AND f_path LIKE ? AND (CONCAT("^", f_ids, "^") LIKE ? '.$groups_sql.')', SB_COOKIE_DOMAIN, $path.'%', '%^u'.$_SESSION['sbAuth']->getUserId().'^%');
                        if (!$res || $res[0][0] <= 0)
                        {
                            $result .= ' ';
                            return;
                        }
                    }
                }

                $str2 = '';
                if (strpos($this->mFoldersSelectedPath, $path) === 0 && strlen($this->mFoldersSelectedPath) > strlen($path))
                    $this->readDir($path, $str2, $level-1, $show);
                else
                    $this->readDir($path, $str2, $level, $show);

                if ($str2 != '')
                {
                    if ($show)
                        $str .= '<item text="'.$file.'" id="'.$path.'" child="1">'."\n".$str2.'</item>'."\n";
                    else
                        $str .= $str2;
                }
                else
                {
                    if ($show)
                        $str .= '<item text="'.$file.'" id="'.$path.'" />'."\n";
                }
            }

            $result .= $str;
        }
    }

    /**
     * Выводит XML для дерева директорий
     */
    public function show()
    {
        if (isset($_GET['id']))
            $tree_id = preg_replace('/[^\s0-9a-zA-Z\/\\\\_\-\.\[\]]+/i', '', urldecode($_GET['id']));
        else
            $tree_id = null;

        if (isset($_GET['folds_never_show']) && !empty($_GET['folds_never_show']))
        {
            $_GET['folds_never_show'] = preg_replace('/[^\s0-9a-zA-Z\/\\\\_\-\.,\[\]]+/i', '', urldecode($_GET['folds_never_show']));
            $this->mFoldersNeverShowFolds = explode(',', $_GET['folds_never_show']);
        }

        if (isset($_GET['folds_only_show']) && !empty($_GET['folds_only_show']))
        {
            $_GET['folds_only_show'] = preg_replace('/[^\s0-9a-zA-Z\/\\\\_\-\.,\[\]]+/i', '', urldecode($_GET['folds_only_show']));
            $this->mFoldersOnlyShowFolds = explode(',', $_GET['folds_only_show']);
        }

        if (isset($_GET['folds_autoloading']))
        {
            $this->mFoldersAutoloading = (bool)$_GET['folds_autoloading'];
        }

        if (isset($_GET['folds_selected_path']) && !empty($_GET['folds_selected_path']))
	    {
	        $_GET['folds_selected_path'] = preg_replace('/[^\s0-9a-zA-Z\/\\\\_\-\.,\[\]]+/i', '', urldecode($_GET['folds_selected_path']));
	        $_SESSION['sb_folders_selected_path'] = $_GET['folds_selected_path'];
		    $this->mFoldersSelectedPath = $_GET['folds_selected_path'];
	    }

	    foreach ($this->mFoldersNeverShowFolds as $key => $value)
	    {
	        $this->mFoldersNeverShowFolds[$key] = '/'.trim($value, '/');
	    }

	    foreach ($this->mFoldersOnlyShowFolds as $key => $value)
	    {
	        $this->mFoldersOnlyShowFolds[$key] = '/'.trim($value, '/');
	    }

	    $str = '';

	    if (is_null($tree_id))
	    {
	        $this->readDir('', $str, 0);
	        $show = fFolders_Check_Rights('');

	        if (trim($str) != '')
                $str = '<?xml version="1.0" encoding="'.SB_CHARSET.'" ?>'."\n".'<tree id="0">'."\n".($show ? '<item text=".." id="/" open="1" child="1">' : '')."\n".$str.($show ? '</item>' : '').'</tree>';
            else
                $str = '<?xml version="1.0" encoding="'.SB_CHARSET.'" ?>'."\n".'<tree id="0">'."\n".($show ? '<item text=".." id="/" />' : '').'</tree>';
	    }
        else
        {
            if ($tree_id == '/')
                $this->readDir('', $str, 0);
            else
                $this->readDir($tree_id, $str, 0);
            $str = '<?xml version="1.0" encoding="'.SB_CHARSET.'" ?>'."\n".'<tree id="'.$tree_id.'">'."\n".$str.'</tree>';
        }

        echo $str;
    }
}
?>