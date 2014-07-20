<?php
function fEditor_Source()
{
	require_once (SB_CMS_LIB_PATH.'/sbLayout.inc.php');

	echo '<script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbSourceDlg.js.php"></script>
	<br><br>';

	$layout = new sbLayout();
	$layout->mShowTitle = false;
	$layout->mTableWidth = '98%';

	$fld = new sbLayoutTextarea('', 'sb_editor_source', '', 'style="width: 100%; height: 550;"');
	$fld->mShowEnlargeBtn = false;
	$fld->mTopHtml = '&nbsp;';

	$layout->addField('', $fld);

	$layout->addButton('button', KERNEL_SAVE, '', '', 'onclick="returnHTML();"');
	$layout->addButton('button', KERNEL_CANCEL, '', '', 'onclick="callReturn = false;sbCloseDialog();"');

	$layout->show();
}

function fEditor_Find()
{
    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    if (!isset($_GET['editor']))
    {
    	echo '<script src="'.SB_CMS_JSCRIPT_URL.'/sbFindDlg.js.php"></script>';
    }
    else
    {
    	echo '<script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbFindDlg.js.php"></script>';
    }

    echo '<br /><br />';

    $layout = new sbLayout();
    $layout->mTableWidth = '95%';

    $layout->addHeader(PL_EDITOR_H_FIND_TITLE);
    $layout->addField(PL_EDITOR_FINDDLG_FIND, new sbLayoutInput('text', '', 'txtFind', 'txtFind', 'onkeyup="sbBtnStat()" oninput="sbBtnStat()" onpaste="sbBtnStatDelayed()" onfocus="this.select()" style="width:100%;"'));
    $layout->addField(PL_EDITOR_FINDDLG_REPLACE, new sbLayoutInput('text', '', 'txtReplace', 'txtReplace', 'onfocus="this.select()" style="width:100%;"'));
    $layout->addField(PL_EDITOR_FINDDLG_WHOLE_WORD, new sbLayoutInput('checkbox', '', 'chkWord', 'chkWord'));
    $layout->addField(PL_EDITOR_FINDDLG_CASE, new sbLayoutInput('checkbox', '', 'chkCase', 'chkCase'));
    $layout->addButton('button', PL_EDITOR_FINDDLG_FIND_BTN, '', 'btnFind', 'onclick="sbFind(true)" disabled="true"');
    $layout->addButton('button', PL_EDITOR_FINDDLG_REPLACE_BTN, '', 'btnReplace', 'onclick="sbReplace()" disabled="true"');
    $layout->addButton('button', PL_EDITOR_FINDDLG_REPLACEALL_BTN, '', 'btnReplaceAll', 'onclick="sbReplaceAll()" disabled="true"');
    $layout->addButton('button', KERNEL_CANCEL, '', '', 'onclick="sbCloseDialog()"');
    $layout->show();
}

function fEditor_Spell()
{
	echo '<script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbSpellDlg.js.php"></script>
	<input type="hidden" id="txtHtml" value="">
	<iframe id="frmSpell" src="javascript:void(0)" name="spellchecker" width="100%" height="100%" frameborder="0"></iframe>
	';
}

function fEditor_Spell_Dialog()
{
	echo '
	<html>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<head>
		<title></title>
		<script>
		var sb_cms_css_url = "'.SB_CMS_CSS_URL.'";
		var sb_cms_img_url = "'.SB_CMS_IMG_URL.'";
		</script>
		<script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbSpellDialogDlg.js.php"></script>
		</head>
		<frameset rows="*,192" onLoad="postWords();" frameborder="no">
			<frame src="javascript: void(0);">
			<frame src="'.SB_CMS_EMPTY_FILE.'?event=pl_editor_spell_controls">
		</frameset>
	</html>';
}

function fEditor_Spell_Controls()
{
	echo '<html>
	<head>
		<link rel="stylesheet" type="text/css" href="'.SB_CMS_CSS_URL.'/sbContent.css" />
		<script src="'.SB_CMS_JSCRIPT_URL.'/sbFunctions.js.php"></script>
		<script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbSpellClasses.js.php"></script>
		<script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbSpellControlsDlg.js.php"></script>
		</head>
		<body class="controlWindowBody" onload="init_spell();" style="overflow: hidden" scroll="no">
		<div id="event_content" style="display: block;visibility: visible;position: static;">';

	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

	$layout = new sbLayout('javascript: void(0);', 'thisDialog', 'post', '', 'spellcheck', 'name="spellcheck"');
	$layout->mTableWidth = '100%';

	$layout->addField(PL_EDITOR_SPELLDLG_MISSED_WORD, new sbLayoutInput('text', '', 'misword', '', 'readonly="readonly" style="width: 100%;"'));
	$layout->addField(PL_EDITOR_FINDDLG_REPLACE, new sbLayoutInput('text', '', 'txtsugg', '', 'style="width: 100%;"'));

	$fld = new sbLayoutSelect(array(''), '', 'sugg', 'onChange="suggText();" onDblClick="replace_word();" size="7" style="width: 200px;"');
	$html = '<table cellpadding="0" cellspacing="0">
		<tr>
			<td valign="top">'.$fld->getField().'</td>
			<td valign="top">
				<table cellpadding="0" cellspacing="0">
				<tr>
					<td style="padding-left: 20px;padding-bottom: 10px;">
						<input type="button" name="btnIgnore" value="'.PL_EDITOR_SPELLDLG_IGNORE.'" onClick="ignore_word();" disabled="disabled">
					</td>
					<td style="padding-left: 20px;padding-bottom: 10px;">
						<input type="button" name="btnIgnoreAll" value="'.PL_EDITOR_SPELLDLG_IGNORE_ALL.'" onClick="ignore_all();" disabled="disabled">
					</td>
				</tr>
				<tr>
					<td style="padding-left: 20px;padding-bottom: 10px;">
						<input type="button" name="btnReplace" value="'.PL_EDITOR_FINDDLG_REPLACE_BTN.'" onClick="replace_word();" disabled="disabled">
					</td>
					<td style="padding-left: 20px;padding-bottom: 10px;">
						<input type="button" name="btnReplaceAll" value="'.PL_EDITOR_FINDDLG_REPLACEALL_BTN.'" onClick="replace_all();" disabled="disabled">
					</td>
				</tr>
				<tr>
					<td style="padding-left: 20px;">
						<input type="button" name="btnUndo" value="'.KERNEL_EDITOR_UNDO.'" onClick="undo();" disabled="disabled">
					</td>
					<td>&nbsp;</td>
				</tr>
				</table>
			</td>
		</tr></table>';

	$layout->addField(PL_EDITOR_SPELLDLG_SUGGESTIONS, new sbLayoutHTML($html));

	$layout->show();

	echo '</div></body></html>';
}

function fEditor_Spell_Checker()
{
	function printWords( $word, $index )
	{
		echo 'words['.$index.'] = \''.escapeQuote($word)."';\n";
	}

	function printSuggs( $suggs, $index )
	{
		echo 'suggs['.$index.'] = [';
		if ($suggs)
		{
			foreach( $suggs as $key => $val )
			{
				if( $val )
				{
					echo "'" . escapeQuote( $val ) . "'";
					if ( $key + 1 < count( $suggs ))
					{
						echo ', ';
					}
				}
			}
		}
		echo "];\n";
	}

	function escapeQuote( $str )
	{
		return str_replace("'", "\\'", $str);
	}

	function checkSpelling ( $string )
	{
		if (function_exists('curl_init'))
		{
			$array_var = array('words' => $string);

	        $ch = @curl_init();
	        if ($ch === false)
	        {
	        	return false;
	        }

	        @curl_setopt($ch, CURLOPT_URL, 'http://services.sbuilder.ru/spelling.php');
	        @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
	        @curl_setopt($ch, CURLOPT_POSTFIELDS, $array_var);
	        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	        $response = @curl_exec($ch);

	        if (@curl_errno($ch) > 0)
	        {
	        	@curl_close($ch);
            	return false;
	        }

	        @curl_close($ch);
		}
		else if (function_exists('fsockopen'))
		{
			$str_data = 'words='.urlencode($string);

			$data = "POST http://services.sbuilder.ru/spelling.php HTTP/1.0\r\n";
            $data .= "Host: services.sbuilder.ru\r\n";
            $data .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $data .= "Content-Length: ".sb_strlen($str_data)."\r\n";
            $data .= "Connection: close\r\n\r\n";
            $data .= $str_data."\r\n";

            $f = @fsockopen('services.sbuilder.ru', 80);
            if ($f === false)
            	return false;

            $GLOBALS['sbVfs']->fwrite($data, $f);

            $response = '';

            while (!$GLOBALS['sbVfs']->feof($f))
            {
            	$response .= $GLOBALS['sbVfs']->fgets(256, $f);
            }
            $GLOBALS['sbVfs']->fclose($f);

            if ($response)
            {
            	$response = split("\r\n\r\n", $response);
            	$response = $response[1];
            }
            else
            {
            	return false;
            }
		}
		else
		{
			return false;
		}

		return unserialize($response);
	}

	function printResults($text)
	{
		$text = preg_replace('@<[\/\!]*?[^<>]*?>@U', '', $text);
		$text = sb_html_entity_decode($text);

		$res = checkSpelling($text);

		if ($res !== false && is_array($res))
		{
			$index = 0;
			$words = array();
			foreach ($res as $word => $suggs)
			{
				if (!in_array($word, $words))
				{
					printWords( $word, $index );
					printSuggs( $suggs, $index );
					$index++;
					$words[] = $word;
				}
			}
		}
		else
		{
			echo 'error = true;';
		}
	}

	$text = urldecode($_POST['textinput']);
	$search = array('@<script[^>]*?>.*?</script>@siU',
              		'@<style[^>]*?>.*?</style>@siU',
           			'@<![\s\S]*?--[ \t\n\r]*>@U');

	$text = preg_replace($search, '', $text);
	$text = preg_replace('@(<[^>]*?)((?:alt|title)=[\'"]?[^>]*?[\'"]?)([^>]*?>)@U', '\\1\\3', $text);

	echo '<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset='.strtolower(SB_CHARSET).'">
		<link rel="stylesheet" type="text/css" href="'.SB_CMS_CSS_URL.'/editor/sbSpeller.css" />
		<script src="'.SB_CMS_JSCRIPT_URL.'/sbFunctions.js.php"></script>
		<script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbSpellClasses.js.php"></script>
		<script>
		var suggs = [];
		var words = [];
		var error = false;
		var textinput = decodeURIComponent("'.rawurlencode($text).'");'."\n";

	printResults($text);

	echo '
		var wordWindowObj = new wordWindow();
		wordWindowObj.originalSpellings = words;
		wordWindowObj.suggestions = suggs;
		wordWindowObj.textInput = textinput;

		function init_spell()
		{
			sbAddEvent(document.body, "click", sbCancelEvent);

			if (!error && parent.frames.length)
			{
				parent.init_spell( wordWindowObj );
			}
		}

		if (error)
		{
			alert("'.PL_EDITOR_SPELLDLG_ERROR.'");
			parent.parent.sbCloseDialog();
		}
		</script>
	</head>
	<body onLoad="init_spell();" bgcolor="#ffffff">
	<script>
		wordWindowObj.writeBody();
	</script>
	</body>
	</html>';
}

function fEditor_Color()
{
    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    echo '<link rel="stylesheet" type="text/css" href="'.SB_CMS_CSS_URL.'/sbColorDlg.css">
          <script src="'.SB_CMS_JSCRIPT_URL.'/sbEasyDAD.js.php"></script>
          <script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbColorDlg.js.php"></script>
          <br />';

    $color = (isset($_GET['color']) && $_GET['color'] != '' ? $_GET['color'] : '#407F7F');
    if (strpos($color, 'rgb') !== false)
    {
    	$left = strpos($color, '(') + 1;
    	$right = strpos($color, ')');
    	$color = explode(',', substr($color, $left, $right - $left));
    	$red = dechex(trim($color[0]));
    	$green = dechex(trim($color[1]));
    	$blue = dechex(trim($color[2]));

    	$color = (strlen($red) > 1 ? $red : '0'.$red).(strlen($green) > 1 ? $green : '0'.$green).(strlen($blue) > 1 ? $blue : '0'.$blue);
    }

    $layout = new sbLayout();
    $layout->mShowTitle = false;
    $layout->mTableWidth = '';

    $layout->addHeader(PL_EDITOR_H_COLOR_TITLE);

    $html = '
    <div id="sb_color_main_box">
        <div id="sb_gradient_box">
            <img id="sb_gradient_img" src="'.SB_CMS_IMG_URL.'/color_dlg/gradient.png" width="256" height="256" />
            <img id="sb_circle_img" src="'.SB_CMS_IMG_URL.'/color_dlg/cursor.gif" />
        </div>
        <div id="sb_hue_box">
            <img id="sb_hue_img" src="'.SB_CMS_IMG_URL.'/color_dlg/bar.png" width="19" height="256" />
            <img id="sb_arrows_img" src="'.SB_CMS_IMG_URL.'/color_dlg/arrows.gif" />
        </div>
        <div id="sb_color_box">
            <div id="sb_colors">
                <div id="sb_quick_color"></div>
                <div id="sb_static_color"></div>
            </div>
            <br />
            <table width="100%" id="sb_colors_table">
            <tr>
                <td>'.PL_EDITOR_H_COLOR_HEX.': </td>
                <td><input size="6" type="text" name="sb_color" id="sb_color" onchange="sbHexBoxChanged();" value="'.$color.'" /></td>
            </tr>
            <tr>
                <td>'.PL_EDITOR_H_COLOR_RED.': </td>
                <td><input size="6" type="text" name="spin_red_box" min_value="0" max_value="255" id="spin_red_box" onchange="sbRedBoxChanged();" /></td>
            </tr>
            <tr>
                <td>'.PL_EDITOR_H_COLOR_GREEN.': </td>
                <td><input size="6" type="text" name="spin_green_box" min_value="0" max_value="255" id="spin_green_box" onchange="sbGreenBoxChanged();" /></td>
            </tr>
            <tr>
                <td>'.PL_EDITOR_H_COLOR_BLUE.': </td>
                <td><input size="6" type="text" name="spin_blue_box" min_value="0" max_value="255" id="spin_blue_box" onchange="sbBlueBoxChanged();" /></td>
            </tr>
            <tr>
                <td>'.PL_EDITOR_H_COLOR_HUE.': </td>
                <td><input size="6" type="text" name="spin_hue_box" min_value="0" max_value="360" id="spin_hue_box" onchange="sbHueBoxChanged();" /></td>
            </tr>
            <tr>
                <td>'.PL_EDITOR_H_COLOR_SATURATION.': </td>
                <td><input size="6" type="text" name="spin_saturation_box" min_value="0" max_value="100" incr="0.1" id="spin_saturation_box" onchange="sbSaturationBoxChanged();" /></td>
            </tr>
            <tr>
                <td>'.PL_EDITOR_H_COLOR_VALUE.': </td>
                <td><input size="6" type="text" name="spin_value_box" min_value="0" max_value="100" incr="0.1" id="spin_value_box"onchange="sbValueBoxChanged();" /></td>
            </tr>
            </table>
        </div>
    </div>
    <script src="'.SB_CMS_JSCRIPT_URL.'/sbSpinButton.js.php"></script>
    <script>
        var sbColorCurrent = sbColors.ColorFromHex(sbGetE("sb_color").value);

        new sbDragObj("sb_arrows_img", "sb_hue_box", sbColorArrowsLowBounds, sbColorArrowsUpBounds, sbColorArrowsDown, sbColorArrowsMoved, sbColorEndMovement);
        new sbDragObj("sb_circle_img", "sb_gradient_box", sbColorCircleLowBounds, sbColorCircleUpBounds, sbColorCircleDown, sbColorCircleMoved, sbColorEndMovement);
        sbColorChanged("box");
    </script>';

    $layout->addField('', new sbLayoutHTML($html, true));

    $layout->addButton('button', KERNEL_CHOOSE, '', '', 'onclick="sbColorChoose();"');
    if (isset($_GET['drop_btn']))
    {
    	$layout->addButton('button', KERNEL_CLEAR, '', '', 'onclick="sbColorDrop();"');
    }

    $layout->addButton('button', KERNEL_CANCEL, '', '', 'onclick="sbCloseDialog();"');

    $layout->show();
}

function fEditor_Special_Char()
{
	echo '<link rel="stylesheet" type="text/css" href="'.SB_CMS_CSS_URL.'/editor/sbSpecChars.css">
	<script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbSpecCharsDlg.js.php"></script>
	<div id="incChar" class="char"></div>
	<br><br>';

	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

	$layout = new sbLayout();
	$layout->mShowTitle = false;
	$layout->mTableWidth = '98%';

	$layout->addField('', new sbLayoutHTML('<script>
		document.open();
   		document.write( tab(7,32) );
   		document.close();
    </script>', true) );

	$layout->addButton('button', KERNEL_CANCEL, '', '', 'onclick="sbCloseDialog();"');

	$layout->show();
}

function fEditor_Templates()
{
	echo '<link rel="stylesheet" type="text/css" href="'.SB_CMS_CSS_URL.'/editor/sbTemplates.css">
	<script src="'.SB_CMS_JSCRIPT_URL.'/sbXMLLoader.js.php"></script>
	<script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbTemplatesDlg.js.php"></script>
	<br><br>';

	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

	$layout = new sbLayout();
	$layout->mShowTitle = false;
	$layout->mTableWidth = '95%';

	$layout->addField('', new sbLayoutHTML('<table width="100%" style="height: 100%">
		<tr>
			<td height="100%" align="center" valign="top">
				<div id="eList" align="left" class="sbTplList">
					<div id="eLoading" align="center" style="display: none">
						<br />
						'.KERNEL_LOADING.'
					</div>
					<div id="eEmpty" align="center" style="display: none">
						'.sb_show_message(PL_EDITOR_H_TEMPLATES_NO, true, 'information', true).'
					</div>
				</div>
			</td>
		</tr>
	</table>', true) );

	$layout->addButton('button', KERNEL_CANCEL, '', '', 'onclick="sbCloseDialog();"');

	$layout->show();
}

function fEditor_Paste()
{
	echo '<script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbPasteDlg.js.php"></script>
	<br><br>';

	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

	$layout = new sbLayout();
	$layout->mShowTitle = false;
	$layout->mTableWidth = '95%';

	$layout->addField('', new sbLayoutHTML('<table cellspacing="0" cellpadding="0" width="100%" border="0" style="height: 400px;">
		<tr>
			<td>
				<div id="xSecurityMsg" style="display: none" class="hint_div">
					'.PL_EDITOR_H_PASTE_SECURITY.'<br />
					&nbsp;
				</div>
				<br>
				<div class="hint_div">
					'.PL_EDITOR_H_PASTE_TEXT.'<br />
					&nbsp;
				</div>
				<br>
			</td>
		</tr>
		<tr>
			<td id="xFrameSpace" valign="top" height="100%" style="border: 1px solid #000000;">
				<textarea id="txtData" style="border: 0px; display: none; width: 100%;"></textarea>
			</td>
		</tr>
		<tr id="oWordCommands">
			<td style="padding-top: 10px;">
				<input id="chkRemoveFont" type="checkbox" checked="checked" />
				<label for="chkRemoveFont">'.PL_EDITOR_H_PASTE_WORD1.'</label>
				<br />
				<input id="chkRemoveStyles" type="checkbox" checked="checked" />
				<label for="chkRemoveStyles">'.PL_EDITOR_H_PASTE_WORD2.'</label>
			</td>
		</tr>
	</table>', true) );

	$layout->addButton('button', KERNEL_INSERT, '', '', 'onclick="save();"');
	$layout->addButton('button', KERNEL_CANCEL, '', '', 'onclick="sbCloseDialog();"');

	$layout->show();
}

function fEditor_Image_Preview()
{
	echo '<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title></title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<style type="text/css">
		body
		{
			font-size: 12px;
			font-family: Arial, Tahoma, Verdana;
			padding: 5px;
			color: #909090;
		}
		</style>
		<script>
		window.onload = function()
		{
			window.parent.SetPreviewElements(
			document.getElementById( "imgPreview" ),
			document.getElementById( "lnkPreview" ) ) ;
		}
		</script>
	</head>
	<body>
		<div>
			<a id="lnkPreview" onclick="return false;" style="cursor: default">
			    <img id="imgPreview" style="display: none" alt="" />
			</a>Lorem ipsum dolor sit amet, consectetuer adipiscing
			elit. Maecenas feugiat consequat diam. Maecenas metus. Vivamus diam purus, cursus
			a, commodo non, facilisis vitae, nulla. Aenean dictum lacinia tortor. Nunc iaculis,
			nibh non iaculis aliquam, orci felis euismod neque, sed ornare massa mauris sed
			velit. Nulla pretium mi et risus. Fusce mi pede, tempor id, cursus ac, ullamcorper
			nec, enim. Sed tortor. Curabitur molestie. Duis velit augue, condimentum at, ultrices
			a, luctus ut, orci. Donec pellentesque egestas eros. Integer cursus, augue in cursus
			faucibus, eros pede bibendum sem, in tempus tellus justo quis ligula. Etiam eget
			tortor. Vestibulum rutrum, est ut placerat elementum, lectus nisl aliquam velit,
			tempor aliquam eros nunc nonummy metus. In eros metus, gravida a, gravida sed, lobortis
			id, turpis. Ut ultrices, ipsum at venenatis fringilla, sem nulla lacinia tellus,
			eget aliquet turpis mauris non enim. Nam turpis. Suspendisse lacinia. Curabitur
			ac tortor ut ipsum egestas elementum. Nunc imperdiet gravida mauris.
		</div>
	</body>
	</html>
	';
}

function fEditor_Image()
{
	echo '<style type="text/css">
		  	.ImagePreviewArea
			{
				border: #000000 1px solid;
				overflow: auto;
				width: 100%;
				height: 200px;
				background-color: #ffffff;
			}
		  </style>
		  <script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbDlg.js.php"></script>
		  <script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbImgDlg.js.php"></script>';

	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

	$layout = new sbLayout();
	$layout->mTitleWidth = '170';
	$layout->mTableWidth = '95%';

	$layout->addTab(PL_EDITOR_H_IMAGE_TAB1);
	$layout->addHeader(PL_EDITOR_H_IMAGE_TAB1);

	$fld = new sbLayoutImage('', 'txtUrl', '', 'onchange="UpdatePreview(true);" onkeydown="CheckKey(event);" style="width: 470px;"');
	$fld->mShow = false;
	$layout->addField(PL_EDITOR_H_IMAGE_URL, $fld);

	$html = '<iframe class="ImagePreviewArea" src="'.SB_CMS_EMPTY_FILE.'?event=pl_editor_image_preview" frameborder="0" marginheight="0" marginwidth="0"></iframe>';
	$layout->addField(PL_EDITOR_H_IMAGE_PREVIEW, new sbLayoutHTML($html));

	$layout->addField('', new sbLayoutDelim());
	$layout->addField(PL_EDITOR_H_IMAGE_ALT, new sbLayoutInput('text', '', 'txtAlt', '', 'onblur="UpdatePreview();" style="width: 500px;"'));

	$fld1 = new sbLayoutInput('text', '', 'spin_Width', '', 'onchange="OnSizeChanged(\'Width\', this.value);" style="width: 50px;"');
	$fld1->mMinValue = 0;
	$fld2 = new sbLayoutInput('text', '', 'spin_Height', '', 'onchange="OnSizeChanged(\'Height\', this.value);" style="width: 50px;"');
	$fld2->mMinValue = 0;

	$html = $fld1->getJavaScript().'<table cellpadding="5" cellspacing="0">
	<tr>
		<td style="font-size: 11px;" align="right">'.PL_EDITOR_H_IMAGE_WIDTH.':&nbsp;</td><td>'.$fld1->getField().'</td>
		<td rowspan="2" valign="middle">
			<img src="'.SB_CMS_IMG_URL.'/editor/lock.gif" title="'.PL_EDITOR_H_IMAGE_RATIO.'" onclick="SwitchLock(this);" align="absmiddle" style="cursor: hand; cursor: pointer;" />
			<img src="'.SB_CMS_IMG_URL.'/editor/reset.gif" title="'.PL_EDITOR_H_IMAGE_RESET.'" onclick="ResetSizes();" align="absmiddle" style="cursor: hand; cursor: pointer;" />
		</td>
	</tr>
	<tr>
		<td style="font-size: 11px;" align="right">'.PL_EDITOR_H_IMAGE_HEIGHT.':&nbsp;</td><td>'.$fld2->getField().'</td>
	</tr>
	</table>';

	$layout->addField(PL_EDITOR_H_IMAGE_SIZE, new sbLayoutHTML($html));

	$options = array(
	'' => PL_EDITOR_H_IMAGE_NOT_SELECTED,
	'left' => PL_EDITOR_H_IMAGE_ALIGN_LEFT,
	'absBottom' => PL_EDITOR_H_IMAGE_ALIGN_ABSBOTTOM,
	'absMiddle' => PL_EDITOR_H_IMAGE_ALIGN_ABSMIDDLE,
	'baseline' => PL_EDITOR_H_IMAGE_ALIGN_BASELINE,
	'bottom' => PL_EDITOR_H_IMAGE_ALIGN_BOTTOM,
	'middle' => PL_EDITOR_H_IMAGE_ALIGN_MIDDLE,
	'right' => PL_EDITOR_H_IMAGE_ALIGN_RIGHT,
	'textTop' => PL_EDITOR_H_IMAGE_ALIGN_TEXTTOP,
	'top' => PL_EDITOR_H_IMAGE_ALIGN_TOP
	);

	$layout->addField(PL_EDITOR_H_IMAGE_ALIGN, new sbLayoutSelect($options, 'cmbAlign', '', 'onchange="UpdatePreview();"'));

	$layout->addField('', new sbLayoutDelim());

	$fld = new sbLayoutInput('text', '', 'spin_HSpace', '', 'onchange="UpdatePreview();" style="width: 50px;"');
	$fld->mMinValue = 0;
	$layout->addField(PL_EDITOR_H_IMAGE_HSPACE, $fld);

	$fld = new sbLayoutInput('text', '', 'spin_VSpace', '', 'onchange="UpdatePreview();" style="width: 50px;"');
	$fld->mMinValue = 0;
	$layout->addField(PL_EDITOR_H_IMAGE_VSPACE, $fld);

	$layout->addTab(PL_EDITOR_H_IMAGE_TAB2);
	$layout->addHeader(PL_EDITOR_H_IMAGE_TAB2);

	$fld = new sbLayoutInput('text', '', 'spin_Border', '', 'onchange="UpdatePreview();" style="width: 50px;"');
	$fld->mMinValue = 0;
	$layout->addField(PL_EDITOR_H_TABLE_BORDER, $fld);

	$fld = new sbLayoutColor('', 'txtBorderColor', '', 'onchange="UpdatePreview();"');
	$fld->mDropButton = true;
	$layout->addField(PL_EDITOR_H_TABLE_BORDER_COLOR, $fld);

	$options = array(
	'' => PL_EDITOR_H_IMAGE_NOT_SELECTED,
	'solid' => PL_EDITOR_H_TABLE_BORDER_TYPE_SOLID,
	'dotted' => PL_EDITOR_H_TABLE_BORDER_TYPE_DOTTED,
	'dashed' => PL_EDITOR_H_TABLE_BORDER_TYPE_DASHED
	);

	$layout->addField(PL_EDITOR_H_TABLE_BORDER_TYPE, new sbLayoutSelect($options, 'selBorderType', '', 'onchange="UpdatePreview();"'));

	$layout->addTab(PL_EDITOR_H_IMAGE_TAB3);
	$layout->addHeader(PL_EDITOR_H_IMAGE_TAB3);

	$fld = new sbLayoutPage('', 'txtLnkUrl', '', 'onchange="SetLink();" onblur="UpdatePreview();" style="width: 460px;"');
	$fld->mHTML = '<div class="hint_div">'.PL_EDITOR_H_IMAGE_LINK_HINT.'</div>';

	$layout->addField(PL_EDITOR_H_IMAGE_LINK, $fld);

	$options = array(
	'' => PL_EDITOR_H_IMAGE_NOT_SELECTED,
	'_blank' => PL_EDITOR_H_IMAGE_LINK_TARGET_BLANK,
	'_parent' => PL_EDITOR_H_IMAGE_LINK_TARGET_PARENT,
	'_top' => PL_EDITOR_H_IMAGE_LINK_TARGET_TOP,
	'_self' => PL_EDITOR_H_IMAGE_LINK_TARGET_SELF
	);

	$layout->addField(PL_EDITOR_H_IMAGE_LINK_TARGET, new sbLayoutSelect($options, 'cmbLnkTarget'));

	$layout->addField('', new sbLayoutDelim());

	$fld = new sbLayoutImage('', 'txtPopup', '', 'onchange="SetPopup();" style="width: 460px;"');
	$fld->mHTML = '<div class="hint_div">'.PL_EDITOR_H_IMAGE_POPUP_HINT.'</div>';
	$layout->addField(PL_EDITOR_H_IMAGE_POPUP, $fld);

	$layout->addField(PL_EDITOR_H_IMAGE_POPUP_TITLE, new sbLayoutInput('text', '', 'txtPopupTitle', '', 'style="width: 490px;"'));

	$layout->addTab(PL_EDITOR_H_IMAGE_TAB4);
	$layout->addHeader(PL_EDITOR_H_IMAGE_TAB4);

	$layout->addField(PL_EDITOR_H_IMAGE_ID, new sbLayoutInput('text', '', 'txtAttId', '', 'style="width: 490px;"'));
	$layout->addField(PL_EDITOR_H_IMAGE_ATT_TITLE, new sbLayoutInput('text', '', 'txtAttTitle', '', 'onblur="UpdatePreview();" style="width: 490px;"'));

	$layout->addField('', new sbLayoutDelim());

	$layout->addField(PL_EDITOR_H_IMAGE_CLASS, new sbLayoutInput('text', '', 'txtAttClasses', '', 'onblur="UpdatePreview();" style="width: 490px;"'));
	$layout->addField(PL_EDITOR_H_IMAGE_STYLE, new sbLayoutInput('text', '', 'txtAttStyle', '', 'onblur="UpdatePreview();" style="width: 490px;"'));

	$layout->addButton('button', KERNEL_INSERT, '', '', 'onclick="save();"');
	$layout->addButton('button', KERNEL_CANCEL, '', '', 'onclick="sbCloseDialog();"');

	$layout->show();
}

function fEditor_Flash_Preview()
{
	echo '<html>
	<head>
		<title></title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<style type="text/css">
		body
		{
			color: #000000;
			background-color: #ffffff;
		}
		</style>
		<script language="javascript">
		window.onload = function()
		{
			window.parent.SetPreviewElement( document.body ) ;
		}
		</script>
		</head>
		<body scroll="no"></body>
	</html>';
}

function fEditor_Flash()
{
	echo '<style type="text/css">
		  	.FlashPreviewArea
			{
				border: #000000 1px solid;
				overflow: auto;
				width: 100%;
				height: 300px;
				background-color: #ffffff;
			}
		  </style>
		  <script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbDlg.js.php"></script>
		  <script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbFlashDlg.js.php"></script>';

	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

	$layout = new sbLayout();
	$layout->mTitleWidth = '170';
	$layout->mTableWidth = '95%';

	$layout->addTab(PL_EDITOR_H_FLASH_TAB1);
	$layout->addHeader(PL_EDITOR_H_FLASH_TAB1);

	$fld = new sbLayoutImage('', 'txtUrl', '', 'onchange="UpdatePreview();" onblur="UpdatePreview();" onkeydown="CheckKey(event);" style="width: 470px;"');
	$fld->mShow = false;
	$fld->mFileTypes = 'swf';
	$layout->addField(PL_EDITOR_H_FLASH_URL, $fld);

	$html = '<iframe class="FlashPreviewArea" src="'.SB_CMS_EMPTY_FILE.'?event=pl_editor_flash_preview" frameborder="0" marginheight="0" marginwidth="0"></iframe>';
	$layout->addField(PL_EDITOR_H_FLASH_PREVIEW, new sbLayoutHTML($html));

	$layout->addField('', new sbLayoutDelim());

	$fld1 = new sbLayoutInput('text', '', 'spin_Width', '', 'style="width: 50px;"');
	$fld1->mMinValue = 0;
	$fld2 = new sbLayoutInput('text', '', 'spin_Height', '', 'style="width: 50px;"');
	$fld2->mMinValue = 0;

	$html = $fld1->getJavaScript().'<table cellpadding="5" cellspacing="0">
	<tr>
		<td style="font-size: 11px;" align="right">'.PL_EDITOR_H_FLASH_WIDTH.':&nbsp;</td><td>'.$fld1->getField().'</td>
	</tr>
	<tr>
		<td style="font-size: 11px;" align="right">'.PL_EDITOR_H_FLASH_HEIGHT.':&nbsp;</td><td>'.$fld2->getField().'</td>
	</tr>
	</table>';

	$layout->addField(PL_EDITOR_H_FLASH_SIZE, new sbLayoutHTML($html));

	$layout->addTab(PL_EDITOR_H_FLASH_TAB2);
	$layout->addHeader(PL_EDITOR_H_FLASH_TAB2);

	$options = array(
	'' => PL_EDITOR_H_IMAGE_NOT_SELECTED,
	'showall' => PL_EDITOR_H_FLASH_SCALE_ALL,
	'noborder' => PL_EDITOR_H_FLASH_SCALE_NOBORDER,
	'exactfit' => PL_EDITOR_H_FLASH_SCALE_FIT
	);

	$layout->addField(PL_EDITOR_H_FLASH_SCALE, new sbLayoutSelect($options, 'cmbScale'));

	$layout->addField('', new sbLayoutDelim());

	$layout->addField(PL_EDITOR_H_FLASH_AUTO, new sbLayoutInput('checkbox', '', 'chkAutoPlay', '', 'checked="checked"'));
	$layout->addField(PL_EDITOR_H_FLASH_LOOP, new sbLayoutInput('checkbox', '', 'chkLoop', '', 'checked="checked"'));
	$layout->addField(PL_EDITOR_H_FLASH_TRANS, new sbLayoutInput('checkbox', '', 'chkTrans', '', 'checked="checked"'));
	$layout->addField(PL_EDITOR_H_FLASH_MENU, new sbLayoutInput('checkbox', '', 'chkMenu'));

	$layout->addField('', new sbLayoutDelim());

	$layout->addField(PL_EDITOR_H_IMAGE_ID, new sbLayoutInput('text', '', 'txtAttId', '', 'style="width: 500px;"'));
	$layout->addField(PL_EDITOR_H_IMAGE_ATT_TITLE, new sbLayoutInput('text', '', 'txtAttTitle', '', 'style="width: 500px;"'));

	$layout->addField('', new sbLayoutDelim());

	$layout->addField(PL_EDITOR_H_IMAGE_CLASS, new sbLayoutInput('text', '', 'txtAttClasses', '', 'style="width: 500px;"'));
	$layout->addField(PL_EDITOR_H_IMAGE_STYLE, new sbLayoutInput('text', '', 'txtAttStyle', '', 'style="width: 500px;"'));

	$layout->addButton('button', KERNEL_INSERT, '', '', 'onclick="save();"');
	$layout->addButton('button', KERNEL_CANCEL, '', '', 'onclick="sbCloseDialog();"');

	$layout->show();
}

function fEditor_Movie()
{
	echo '<style type="text/css">
		  	.FlashPreviewArea
			{
				border: #000000 1px solid;
				overflow: auto;
				width: 100%;
				height: 250px;
				background-color: #ffffff;
			}
		  </style>
		  <script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbDlg.js.php"></script>
		  <script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbVideoDlg.js.php"></script>';

	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

	$layout = new sbLayout();
	$layout->mTitleWidth = '170';
	$layout->mTableWidth = '95%';

	$layout->addTab(PL_EDITOR_H_MOVIE_TAB1);
	$layout->addHeader(PL_EDITOR_H_MOVIE_TAB1);

	$options = array(
	'avi' => PL_EDITOR_H_MOVIE_TYPE_AVI,
	'mov' => PL_EDITOR_H_MOVIE_TYPE_MOV,
	'rm' => PL_EDITOR_H_MOVIE_TYPE_RM
	);

	$layout->addField(PL_EDITOR_H_MOVIE_TYPE, new sbLayoutSelect($options, 'cmbType', '', 'onchange="setMimeType(this.value);UpdatePreview();"'));

	$fld = new sbLayoutImage('', 'txtUrl', '', 'onchange="UpdatePreview();" onkeydown="CheckKey(event);" style="width: 470px;"');
	$fld->mShow = false;
	$fld->mFileTypes = 'avi mov rm mpeg mpeg4 mpg';
	$layout->addField(PL_EDITOR_H_FLASH_URL, $fld);

	$html = '<iframe class="FlashPreviewArea" src="'.SB_CMS_EMPTY_FILE.'?event=pl_editor_flash_preview" frameborder="0" marginheight="0" marginwidth="0"></iframe>';
	$layout->addField(PL_EDITOR_H_FLASH_PREVIEW, new sbLayoutHTML($html));

	$layout->addField('', new sbLayoutDelim());

	$fld1 = new sbLayoutInput('text', '', 'spin_Width', '', 'style="width: 50px;" onchange="UpdatePreview();"');
	$fld1->mMinValue = 0;
	$fld2 = new sbLayoutInput('text', '', 'spin_Height', '', 'style="width: 50px;" onchange="UpdatePreview();"');
	$fld2->mMinValue = 0;

	$html = $fld1->getJavaScript().'<table cellpadding="5" cellspacing="0">
	<tr>
		<td style="font-size: 11px;" align="right">'.PL_EDITOR_H_FLASH_WIDTH.':&nbsp;</td><td>'.$fld1->getField().'</td>
	</tr>
	<tr>
		<td style="font-size: 11px;" align="right">'.PL_EDITOR_H_FLASH_HEIGHT.':&nbsp;</td><td>'.$fld2->getField().'</td>
	</tr>
	</table>';

	$layout->addField(PL_EDITOR_H_FLASH_SIZE, new sbLayoutHTML($html));

	$layout->addTab(PL_EDITOR_H_FLASH_TAB2);
	$layout->addHeader(PL_EDITOR_H_FLASH_TAB2);

	$layout->addField(PL_EDITOR_H_FLASH_AUTO, new sbLayoutInput('checkbox', '', 'chkAutoPlay', '', 'checked="checked"'));
	$layout->addField(PL_EDITOR_H_FLASH_LOOP, new sbLayoutInput('checkbox', '', 'chkLoop', '', 'checked="checked"'));

	$layout->addField('', new sbLayoutDelim());

	$layout->addField(PL_EDITOR_H_IMAGE_ID, new sbLayoutInput('text', '', 'txtAttId', '', 'style="width: 500px;"'));

	$layout->addButton('button', KERNEL_INSERT, '', '', 'onclick="save();"');
	$layout->addButton('button', KERNEL_CANCEL, '', '', 'onclick="sbCloseDialog();"');

	$layout->show();
}

function fEditor_Link()
{
	echo '<script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbDlg.js.php"></script>
		  <script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbLinkDlg.js.php"></script>';

	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

	$layout = new sbLayout();
	$layout->mTitleWidth = '170';
	$layout->mTableWidth = '95%';

	$layout->addTab(PL_EDITOR_H_LINK_TAB1);
	$layout->addHeader(PL_EDITOR_H_LINK_TAB1);

	$options = array(
	'url' => PL_EDITOR_H_LINK_TYPE_URL,
	'popup' => PL_EDITOR_H_LINK_TYPE_POPUP,
	'file' => PL_EDITOR_H_LINK_TYPE_FILE,
	'anchor' => PL_EDITOR_H_LINK_TYPE_ANCHOR,
	'email' => PL_EDITOR_H_LINK_TYPE_MAIL
	);

	$layout->addField(PL_EDITOR_H_LINK_TYPE, new sbLayoutSelect($options, 'cmbLinkType', '', 'onchange="SetLinkType(this.value);"'));

	$layout->addField('', new sbLayoutDelim());

	$layout->addField(PL_EDITOR_H_LINK_HREF, new sbLayoutPage('', 'txtUrl', '', 'onchange="SetLink();" style="width: 450px;"'), '', '', 'id="url_tr"');
	$layout->addField(PL_EDITOR_H_LINK_HREF, new sbLayoutFile('', 'txtFile', '', 'style="width: 450px;"'), '', '', 'id="file_tr"');

	$options = array(
	'' => PL_EDITOR_H_IMAGE_NOT_SELECTED,
    '_blank' => PL_EDITOR_H_IMAGE_LINK_TARGET_BLANK,
    '_parent' => PL_EDITOR_H_IMAGE_LINK_TARGET_PARENT,
    '_top' => PL_EDITOR_H_IMAGE_LINK_TARGET_TOP,
    '_self' => PL_EDITOR_H_IMAGE_LINK_TARGET_SELF,
	);

	$layout->addField(PL_EDITOR_H_IMAGE_LINK_TARGET, new sbLayoutSelect($options, 'cmbTarget'), '', '', 'id="target_tr"');

	$fld = new sbLayoutImage('', 'txtPopup', '', 'onchange="SetPopup();" style="width: 450px;"');
	$fld->mHTML = '<div class="hint_div">'.PL_EDITOR_H_LINK_POPUP_HINT.'</div>';
	$layout->addField(PL_EDITOR_H_IMAGE_POPUP, $fld, '', '', 'id="popup_tr"');

	$layout->addField(PL_EDITOR_H_IMAGE_POPUP_TITLE, new sbLayoutInput('text', '', 'txtPopupTitle', '', 'style="width: 480px;"'), '', '', 'id="popup_title_tr"');

	$layout->addField('', new sbLayoutDelim('id="delim_tr"'));

	$layout->addField(PL_EDITOR_H_IMAGE_LINK_SHADOWBOX, new sbLayoutInput('checkbox', '1', 'chkShadowbox', '', 'onclick="SetShadowbox(this);"'), '', '', 'id="shadowbox_tr"');

	$options = array('' => '');
	$fld1 = new sbLayoutSelect($options, 'cmbAnchorName', '', 'onchange="sbGetE(\'cmbAnchorId\').value=\'\';"');
	$fld2 = new sbLayoutSelect($options, 'cmbAnchorId', '', 'onchange="sbGetE(\'cmbAnchorName\').value=\'\';"');

	$html = '<div id="divSelAnchor" style="DISPLAY: none">
	<table cellpadding="5" cellspacing="0">
	<tr id="anchor_name_tr"><td>'.PL_EDITOR_H_LINK_ANCHOR_NAME.'&nbsp;</td><td>'.$fld1->getField().'</td></tr>
	<tr id="anchor_id_tr"><td>'.PL_EDITOR_H_LINK_ANCHOR_ID.'&nbsp;</td><td>'.$fld2->getField().'</td></tr>
	</table>
	</div>
	<div id="divNoAnchor" class="hint_div">'.PL_EDITOR_H_LINK_NO_ANCHOR.'</div>';

	$layout->addField(PL_EDITOR_H_LINK_ANCHOR, new sbLayoutHTML($html), '', '', 'id="anchor_tr"');

	$layout->addField(PL_EDITOR_H_LINK_MAIL, new sbLayoutInput('text', '', 'txtEMailAddress', '', 'style="width: 480px;"'), '', '', 'id="mail_tr"');
	$layout->addField(PL_EDITOR_H_LINK_MAIL_SUBJ, new sbLayoutInput('text', '', 'txtEMailSubject', '', 'style="width: 480px;"'), '', '', 'id="mail_subj_tr"');
	$fld = new sbLayoutTextarea('', 'txtEMailBody', '', 'style="width: 480px; height: 100px;"');
	$fld->mShowToolbar = false;
	$fld->mShowEnlargeBtn = false;
	$layout->addField(PL_EDITOR_H_LINK_MAIL_TEXT, $fld, '', '', 'id="mail_text_tr"');

	$layout->addTab(PL_EDITOR_H_LINK_TAB2);
	$layout->addHeader(PL_EDITOR_H_LINK_TAB2);

	$layout->addField(PL_EDITOR_H_IMAGE_ID, new sbLayoutInput('text', '', 'txtAttId', '', 'style="width: 480px;"'));
	$layout->addField(PL_EDITOR_H_LINK_NAME, new sbLayoutInput('text', '', 'txtAttName', '', 'style="width: 480px;"'));
	$layout->addField(PL_EDITOR_H_IMAGE_ATT_TITLE, new sbLayoutInput('text', '', 'txtAttTitle', '', 'style="width: 480px;"'));

	$layout->addField('', new sbLayoutDelim());

	$layout->addField(PL_EDITOR_H_IMAGE_CLASS, new sbLayoutInput('text', '', 'txtAttClasses', '', 'style="width: 480px;"'));
	$layout->addField(PL_EDITOR_H_IMAGE_STYLE, new sbLayoutInput('text', '', 'txtAttStyle', '', 'style="width: 480px;"'));

	$layout->addButton('button', KERNEL_INSERT, '', '', 'onclick="save();"');
	$layout->addButton('button', KERNEL_CANCEL, '', '', 'onclick="sbCloseDialog();"');

	$layout->show();
}

function fEditor_Anchor()
{
	echo '<script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbDlg.js.php"></script>
		  <script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbAnchorDlg.js.php"></script>
		  <br><br>';

	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

	$layout = new sbLayout();
	$layout->mTableWidth = '95%';

	$layout->addHeader(PL_EDITOR_H_ANCHOR_HEADER);

	$layout->addField(PL_EDITOR_H_ANCHOR_NAME, new sbLayoutInput('text', '', 'txtName', '', 'style="width: 400px;" onkeyup="setSaveBtn(this);"'));

	$layout->addButton('button', KERNEL_INSERT, 'btnSave', '', 'onclick="save();"');
	$layout->addButton('button', KERNEL_CANCEL, '', '', 'onclick="sbCloseDialog();";');

	$layout->show();
}

function fEditor_Imagemap()
{
	echo '<style type="text/css">
		.sbmap
		{
			border: 1px solid green;
			position: absolute;
			font-size: 1px;
			background-color: gray;
			filter: alpha(opacity=40);
    		opacity: 0.40;
		}
		</style>
		<script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbDlg.js.php"></script>
		<script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbImagemapDlg.js.php"></script>
		<br>';

	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

	$layout = new sbLayout();
	$layout->mTitleWidth = '170';
	$layout->mTableWidth = '95%';

	$layout->addHeader(PL_EDITOR_H_IMAGEMAP_HEADER);

	$layout->addField(PL_EDITOR_H_LINK_HREF, new sbLayoutPage('', 'txtUrl', '', 'onkeyup="updateArea();" style="width: 500px;"'));

	$options = array(
	'' => PL_EDITOR_H_IMAGE_NOT_SELECTED,
    '_blank' => PL_EDITOR_H_IMAGE_LINK_TARGET_BLANK,
    '_parent' => PL_EDITOR_H_IMAGE_LINK_TARGET_PARENT,
    '_top' => PL_EDITOR_H_IMAGE_LINK_TARGET_TOP,
    '_self' => PL_EDITOR_H_IMAGE_LINK_TARGET_SELF,
	);

	$layout->addField(PL_EDITOR_H_IMAGE_LINK_TARGET, new sbLayoutSelect($options, 'cmbTarget', '', 'onchange="updateArea();"'));

	$layout->addField('', new sbLayoutDelim());

	$layout->addField(PL_EDITOR_H_IMAGE_ALT, new sbLayoutInput('text', '', 'txtAlt', '', 'style="width: 530px;" onkeyup="updateArea();"'));
	$layout->addField(PL_EDITOR_H_IMAGE_ATT_TITLE, new sbLayoutInput('text', '', 'txtTitle', '', 'style="width: 530px;" onkeyup="updateArea();"'));

	$layout->addField('', new sbLayoutDelim());

	$html = '<table cellpadding="3" cellspacing="0">
    		<tr>
    			<td style="font-size: 11px;">X:&nbsp;</td><td style="font-size: 11px;"><nobr><span id="areaX"></span></nobr></td>
    			<td style="font-size: 11px;">Y:&nbsp;</td><td style="font-size: 11px;"><nobr><span id="areaY"></span></nobr></td></tr>
    		<tr>
    			<td style="font-size: 11px;">'.PL_EDITOR_H_IMAGEMAP_WIDTH.':&nbsp;</td><td style="font-size: 11px;"><nobr><span id="areaWidth"></span></nobr></td>
    			<td style="font-size: 11px;">'.PL_EDITOR_H_IMAGEMAP_HEIGHT.':&nbsp;</td><td style="font-size: 11px;"><nobr><span id="areaHeight"></span></nobr></td>
    		</tr>
    		</table>';

	$layout->addField(PL_EDITOR_H_IMAGEMAP_INFO, new sbLayoutHTML($html));

	$layout->addButton('button', KERNEL_SAVE, 'btnSave', '', 'onclick="save();"');
	$layout->addButton('button', KERNEL_CANCEL, '', '', 'onclick="sbCloseDialog();";');

	$layout->show();

	echo '<br><br>
	<script>
	var imgAttr = "";
	if (imgHeight)
		imgAttr	= " height=" + imgHeight + " ";
	if (imgWidth)
		imgAttr	+= " width=" + imgWidth + " ";
	document.write(\'<center><img id="editedImg" src="\'+imgSrc+\'" \'+imgAttr+\' style="cursor:crosshair;" alt="" border="1" onmousedown="createMap(event, false);" ondragstart="sbCancelEvent(event);"></center>\');
	</script>
	<br><br>';
}

function fEditor_Table()
{
	echo '<script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbDlg.js.php"></script>
		<script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbTableDlg.js.php"></script>';

	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

	$layout = new sbLayout();
	$layout->mTableWidth = '95%';

	$layout->addTab(PL_EDITOR_H_TABLE_TAB1);
	$layout->addHeader(PL_EDITOR_H_TABLE_TAB1);

	$fld = new sbLayoutInput('text', '3', 'spin_Rows', '', 'style="width: 50px;"');
	$fld->mMinValue = 0;
	$fld->mMaxValue = 999;

	$layout->addField(PL_EDITOR_H_TABLE_ROWS, $fld);

	$fld = new sbLayoutInput('text', '3', 'spin_Cols', '', 'style="width: 50px;"');
	$fld->mMinValue = 0;
	$fld->mMaxValue = 999;

	$layout->addField(PL_EDITOR_H_TABLE_COLS, $fld);

	$layout->addField('', new sbLayoutDelim());

	$fld = new sbLayoutInput('text', '300', 'spin_Width', '', 'style="width: 50px;"');
	$fld->mMinValue = 0;
	$html = '<table cellpadding="0" cellspacing="0"><tr><td>'.$fld->getField().'</td>
			<td>&nbsp;&nbsp;
			<select id="selWidthType">
			<option value="pixels" selected="selected">px</option>
			<option value="percent">%</option>
			</select>
			</td></tr></table>';

	$layout->addField(PL_EDITOR_H_TABLE_WIDTH, new sbLayoutHTML($html));

	$fld = new sbLayoutInput('text', '', 'spin_Height', '', 'style="width: 50px;"');
	$fld->mMinValue = 0;
	$html = '<table cellpadding="0" cellspacing="0"><tr><td>'.$fld->getField().'</td>
			<td>&nbsp;&nbsp;
			<select id="selHeightType">
			<option value="pixels" selected="selected">px</option>
			<option value="percent">%</option>
			</select>
			</td></tr></table>';

	$layout->addField(PL_EDITOR_H_TABLE_HEIGHT, new sbLayoutHTML($html));

	$layout->addField('', new sbLayoutDelim());

	$fld = new sbLayoutInput('text', '1', 'spin_CellSpacing', '', 'style="width: 50px;"');
	$fld->mMinValue = 0;

	$layout->addField(PL_EDITOR_H_TABLE_SPACING, $fld);

	$fld = new sbLayoutInput('text', '1', 'spin_CellPadding', '', 'style="width: 50px;"');
	$fld->mMinValue = 0;

	$layout->addField(PL_EDITOR_H_TABLE_PADDING, $fld);

	$layout->addField('', new sbLayoutDelim());

	$options = array(
	'' => PL_EDITOR_H_IMAGE_NOT_SELECTED,
	'left' => PL_EDITOR_H_TABLE_ALIGN_LEFT,
	'center' => PL_EDITOR_H_TABLE_ALIGN_CENTER,
	'right' => PL_EDITOR_H_TABLE_ALIGN_RIGHT
	);

	$layout->addField(PL_EDITOR_H_TABLE_ALIGN, new sbLayoutSelect($options, 'selAlignment'));

	$layout->addTab(PL_EDITOR_H_TABLE_TAB2);
	$layout->addHeader(PL_EDITOR_H_TABLE_TAB2);

	$fld = new sbLayoutInput('text', '', 'spin_Border', '', 'style="width: 50px;"');
	$fld->mMinValue = 0;
	$layout->addField(PL_EDITOR_H_TABLE_BORDER, $fld);

	$fld = new sbLayoutColor('', 'txtBorderColor');
	$fld->mDropButton = true;
	$layout->addField(PL_EDITOR_H_TABLE_BORDER_COLOR, $fld);

	$options = array(
	'' => PL_EDITOR_H_IMAGE_NOT_SELECTED,
	'solid' => PL_EDITOR_H_TABLE_BORDER_TYPE_SOLID,
	'dotted' => PL_EDITOR_H_TABLE_BORDER_TYPE_DOTTED,
	'dashed' => PL_EDITOR_H_TABLE_BORDER_TYPE_DASHED
	);

	$layout->addField(PL_EDITOR_H_TABLE_BORDER_TYPE, new sbLayoutSelect($options, 'selBorderType'));

	$layout->addTab(PL_EDITOR_H_TABLE_TAB3);
	$layout->addHeader(PL_EDITOR_H_TABLE_TAB3);

	$layout->addField(PL_EDITOR_H_TABLE_BACK_COLOR, new sbLayoutColor('', 'txtBackColor'));

	$layout->addField('', new sbLayoutDelim());

	$fld = new sbLayoutImage('', 'txtBackImage', '', 'style="width: 450px;"');
	$layout->addField(PL_EDITOR_H_TABLE_BACK_IMAGE, $fld);

	$options = array(
	'' => PL_EDITOR_H_IMAGE_NOT_SELECTED,
	'no-repeat' => PL_EDITOR_H_TABLE_BACK_IMAGE_POS_NO_REPEAT,
	'repeat' => PL_EDITOR_H_TABLE_BACK_IMAGE_POS_REPEAT,
	'repeat-x' => PL_EDITOR_H_TABLE_BACK_IMAGE_POS_REPEAT_X,
	'repeat-y' => PL_EDITOR_H_TABLE_BACK_IMAGE_POS_REPEAT_Y
	);

	$layout->addField(PL_EDITOR_H_TABLE_BACK_IMAGE_POS, new sbLayoutSelect($options, 'selBackImagePos'));

	$layout->addTab(PL_EDITOR_H_TABLE_TAB4);
	$layout->addHeader(PL_EDITOR_H_TABLE_TAB4);

	$layout->addField(PL_EDITOR_H_TABLE_ID, new sbLayoutInput('text', '', 'txtAttId', '', 'style="width: 480px;"'));
	$layout->addField(PL_EDITOR_H_IMAGE_CLASS, new sbLayoutInput('text', '', 'txtAttClasses', '', 'style="width: 480px;"'));
	$layout->addField(PL_EDITOR_H_IMAGE_STYLE, new sbLayoutInput('text', '', 'txtAttStyle', '', 'style="width: 480px;"'));

	$layout->addField('', new sbLayoutDelim());

	$layout->addField(PL_EDITOR_H_TABLE_CAPTION, new sbLayoutInput('text', '', 'txtCaption', '', 'style="width: 480px;"'));
	$layout->addField(PL_EDITOR_H_TABLE_SUMMARY, new sbLayoutInput('text', '', 'txtSummary', '', 'style="width: 480px;"'));

	$layout->addButton('button', KERNEL_SAVE, 'btnSave', '', 'onclick="save();"');
	$layout->addButton('button', KERNEL_CANCEL, '', '', 'onclick="sbCloseDialog();";');

	$layout->show();
}

function fEditor_Table_Cell()
{
	echo '<script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbDlg.js.php"></script>
		  <script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbTableCellDlg.js.php"></script>';

	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

	$layout = new sbLayout();
	$layout->mTableWidth = '95%';

	if ($_GET['block'] == 'cell')
	{
		$layout->addTab(PL_EDITOR_H_TABLE_CELL_TAB1);
		$layout->addHeader(PL_EDITOR_H_TABLE_CELL_TAB1);
	}
	else
	{
		$layout->addTab(PL_EDITOR_H_TABLE_BLOCK_TAB1);
		$layout->addHeader(PL_EDITOR_H_TABLE_BLOCK_TAB1);
	}

	$fld = new sbLayoutInput('text', '', 'spin_Width', '', 'style="width: 50px;"');
	$fld->mMinValue = 0;
	$html = '<table cellpadding="0" cellspacing="0"><tr><td>'.$fld->getField().'</td>
			<td>&nbsp;&nbsp;
			<select id="selWidthType">
			<option value="pixels" selected="selected">px</option>
			<option value="percent">%</option>
			</select>
			</td></tr></table>';

	$layout->addField(PL_EDITOR_H_TABLE_WIDTH, new sbLayoutHTML($html));

	$fld = new sbLayoutInput('text', '', 'spin_Height', '', 'style="width: 50px;"');
	$fld->mMinValue = 0;
	$html = '<table cellpadding="0" cellspacing="0"><tr><td>'.$fld->getField().'</td>
			<td>&nbsp;&nbsp;
			<select id="selHeightType">
			<option value="pixels" selected="selected">px</option>
			<option value="percent">%</option>
			</select>
			</td></tr></table>';

	$layout->addField(PL_EDITOR_H_TABLE_HEIGHT, new sbLayoutHTML($html));

	$layout->addField('', new sbLayoutDelim());

    // Отступы ячеек таблицы
    $fields  = array('Top', 'Bottom', 'Left', 'Right');
    foreach ($fields as $f) {
        $fld = new sbLayoutInput('text', '', 'spin_Padding'.$f, '', 'style="width: 50px;"');
        $fld->mMinValue = 0;
        $html = '<table cellpadding="0" cellspacing="0"><tr><td>'.$fld->getField().'</td>
			<td>&nbsp;&nbsp;
			<select id="selPadding'.$f.'Type">
			<option value="pixels" selected="selected">px</option>
			<option value="percent">%</option>
			</select>
			</td></tr></table>';
        $layout->addField(constant(PL_EDITOR_H_TABLE_CELL_PADDING_.sb_strtoupper($f)), new sbLayoutHTML($html));
    }

	$layout->addField('', new sbLayoutDelim());

	$fld1 = new sbLayoutInput('text', '', 'spin_LineHeight', '', 'style="width: 50px;" disabled="disabled"');
	$fld1->mMinValue = 0;
	$fld1->mIncrement = 0.1;

	$options = array(
	'' => PL_EDITOR_H_IMAGE_NOT_SELECTED,
	'1.2' => PL_EDITOR_H_TABLE_CELL_LINE_HEIGHT_1,
	'1.5' => PL_EDITOR_H_TABLE_CELL_LINE_HEIGHT_2,
	'2' => PL_EDITOR_H_TABLE_CELL_LINE_HEIGHT_3,
	'other' => PL_EDITOR_H_TABLE_CELL_LINE_HEIGHT_4
	);
	$fld2 = new sbLayoutSelect($options, 'selLineHeight', '', 'onchange="changeInput(this, \'spin_LineHeight\')"');

	$html = '<table cellpadding="0" cellspacing="0"><tr><td>'.$fld2->getField().'</td><td style="padding-left: 13px;">'.$fld1->getField().'</td></tr></table>';
	$layout->addField(PL_EDITOR_H_TABLE_CELL_LINE_HEIGHT, new sbLayoutHTML($html));

	$fld1 = new sbLayoutInput('text', '', 'spin_TextIdent', '', 'style="width: 50px;" disabled="disabled"');
	$fld1->mMinValue = 0;
	$fld1->mIncrement = 0.1;

	$options = array(
	'' => PL_EDITOR_H_IMAGE_NOT_SELECTED,
	'20px' => PL_EDITOR_H_TABLE_CELL_TEXT_IDENT_1,
	'-20px' => PL_EDITOR_H_TABLE_CELL_TEXT_IDENT_2,
	'other' => PL_EDITOR_H_TABLE_CELL_LINE_HEIGHT_4
	);
	$fld2 = new sbLayoutSelect($options, 'selTextIdent', '', 'onchange="changeInput(this, \'spin_TextIdent\')"');

	$html = '<table cellpadding="0" cellspacing="0"><tr><td>'.$fld2->getField().'</td><td style="padding-left: 20px;">'.$fld1->getField().'</td></tr></table>';
	$layout->addField(PL_EDITOR_H_TABLE_CELL_TEXT_IDENT, new sbLayoutHTML($html));

	$layout->addTab(PL_EDITOR_H_TABLE_TAB2);
	$layout->addHeader(PL_EDITOR_H_TABLE_TAB2);

	$fld = new sbLayoutInput('text', '', 'spin_Border', '', 'style="width: 50px;"');
	$fld->mMinValue = 0;
	$layout->addField(PL_EDITOR_H_TABLE_BORDER, $fld);

	$fld = new sbLayoutColor('', 'txtBorderColor');
	$fld->mDropButton = true;
	$layout->addField(PL_EDITOR_H_TABLE_BORDER_COLOR, $fld);

	$options = array(
	'' => PL_EDITOR_H_IMAGE_NOT_SELECTED,
	'solid' => PL_EDITOR_H_TABLE_BORDER_TYPE_SOLID,
	'dotted' => PL_EDITOR_H_TABLE_BORDER_TYPE_DOTTED,
	'dashed' => PL_EDITOR_H_TABLE_BORDER_TYPE_DASHED
	);

	$layout->addField(PL_EDITOR_H_TABLE_BORDER_TYPE, new sbLayoutSelect($options, 'selBorderType'));

	$layout->addTab(PL_EDITOR_H_TABLE_TAB3);
	$layout->addHeader(PL_EDITOR_H_TABLE_TAB3);

	$fld = new sbLayoutColor('', 'txtBackColor');
	$fld->mDropButton = true;
	$layout->addField(PL_EDITOR_H_TABLE_BACK_COLOR, $fld);

	$layout->addField('', new sbLayoutDelim());

	$fld = new sbLayoutImage('', 'txtBackImage', '', 'style="width: 450px;"');
	$layout->addField(PL_EDITOR_H_TABLE_BACK_IMAGE, $fld);

	$options = array(
	'' => PL_EDITOR_H_IMAGE_NOT_SELECTED,
	'no-repeat' => PL_EDITOR_H_TABLE_BACK_IMAGE_POS_NO_REPEAT,
	'repeat' => PL_EDITOR_H_TABLE_BACK_IMAGE_POS_REPEAT,
	'repeat-x' => PL_EDITOR_H_TABLE_BACK_IMAGE_POS_REPEAT_X,
	'repeat-y' => PL_EDITOR_H_TABLE_BACK_IMAGE_POS_REPEAT_Y
	);

	$layout->addField(PL_EDITOR_H_TABLE_BACK_IMAGE_POS, new sbLayoutSelect($options, 'selBackImagePos'));

	$layout->addTab(PL_EDITOR_H_TABLE_TAB4);
	$layout->addHeader(PL_EDITOR_H_TABLE_TAB4);

	$layout->addField(PL_EDITOR_H_TABLE_ID, new sbLayoutInput('text', '', 'txtAttId', '', 'style="width: 480px;"'));
	$layout->addField(PL_EDITOR_H_IMAGE_CLASS, new sbLayoutInput('text', '', 'txtAttClasses', '', 'style="width: 480px;"'));
	$layout->addField(PL_EDITOR_H_IMAGE_STYLE, new sbLayoutInput('text', '', 'txtAttStyle', '', 'style="width: 480px;"'));

	$layout->addButton('button', KERNEL_SAVE, 'btnSave', '', 'onclick="save();"');
	$layout->addButton('button', KERNEL_CANCEL, '', '', 'onclick="sbCloseDialog();";');

	$layout->show();
}

function fEditor_Popup()
{
	require_once SB_CMS_LIB_PATH.'/sbVisualEditor.inc.php';

	$editor = new sbVisualEditor();
	$editor->show();

	echo '<script>
		var args = (_isIE || _isFF ? dialogArguments : window.opener.sbModalDialog.args);
		if (!args)
			sbCloseDialog();

		sbGetE("sb_editor_value").value = args.value;
	</script>';
}

function fEditor_Full()
{
	require_once(SB_CMS_LIB_PATH.'/sbTemplates.inc.php');

    $template = new sbTemplates(intval($_GET['p_id']), $error);

    if ($error)
    {
        sb_show_message(PL_EDITOR_H_PAGE_NOT_FOUND, true, 'warning');
		return;
    }

    $id = $template->printToFile();
    if ($id)
    {
    	header('Location: /cms/admin/editor.php?id='.intval($id));
    	exit(0);
    }
}

function fEditor_Toolbar()
{
	$fonts = sbPlugins::getSetting('sb_editor_fonts');
	if (is_null($fonts))
	{
		$fonts = 'Arial;Tahoma;Times New Roman;Verdana;Comic Sans MS;Courier New;Georgia';
	}

	$font_sizes = sbPlugins::getSetting('sb_editor_font_sizes');
	if (is_null($font_sizes))
	{
		$font_sizes = '8px;9px;10px;11px;12px;14px;16px;18px;20px;22px;24px;26px;28px;36px;48px;72px';
	}

	echo '
	<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
	<html>
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset='.SB_CHARSET.'">
			<script>
			var sbEditorAreaWnd = null ;
            var sbEditorAreaDoc = null ;

            var sbEditorFonts = new String("'.$fonts.'").split(";");
            var sbEditorFontSizes = new String("'.$font_sizes.'").split(";");
            </script>
			<script src="'.SB_CMS_JSCRIPT_URL.'/sbFunctions.js.php"></script>
        	<script src="'.SB_CMS_JSCRIPT_URL.'/sbAJAX.js.php"></script>
        	<script src="'.SB_CMS_JSCRIPT_URL.'/sbXMLLoader.js.php"></script>
			<script src="'.SB_CMS_JSCRIPT_URL.'/sbSpecialCombo.js.php"></script>
			<script>

			var sb_cms_img_url = "'.SB_CMS_IMG_URL.'";

			var SB_EDITOR_ENTER_MODE = "'.sbPlugins::getUserSetting('sb_editor_enter_mode').'";
			var SB_EDITOR_SHIFT_ENTER_MODE = "'.sbPlugins::getUserSetting('sb_editor_shift_enter_mode').'" ;
			var SB_EDITOR_TAB_SPACES = '.intval(sbPlugins::getUserSetting('sb_editor_tab_spaces')).';
			var SB_EDITOR_IDENT_OFFSET = '.intval(sbPlugins::getUserSetting('sb_editor_ident_offset')).';
			var SB_EDITOR_STRONG_OVERRIDE = '.(sbPlugins::getUserSetting('sb_editor_strong') ? 'true' : 'false').';
			var SB_EDITOR_EM_OVERRIDE = '.(sbPlugins::getUserSetting('sb_editor_em') ? 'true' : 'false').';';

	if ($_GET['mode'] == 'full')
	{
		echo 'sbEditorMode = "full";
			'.($_SESSION['sbPlugins']->isPluginAvailable('pl_workflow') ? 'var sbWorkflow = true;' : 'var sbWorkflow = false;').'
			var sbWrokflowWasCancel = false;

			function sbEditorSaveAction(el, sync)
			{
				if ( el.className == "buttonDisabled" )
					return;

				if (sbEditorArea)
				{
					sbEditorTopWnd.sbShowMsgDiv("'.KERNEL_SAVING.'", "loading.gif", false);

					var el_type = sbEditorArea.getAttribute("sb_type");
					var el_id = sbEditorArea.getAttribute("sb_el_id");
					var el_html = sbEditorGetData();

					sbEditorTopWnd.sbGetE("sb_editor_el_id").value = el_id;
					sbEditorTopWnd.sbGetE("sb_editor_el_type").value = el_type;

					if (sbWorkflow && (el_type == "pl_texts_html" || el_type == "pl_texts_plain"))
					{
						var strPage = "'.SB_CMS_MODAL_DIALOG_FILE.'?event=pl_texts_get_status&id=" + el_id;
                		var strAttr = "resizable=0,width=600,height=600";
                		var res = new Object();
                		res.html = el_html;

                		sbEditorTopWnd.sbShowModalDialog(strPage, strAttr, sbEditorAfterGetStatus, res);
                		return;
					}

					sbEditorTopWnd.sbGetE("sb_editor_value").value = el_html;

					if (sync)
					{
						sbEditorSaveActionHelper(sbSendForm(sbEditorTopWnd.sbGetE("sb_editor_form")));
					}
					else
					{
						sbSendFormAsync(sbEditorTopWnd.sbGetE("sb_editor_form"), sbEditorSaveActionHelper);
					}
				}
			}

			function sbEditorAfterGetStatus()
			{
				sbEditorTopWnd.sbHideMsgDiv();

				var res = sbEditorTopWnd.sbModalDialog.returnValue;

				if (res)
				{
					var el_id = sbEditorTopWnd.sbGetE("sb_editor_el_id").value;

					sbEditorTopWnd.sbGetE("sb_editor_el_id").value = "";
					sbEditorTopWnd.sbGetE("sb_editor_el_type").value = "";
					sbEditorTopWnd.sbGetE("sb_editor_value").value = sbEditorGetData();

					if (sbWrokflowWasCancel)
						sbEditorTopWnd.sbEditorCancelTextEdit(sbEditorArea);

					var strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_texts_gen_pages&id=" + el_id;
	                var strAttr = "resizable=0,width=600,height=400";
	                sbEditorTopWnd.sbShowDialog(strPage, strAttr);
	            }

	            sbWrokflowWasCancel = false;
			}

			function sbEditorSaveActionHelper(res)
			{
				sbEditorTopWnd.sbHideMsgDiv();

				var el_id = sbEditorTopWnd.sbGetE("sb_editor_el_id").value;
			    var el_type = sbEditorTopWnd.sbGetE("sb_editor_el_type").value;

			    sbEditorTopWnd.sbGetE("sb_editor_el_id").value = "";
			    sbEditorTopWnd.sbGetE("sb_editor_el_type").value = "";

				if (res != "")
				{
					sbEditorTopWnd.sbShowMsgDiv(res, "warning.png", false);
					return;
				}

				switch (el_type)
				{
					case "pl_texts_html":
					case "pl_texts_plain":
						var strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_texts_gen_pages&id=" + el_id;
                		var strAttr = "resizable=0,width=600,height=400";
                		sbEditorTopWnd.sbShowDialog(strPage, strAttr);
                		break;
                }

                sbEditorFocus();
			}

			function sbEditorExitAction(el)
			{
				if ( el.className == "buttonDisabled" )
					return;

				el.className = "button";
				if (sbEditorTopWnd.sbGetE("sb_editor_value").value == sbEditorGetData())
				{
				    sbEditorTopWnd.sbEditorCancelTextEdit(sbEditorArea);
				    return;
				}

				if (confirm("'.PL_EDITOR_H_EXIT_MSG.'"))
				{
					sbEditorSaveAction(el, true);

					if (sbWorkflow)
						sbWrokflowWasCancel = true;
					else
						sbEditorTopWnd.sbEditorCancelTextEdit(sbEditorArea);
				}
				else
				{
					sbEditorTopWnd.sbEditorCancelTextEdit(sbEditorArea);
				}
			}';
	}
	else
	{
		echo 'sbEditorMode = "popup";
			function sbEditorSaveAction(el)
			{
				if ( el.className == "buttonDisabled" )
					return;

				if (sbEditorArea)
					sbEditorTopWnd.sbReturnValue(sbEditorGetData());
				else
					sbEditorTopWnd.sbCloseDialog();
			}

			function sbEditorExitAction(el)
			{
				if ( el.className == "buttonDisabled" )
					return;

				el.className = "button";
				if (sbEditorTopWnd.sbGetE("sb_editor_value").value == sbEditorGetData())
                {
                    sbEditorTopWnd.sbCloseDialog();
                    return;
                }

				if (confirm("'.PL_EDITOR_H_EXIT_MSG.'"))
				{
					sbEditorTopWnd.sbCloseDialog();
				}
				else
				{
					sbEditorFocus();
				}
			}';
	}

	echo '	</script>
			<script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbEditorDTD.js.php"></script>
			<script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbEditorDOM.js.php"></script>
			<script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbEditorXHTML.js.php"></script>
			<script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbEditorStyles.js.php"></script>
			<script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbEditorTools.js.php"></script>
			<script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbEditorUndo.js.php"></script>
			<script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbEditorKeystrokes.js.php"></script>
			<script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbEditorActions.js.php"></script>
			<script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbEditorToolbar.js.php"></script>
			<script src="'.SB_CMS_JSCRIPT_URL.'/editor/sbEditorTables.js.php"></script>';

	$icons = sbPlugins::getUserSetting('sb_editor_icons');
	if (!$icons)
		$icons = array();

	$num_rows = 1;

	echo '	<link rel="stylesheet" type="text/css" href="'.SB_CMS_CSS_URL.'/editor/sbToolbar.css">
			<link rel="stylesheet" type="text/css" href="'.SB_CMS_CSS_URL.'/sbSpecialCombo.css">
		</head>
		<body oncontextmenu="return sbDisCMenu(event);" tabindex="-1" onselectstart="sbCancelSelect(event);" unselectable="on">
		<table id="toolbar" cellspacing="0" cellpadding="0">
		<tr ondragstart="sbCancelEvent()">
		<td rowspan="4" class="toolbar_ar" valign="bottom" title="'.PL_EDITOR_H_MINIMIZE.'" onclick="sbEditorMinMaxToolbar(this);"><img id="sbEditorBtnMinMax" src="'.SB_CMS_IMG_URL.'/editor/up.gif" width="7" height="4" class="toolbar_ar" title="'.PL_EDITOR_H_MINIMIZE.'" /></td>
		<td class="toolbar" id="toolbar_row1"><img class="toolbar" src="'.SB_CMS_IMG_URL.'/editor/toolbar.gif" /><img class="button" style="background-position: 0px -330px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnSave" title="'.PL_EDITOR_H_BTN_SAVE.'" onclick="sbEditorSaveAction(this);" />';

		if ( in_array('icon_source', $icons) )
			echo '<img class="button" style="background-position: 0px -836px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnSource" title="'.PL_EDITOR_H_BTN_HTML.'" onclick="sbEditorSourceAction(this);" />';

		echo '<img class="button" style="background-position: 0px -968px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnExit" title="'.PL_EDITOR_H_BTN_EXIT.'" onclick="sbEditorExitAction(this);" />';

		$str1 = '<img class="spacer" src="'.SB_CMS_IMG_URL.'/editor/separator.gif" />';
		$str = '';

		if ( in_array('icon_cut', $icons) )
			$str .= '<img class="button" style="background-position: 0px -990px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnCut" title="'.PL_EDITOR_H_BTN_CUT.'" onclick="sbEditorCutCopyAction(this, \'cut\');" />';
		if ( in_array('icon_copy', $icons) )
			$str .= '<img class="button" style="background-position: 0px -1012px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnCopy" title="'.PL_EDITOR_H_BTN_COPY.'" onclick="sbEditorCutCopyAction(this, \'copy\');" />';

		if ($str != '')
			echo $str1.$str;

		$str = '';

		if ( in_array('icon_paste', $icons) )
			$str .= '<img class="button" style="background-position: 0px -550px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnPaste" title="'.PL_EDITOR_H_BTN_PASTE.'" onclick="sbEditorPasteAction(this);" />';

		if ( in_array('icon_pasteplain', $icons) )
			$str .= '<img class="button" style="background-position: 0px -528px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnPastePlain" title="'.PL_EDITOR_H_BTN_PASTE_PLAIN.'" onclick="sbEditorPastePlainAction(this);" />';

		if ( in_array('icon_pasteword', $icons) )
			$str .= '<img class="button" style="background-position: 0px -506px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnPasteWord" title="'.PL_EDITOR_H_BTN_PASTE_WORD.'" onclick="sbEditorPasteFromWordAction(this);" />';

		if ($str != '')
			echo $str1.$str;

		$str = '';

		if ( in_array('icon_find', $icons) )
			$str .= '<img class="button" style="background-position: 0px -946px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnFind" title="'.PL_EDITOR_H_BTN_FIND.'" onclick="sbEditorFindAction(this);" />';

		if ( in_array('icon_spell', $icons) )
			$str .= '<img class="button" style="background-position: 0px -264px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnSpell" title="'.PL_EDITOR_H_BTN_SPELL.'" onclick="sbEditorSpellAction(this);" />';

		if ($str != '')
			echo $str1.$str;

		$str = '';

		if ( in_array('icon_undo', $icons) )
			$str .= '<img class="button" style="background-position: 0px -44px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnUndo" title="'.PL_EDITOR_H_BTN_UNDO.'" onclick="sbEditorUndoAction(this);" />';

		if ( in_array('icon_redo', $icons) )
			$str .= '<img class="button" style="background-position: 0px -462px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnRedo" title="'.PL_EDITOR_H_BTN_REDO.'" onclick="sbEditorRedoAction(this);" />';

		if ($str != '')
			echo $str1.$str;

		$str = '';

		if ( in_array('icon_link', $icons) )
			$str .= '<img class="button" style="background-position: 0px -704px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnLink" title="'.PL_EDITOR_H_BTN_LINK.'" onclick="sbEditorLinkAction(this);" />';

		if ( in_array('icon_anchor', $icons) )
			$str .= '<img class="button" style="background-position: 0px -1232px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnAnchor" title="'.PL_EDITOR_H_BTN_ANCHOR.'" onclick="sbEditorAnchorAction(this);" />';

		if ( in_array('icon_unlink', $icons) )
			$str .= '<img class="button" style="background-position: 0px -22px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnUnlink" title="'.PL_EDITOR_H_BTN_UNLINK.'" onclick="sbEditorUnlinkAction(this);" />';

		if ($str != '')
			echo $str1.$str;

		$str = '';

		if ( in_array('icon_image', $icons) )
			$str .= '<img class="button" style="background-position: 0px -814px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnImage" title="'.PL_EDITOR_H_BTN_IMAGE.'" onclick="sbEditorImageAction(this);" />';

		if ( in_array('icon_imagemap', $icons) )
			$str .= '<img class="button" style="background-position: 0px -792px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnImageMap" title="'.PL_EDITOR_H_BTN_IMAGEMAP.'" onclick="sbEditorImagemapAction(this);" />';

		if ($str != '')
			echo $str1.$str;

		$str = '';

		if ( in_array('icon_flash', $icons) )
			$str .= '<img class="button" style="background-position: 0px -924px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnFlash" title="'.PL_EDITOR_H_BTN_FLASH.'" onclick="sbEditorFlashAction(this);" />';

		if ( in_array('icon_movie', $icons) )
			$str .= '<img class="button" style="background-position: 0px -484px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnMovie" title="'.PL_EDITOR_H_BTN_MOVIE.'" onclick="sbEditorMovieAction(this);" />';

		if ($str != '')
			echo $str1.$str;

		$str = '';

		if ( in_array('icon_templates', $icons) )
			$str .= '<img class="button" style="background-position: 0px -132px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnTemplates" title="'.PL_EDITOR_H_BTN_TEMPLATES.'" onclick="sbEditorTemplatesAction(this);" />';

		if ( in_array('icon_char', $icons) )
			$str .= '<img class="button" style="background-position: 0px -286px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnChar" title="'.PL_EDITOR_H_BTN_SPECIAL_CHAR.'" onclick="sbEditorSpecialCharAction(this);" />';

		if ($str != '')
			echo $str1.$str;

		$str = '';

		if ( in_array('icon_pagebreak', $icons) )
			$str .= '<img class="button" style="background-position: 0px -594px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnPageBreak" title="'.PL_EDITOR_H_BTN_PAGE_BREAK.'" onclick="sbEditorPageBreakAction(this);" />';

		if ( in_array('icon_hr', $icons) )
			$str .= '<img class="button" style="background-position: 0px -858px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnHR" title="'.PL_EDITOR_H_BTN_HR.'" onclick="sbEditorHRAction(this);" />';

		if ($str != '')
			echo $str1.$str;

		echo '</td></tr>';


		$str1 = '<tr ondragstart="sbCancelEvent()"><td class="toolbar" id="toolbar_row2"><img class="toolbar" src="'.SB_CMS_IMG_URL.'/editor/toolbar.gif" />';
		$str2 = '<img class="spacer" src="'.SB_CMS_IMG_URL.'/editor/separator.gif" />';
		$str = '';
		$all_str = '';

		if ( in_array('icon_bold', $icons) )
			$str .= '<img class="button" style="background-position: 0px -1188px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnBold" title="'.PL_EDITOR_H_BTN_BOLD.'" onclick="sbEditorCoreStyleAction(this, \'Bold\');" />';

		if ( in_array('icon_italic', $icons) )
			$str .= '<img class="button" style="background-position: 0px -748px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnItalic" title="'.PL_EDITOR_H_BTN_ITALIC.'" onclick="sbEditorCoreStyleAction(this, \'Italic\');" />';

		if ( in_array('icon_underline', $icons) )
			$str .= '<img class="button" style="background-position: 0px -66px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnUnderline" title="'.PL_EDITOR_H_BTN_UNDERLINE.'" onclick="sbEditorCoreStyleAction(this, \'Underline\');" />';

		if ( in_array('icon_strike', $icons) )
			$str .= '<img class="button" style="background-position: 0px -242px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnStrike" title="'.PL_EDITOR_H_BTN_STRIKE.'" onclick="sbEditorCoreStyleAction(this, \'StrikeThrough\');" />';

		if ($str != '')
			$all_str .= $str.$str2;

		$str = '';

		if ( in_array('icon_sub', $icons) )
			$str .= '<img class="button" style="background-position: 0px -220px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnSub" title="'.PL_EDITOR_H_BTN_SUBSCRIPT.'" onclick="sbEditorCoreStyleAction(this, \'Subscript\');" />';

		if ( in_array('icon_sup', $icons) )
			$str .= '<img class="button" style="background-position: 0px -198px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnSup" title="'.PL_EDITOR_H_BTN_SUPERSCRIPT.'" onclick="sbEditorCoreStyleAction(this, \'Superscript\');" />';

		if ($str != '')
			$all_str .= $str.$str2;

		$str = '';

		if ( in_array('icon_left', $icons) )
			$str .= '<img class="button" style="background-position: 0px -726px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnLeft" title="'.PL_EDITOR_H_BTN_LEFT.'" onclick="sbEditorJustifyAction(this, \'left\');" />';

		if ( in_array('icon_center', $icons) )
			$str .= '<img class="button" style="background-position: 0px -1056px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnCenter" title="'.PL_EDITOR_H_BTN_CENTER.'" onclick="sbEditorJustifyAction(this, \'center\');" />';

		if ( in_array('icon_right', $icons) )
			$str .= '<img class="button" style="background-position: 0px -374px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnRight" title="'.PL_EDITOR_H_BTN_RIGHT.'" onclick="sbEditorJustifyAction(this, \'right\');" />';

		if ( in_array('icon_justify', $icons) )
			$str .= '<img class="button" style="background-position: 0px -880px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnJustify" title="'.PL_EDITOR_H_BTN_FULL.'" onclick="sbEditorJustifyAction(this, \'justify\');" />';

		if ($str != '')
			$all_str .= $str.$str2;

		$str = '';

		if ( in_array('icon_numberlist', $icons) )
			$str .= '<img class="button" style="background-position: 0px -638px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnNumberList" title="'.PL_EDITOR_H_BTN_ORDERED_LIST.'" onclick="sbEditorListAction(this, \'ol\');" />';

		if ( in_array('icon_bulletlist', $icons) )
			$str .= '<img class="button" style="background-position: 0px -1100px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnBulletList" title="'.PL_EDITOR_H_BTN_UNORDERED_LIST.'" onclick="sbEditorListAction(this, \'ul\');" />';

		if ($str != '')
			$all_str .= $str.$str2;

		$str = '';

		if ( in_array('icon_indent', $icons) )
			$str .= '<img class="button" style="background-position: 0px -770px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnIndent" title="'.PL_EDITOR_H_BTN_INDENT.'" onclick="sbEditorIdentAction(this, \'indent\');" />';

		if ( in_array('icon_outdent', $icons) )
			$str .= '<img class="button" style="background-position: 0px -616px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnOutdent" title="'.PL_EDITOR_H_BTN_OUTDENT.'" onclick="sbEditorIdentAction(this, \'outdent\');" />';

		if ($str != '')
			$all_str .= $str.$str2;

		$str = '';

		if ( in_array('icon_fontcolor', $icons) )
			$str .= '<img class="button" style="background-position: 0px -902px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnFontColor" title="'.PL_EDITOR_H_BTN_FONT_COLOR.'" onclick="sbEditorTextColorAction(this, \'ForeColor\');" />';

		if ( in_array('icon_backcolor', $icons) )
			$str .= '<img class="button" style="background-position: 0px -1210px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnBackColor" title="'.PL_EDITOR_H_BTN_BACKGROUND_COLOR.'" onclick="sbEditorTextColorAction(this, \'BackColor\');" />';

		if ($str != '')
			$all_str .= $str.$str2;

		$str = '';

		if ( in_array('icon_select', $icons) )
			$str .= '<img class="button" style="background-position: 0px -308px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnSelect" title="'.PL_EDITOR_H_BTN_SELECTALL.'" onclick="if (this.className != \'buttonDisabled\') sbEditorExecuteNamedCommand( \'SelectAll\', null, true ); sbEditorFocus();" />';

		if ( in_array('icon_removeformat', $icons) )
			$str .= '<img class="button" style="background-position: 0px -418px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnRemoveFormat" title="'.PL_EDITOR_H_BTN_REMOVE_FORMAT.'" onclick="sbEditorRemoveFormatAction(this);" />';

		if ( in_array('icon_borders', $icons) )
			$str .= '<img class="button" style="background-position: 0px -1166px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnBorders" title="'.PL_EDITOR_H_BTN_SHOW_BLOCKS.'" onclick="sbEditorShowBlocksAction(this);" />';

		if ($str != '')
			$all_str .= $str.$str2;

		$str = '';

		if ( in_array('icon_par', $icons) )
			$str .= '<img class="button" style="background-position: 0px -572px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnPar" title="'.PL_EDITOR_H_BTN_PARAGRAPH.'" onclick="sbEditorTableCellAction(this);" />';

		if ( in_array('icon_div', $icons) )
			$str .= '<img class="button" style="background-position: 0px -1342px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnDiv" title="'.PL_EDITOR_H_BTN_DIV.'" onclick="sbEditorTableCellAction(this, \'div\');" />';

		if ($str != '')
			$all_str .= $str.$str2;

		$str = '';

		if ( in_array('icon_br', $icons) )
			$str .= '<img class="button" style="background-position: 0px -1122px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnBR" title="'.PL_EDITOR_H_BTN_BR.'" onclick="sbEditorBRAction(this);" />';

		if ( in_array('icon_nbsp', $icons) )
			$str .= '<img class="button" style="background-position: 0px -660px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnNbsp" title="'.PL_EDITOR_H_BTN_NBSP.'" onclick="sbEditorNbspAction(this);" />';

		if ($str != '')
			$all_str .= $str;

		if ($all_str != '')
		{
			$num_rows++;
			echo $str1.$all_str.'</td></tr>';
		}

		$str1 = '<tr ondragstart="sbCancelEvent()">
		<td class="toolbar" style="padding-top: 1px;" id="toolbar_row3">
			<table cellpadding="0" cellspacing="0">
		    <tr>
		    <td>
				<img class="toolbar" src="'.SB_CMS_IMG_URL.'/editor/toolbar.gif" />
			</td>';

		$str = '';

		if ( in_array('combo_styles', $icons) )
		{
			$str .= '
			<td>
				<div id="sbEditorStylesDiv"></div>
				<script>
					var sbEditorStylesCombo = new sbEditorStyleCombo();
					sbEditorStylesCombo._Create(sbGetE("sbEditorStylesDiv"));
				</script>
			</td>
			<td style="padding-left: 5px;">
				<img class="spacer" src="'.SB_CMS_IMG_URL.'/editor/separator.gif" style="margin-bottom: 0px;" />
			</td>';
		}

		if ( in_array('combo_formats', $icons) )
		{
			$str .= '
			<td>
				<div id="sbEditorFontFormatDiv"></div>
				<script>
					var sbEditorFontFormatsCombo = new sbEditorFontFormatCombo();
					sbEditorFontFormatsCombo._Create(sbGetE("sbEditorFontFormatDiv"));
				</script>
			</td>
			<td style="padding-left: 5px;">
				<img class="spacer" src="'.SB_CMS_IMG_URL.'/editor/separator.gif" style="margin-bottom: 0px;" />
			</td>';
		}

		if ( in_array('combo_fonts', $icons) )
		{
			$str .= '
			<td>
				<div id="sbEditorFontNameDiv"></div>
				<script>
					var sbEditorFontNamesCombo = new sbEditorFontNameCombo();
					sbEditorFontNamesCombo._Create(sbGetE("sbEditorFontNameDiv"));
				</script>
			</td>
			<td style="padding-left: 5px;">
				<img class="spacer" src="'.SB_CMS_IMG_URL.'/editor/separator.gif" style="margin-bottom: 0px;" />
			</td>';
		}

		if ( in_array('combo_sizes', $icons) )
		{
			$str .= '
			<td>
				<div id="sbEditorFontSizeDiv"></div>
				<script>
					var sbEditorFontSizesCombo = new sbEditorFontSizeCombo();
					sbEditorFontSizesCombo._Create(sbGetE("sbEditorFontSizeDiv"));
				</script>
			</td>';
		}

		$num_rows++;
		echo $str1.$str.'<td><img class="button" style="background-position: 0px -1430px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnHelp" title="'.PL_EDITOR_H_BTN_HELP.'" onclick="sbEditorHelpAction();" /></td></tr></table></td></tr>';

		$str1 = '<tr ondragstart="sbCancelEvent()"><td class="toolbar" id="toolbar_row4"><img class="toolbar" src="'.SB_CMS_IMG_URL.'/editor/toolbar.gif" />';
		$str2 = '<img class="spacer" src="'.SB_CMS_IMG_URL.'/editor/separator.gif" />';
		$str = '';
		$all_str = '';

		if ( in_array('icon_table', $icons) )
			$str .= '<img class="button" style="background-position: 0px -176px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnTable" title="'.PL_EDITOR_H_BTN_TABLE.'" onclick="sbEditorTableAction(this, \'insert\');" />';

		if ( in_array('icon_removetable', $icons) )
			$str .= '<img class="button" style="background-position: 0px -1364px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnRemoveTable" title="'.PL_EDITOR_H_BTN_REMOVE_TABLE.'" onclick="sbEditorTableAction(this, \'remove\');" />';

		if ($str != '')
			$all_str .= $str.$str2;

		$str = '';

		if ( in_array('icon_tableedit', $icons) )
			$str .= '<img class="button" style="background-position: 0px -154px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnTableEdit" title="'.PL_EDITOR_H_BTN_EDIT_TABLE.'" onclick="sbEditorTableAction(this, \'props\');" />';

		if ( in_array('icon_celledit', $icons) )
			$str .= '<img class="button" style="background-position: 0px -1078px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnCellEdit" title="'.PL_EDITOR_H_BTN_EDIT_CELL.'" onclick="sbEditorTableCellAction(this, \'cell\');" />';

		if ($str != '')
			$all_str .= $str.$str2;

		$str = '';

		if ( in_array('icon_topalign', $icons) )
			$str .= '<img class="button" style="background-position: 0px -110px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnTopAlign" title="'.PL_EDITOR_H_BTN_ALIGN_TOP.'" onclick="sbEditorValignAction(this, \'top\');" />';

		if ( in_array('icon_middlealign', $icons) )
			$str .= '<img class="button" style="background-position: 0px -682px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnMiddleAlign" title="'.PL_EDITOR_H_BTN_ALIGN_MIDDLE.'" onclick="sbEditorValignAction(this, \'middle\');" />';

		if ( in_array('icon_bottomalign', $icons) )
			$str .= '<img class="button" style="background-position: 0px -1144px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnBottomAlign" title="'.PL_EDITOR_H_BTN_ALIGN_BOTTOM.'" onclick="sbEditorValignAction(this, \'bottom\');" />';

		if ($str != '')
			$all_str .= $str.$str2;

		$str = '';

		if ( in_array('icon_mergecol', $icons) )
			$str .= '<img class="button" style="background-position: 0px -1032px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnMergeCol" title="'.PL_EDITOR_H_BTN_MERGE_COL.'" onclick="sbEditorTableCellsAction(this, \'cells\', \'merge\', \'col\');" />';

		if ( in_array('icon_mergerow', $icons) )
			$str .= '<img class="button" style="background-position: 0px -1386px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnMergeRow" title="'.PL_EDITOR_H_BTN_MERGE_ROW.'" onclick="sbEditorTableCellsAction(this, \'cells\', \'merge\', \'row\');" />';

		if ($str != '')
			$all_str .= $str.$str2;

		$str = '';

		if ( in_array('icon_merge', $icons) )
			$str .= '<img class="button" style="background-position: 0px -352px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnMerge" title="'.PL_EDITOR_H_BTN_MERGE.'" onclick="sbEditorTableCellsAction(this, \'cells\', \'merge\', \'cells\');" />';

		if ( in_array('icon_splitcol', $icons) )
			$str .= '<img class="button" style="background-position: 0px -88px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnSplitCol" title="'.PL_EDITOR_H_BTN_SPLIT_COL.'" onclick="sbEditorTableCellsAction(this, \'col\', \'split\');" />';

		if ( in_array('icon_splitrow', $icons) )
			$str .= '<img class="button" style="background-position: 0px 0px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnSplitRow" title="'.PL_EDITOR_H_BTN_SPLIT_ROW.'" onclick="sbEditorTableCellsAction(this, \'row\', \'split\');" />';

		if ($str != '')
			$all_str .= $str.$str2;

		$str = '';

		if ( in_array('icon_addcolbefore', $icons) )
			$str .= '<img class="button" style="background-position: 0px -1298px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnAddColBefore" title="'.PL_EDITOR_H_BTN_ADDCOL_BEFORE.'" onclick="sbEditorTableCellsAction(this, \'col\', \'add\', true);" />';

		if ( in_array('icon_addcol', $icons) )
			$str .= '<img class="button" style="background-position: 0px -1320px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnAddCol" title="'.PL_EDITOR_H_BTN_ADDCOL_AFTER.'" onclick="sbEditorTableCellsAction(this, \'col\', \'add\', false);" />';

		if ( in_array('icon_removecol', $icons) )
			$str .= '<img class="button" style="background-position: 0px -440px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnRemoveCol" title="'.PL_EDITOR_H_BTN_REMOVE_COL.'" onclick="sbEditorTableCellsAction(this, \'col\', \'remove\');" />';

		if ($str != '')
			$all_str .= $str.$str2;

		$str = '';

		if ( in_array('icon_addrowbefore', $icons) )
			$str .= '<img class="button" style="background-position: 0px -1276px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnAddRowBefore" title="'.PL_EDITOR_H_BTN_ADDROW_BEFORE.'" onclick="sbEditorTableCellsAction(this, \'row\', \'add\', true);" />';

		if ( in_array('icon_addrow', $icons) )
			$str .= '<img class="button" style="background-position: 0px -1254px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnAddRow" title="'.PL_EDITOR_H_BTN_ADDROW_AFTER.'" onclick="sbEditorTableCellsAction(this, \'row\', \'add\', false);" />';

		if ( in_array('icon_removerow', $icons) )
			$str .= '<img class="button" style="background-position: 0px -396px;background-image: url('.SB_CMS_IMG_URL.'/editor/icons.png);" src="'.SB_CMS_IMG_URL.'/blank.gif" id="sbEditorBtnRemoveRow" title="'.PL_EDITOR_H_BTN_REMOVE_ROW.'" onclick="sbEditorTableCellsAction(this, \'row\', \'remove\');" />';

		if ($str != '')
			$all_str .= $str;

		if ($all_str != '')
		{
			$num_rows++;
			echo $str1.$all_str.'</td></tr>';
		}

		echo'</table>';

		if ($num_rows != 4)
		{
			echo '<script>
				window.parent.sbGetE("sb_editor_toolbar").height = '.($num_rows * 29).';
			</script>';
		}

		echo '</body></html>';
}


function fEditor_Save_Block()
{
	$el_type = preg_replace('/[^A-Za-z_0-9]+/'.SB_PREG_MOD, '', $_POST['el_type']);
	$el_id = intval($_POST['el_id']);

	switch ($el_type)
	{
		case 'pl_texts_html':
		case 'pl_texts_plain':
			$res = sql_query('SELECT t_name FROM sb_texts WHERE t_id=?d', $el_id);
			$res_cat = sql_query('SELECT categs.cat_id, links.link_id FROM sb_categs categs, sb_catlinks links WHERE categs.cat_ident=? AND links.link_cat_id=categs.cat_id AND links.link_el_id=?d', 'pl_texts', $el_id);

		    if ($res && $res_cat)
		    {
		        // редактирование
		        list($old_title) = $res[0];

	    	    sql_query('UPDATE sb_texts SET t_html=? WHERE t_id=?d', $_POST['value'], $el_id, sprintf(PL_EDITOR_H_SAVE_TEXT_EDIT_OK, $old_title));

	            $_GET['plugin_ident'] = 'pl_texts';
	            $_GET['link_src_cat_id'] = 0;
	            $_GET['cat_id'] = $res_cat[0][0];
	            $_GET['link_id'] = $res_cat[0][1];
	            $_GET['id'] = $el_id;

	            $footer_ar = fCategs_Edit_Elem();
	    	    if (!$footer_ar)
	    	    {
	    	        echo PL_EDITOR_H_SAVE_TEXT_EDIT_SYSTEMLOG_ERROR2;
	                sb_add_system_message(sprintf(PL_EDITOR_H_SAVE_TEXT_EDIT_SYSTEMLOG_ERROR, $old_title), SB_MSG_WARNING);
	    	    }
		    }
		    else
		    {
		    	echo PL_EDITOR_H_SAVE_TEXT_EDIT_SYSTEMLOG_ERROR2;
            	sb_add_system_message(PL_EDITOR_H_SAVE_TEXT_EDIT_SYSTEMLOG_ERROR2, SB_MSG_WARNING);
		    }
			break;
	}
}

function fEditor_Delete_Block()
{
	$res = sql_query('SELECT elems.e_link, elems.e_tag, elems.e_p_id, pages.p_id, pages.p_temp_id, pages.p_name FROM sb_elems elems LEFT JOIN sb_pages pages ON pages.p_id=elems.e_p_id WHERE elems.e_id=?d', $_GET['e_id']);
	if (!$res)
	{
		echo 'SB_ERROR1';
		return;
	}

	list($e_link, $e_tag, $e_p_id, $p_id, $p_temp_id, $p_title) = $res[0];
	$e_tag_name = sb_str_replace(array('{', '}', '_'), array('', '', ' '), $e_tag);

	if ($e_link != 'page')
	{
		echo 'SB_ERROR2';
		return;
	}

	if (!sql_query('DELETE FROM sb_elems WHERE e_id=?d', $_GET['e_id'], sprintf(PL_EDITOR_H_DELETE_BLOCK_OK, $e_tag_name, $p_title)))
	{
		echo 'SB_ERROR1';
		sb_add_system_message(sprintf(PL_EDITOR_H_DELETE_BLOCK_ERROR, $e_tag_name, $p_title), SB_MSG_WARNING);
		return;
	}

	$res_cat = sql_query('SELECT categs.cat_id, links.link_id FROM sb_categs categs, sb_catlinks links WHERE categs.cat_ident=? AND links.link_cat_id=categs.cat_id AND links.link_el_id=?d', 'pl_pages', $p_id);
	if (!$res_cat)
	{
		echo 'SB_ERROR1';
		sb_add_system_message(sprintf(PL_EDITOR_H_DELETE_BLOCK_ERROR2, $p_title, $e_tag_name), SB_MSG_WARNING);
		return;
	}

	$_GET['plugin_ident'] = 'pl_pages';
	$_GET['link_src_cat_id'] = 0;
	$_GET['cat_id'] = $res_cat[0][0];
	$_GET['link_id'] = $res_cat[0][1];
	$_GET['id'] = $p_id;

	$footer_ar = fCategs_Edit_Elem();

	if(!$footer_ar)
	{
		echo 'SB_ERROR1';
		sb_add_system_message(sprintf(PL_EDITOR_H_DELETE_BLOCK_ERROR2, $p_title, $e_tag_name), SB_MSG_WARNING);
		return;
	}

	if (!$_SESSION['sbPlugins']->isRightAvailable('pl_pages', 'elems_public'))
    {
    	sql_query('UPDATE sb_pages SET p_state=0 WHERE p_id=?d AND p_state=1', $p_id);
    }
	elseif (!fPages_Gen_Page($p_id))
	{
		echo 'SB_ERROR1';
		return;
	}

	$res = sql_query('SELECT e_id FROM sb_elems WHERE e_tag=? AND e_p_id=?d', $e_tag, $p_temp_id);
	if (!$res)
		echo 'SB_EMPTY';
}

function fEditor_Block_Settings()
{
	$res = sql_query('SELECT e_ident, e_el_id, e_temp_id, e_params FROM sb_elems WHERE e_id=?d', $_GET['e_id']);
	if (!$res)
	{
		sb_show_message(PL_EDITOR_H_BLOCK_SETTINGS_ERROR, true, 'warning');
		return;
	}

	list($e_ident, $_GET['el_id'], $_GET['temp_id'], $_GET['params']) = $res[0];

	$elems = $_SESSION['sbPlugins']->getElems();
	if (!isset($elems[$e_ident]))
	{
		sb_show_message(PL_EDITOR_H_BLOCK_SETTINGS_ERROR, true, 'warning');
		return;
	}

	$event = $elems[$e_ident]['get_event'];
	$event = explode('&', $event);
	if (count($event) > 1)
	{
		for ($i = 1; $i < count($event); $i++)
		{
			$get_param = explode('=', $event[$i]);
			if (count($get_param) == 2)
			{
				$_GET[$get_param[0]] = $get_param[1];
			}
		}
	}

	$func = $_SESSION['sbPlugins']->getEventFunction($event[0]);
	if (!$func)
	{
		sb_show_message(PL_EDITOR_H_BLOCK_SETTINGS_ERROR, true, 'warning');
		return;
	}

	$func['function']();
}

function fEditor_Block_Settings_Submit()
{
	$res = sql_query('SELECT e_tag, e_ident, e_link, e_temp_id, e_el_id, e_params, e_search, e_edit, e_yandex FROM sb_elems WHERE e_id=?d', $_GET['e_id']);
	if (!$res)
	{
		echo 'FALSE';
		sb_add_system_message(PL_EDITOR_H_BLOCK_SETTINGS_ERROR2, SB_MSG_WARNING);
		return;
	}

	list($e_tag, $e_ident, $e_link, $e_temp_id, $e_el_id, $e_params, $e_search, $e_edit, $e_yandex) = $res[0];
	$e_tag_name = sb_str_replace(array('{', '}', '_'), array('', '', ' '), $e_tag);

	$res = sql_query('SELECT pages.p_name, pages.p_temp_id, categs.cat_id, links.link_id FROM sb_pages pages, sb_categs categs, sb_catlinks links WHERE pages.p_id=?d AND categs.cat_ident=? AND links.link_el_id=pages.p_id AND links.link_cat_id=categs.cat_id', $_GET['p_id'], 'pl_pages');
	if (!$res)
	{
		echo 'FALSE';
		sb_add_system_message(PL_EDITOR_H_BLOCK_SETTINGS_ERROR3, SB_MSG_WARNING);
		return;
	}

	list($p_name, $p_temp_id, $cat_id, $link_id) = $res[0];

	$row = array();
	$row['e_temp_id'] = intval($_POST['temp_id']);
	$row['e_el_id'] = intval($_POST['el_id']);
	$row['e_params'] = $_POST['params'];

	if ($e_link == 'page')
	{
		$deleted = false;
		$res = sql_query('SELECT e_ident, e_temp_id, e_el_id, e_params, e_search, e_edit, e_yandex FROM sb_elems WHERE e_tag=? AND e_link=? AND e_p_id=?d', $e_tag, 'temp', $p_temp_id);
		if ($res)
		{
			list($temp_e_ident, $temp_e_temp_id, $temp_e_el_id, $temp_e_params, $temp_e_search, $temp_e_edit, $temp_e_yandex) = $res[0];

			if ($temp_e_ident == $e_ident && $temp_e_temp_id == $_POST['temp_id'] && $temp_e_el_id == $_POST['el_id']
				&& $temp_e_params == $_POST['params'] && $temp_e_search == $e_search && $temp_e_edit == $e_edit && $temp_e_yandex == $e_yandex)
			{
				$deleted = true;
				if (!sql_query('DELETE FROM sb_elems WHERE e_id=?d', $_GET['e_id'], sprintf(PL_EDITOR_H_BLOCK_SETTINGS_OK, $e_tag_name, $p_name)))
				{
					echo 'FALSE';
					sb_add_system_message(sprintf(PL_EDITOR_H_BLOCK_SETTINGS_ERROR4, $e_tag_name, $p_name), SB_MSG_WARNING);
					return;
				}
			}
		}

		if (!$deleted && !sql_query('UPDATE sb_elems SET ?a WHERE e_id=?d', $row, $_GET['e_id'], sprintf(PL_EDITOR_H_BLOCK_SETTINGS_OK, $e_tag_name, $p_name)))
		{
			echo 'FALSE';
			sb_add_system_message(sprintf(PL_EDITOR_H_BLOCK_SETTINGS_ERROR4, $e_tag_name, $p_name), SB_MSG_WARNING);
			return;
		}
	}
	else
	{
		$row['e_tag'] = $e_tag;
		$row['e_ident'] = $e_ident;
		$row['e_link'] = 'page';
		$row['e_p_id'] = intval($_GET['p_id']);
		$row['e_search'] = $e_search;
		$row['e_edit'] = $e_edit;
		$row['e_yandex'] = $e_yandex;

		if (!sql_query('INSERT INTO sb_elems (?#) VALUES (?a)', array_keys($row), array_values($row), sprintf(PL_EDITOR_H_BLOCK_SETTINGS_OK, $e_tag_name, $p_name)))
		{
			echo 'FALSE';
			sb_add_system_message(sprintf(PL_EDITOR_H_BLOCK_SETTINGS_ERROR4, $e_tag_name, $p_name), SB_MSG_WARNING);
			return;
		}
	}

	$_GET['plugin_ident'] = 'pl_pages';
	$_GET['link_src_cat_id'] = 0;
	$_GET['cat_id'] = $cat_id;
	$_GET['link_id'] = $link_id;
	$_GET['id'] = intval($_GET['p_id']);

	$footer_ar = fCategs_Edit_Elem();

	if(!$footer_ar)
	{
		echo 'FALSE';
		sb_add_system_message(sprintf(PL_EDITOR_H_BLOCK_SETTINGS_ERROR4, $e_tag_name, $p_name), SB_MSG_WARNING);
		return;
	}

	if (!$_SESSION['sbPlugins']->isRightAvailable('pl_pages', 'elems_public'))
    {
    	sql_query('UPDATE sb_pages SET p_state=0 WHERE p_id=?d AND p_state=1', $_GET['p_id']);
    }
	elseif (!fPages_Gen_Page(intval($_GET['p_id'])))
	{
		echo 'FALSE';
		return;
	}
}

function fEditor_Replace_Block()
{
	$e_tag = '{'.preg_replace('/[^_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+/'.SB_PREG_MOD, '', $_GET['e_tag']).'}';
	$p_id = intval($_GET['p_id']);

	if ($_GET['e_id'] != -1)
	{
        $res = sql_query('SELECT e_tag, e_ident, e_link, e_temp_id, e_el_id, e_p_id, e_params, e_search, e_edit, e_yandex
                                FROM sb_elems WHERE e_id=?d', $_GET['e_id']);

	    if (!$res)
	    {
	    	sb_show_message(PL_EDITOR_H_BLOCK_SETTINGS_ERROR, true, 'warning');
	    	return;
	    }

	    $e_id = intval($_GET['e_id']);
        list($e_tag, $e_ident, $e_link, $e_temp_id, $e_el_id, $e_p_id, $e_params, $e_search, $e_edit, $e_yandex) = $res[0];
	}
	else
	{
		$e_id = -1;
        $e_ident = '';
        $e_temp_id = -1;
        $e_el_id = -1;
        $e_params = '';
        $e_search = 'none';
        $e_edit = 1;
        $e_yandex = 0;
        $e_link = '';
	}

    echo '<script src="'.SB_CMS_JSCRIPT_URL.'/sbTemplates.js.php"></script>
    <script>
        sbElemsIdent = "page";
        function checkValues()
        {
            var e_params = sbGetE("e_params0");
            var e_ident = sbGetE("e_ident0");

            if (e_ident.value != "" && typeof(sb_pl_elems[e_ident.value]) != "undefined" && e_params.value == "")
            {
                alert("'.PL_EDITOR_H_BLOCK_REPLACE_NO_PARAMS_MSG.'");
                return false;
            }
        }';

    $elems = $_SESSION['sbPlugins']->getElems();
	if (0 == count($elems))
    {
        // зарегистрированных элементов нет
        sb_show_message(PL_EDITOR_H_BLOCK_NO_ELEMS, true, 'information', true);
        return;
    }

    if (!isset($elems[$e_ident]))
    {
        $e_ident = '';
        $e_temp_id = -1;
        $e_el_id = -1;
        $e_params = '';
    }

    $options_str = '<option value="" style="color: red;">'.KERNEL_ELEM_NOT_CHOOSED_PAGES.'</option>';
	$cur_group = '';
	foreach ($elems as $ident => $value)
	{
	    $tmp = explode('|', $value['name']);
	    $e_group = $tmp[0];
	    $value['name'] = $tmp[1];

	    if ($e_group != $cur_group)
	    {
	        if ($cur_group != '')
	        {
	            $options_str .= '</optgroup>';
	        }
	        $options_str .= '<optgroup label="'.$e_group.'">';

	        $cur_group = $e_group;
	    }

	   	$options_str .= '<option value="'.$ident.'">'.$value['name'].'</option>';

	   	if ($value['get_event'] != '')
	   	{
	    	echo 'sb_pl_elems["'.$ident.'"] = new Array();
	                  sb_pl_elems["'.$ident.'"]["event"] = "'.$value['get_event'].'";
	                  sb_pl_elems["'.$ident.'"]["width"] = "'.$value['dlg_width'].'";
	                  sb_pl_elems["'.$ident.'"]["height"] = "'.$value['dlg_height'].'";';
	   	}
	}

	if ($cur_group != '')
	    $options_str .= '</optgroup>';

	$search_options = array('none' => KERNEL_ELEM_TAGS_SEARCH_0,
							'' => '-'.KERNEL_ELEM_TAGS_SEARCH_INDEX,
	                        'all' => KERNEL_ELEM_TAGS_SEARCH_1,
	                        'links' => KERNEL_ELEM_TAGS_SEARCH_2,
	                        'text' => KERNEL_ELEM_TAGS_SEARCH_3);

    echo 'sb_pl_old_elems["'.$e_tag.'"] = new Array();
  	      sb_pl_old_elems["'.$e_tag.'"]["e_id"] = "'.$e_id.'";
  	      sb_pl_old_elems["'.$e_tag.'"]["e_ident"] = "'.$e_ident.'";
  	      sb_pl_old_elems["'.$e_tag.'"]["e_temp_id"] = "'.$e_temp_id.'";
  	      sb_pl_old_elems["'.$e_tag.'"]["e_el_id"] = "'.$e_el_id.'";
  	      sb_pl_old_elems["'.$e_tag.'"]["e_params"] = "'.str_replace('"', '\\"', $e_params).'";
  	      sb_pl_old_elems["'.$e_tag.'"]["e_info"] = "event:pl_tamplates_load_info&e_id='.$e_id.'";
  	      sb_pl_old_elems["'.$e_tag.'"]["e_link"] = "'.$e_link.'";';

    if ($e_link == 'temp')
    {
        echo 'sb_pl_temp_elems["'.$e_tag.'"] = new Array();
	          sb_pl_temp_elems["'.$e_tag.'"]["e_id"] = "'.$e_id.'";
	          sb_pl_temp_elems["'.$e_tag.'"]["e_ident"] = "'.$e_ident.'";
	          sb_pl_temp_elems["'.$e_tag.'"]["e_temp_id"] = "'.$e_temp_id.'";
	          sb_pl_temp_elems["'.$e_tag.'"]["e_el_id"] = "'.$e_el_id.'";
	          sb_pl_temp_elems["'.$e_tag.'"]["e_search"] = "'.$e_search.'";
	          sb_pl_temp_elems["'.$e_tag.'"]["e_edit"] = "'.$e_edit.'";
	          sb_pl_temp_elems["'.$e_tag.'"]["e_yandex"] = "'.$e_yandex.'";
	          sb_pl_temp_elems["'.$e_tag.'"]["e_params"] = "'.str_replace('"', '\\"', $e_params).'";
	          sb_pl_temp_elems["'.$e_tag.'"]["e_info"] = "event:pl_templates_load_info&e_id='.$e_id.'";';
    }
	else
	{
		$res = sql_query('SELECT p_temp_id FROM sb_pages WHERE p_id=?d', $p_id);
		if ($res)
		{
			list($p_temp_id) = $res[0];
			$res = sql_query('SELECT e_id, e_ident, e_temp_id, e_el_id, e_params, e_search, e_edit, e_yandex
            	                    FROM sb_elems WHERE e_tag=? AND e_p_id=?d AND e_link=?', $e_tag, $p_temp_id, 'temp');
			if ($res)
			{
	    		list($temp_e_id, $temp_e_ident, $temp_e_temp_id, $temp_e_el_id, $temp_e_params, $temp_e_search, $temp_e_edit, $temp_e_yandex) = $res[0];

	    		echo 'sb_pl_temp_elems["'.$e_tag.'"] = new Array();
			          sb_pl_temp_elems["'.$e_tag.'"]["e_id"] = "'.$temp_e_id.'";
			          sb_pl_temp_elems["'.$e_tag.'"]["e_ident"] = "'.$temp_e_ident.'";
			          sb_pl_temp_elems["'.$e_tag.'"]["e_temp_id"] = "'.$temp_e_temp_id.'";
			          sb_pl_temp_elems["'.$e_tag.'"]["e_el_id"] = "'.$temp_e_el_id.'";
			          sb_pl_temp_elems["'.$e_tag.'"]["e_search"] = "'.$temp_e_search.'";
			          sb_pl_temp_elems["'.$e_tag.'"]["e_edit"] = "'.$temp_e_edit.'";
			          sb_pl_temp_elems["'.$e_tag.'"]["e_yandex"] = "'.$temp_e_yandex.'";
			          sb_pl_temp_elems["'.$e_tag.'"]["e_params"] = "'.str_replace('"', '\\"', $temp_e_params).'";
			          sb_pl_temp_elems["'.$e_tag.'"]["e_info"] = "event:pl_templates_load_info&e_id='.$temp_e_id.'";';
			}
		}
	}

    echo 'num_tags = 1;
    </script><br><br>';

    $e_params = sb_htmlspecialchars($e_params, ENT_QUOTES);
    $tag = str_replace('_', ' ', trim($e_tag, '{}'));

    if ($e_link == 'page' && $e_ident != '')
    {
    	if (isset($elems[$e_ident]) && $elems[$e_ident]['get_event'] != '' && $e_params == '')
        {
        	$tag = '<b id="e_tag_td0" style="color:#990033; font-size: 12px;">'.$tag.'</b>';
        }
        else
        {
            $tag = '<b id="e_tag_td0" style="font-size: 12px;">'.$tag.'</b>';
        }
    }
    elseif ($e_ident != '' || ($e_link == 'temp' && $e_ident == '' && ($e_search != 'none' || $e_edit != 1 || $e_yandex != 0)))
        $tag = '<b id="e_tag_td0" style="color:#008000; font-size:12px;">'.$tag.'</b>';
    else
        $tag = '<b id="e_tag_td0" style="color: #999;font-size: 12px;">'.$tag.'</b>';

    $str = '<select id="e_ident0" name="e_ident[0]" onchange="changeIdent(0, this)" onkeyup="changeIdent(0, this)" style="font-size: 13px;">
    		'.($e_ident != '' ? sb_str_replace('"'.$e_ident.'"', '"'.$e_ident.'" selected="selected" style="background-color: #E2E2E2;"', $options_str) : $options_str).'</select>
    	    <input type="hidden" name="p_id" value="'.$p_id.'">
    	    <input type="hidden" name="old_e_id" value="'.$e_id.'">
    	    <input type="hidden" id="e_id0" name="e_id[0]" value="'.$e_id.'">
    		<input type="hidden" id="e_el_id0" name="e_el_id[0]" value="'.$e_el_id.'">
    		<input type="hidden" id="e_temp_id0" name="e_temp_id[0]" value="'.$e_temp_id.'">
    		<input type="hidden" id="e_params0" name="e_params[0]" value="'.$e_params.'">
    		<input type="hidden" id="e_tag0" name="e_tag[0]" value="'.$e_tag.'">
    		<input type="hidden" id="e_link0" name="e_link[0]" value="'.$e_link.'">';

    if ($e_params != '')
        $info = '<img info="event:pl_templates_load_info&e_id='.$e_id.'" src="'.SB_CMS_IMG_URL.'/info.png" id="e_info0" style="cursor:help;" width="20" height="20" />';
    else
        $info = '<img info="" src="'.SB_CMS_IMG_URL.'/info.png" id="e_info0" style="display:none;cursor:help;" width="20" height="20" />';

    $btn = '<img src="'.SB_CMS_IMG_URL.'/btn_props.png" width="20" height="20" id="e_btn0" onclick="browseEl(0);" title="'.KERNEL_SETTINGS.'" style="cursor:hand;cursor:pointer;'.('' == $e_ident || '' == $elems[$e_ident]['get_event'] ? 'display:none;' : '').'" />';
    $edit = '<input type="checkbox" name="e_edit[0]" id="e_edit0" value="1"'.($e_edit == 1 ? ' checked="checked"' : '').($e_ident == '' ? ' disabled="disabled"' : '').' onclick="changeEl(\'0\')" />';
    $yandex = '<input type="checkbox" name="e_yandex[0]" id="e_yandex0" value="1"'.($e_yandex == 1 ? ' checked="checked"' : '').($e_ident == '' ? ' disabled="disabled"' : '').' onclick="changeEl(\'0\')" />';

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_editor_replace_block_submit', 'thisDialog', 'post', 'checkValues()');
    $layout->mTableWidth = '95%';

    $table = array();
    if($_SESSION['sbPlugins']->isPluginAvailable('pl_search'))
    {
        $fld = new sbLayoutSelect($search_options, 'e_search[0]', 'e_search0', 'style="font-size: 13px;" onchange="changeEl(\'0\')"'.($e_ident == '' ? ' disabled="disabled"' : ''));
    	$fld->mSelOptions[] = $e_search;

    	$table[] = array($tag, $str, $fld->getField(), $edit, $yandex, $info, $btn);
    }
    else
    {
        $table[] = array($tag, $str, $edit, $yandex, $info, $btn);
    }

    if($_SESSION['sbPlugins']->isPluginAvailable('pl_search'))
    {
        $label = array(KERNEL_ELEM_TAG, KERNEL_ELEM_TYPE, KERNEL_ELEM_SEARCH, KERNEL_ELEM_EDIT, KERNEL_ELEM_YANDEX, '', '');
        $align = array('left', 'left', 'center', 'center', 'center', 'center', 'center');
        $width = array('200', '', '1', '100', '100', '26', '26');
        $colspan = 7;
    }
    else
    {
        $label = array(KERNEL_ELEM_TAG, KERNEL_ELEM_TYPE, KERNEL_ELEM_EDIT, KERNEL_ELEM_YANDEX, '', '');
        $align = array('left', 'left', 'center', 'center', 'center', 'center');
        $width = array('200', '', '100', '100', '26', '26');
        $colspan = 6;
    }

    $fld = new sbLayoutTable($label, $table);
    $fld->mAlign = $align;
    $fld->mWidth = $width;

    $btn1 = new sbLayoutInput('submit', KERNEL_SAVE, 'btn_save');
    $btn2 = new sbLayoutInput('button', KERNEL_CANCEL, '', '', 'onclick="sbCloseDialog()"');

    $fld->mHTML = '<tr><td class="footer" colspan="'.$colspan.'"><div class="footer">'.$btn1->getField().'&nbsp;&nbsp;&nbsp;'.$btn2->getField().'</div></td></tr>';

    $layout->addField('', $fld);
    $layout->mShowInfo = true;

    $layout->show();
}

function fEditor_Replace_Block_Submit()
{
    // сохраняем элемент
    extract($_POST);
    $res = sql_query('SELECT pages.p_name, categs.cat_id, links.link_id FROM sb_pages pages, sb_categs categs, sb_catlinks links WHERE pages.p_id=?d AND categs.cat_ident=? AND links.link_el_id=pages.p_id AND links.link_cat_id=categs.cat_id', $p_id, 'pl_pages');
    if (!$res)
    {
        sb_show_message(PL_EDITOR_H_BLOCK_REPLACE_PAGE_ERROR, false, 'warning');
        return;
    }

    list($p_name, $cat_id, $link_id) = $res[0];

    if ($old_e_id != -1)
        sql_query('DELETE FROM sb_elems WHERE e_id=?d AND e_link != ?', $old_e_id, 'temp');

    if ($e_link[0] == 'page' && $e_ident[0] != '')
    {
        $raw = array();
        $raw['e_tag'] = $e_tag[0];
        $raw['e_ident'] = $e_ident[0];
        $raw['e_link'] = 'page';
        $raw['e_temp_id'] = intval($e_temp_id[0]);
        $raw['e_el_id'] = intval($e_el_id[0]);
        $raw['e_p_id'] = intval($p_id);
        $raw['e_params'] = $e_params[0];
        $raw['e_search'] = isset($e_search[0]) ? $e_search[0] : 'none';
        $raw['e_edit'] = isset($e_edit[0]) ? $e_edit[0] : 0;
        $raw['e_yandex'] = isset($e_yandex[0]) ? $e_yandex[0] : 0;
        if (!sql_query('INSERT INTO sb_elems (?#) VALUES (?a)', array_keys($raw), array_values($raw)))
        {
            sb_show_message(PL_EDITOR_H_BLOCK_REPLACE_ELEMS_ERROR, false, 'warning');
            sb_add_system_message(sprintf(PL_EDITOR_H_BLOCK_REPLACE_ELEMS_SYSTEMLOG_ERROR, $p_name), SB_MSG_WARNING);

            return;
        }
    }

    echo '<script>
            sbReturnValue(1);
          </script>';

    $_GET['plugin_ident'] = 'pl_pages';
    $_GET['link_src_cat_id'] = 0;
    $_GET['cat_id'] = $cat_id;
    $_GET['link_id'] = $link_id;
    $_GET['id'] = intval($p_id);

    $footer_ar = fCategs_Edit_Elem();
    if(!$footer_ar)
    {
        sb_add_system_message(sprintf(PL_EDITOR_H_BLOCK_REPLACE_ERROR, $p_name), SB_MSG_WARNING);
        return;
    }

    sb_add_system_message(sprintf(PL_EDITOR_H_BLOCK_REPLACE_OK, sb_str_replace(array('{', '}', '_'), array('', '', ' '), $e_tag[0]), $p_name));

    if (!$_SESSION['sbPlugins']->isRightAvailable('pl_pages', 'elems_public'))
    {
    	sql_query('UPDATE sb_pages SET p_state=0 WHERE p_id=?d AND p_state=1', $p_id);
    }
    elseif (!fPages_Gen_Page(intval($p_id)))
    {
        sb_add_system_message(sprintf(PL_EDITOR_H_BLOCK_REPLACE_ERROR, $p_name), SB_MSG_WARNING);
        return;
    }
}

function fEditor_New_Text()
{
	require_once(SB_CMS_LIB_PATH.'/sbJustCategs.inc.php');

    $js_str = '
        function chooseCat(submit)
        {
            if (sbGetE("t_name").value == "")
            {
                alert("'.PL_EDITOR_H_NEW_TEXT_NO_TITLE.'");
                sbSelectField("t_name");
                return false;
            }

            var cat_id = sbCatTree.getSelectedItemId();
            if (!cat_id)
            {
                alert("'.PL_EDITOR_H_NEW_TEXT_NO_CATEG.'");
                return false;
            }

            sbGetE("cat_id").value = cat_id;
            if (submit)
            	sbGetE("main_form").submit();
        }';

    $footer_str = '<table cellspacing="0" cellpadding="7" width="100%" class="form">
    <tr>
        <th width="150">'.PL_EDITOR_H_NEW_TEXT.':</th>
        <td>
	        <form id="main_form" action="'.SB_CMS_MODAL_DIALOG_FILE.'?event=pl_editor_new_text_submit'.($_SERVER['QUERY_STRING'] ? '&'.$_SERVER['QUERY_STRING'] : '').'" method="post" target="thisDialog" onsubmit="return chooseCat(false);">
		        <input type="text" name="t_name" id="t_name" value="'.(isset($_POST['t_name']) ? sb_htmlspecialchars(strip_tags($_POST['t_name'])) : '').'" style="width: 300px;">
		        <input type="hidden" name="cat_id" id="cat_id" value="">
	        </form>
        </td>
    </tr>
    <tr><td class="footer" colspan="2">
        <div class="footer">
            <button onclick="chooseCat(true);">'.KERNEL_SAVE.'</button>&nbsp;&nbsp;&nbsp;
            <button onclick="sbCloseDialog();">'.KERNEL_CANCEL.'</button>
        </div>
    </td></tr></table>
    <script>
        function sbFocus()
        {
            sbSelectField("t_name");
        }

        sbAddEvent(window, "load", sbFocus);
    </script>';

    $categs = new sbJustCategs('pl_texts');

    if (isset($_POST['cat_id']))
        $categs->mCategsSelectedIds = intval($_POST['cat_id']);

    $categs->setCategsEvent('pl_texts_init');
    $categs->mCategsClosed = false;
    $categs->mCategsRubrikator = false;

    $categs->mCategsJavascriptStr = $js_str;

    $categs->showTree($footer_str);
    $categs->init();
}

function fEditor_New_Text_Submit()
{
    $row = array();
    if (isset($_GET['default']) && $_GET['default'] == 1)
        $default = true;
    else
        $default = false;

    if ($default)
        $row['t_name'] = PL_EDITOR_H_NEW_TEXT_DEFAULT_TITLE;
    else
        $row['t_name'] = strip_tags($_POST['t_name']);

    $row['t_html'] = '&nbsp;';

    if($_SESSION['sbPlugins']->isPluginAvailable('pl_workflow') && $_SESSION['sbPlugins']->isPluginInWorkflow('pl_texts'))
    {
    	$row['t_status'] = current(sb_get_avail_workflow_status('pl_texts'));
    }
    else
    {
    	$row['t_status'] = 1;
    }

    $error = true;
    if (sql_query('INSERT INTO sb_texts (?#) VALUES (?a)', array_keys($row), array_values($row)))
    {
        $id = sql_insert_id();

        $_GET['plugin_ident'] = 'pl_texts';
        $_GET['link_src_cat_id'] = 0;
        if ($default)
        {
        	$default_cat_id = sbPlugins::getUserSetting('sb_editor_default_new_text');
        	$res = sql_query('SELECT COUNT(*) FROM sb_categs WHERE cat_id=?d AND cat_ident=?', $default_cat_id, 'pl_texts');
        	if (!$res || $res[0][0] <= 0 || !fCategs_Check_Rights($default_cat_id))
        	{
        		echo 'FALSE';
                sb_add_system_message(PL_EDITOR_H_ADD_ERROR, SB_MSG_WARNING);
                return;
        	}
			else
			{
				$_GET['cat_id'] = $default_cat_id;
			}
        }
        else
        {
        	$_GET['cat_id'] = intval($_POST['cat_id']);
        }

        if ($default)
        {
        	$row['t_name'] = $row['t_name'].' '.$id;
        	sql_query('UPDATE sb_texts SET t_name=? WHERE t_id=?d', $row['t_name'], $id);
        }

        if (fCategs_Add_Elem($id))
        {
            sb_add_system_message(sprintf(PL_EDITOR_H_NEW_TEXT_OK, $row['t_name']));
            $error = false;
        }
        else
        {
            sql_query('DELETE FROM sb_texts WHERE t_id=?d', $id);
        }
    }

    if ($error)
    {
        sb_add_system_message(sprintf(PL_EDITOR_H_ADD_SYSTEMLOG_ERROR, $row['t_name']), SB_MSG_WARNING);

        if ($default)
        {
        	echo 'FALSE';
        	return;
        }
        else
        {
        	sb_show_message(sprintf(PL_EDITOR_H_ADD_ERROR, $row['t_name']), false, 'warning');
            fEditor_New_Text();
            return;
        }
    }

    // связываем текст со страницей
    sql_query('DELETE FROM sb_elems WHERE e_id=?d AND e_link != ?', $_GET['e_id'], 'temp');

    $_GET['e_tag'] = urldecode($_GET['e_tag']);
    $res = sql_query('SELECT elems.e_search, elems.e_yandex FROM sb_elems elems, sb_pages pages WHERE pages.p_id=?d AND elems.e_p_id=pages.p_temp_id AND elems.e_link=? AND elems.e_tag=?', $_GET['p_id'], 'temp', $_GET['e_tag']);
    if ($res)
    {
    	list($e_search, $e_yandex) = $res[0];
    }
    else
    {
    	$e_search = 'none';
    	$e_yandex = 0;
    }

    $raw = array();
    $raw['e_tag'] = $_GET['e_tag'];
    $raw['e_ident'] = 'pl_texts_html';
    $raw['e_link'] = 'page';
    $raw['e_temp_id'] = -1;
    $raw['e_el_id'] = $id;
    $raw['e_p_id'] = intval($_GET['p_id']);
    $raw['e_params'] = serialize(array('id' => $id));
    $raw['e_search'] = $e_search;
    $raw['e_edit'] = 1;
    $raw['e_yandex'] = $e_yandex;
    if (!sql_query('INSERT INTO sb_elems (?#) VALUES (?a)', array_keys($raw), array_values($raw)))
    {
        sb_add_system_message(PL_EDITOR_H_BLOCK_REPLACE_ELEMS_ERROR, SB_MSG_WARNING);

        if ($default)
        {
            echo 'FALSE';
            return;
        }
        else
        {
        	sb_show_message(PL_EDITOR_H_BLOCK_REPLACE_ELEMS_ERROR, false, 'warning');
            fEditor_New_Text();
            return;
        }
    }

    $e_id = sql_insert_id();

    sql_query('INSERT INTO sb_catchanges (el_id, cat_ident, change_date, change_user_id, action) VALUES (?d, ?, ?d, ?d, ?)', $_GET['p_id'], 'pl_pages', time(), $_SESSION['sbAuth']->getUserId(), 'edit');

    echo '<script>
            var res = new Object();
            res.sb_tag = "'.$_GET['e_tag'].'";
            res.sb_type = "pl_texts_html";
            res.sb_el_id = '.$id.';
            res.sb_avail = 1;
            res.sb_e_id = '.$e_id.';
            res.sb_e_link = "page";
            res.sb_template = "";
            res.sb_edit = "";
            res.sb_settings_width = "";
            res.sb_settings_height = "";';

	if (!$default)
	    echo 'sbReturnValue(res);';

	echo '</script>';
}

function fEditor_Add_Block()
{
	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    echo '<script>
        function checkValues()
        {
            if (sbGetE("el_name").value == "")
            {
                alert("'.PL_EDITOR_H_ADD_BLOCK_ERROR_MSG.'");
                return false;
            }
        }
    </script>
    <br /><br />';

    $layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_editor_add_block_submit');
    $layout->mTableWidth = '95%';

    $layout->addHeader(PL_EDITOR_H_ADD_BLOCK_HEADER);
    $layout->addField(PL_EDITOR_H_ADD_BLOCK_NAME, new sbLayoutInput('text', isset($_POST['el_name']) ? $_POST['el_name'] : '', 'el_name', '', 'style="width: 300px"'));

    $options = array('down' => PL_EDITOR_H_ADD_BLOCK_DOWN, 'up' => PL_EDITOR_H_ADD_BLOCK_UP);
    $fld = new sbLayoutSelect($options, 'el_pos');
    if (isset($_POST['el_pos']))
    {
		$fld->mSelOptions = array($_POST['el_pos']);
    }
	$layout->addField(PL_EDITOR_H_ADD_BLOCK_POS, $fld);

	$p_id = isset($_GET['p_id']) ? intval($_GET['p_id']) : intval($_POST['p_id']);
	$e_tag = preg_replace('/[^_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+/'.SB_PREG_MOD, '', isset($_GET['e_tag']) ? $_GET['e_tag'] : $_POST['e_tag']);

	$layout->addField('', new sbLayoutInput('hidden', $p_id, 'p_id'));
	$layout->addField('', new sbLayoutInput('hidden', $e_tag, 'e_tag'));

    $layout->addButton('submit', KERNEL_ADD);
    $layout->addButton('button', KERNEL_CANCEL, '', '', 'onclick="sbCloseDialog()"');

    $layout->show();
}

function fEditor_Add_Block_Submit()
{
	#TODO: Добавить проверку на права доступа к темплейту
	if(preg_match('/[^_A-Za-z0-9'.$GLOBALS['sb_reg_upper_interval'].$GLOBALS['sb_reg_lower_interval'].'\s]+/'.SB_PREG_MOD, $_POST['el_name']))
    {
        sb_show_message(PL_EDITOR_H_ADD_BLOCK_ERROR1, false, 'warning');
        fEditor_Add_Block();
        return;
    }

    $res = sql_query('SELECT p_temp_id FROM sb_pages WHERE p_id=?d', $_POST['p_id']);
	if (!$res)
	{
		sb_show_message(PL_EDITOR_H_BLOCK_SETTINGS_ERROR, true, 'warning');
		return;
	}

	list($t_id) = $res[0];

	$res = sql_query('SELECT t.t_html, t.t_name, c.cat_id, l.link_id
		FROM sb_templates t, sb_categs c, sb_catlinks l
		WHERE t.t_id=?d AND c.cat_ident=? AND l.link_el_id=t.t_id
			AND l.link_cat_id=c.cat_id', $t_id, 'pl_templates');

	if (!$res)
	{
		sb_show_message(PL_EDITOR_H_BLOCK_SETTINGS_ERROR, true, 'warning');
		return;
	}

	list($t_html, $t_name, $cat_id, $link_id) = $res[0];

    $new_tag = '{'.str_replace(' ', '_', sb_strtoupper($_POST['el_name'])).'}';
    $old_tag = '{'.preg_replace('/[^_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+/'.SB_PREG_MOD, '', $_POST['e_tag']).'}';

    $str = array();
    preg_match_all('/(\{[_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+\})/'.SB_PREG_MOD, $t_html, $str);

    if(in_array($new_tag, $str[1]))
    {
        sb_show_message(PL_EDITOR_H_ADD_BLOCK_ERROR2, false, 'warning');
        fEditor_Add_Block();
        return;
    }

    $t_html = str_replace($old_tag, $_POST['el_pos'] == 'down' ? $old_tag.$new_tag : $new_tag.$old_tag, $t_html);
    sql_query('UPDATE sb_templates SET t_html=? WHERE t_id=?d', $t_html, $t_id, sprintf(PL_EDITOR_H_ADD_BLOCK_OK, $t_name));

	$_GET['plugin_ident'] = 'pl_templates';
    $_GET['link_src_cat_id'] = 0;
    $_GET['cat_id'] = $cat_id;
    $_GET['link_id'] = $link_id;
    $_GET['id'] = $t_id;

    fCategs_Edit_Elem();

	echo '<script>
	        sbReturnValue(true);
		  </script>';
}

function fEditor_Move_Block()
{
	$from_id = intval($_GET['from_id']);
	$to_id = intval($_GET['to_id']);
	$page_id = intval($_GET['page_id']);

	$res = sql_query('SELECT pages.p_name, categs.cat_id, links.link_id FROM sb_pages pages, sb_categs categs, sb_catlinks links WHERE pages.p_id=?d AND categs.cat_ident=? AND links.link_el_id=pages.p_id AND links.link_cat_id=categs.cat_id', $page_id, 'pl_pages');
	if (!$res)
	{
		sb_add_system_message(PL_EDITOR_H_MOVE_BLOCK_ERROR_PAGE, SB_MSG_WARNING);
		echo 'FALSE';
		return;
	}

	list($p_name, $cat_id, $link_id) = $res[0];

	$from_tag = '{'.preg_replace('/[^_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+/'.SB_PREG_MOD, '', $_GET['from_tag']).'}';
	$to_tag = '{'.preg_replace('/[^_A-Z0-9'.$GLOBALS['sb_reg_upper_interval'].']+/'.SB_PREG_MOD, '', $_GET['to_tag']).'}';

	if ($from_tag == '{}' || $to_tag == '{}')
	{
		sb_add_system_message(sprintf(PL_EDITOR_H_MOVE_BLOCK_ERROR, $p_name), SB_MSG_WARNING);
		echo 'FALSE';
		return;
	}

	$res = sql_query('SELECT e_id, e_ident, e_temp_id, e_el_id, e_params, e_search, e_edit, e_yandex
		FROM sb_elems WHERE e_id IN (?d, ?d) AND e_link != ? AND e_ident != ?', $from_id, $to_id, 'temp', '');

	if (!$res)
	{
		sb_add_system_message(sprintf(PL_EDITOR_H_MOVE_BLOCK_ERROR_BLOCK, trim($from_tag, '{}'), $p_name), SB_MSG_WARNING);
		echo 'FALSE';
		return;
	}

	$row_from = array();
	$row_to = array();

	foreach ($res as $value)
	{
		list($e_id, $e_ident, $e_temp_id, $e_el_id, $e_params, $e_search, $e_edit, $e_yandex) = $value;

		if ($e_id == $from_id)
		{
			$row_from['e_tag'] = $to_tag;
			$row_from['e_ident'] = $e_ident;
			$row_from['e_link'] = 'page';
			$row_from['e_temp_id'] = $e_temp_id;
			$row_from['e_el_id'] = $e_el_id;
			$row_from['e_p_id'] = $page_id;
			$row_from['e_params'] = $e_params;
			$row_from['e_search'] = $e_search;
			$row_from['e_edit'] = $e_edit;
			$row_from['e_yandex'] = $e_yandex;
		}
		elseif ($e_id == $to_id)
		{
			$row_to['e_tag'] = $from_tag;
			$row_to['e_ident'] = $e_ident;
			$row_to['e_link'] = 'page';
			$row_to['e_temp_id'] = $e_temp_id;
			$row_to['e_el_id'] = $e_el_id;
			$row_to['e_p_id'] = $page_id;
			$row_to['e_params'] = $e_params;
			$row_to['e_search'] = $e_search;
			$row_to['e_edit'] = $e_edit;
			$row_to['e_yandex'] = $e_yandex;
		}
	}

	sql_query('DELETE FROM sb_elems WHERE e_id IN (?d, ?d) AND e_link != ?', $from_id, $to_id, 'temp');

	if (count($row_to) != 0)
	{
		sql_query('INSERT INTO sb_elems (?#) VALUES (?a)', array_keys($row_to), array_values($row_to));
	}

	if (count($row_from) != 0)
	{
		sql_query('INSERT INTO sb_elems (?#) VALUES (?a)', array_keys($row_from), array_values($row_from));
	}

	sb_add_system_message(sprintf(PL_EDITOR_H_MOVE_BLOCK_OK, trim($from_tag, '{}'), trim($to_tag, '{}'), $p_name));

	$_GET['plugin_ident'] = 'pl_pages';
	$_GET['link_src_cat_id'] = 0;
	$_GET['cat_id'] = $cat_id;
	$_GET['link_id'] = $link_id;
	$_GET['id'] = $page_id;

	fCategs_Edit_Elem();

	if (!$_SESSION['sbPlugins']->isRightAvailable('pl_pages', 'elems_public'))
    {
    	sql_query('UPDATE sb_pages SET p_state=0 WHERE p_id=?d AND p_state=1', $page_id);
    }
	elseif (!fPages_Gen_Page($page_id))
	{
		echo 'ERROR_PAGE';
		return;
	}
}
