<?php
/**
 * Построение диалогового окна для указания вызываемой функции
 *
 * @return void
 */
function fCode_getFunc()
{
    $params = unserialize($_GET['params']);

    echo '<script>
        function selectParams()
        {
            var res = {};
            var params = [];

            params["execute_code"] = sbGetE("execute_code").value;

            res.params = sb_serialize(params);

            sbReturnValue(res);
        }
        </script>';

    $layout = new sbLayout();
    $layout->mTableWidth = '100%';
    $layout->mTitleWidth = '200';

    $html = '<div class="hint_div">'.PL_CODE_H_HINT.'</div>';
    $layout->addField('', new sbLayoutHTML(nl2br($html), true));

    $fld = new sbLayoutInput('text', (isset($params['execute_code']) ? $params['execute_code'] : ''), 'execute_code', 'execute_code', ' style="width:90%"', true);
    $layout->addField('PHP-выражение', $fld);

    $layout->addButton('button', KERNEL_SAVE, '', '', 'onclick="selectParams();"');
    $layout->addButton('button', KERNEL_CANCEL, '', '', 'onclick="sbCloseDialog();"');

    $layout->show();
}

/**
 * Вывод подсказки
 *
 * @param Array $params
 * @return void
 */
function fCode_Info($params)
{
    echo ' <div style="padding-bottom: 5px;"><b>'.PL_CODE_H_INFO_FUNC.':</b> '.(isset($params['execute_code']) ? $params['execute_code'] : '').'</div>';
}

/**
 * Генерация кода
 *
 * @param $el_id
 * @param $temp_id
 * @param $params
 * @param $tag_id
 * @return String
 */
function fCode_Generate($el_id, $temp_id, $params, $tag_id)
{
    if (isset($params['execute_code']))
    {
        $code = $params['execute_code'];


        return "<?php\n
            $code\n
        ?>";
    }

    return "";
}