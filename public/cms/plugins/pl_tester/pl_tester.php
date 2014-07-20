<?php
/**
 * Функции управления вопросами
 */
function fTester_Get_Question($args)
{
	$question = $args['stq_question'];
    $answers = sql_param_query('SELECT COUNT(*) FROM sb_tester_answers WHERE stw_quest_id="'.$args['stq_id'].'"');

    if ($answers)
    {
        list($num_answers) = $answers[0][0];
    }
    else
    {
       $num_answers = 0;
    }

    switch ($args['stq_type'])
    {
        case 'radio': $args['stq_type'] = PL_TESTER_EDIT_TYPE_RADIO;
           break;
        case 'checkbox': $args['stq_type'] = PL_TESTER_EDIT_TYPE_CHECKBOX;
           break;
        case 'sorting': $args['stq_type'] = PL_TESTER_EDIT_TYPE_SORTING;
           break;
        case 'inline': $args['stq_type'] = PL_TESTER_EDIT_TYPE_INLINE;
           break;
    }

	if ($_SESSION['sbPlugins']->isRightAvailable('pl_tester', 'elems_edit'))
	{
		$result = '<b><a href="javascript:void(0);" onclick="sbElemsEdit(event);" id="stq_question_'.$args['stq_id'].'">'.$question.'</a></b>';
	}
	else
	{
		$result = '<b class="elem_title">'.$question.'</b>';
	}
	$result .= '<div class="smalltext" style="margin-top:5px;">';
	$result .= '<span>'.PL_TESTER_QUESTION_ID.': <span style="color: green;">'.$args['stq_id'].'</span><br />
				<span>'.PL_TESTER_GET_TYPE.': <span style="color: green;">'.$args['stq_type'].'</span><br />
				<span>'.PL_TESTER_GET_NUM_ANSWERS.': '.$num_answers.'</span><br />
				<span>'.PL_TESTER_GET_ORDER.': '.$args['stq_order'].'</span>';

	sb_get_workflow_status($result, 'pl_tester', $args['stq_show']);
	
	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');
    $result .= sbLayout::getPluginFieldsInfo('pl_tester', $args);
	
	$result .= '</div>';
	return $result;
}

function fTester_Questions()
{
	require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');
	$elems = new sbElements('sb_tester_questions', 'stq_id', 'stq_question', 'fTester_Get_Question', 'pl_tester_questions', 'pl_tester');
    $elems->mCategsDeleteWithElementsMenu = true;
    $elems->mCategsAfterDeleteWithElementsEvent = 'pl_tester_delete_cat_with_elements';

	$elems->mCategsRootName = PL_TESTER_ROOT_NAME;

    $elems->addField('stq_question');
    $elems->addField('stq_type');
    $elems->addField('stq_order');
    $elems->addField('stq_show');

    $elems->addFilter(PL_TESTER_QUESTION_ID, 'stq_id', 'number');
    $elems->addFilter(PL_TESTER_DESIGN_EDIT_QUESTION_TAG, 'stq_question', 'string');

    $elems->addSorting(PL_TESTER_SORT_BY_ID, 'stq_id');
    $elems->addSorting(PL_TESTER_SORT_BY_ORDER, 'stq_order');
    $elems->addSorting(PL_TESTER_SORT_BY_TYPE, "stq_type");

    $elems->mElemsEditEvent =  'pl_tester_edit_question';
    $elems->mElemsEditDlgWidth = 800;
    $elems->mElemsEditDlgHeight = 700;

    $elems->mElemsAddEvent =  'pl_tester_edit_question';
    $elems->mElemsAddDlgWidth = 800;
    $elems->mElemsAddDlgHeight = 700;

    $elems->mElemsAfterPasteEvent = 'pl_tester_after_paste_question';
	$elems->mCategsPasteWithElementsMenu = true;
	$elems->mCategsAfterPasteWithElementsEvent = 'pl_tester_after_paste_with_elements';

    $elems->mCategsEditEvent = 'pl_tester_edit_test';
    $elems->mCategsEditDlgWidth = 800;
    $elems->mCategsEditDlgHeight = 800;

    $elems->mCategsAddEvent = 'pl_tester_edit_test';
    $elems->mCategsAddDlgWidth = 800;
    $elems->mCategsAddDlgHeight = 800;
	$elems->mCategsRubrikator = true;

	$elems->mElemsDeleteEvent = 'pl_tester_delete_question';
	$elems->mCategsDeleteEvent = 'pl_tester_delete_test';
	$elems->mCategsDeleteWithElementsMenu = true;

	echo '<script src="'.SB_CMS_JSCRIPT_URL.'/sbTree.js.php"></script>';

	$elems->mElemsJavascriptStr = '';
	if($_SESSION['sbPlugins']->isRightAvailable('pl_tester', 'elems_public') && (!$_SESSION['sbPlugins']->isPluginAvailable('pl_workflow') || !$_SESSION['sbPlugins']->isPluginInWorkflow('pl_tester')))
	{
		$elems->mElemsJavascriptStr .= '
					function setShow()
		            {
		            	var ids = "0";
		                for (var i = 0; i < sbSelectedEls.length; i++)
					    {
							var el = sbGetE("el_" + sbSelectedEls[i]);
							if (el)
								ids += "," + el.getAttribute("el_id");
					    }

		                var res = sbLoadSync("'.SB_CMS_EMPTY_FILE.'?event=pl_tester_show_question&ids=" + ids);
		                if (res != "TRUE")
		                {
		                    alert("'.PL_TESTER_SHOWHIDE_ERROR.'");
		                    return;
		                }

						var div_el = sbGetE("elems_list_div");
						var from = "";
						if(div_el)
						{
		                    from = div_el.getAttribute("from");
						}

						var url = sb_cms_empty_file + "?event=" + sb_elems_event + "&id=" + sbCatTree.getSelectedItemId() + "&page_elems=" + from;
						sbElemsShow(url);
					}';

		$elems->addElemsMenuItem(PL_TESTER_SHOWHIDE_QUESTION, 'setShow();');
	}

	if ($_SESSION['sbPlugins']->isRightAvailable('pl_tester', 'design_read'))
    {
        $elems->mElemsJavascriptStr .= 'function editQuestionTemp()
                  {
                        window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_tester_temps";
                  }

                  function editResultsTemp()
                  {
                        window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_tester_result_temps";
                  }

                  function editRatingTemp()
                  {
                        window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_tester_rating_temps";
                  }';
    }

    if ($_SESSION['sbPlugins']->isRightAvailable('pl_tester', 'design_read'))
    {
        $elems->addElemsMenuItem(PL_TESTER_QUESTION_TEMPS_MENU, 'editQuestionTemp();', false);
        $elems->addElemsMenuItem(PL_TESTER_QUESTION_RESULTS_TEMPS_MENU, 'editResultsTemp();', false);
        $elems->addElemsMenuItem(PL_TESTER_QUESTION_RATING_TEMPS_MENU, 'editRatingTemp();', false);
    }

    if ($_SESSION['sbPlugins']->isRightAvailable('pl_tester', 'categs_edit'))
    {
		$elems->mElemsJavascriptStr .= '
					function viewStatistic()
					{
						var cat_id = sbSelCat.id;

						var strPage = sb_cms_modal_dialog_file + "?event=pl_tester_view_test_statistic&cat_id=" + cat_id;
						var strAttr = "resizable=1,width=1100,height=700";
						sbShowModalDialog(strPage, strAttr, "");
					}';
		$elems->addCategsMenuItem(PL_TESTER_STATISTICS, 'viewStatistic();');
	}
	$elems->init();
}

function fTester_Answers_Output($id, $edit_group, $quest_type)
{
	$html = '<script>
         _sbPDPreventNsDrag = function (e, call)
        {
            if(e && e.preventDefault)
            {
                e.preventDefault();
                return false;
            }
            return false;
        }

        sbPDDraggingEl = null;
        sbPDOverEl = null;
        sbPDTempOverEvent = new Array();

        sbPDSetBorder = function(el, border)
        {
            if (el)
            {
                var childs = el.childNodes;
                for(var i = 0; i < childs.length; i++)
                {
                    if(childs[i].tagName == "TD")
                    {
                        if (_isIE)
                            childs[i].style.borderTop = "1px solid " + border;
                        else
                            childs[i].style.borderTop = "1px solid " + border;
                    }
                }
            }
        }

        sbPDChangeRowClass = function(el_id, cls)
        {
            var el_tr = sbGetE(el_id);
            if (el_tr)
            {
                if (!cls)
                {
                    if (_isIE)
                        el_tr.setAttribute("className", el_tr.getAttribute("oldClassName"));
                    else
                        el_tr.setAttribute("class", el_tr.getAttribute("oldClassName"));
                }
                else
                {
                    if (_isIE)
                    {
                        if (el_tr.getAttribute("className") != "drag")
                        {
                            el_tr.setAttribute("oldClassName", el_tr.getAttribute("className"));
                        }
                    }
                    else
                    {
                        if (el_tr.getAttribute("class") != "drag")
                        {
                            el_tr.setAttribute("oldClassName", el_tr.getAttribute("class"));
                        }
                    }

                    if (_isIE)
                        el_tr.setAttribute("className", cls);
                    else
                        el_tr.setAttribute("class", cls);
                }
            }
        }

        sbPDStartDrag = function(e, el)
        {
            sbAddEvent(document, "mousemove", sbPDDrag);
            sbAddEvent(document, "mouseup", sbPDStopDrag);

            if (sbPDDraggingEl && sbPDDraggingEl != el)
                sbPDChangeRowClass(sbPDDraggingEl.id, null);

            sbPDChangeRowClass(el.id, "drag");
            sbPDDraggingEl = el;

            if(_isIE)
            {
                window.document.body.setCapture();
            }
            else
            {
                if (window.parent.frames)
                {
                    for(var i=0; i<window.parent.frames.length; i++)
                    {
                        if(window.parent.frames[i] != window)
                        {
                            sbPDTempOverEvent[sbPDTempOverEvent.length] = window.parent.frames[i].onmouseover;
                            window.parent.frames[i].onmouseover = sbPDStopDrag;
                        }
                    }
                }
            }
        }

        sbPDDrag = function(e)
        {
            if (!sbPDDraggingEl)
            {
                sbPDOverEl = null;
                return;
            }

            var el = sbEventTarget(e);
            if (!el)
                return;

            while(el && el.tagName != "TR")
                el = el.parentNode;

            if (!el)
                return;

            var table = el;
            while(table.tagName != "TABLE")
                table = table.parentNode;

            if (table.id != "pl_tester_answer_table")
                return;

            var first_tr = table.tBodies[0].childNodes[0];

            var tab = sbGetE("sb_tabs_con");
            var a1=sbGetAbsoluteTop(el);
            var a2=sbGetAbsoluteTop(tab);

            if(a1-a2-parseInt(tab.scrollTop)>parseInt(tab.offsetHeight)-50)
                tab.scrollTop=parseInt(tab.scrollTop)+30;
            if(a1-a2<parseInt(tab.scrollTop)+30)
                tab.scrollTop=parseInt(tab.scrollTop)-30;

            if (el == sbPDDraggingEl || el == sbPDDraggingEl.nextSibling || el == first_tr)
            {
                sbPDSetBorder(sbPDOverEl, "#FFF5E0");
                sbPDOverEl = null;
                return;
            }

            sbPDSetBorder(sbPDOverEl, "#FFF5E0");
            sbPDSetBorder(el, "black");
            sbPDOverEl = el;
        }

        sbPDStopDrag = function(e)
        {
            if(_isIE)
            {
                window.document.body.releaseCapture();
            }
            else
            {
                if (window.parent.frames)
                {
                    for(var i=0; i<window.parent.frames.length; i++)
                    {
                        if(window.parent.frames[i] != window)
                        {
                            window.parent.frames[i].onmouseover = sbPDTempOverEvent.shift();
                        }
                    }
                }
            }

            sbRemoveEvent(document, "mousemove", sbPDDrag);
            sbRemoveEvent(document, "mouseup", sbPDStopDrag);

            if (!sbPDDraggingEl || !sbPDOverEl)
                return;

            sbPDSetBorder(sbPDOverEl, "#FFF5E0");

            var table = sbGetE("pl_tester_answer_table");
            table = table.tBodies[0];
            table.insertBefore(sbPDDraggingEl, sbPDOverEl);

            for (var i = 1; i < table.rows.length; i++)
            {
                if (i % 2 == 0)
                {
                    if (_isIE)
                        table.rows[i].setAttribute("className", "even");
                    else
                        table.rows[i].setAttribute("class", "even");
                    table.rows[i].setAttribute("oldClassName", "even");
                }
                else
                {
                    if (_isIE)
                        table.rows[i].removeAttribute("className");
                    else
                        table.rows[i].removeAttribute("class");
                    table.rows[i].removeAttribute("oldClassName");
                }
            }
            sbPDDraggingEl = null;
            sbPDOverEl = null;
            resortAll();
        }

        var lastSortPosition = 0;
        var editingAnswerId = -1;

        function editAnswerClick(answer_id)
        {
            var el_mark = sbGetE("spin_mark");
            var el_cur_answer = sbGetE("stw_answer_" + answer_id + "_answer");
            var el_cur_mark = sbGetE("stw_answer_" + answer_id + "_mark");
            var any_answer = sbGetE("any_answer");

            editingAnswerId = answer_id;

            var img = sbGetE("img_"+editingAnswerId);
            if(img)
            {
                any_answer.disabled = false;
                any_answer.checked = true;
            }

            var cancelButton = sbGetE("answerCancelButton");
            var addButton = sbGetE("answerAddButton");

			addButton.innerHTML = "'.PL_TESTER_EDIT_EDIT_ANSWER.'";
			cancelButton.style.display = "inline";

			if (window.sbCodeditor_new_answer)
           	{
           		sbCodeditor_new_answer.setCode(el_cur_answer.value);
           	}
           	else
           	{
				var el_answer = sbGetE("new_answer");
				el_answer.value = el_cur_answer.value;
           	}

			el_mark.value = el_cur_mark.value;

            window.scrollTo(0, 0);
            return false;
        }

        sbPDAddTr = function(id, answer, mark, any_answer)
        {
            var table = sbGetE("pl_tester_answer_table");
            var null_tr = sbGetE("null_tr");

            if (!table || !null_tr)
                return;

            if (null_tr.className != "")
            {
                var cls = "even";
                if (_isIE)
                    null_tr.removeAttribute("className");
                else
                    null_tr.removeAttribute("class");
            }
            else
            {
                var cls = "";
                null_tr.className = "even";
            }
            var tr = document.createElement("TR");
            tr.height = "31px";
            tr.setAttribute("id", id);
            if (_isIE)
                tr.setAttribute("className", cls);
            else
                tr.setAttribute("class", cls);
            tr.style.cursor = "default";
            if(_isIE)
            {
                tr.setAttribute("onmousedown", function(){sbPDStartDrag(event, this)});
            }
            else
            {
                tr.setAttribute("onmousedown", "sbPDStartDrag(event, this)");
            }

            // Первая ячейка
            var td1 = document.createElement("TD");
            td1.innerHTML = "<textarea style=\'width:1px;height:1px;visibility:hidden;\' id=\'stw_answer_" + id + "_answer\' name=\'stw_answer_" + id + "_answer\' onmousedown=\'_sbPDPreventNsDrag(event, true);\' ondragstart=\'_sbPDPreventNsDrag(event);\'>"+answer+"</textarea><input type=\'hidden\' name=\'stw_answer[]\' id=\'stw_answer_" + id + "_id\' value=\'" + id + "\'>";
            if(any_answer)
            {
                td1.innerHTML += "<img id=\'img_"+id+"\' src=\'/cms/images/tree/iconCheckAll.gif\'/><input type=\'hidden\' id=\'stw_answer_"+id+"_any\' name=\'stw_answer_"+id+"_any\' value=\'1\'/>";
            }
            td1.align = "center";
            td1.style.verticalAlign = "middle";
            td1.style.width = "5px";
            td1.style.borderTop = "1px solid FFF5E0";
            tr.appendChild(td1);

            // Вариант ответа
            var td2 = document.createElement("TD");
            td2.style.verticalAlign = "middle";
            td2.style.borderTop = "1px solid FFF5E0";
            td2.innerHTML = "<span id=\'stw_answer_" + id + "_answer_text\'>" + answer + "</span>";
            tr.appendChild(td2);

            // Оценка за ответ
            var td3 = document.createElement("TD");
            td3.align = "center";
            td3.style.verticalAlign = "middle";
            td3.style.borderTop = "1px solid FFF5E0";
            td3.style.width = "150px";
            td3.innerHTML = "<div id=\'stw_answer_" + id + "_mark_text\'>" + mark + "</div>";
            tr.appendChild(td3);

            // Редактировать
            var td4 = document.createElement("TD");
            td4.align = "center";
            td4.style.verticalAlign = "middle";
            td4.style.borderTop = "1px solid FFF5E0";
            td4.style.width = "30px";
            td4.innerHTML = "<img src=\'/cms/images/btn_props.png\' title=\''.PL_TESTER_EDIT_PROPS_LABEL.'\' onmousedown=\'_sbPDPreventNsDrag(event, true);\' ondragstart=\'_sbPDPreventNsDrag(event);\' onclick=\'return editAnswerClick(" + id + ");\' style=\'cursor: pointer; margin:0px 8px 0px 8px;\' width=\'20\' border=\'0\' height=\'20\'>";
            tr.appendChild(td4);

            // Удалить
            var td5 = document.createElement("TD");
            td5.align = "center";
            td5.style.verticalAlign = "middle";
            td5.style.borderTop = "1px solid FFF5E0";
            td5.style.width = "30px";
            td5.innerHTML = "<img src=\'/cms/images/btn_delete.png\' title=\''.PL_TESTER_EDIT_DEL_LABEL.'\' onmousedown=\'_sbPDPreventNsDrag(event, true);\' ondragstart=\'_sbPDPreventNsDrag(event);\' onclick=\'deleteAnswerClick(" + id + ");\' style=\'cursor: pointer; margin:0px 8px 0px 8px;\' width=\'20\' height=\'20\'><input type=\'hidden\' name=\'stw_answer_" + id + "_order\' id=\'stw_answer_" + id + "_order\' value=\'"+lastSortPosition+"\'><input type=\'hidden\' name=\'stw_answer_" + id + "_mark\' id=\'stw_answer_" + id + "_mark\' value=\'"+mark+"\'>";
            tr.appendChild(td5);

            var el = table.tBodies[0];
            if(el)
            {
                el.insertBefore(tr, null_tr);
            }
        }

        function addAnswerClick()
        {
            if (editingAnswerId == -1)
            {
				if (window.sbCodeditor_new_answer)
	           	{
					var el_answer = sbCodeditor_new_answer.getCode();
	           	}
				else
	           	{
					var el_answer = sbGetE("new_answer").value;
	           	}

				if (!el_answer)
                {
                    sbShowMsgDiv("'.PL_TESTER_EDIT_NO_ANSWER_TEXT.'", "warning.png");
                    return false;
                }
                var el_mark = sbGetE("spin_mark");
                var any_answer = sbGetE("any_answer");
                var newId = new Number(new Date());

                lastSortPosition++;

                sbPDAddTr(newId, el_answer, el_mark.value, (any_answer && any_answer.checked && !any_answer.disabled));
                if(any_answer && any_answer.checked)
                {
                    any_answer.disabled = true;
                }

				if(window.sbCodeditor_new_answer)
	           	{
					sbCodeditor_new_answer.setCode("");
	           	}
				else
	           	{
					sbGetE("new_answer").value = "";
	           	}
				el_mark.value = 0;

                return false;
            }
            else
            {
                //изменение ответа
                var prevRow = sbGetE(editingAnswerId);
                if (prevRow)
                {
					if (window.sbCodeditor_new_answer)
		           	{
						var el_answer = sbCodeditor_new_answer.getCode();
		           	}
					else
		           	{
	                	var el_answer = sbGetE("new_answer").value;
		           	}

                    var el_mark = sbGetE("spin_mark");
                    var any_answer = sbGetE("any_answer");
                    var el_cur_answer = sbGetE("stw_answer_" + editingAnswerId + "_answer");
                    var el_cur_answer_text = sbGetE("stw_answer_" + editingAnswerId + "_answer_text");
                    var el_cur_mark = sbGetE("stw_answer_" + editingAnswerId + "_mark");
                    var el_cur_mark_text = sbGetE("stw_answer_" + editingAnswerId + "_mark_text");
                    var img = sbGetE("img_"+editingAnswerId);

                    if(any_answer && any_answer.checked && !any_answer.disabled)
                    {
                        any_answer.disabled = true;
                        if(!img)
                        {
                            var cell = sbGetE("stw_answer_"+editingAnswerId+"_id").parentNode;
                            var newImg = document.createElement("img");
                            newImg.id = "img_"+editingAnswerId;
                            newImg.src = "/cms/images/tree/iconCheckAll.gif";
                            cell.appendChild(newImg);

                            var input = document.createElement("input");
                            input.id = "stw_answer_"+editingAnswerId+"_any";
                            input.name = input.id;
                            input.value = "1";
                            input.type = "hidden";
                            cell.appendChild(input);
                        }
                    }
                    else if(any_answer && !any_answer.checked && img)
                    {
                        img.parentNode.removeChild(img);
                        var input = sbGetE("stw_answer_"+editingAnswerId+"_any");
                        input.parentNode.removeChild(input);
                    }

                    editingAnswerId = -1;

                    var cancelButton = sbGetE("answerCancelButton");
                    var addButton = sbGetE("answerAddButton");

                    addButton.innerHTML = "'.KERNEL_ADD.'";
                    cancelButton.style.display = "none";
                    el_cur_answer.value = el_answer;
                    el_cur_answer_text.innerHTML = el_answer;
                    el_cur_mark.value = el_mark.value;
                    el_cur_mark_text.innerHTML = el_mark.value;

					if (window.sbCodeditor_new_answer)
		           	{
						sbCodeditor_new_answer.setCode("");
		           	}
					else
		           	{
						sbGetE("new_answer").value = "";
		           	}

                    el_mark.value = 0;
                }
            }
            return false;
        }

        function deleteAnswerClick(answer_id)
        {
            //если удаляется произвольный ответ
            var img = sbGetE("img_"+answer_id);
            if(img)
            {
                var checkbox = sbGetE("any_answer");
                checkbox.disabled = false;
                checkbox.checked = false;
            }

            // определить строку в таблице
            var curRow = sbGetE(answer_id);
            curRow.removeNode(curRow);

	        var table = sbGetE("pl_tester_answer_table");
	        for (var i = 1; i < table.rows.length; i++)
	        {
	            if (i % 2 == 0)
	            {
	                if (_isIE)
	                    table.rows[i].setAttribute("className", "even");
	                else
	                    table.rows[i].setAttribute("class", "even");
	                table.rows[i].setAttribute("oldClassName", "even");
	            }
	            else
	            {
	                if (_isIE)
	                    table.rows[i].removeAttribute("className");
	                else
	                    table.rows[i].removeAttribute("class");
	                table.rows[i].removeAttribute("oldClassName");
	            }
	        }
            //Пересортировать все
            resortAll();
        }

        function resortAll()
        {
            var els = document.getElementsByName("stw_answer[]");
            if (els)
            {
                for (var i = 0; i < els.length; i++)
                {
                    var curPos = sbGetE("stw_answer_" + els[i].value + "_order");
                    curPos.value = parseInt(i + 1);
                }
            }
            lastSortPosition = els.length;
        }

        function cancelClick()
        {
			var el_mark = sbGetE("spin_mark");
            var img = sbGetE("img_"+editingAnswerId);
            if(img)
            {
                sbGetE("any_answer").checked = true;
                sbGetE("any_answer").disabled = true;
            }
			editingAnswerId = -1;

			var cancelButton = sbGetE("answerCancelButton");
			var addButton = sbGetE("answerAddButton");

            addButton.innerHTML = "'.KERNEL_ADD.'";
            cancelButton.style.display = "none";


			if (window.sbCodeditor_new_answer)
           	{
				sbCodeditor_new_answer.setCode("");
           	}
			else
           	{
				var el_answer = sbGetE("new_answer");
				el_answer.value = "";
			}
			el_mark.value = 0;
			return false;
		}
	</script>';

	$fld1 = new sbLayoutTextarea('', 'new_answer', 'new_answer', 'style="width:100%;height:100px"');
	$fld1->mShowToolbar = false;
	$fld1->mShowEnlargeBtn = false;
	$fld1->mShowEditorBtn = true;

	$fld2 = new sbLayoutInput('text', '0', 'spin_mark', 'spin_mark', 'style="width:120px;"');
	$fld2->mIncrement = '0.1';

    $count = 0;
    if(!$edit_group)
    {
        $res = sql_param_query('SELECT COUNT(*) FROM sb_tester_answers WHERE stw_any_answer = 1 AND stw_quest_id=?d', $id);
        if($res)
        {
            $count = $res[0][0];
        }
    }
    $fld_any_answer = new sbLayoutInput('checkbox', '1', 'any_answer', 'any_answer', ($count > 0)? 'checked="checked" disabled="disabled"' : '');

	$html .= '
		<table cellspacing="0" cellpadding="0" align="center" width="100%">
            <tr>
                <td colspan="2">
                    <div class="hint_div" style="text-align:center;">'.PL_TESTER_EDIT_ANSWER.'</div><br />
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    '.$fld1->getField().'
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <div class="hint_div" style="text-align:center;">'.PL_TESTER_EDIT_MARK.'</div><br />
                </td>
            </tr>
            <tr>
                <td>
                    '.$fld2->getField().preg_replace('/^(.*?float[ ]*?:[ ]*?)(right)([ ]*?;.*)/is', '$1left$3', sbGetGroupEditCheckbox('spin_mark', $edit_group)).'
                </td>
                <td>
                    '.((!$edit_group && 'sorting' != $quest_type)? $fld_any_answer->getField().' '.PL_TESTER_EDIT_ANY_ANSWER : '').'
                </td>
            </tr>
            <tr>
                <td align="center" colspan="2">
                    <div style="border-bottom:1px dotted #CBB49A; margin:3px;">&nbsp;</div>
                    <button onClick="return addAnswerClick();" id="answerAddButton"> '.KERNEL_ADD.'</button>&nbsp;&nbsp;<button onClick="return cancelClick();" style="display: none;" id="answerCancelButton"> '.KERNEL_CANCEL.'</button>
                </td>
            </tr>
        </table><br />';

    $html .= '
    <table cellspacing="0" cellpadding="5" width="100%" style="empty-cells: show; -moz-user-select: none;-khtml-user-select: none;" id="pl_tester_answer_table" class="form">
        <tr>
            <th class="header">&nbsp;</th>
            <th class="header">'.PL_TESTER_EDIT_ANSWER.'</th>
            <th class="header" width="150">'.PL_TESTER_EDIT_MARK.'</th>
            <th class="header" width="30">&nbsp;</th>
			<th class="header" width="30">&nbsp;</th>
		</tr>';

	$class = '';
	$res = 0;
	//	список уже имеющихся типов
	if(!$edit_group)
	{
		$res = sql_query('SELECT stw_id, stw_answer, stw_mark, stw_any_answer
					FROM sb_tester_answers
						WHERE stw_quest_id = ?d
                        AND stw_is_delete = 0
						ORDER BY stw_order', $id);

	    $class = ' class="even"';
	    if($res)
	    {
	        $count_res = count($res);
	        for ($i = 0; $i < $count_res; $i++)
	        {
	            list($stw_id, $stw_answer, $stw_mark, $stw_any_answer) = $res[$i];

	            if ($class == '')
	                $class = ' class="even"';
	            else
	                $class = '';

	            $html .= '
	            <tr '.$class.' id="'.$stw_id.'" onmousedown="sbPDStartDrag(event, this)" style="cursor:default; height:31px;">
	              <td align="center" style="vertical-align: middle; border-top: 1px solid FFF5E0; width:5px;">
	                  <textarea style="width: 1px; height: 1px;visibility:hidden;" id="stw_answer_'.$stw_id.'_answer" name="stw_answer_'.$stw_id.'_answer" onmousedown="_sbPDPreventNsDrag(event, true);" ondragstart="_sbPDPreventNsDrag(event);">'.htmlspecialchars($stw_answer, ENT_QUOTES).'</textarea><br />
	                  <input type="hidden" name="stw_answer[]" id="stw_answer_'.$stw_id.'_id" value="'.$stw_id.'">';

                if(1 == $stw_any_answer)
                {
                    $html .= '
                        <img id="img_'.$stw_id.'" src="/cms/images/tree/iconCheckAll.gif"/>
                        <input type="hidden" id="stw_answer_'.$stw_id.'_any" name="stw_answer_'.$stw_id.'_any" value="1"/>';
                }

                $html .= '
	              </td>
	              <td style="vertical-align: middle; border-top: 1px solid FFF5E0;">
	                  <span id="stw_answer_'.$stw_id.'_answer_text">'.($stw_answer != '' ? $stw_answer : '&nbsp;').'</span>
	              </td>
	              <td align="center" style="vertical-align: middle; border-top: 1px solid FFF5E0; width:150px;">
	                  <div id="stw_answer_'.$stw_id.'_mark_text">'.$stw_mark.'</div>
	              </td>
	              <td style="vertical-align: middle; border-top: 1px solid FFF5E0; width:30px;">
	                  <img src="/cms/images/btn_props.png" title="'.PL_TESTER_EDIT_PROPS_LABEL.'" onmousedown="_sbPDPreventNsDrag(event, true);" ondragstart="_sbPDPreventNsDrag(event);" onclick="return editAnswerClick(\''.$stw_id.'\');" style="cursor: pointer; margin:0px 8px 0px 8px;" width="20" border="0" height="20">
	              </td>
	              <td style="vertical-align: middle; border-top: 1px solid FFF5E0; width:30px;">
	                  <img src="/cms/images/btn_delete.png" title="'.PL_TESTER_EDIT_DEL_LABEL.'" onmousedown="_sbPDPreventNsDrag(event, true);" ondragstart="_sbPDPreventNsDrag(event);" onclick="deleteAnswerClick(\''.$stw_id.'\');" style="cursor: pointer; margin:0px 8px 0px 8px;" width="20" height="20">
	                  <input type="hidden" name="stw_answer_'.$stw_id.'_order" id="stw_answer_'.$stw_id.'_order" value="'.($i+1).'">
	                  <input type="hidden" name="stw_answer_'.$stw_id.'_mark" id="stw_answer_'.$stw_id.'_mark" value="'.$stw_mark.'">
	              </td>
	            </tr>';
	        }
	    }
	}

	if($class != '')
		$class = '';
	else
        $class = 'class="even"';

    $html .= '
            <tr id="null_tr" '.$class.'>
                <td style=""> </td>
                <td style=""> </td>
                <td style=""> </td>
                <td style=""> </td>
                <td style=""> </td>
            </tr>
        </table>
        <script>
            lastSortPosition = '.count($res).';
        </script>';

    return $html;
}

function fTester_Edit_Question($htmlStr = '', $footerStr = '', $footerLinkStr = '', $reload = false)
{
	$edit_group = sbIsGroupEdit();

	// проверка прав доступа
	if ($edit_group && !fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_tester'))
	{
		return;
	}
	else if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_tester'))
	{
		return;
	}

	$edit_rights = $_SESSION['sbPlugins']->isRightAvailable('pl_tester', 'elems_edit');
	if ((count($_POST) == 0 || $reload) && isset($_GET['id']) && $_GET['id'] != '' && !$edit_group)
    {
        $result = sql_param_query('SELECT stq_question, stq_type, stq_order, stq_show, stq_ball, stq_aps_write FROM sb_tester_questions WHERE stq_id=?d', $_GET['id']);
        if ($result)
        {
            list($stq_question, $stq_type, $stq_order, $stq_show, $stq_ball, $stq_aps_write) = $result[0];
            //$stq_question = htmlspecialchars($stq_question, ENT_QUOTES);
        }
        else
        {
            sb_show_message(PL_TESTER_EDIT_ERROR, true, 'warning');
            return;
        }
    }
    elseif(count($_POST) > 0 && $reload)
    {
        extract($_POST);
        $stq_ball = $spin_ball;
    }
    else
    {
        $stq_question = '';
        $stq_type = 'radio';
        $res = sql_query('SELECT MAX(stq_order) FROM sb_tester_questions;');
        if ($res)
        {
           list($stq_order) = $res[0];
           $stq_order += 10;
        }
        else
        {
           $stq_order = 0;
        }
        $stq_show = 1;
        $_GET['id'] = '';
        $stq_ball = 0;
    }
	echo '<script>';

	if(!$edit_group)
	{
		echo 'function checkValues()
            {
				if (window.sbCodeditor_stq_question)
            	{
            		var question = sbCodeditor_stq_question.getCode();
            	}
            	else
            	{
            		var question = sbGetE("stq_question").value;
            	}

				if (!question)
                {
                    alert("'.PL_TESTER_EDIT_NO_QUESTION.'");
                    return false;
                }

                if (sbGetE("pl_tester_answer_table").rows.length < 3)
                {
                    alert("'.PL_TESTER_EDIT_NO_ANSWERS.'");
                    return false;
                }
            }';
	}

	if ($htmlStr != '')
	{
		echo '
			function cancel()
            {';
			if($edit_group)
			{
				echo 'sbReturnValue("refresh");';
			}
			else
			{
				echo 'var res = new Object();
			        res.html = "'.$htmlStr.'";
			        res.footer = "'.$footerStr.'";
			        res.footer_link = "'.$footerLinkStr.'";
					sbReturnValue(res);';
			}
		echo '}
			sbAddEvent(window, "close", cancel);';
	}
	elseif($edit_group)
	{
		echo '
			function cancel()
            {
				sbReturnValue("refresh");
			}
			sbAddEvent(window, "close", cancel);';
	}
    echo '
        function checkType()
        {
            var select = sbGetE("stq_type");
            var any_answer = sbGetE("any_answer");
            var ball = sbGetE("ball_tr");

            if(select.value == "sorting" || select.value == "inline")
            {
                ball_tr.setAttribute("style", "display: table-row;");
                any_answer.checked = false;
                any_answer.disabled = true;
            }
            else
            {
                ball_tr.style.display = "none";
                any_answer.disabled = false;
            }
        }
        ';
	echo '</script>';

	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');
    $layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_tester_update_question'.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), 'thisDialog', 'post', 'checkValues()', 'main', 'name="main" enctype="multipart/form-data"');

    $layout->mTableWidth = '100%';
    $layout->mTitleWidth = '200';

    $layout->addTab(PL_TESTER_EDIT_TAB1);
    $layout->addHeader(PL_TESTER_EDIT_TAB1);
	
	$txt_field = new sbLayoutTextarea($stq_question, 'stq_question', 'stq_question', 'style="width:100%;height:90px;"');
	$txt_field->mShowEditorBtn = true;
	$layout->addField(PL_TESTER_EDIT_TAB1.sbGetGroupEditCheckbox('stq_question', $edit_group), $txt_field);


    $type = array();
    $type['radio'] = PL_TESTER_EDIT_TYPE_RADIO;
    $type['checkbox'] = PL_TESTER_EDIT_TYPE_CHECKBOX;
    $type['sorting'] = PL_TESTER_EDIT_TYPE_SORTING;
    $type['inline'] = PL_TESTER_EDIT_TYPE_INLINE;


    $fld = new sbLayoutSelect($type, 'stq_type', 'stq_type' ,' onchange="checkType()"');
    $fld->mSelOptions = array($stq_type);
    $layout->addField(PL_TESTER_EDIT_TYPE.sbGetGroupEditCheckbox('stq_type', $edit_group), $fld);
    $layout->addField(PL_TESTER_EDIT_BALL_FOR_QUEST.sbGetGroupEditCheckbox('spin_ball', $edit_group), new sbLayoutInput('text', $stq_ball, 'spin_ball', 'spin_ball', 'style="width:120px;"'), '', '', 'id="ball_tr"');
    $layout->addField(PL_TESTER_EDIT_ORDER.sbGetGroupEditCheckbox('stq_order', $edit_group), new sbLayoutInput('text', $stq_order, 'stq_order', 'spin_order', 'style="width:80px;"'));

	if (!$edit_group && $_SESSION['sbPlugins']->isRightAvailable('pl_tester', 'elems_public'))
	{
		$edit_rights = $edit_rights && sb_add_workflow_status($layout, 'pl_tester', $_GET['id'], 'stq_show', $stq_show);
	}
	elseif($edit_group)
	{
		$states_arr = array();
		$states = sql_query('SELECT stq_show FROM sb_tester_questions WHERE stq_id IN (?a)', $_GET['ids']);

		if ($states)
		{
			foreach($states as $val)
			{
				$states_arr[] = $val[0];
			}
		}
		if ($_SESSION['sbPlugins']->isRightAvailable('pl_tester', 'elems_public'))
	    {
			$edit_rights = $edit_rights && sb_add_workflow_status($layout, 'pl_tester', $_GET['ids'], 'stq_show', $stq_show);
	    }
	}
	
	if ( $stq_aps_write ) $param_checked = 'checked = "checked"';
	else $param_checked = '';
	
	$fld = new sbLayoutInput('checkbox', 1, 'stq_aps_write', 'stq_aps_write', $param_checked);
	$layout->addField(PL_TESTER_CALCULATE_ABSOLUTE_WRITE_ANSWER, $fld);
	

    $layout->addField('', new sbLayoutDelim());

    $layout->getPluginFields('pl_tester', $_GET['id'], 'stq_id', false, $edit_group);

    $layout->addTab(PL_TESTER_EDIT_TAB2);
    $layout->addHeader(PL_TESTER_EDIT_TAB2);

    $html = fTester_Answers_Output($_GET['id'], $edit_group, $stq_type);

    $layout->addField('', new sbLayoutHTML($html, true));

    $layout->addButton('submit', KERNEL_SAVE, 'btt_save', '', ($edit_rights ? '' : 'disabled="disabled"'));
    $layout->addButton('button', KERNEL_CANCEL, '', '', $htmlStr != '' ? 'onclick="cancel()"' : 'onclick="sbCloseDialog()"');

    $layout->show();
    echo '<script type="text/javascript">checkType();</script>';
}

function fTester_Update_Question()
{
	$edit_group = sbIsGroupEdit();

	//	проверка прав доступа
	if ($edit_group)
	{
		if (!fCategs_Check_Elems_Rights($_GET['ids'][0], $_GET['cat_id'], 'pl_tester'))
		{
			return;
		}
	}
	else if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_tester'))
	{
		return;
	}
	if (!isset($_GET['id']))
        $_GET['id'] = '';

    $stw_answer = array();
    $stq_question = $stq_type = '';
    $stq_show = $stq_order = 0;
	$ch_stq_question = $ch_stq_type = $ch_stq_order = $ch_stq_show = $ch_spin_mark = 0;

	extract($_POST);

    if ((!$edit_group || $edit_group && $ch_stq_question == 1) && $stq_question == '')
    {
        sb_show_message(PL_TESTER_EDIT_NO_QUESTION_MSG, false, 'warning');
        fTester_Edit_Question('', '', '', true);
        return;
    }

    // пользовательские поля
    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $layout = new sbLayout();
    $user_row = array();
    $user_row = $layout->checkPluginFields('pl_tester', ($edit_group ? $_GET['ids'] : $_GET['id']), 'su_id', false, $edit_group);

    if ($user_row === false)
    {
        $layout->deletePluginFieldsFiles();
        fTester_Edit_Question('', '', '', true);
        return;
    }

    $stq_show = intval($stq_show);

    $rows = array();

	if (!$edit_group || $edit_group && $ch_stq_question == 1)
	{
	    $rows['stq_question'] = $stq_question;
	}
	if (!$edit_group || $edit_group && $ch_stq_type == 1)
	{
	    $rows['stq_type'] = $stq_type;
	}
	if (!$edit_group || $edit_group && $ch_stq_order == 1)
	{
		$rows['stq_order'] = $stq_order;
	}
    if (!$edit_group || $edit_group && $ch_stq_ball_quest == 1)
	{
		$rows['stq_ball'] = $spin_ball;
	}
	if ( isset($stq_aps_write) )
	{
		$rows['stq_aps_write'] = $stq_aps_write;
	}
	else
	{
		$rows['stq_aps_write'] = 0;
	}
	
    $rows = array_merge($rows, $user_row);

	sb_submit_workflow_status($rows, 'stq_show', '', '', $edit_group);
	$id = $_GET['id'];

	if ($edit_group || $id != '')
	{
		if (!$edit_group)
		{
	        $res = sql_query('SELECT stq_question FROM sb_tester_questions WHERE stq_id=?d', $id);
		}
		else
		{
			//Обяъвляем в true просто для того чтобы пройти проверку ниже
			$res = true;
		}

		if ($res)
        {
	        if (!$edit_group)
	        {
	            // редактирование
	            list($old_title) = $res[0];
            	sql_query('UPDATE sb_tester_questions SET ?a WHERE stq_id=?d', $rows, $_GET['id'], sprintf(PL_TESTER_EDIT_OK, $old_title));
	        }
	        else if(count($rows) > 0)
	        {
				sql_query('UPDATE sb_tester_questions SET ?a WHERE stq_id IN (?a)', $rows, $_GET['ids'], PL_TESTER_EDIT_GROUP_OK);
			}

            $footer_ar = fCategs_Edit_Elem();

            if (!$edit_group)
            {
	            if (!$footer_ar)
	            {
	                sb_show_message(PL_TESTER_EDIT_ERROR, false, 'warning');
	                sb_add_system_message(sprintf(PL_TESTER_EDIT_SYSTEMLOG_ERROR, $old_title), SB_MSG_WARNING);

	                $layout->deletePluginFieldsFiles();
	                fTester_Edit_Question('', '', '', true);
	                return;
	            }

	            $footer_str = $GLOBALS['sbSql']->escape($footer_ar[0], false, false);
	            $footer_link_str = $GLOBALS['sbSql']->escape($footer_ar[1], false, false);

	            $rows['stq_id'] = intval($_GET['id']);

	            $html_str = fTester_Get_Question($rows);
	            $html_str = sb_str_replace(array("\r\n", "\r", "\n"), '', $html_str);
	            $html_str = $GLOBALS['sbSql']->escape($html_str, false, false);
            }


            if (!$edit_group)
    	    {
				echo '<script>
	                    var res = new Object();
	                    res.html = "'.$html_str.'";
	                    res.footer = "'.$footer_str.'";
	                    res.footer_link = "'.$footer_link_str.'";
	                    sbReturnValue(res);
	                 </script>';
			}
			else
			{
				echo '<script>
						sbReturnValue("refresh");
					</script>';
			}

            sb_mail_workflow_status('pl_tester', (!$edit_group ? $_GET['id'] : $_GET['ids']), $stq_question, $stq_show);
        }
        else
        {
            sb_show_message(PL_TESTER_EDIT_ERROR, false, 'warning');
            sb_add_system_message(sprintf(PL_TESTER_EDIT_SYSTEMLOG_ERROR, $stq_question), SB_MSG_WARNING);

            $layout->deletePluginFieldsFiles();
            fTester_Edit_Question('', '', '', true);
            return;
        }
    }
    else
    {
        $error = true;

        if (sql_param_query('INSERT INTO sb_tester_questions SET ?a', $rows))
        {
            $id = sql_insert_id();

            if (fCategs_Add_Elem($id))
            {
                sb_add_system_message(sprintf(PL_TESTER_EDIT_ADD_OK, $stq_question));

                echo '<script>
                        sbReturnValue('.$id.');
                      </script>';

				sb_mail_workflow_status('pl_tester', $id, $stq_question, $stq_show);
				$error = false;
            }
            else
            {
                sql_query('DELETE FROM sb_tester_questions WHERE stq_id="'.$id.'"');
            }
        }

        if ($error)
        {
            sb_show_message(sprintf(PL_TESTER_EDIT_ADD_ERROR, $stq_question), false, 'warning');
            sb_add_system_message(sprintf(PL_TESTER_EDIT_ADD_SYSTEMLOG_ERROR, $stq_question), SB_MSG_WARNING);

            $layout->deletePluginFieldsFiles();
            fTester_Edit_Question('', '', '', true);
            return;
        }
	}

	if(!$edit_group || $edit_group && $ch_spin_mark == 1)
	{
        // определяем какие ответы надо редактировать, добавлять, удалять
        if($edit_group && $ch_spin_mark == 1)
        {
            $res = sql_param_query('SELECT stw_id FROM sb_tester_answers WHERE stw_quest_id IN (?a) AND stw_is_delete=0', $_GET['ids']);
        }
        else
        {
            $res = sql_param_query('SELECT stw_id FROM sb_tester_answers WHERE stw_quest_id=?d AND stw_is_delete=0', $id);
        }
        $new = array();
        $del = array();
        if($res){
            $allAnswers = array();
            foreach ($res as $row)
            {
                $allAnswers[] = $row[0];
            }

            $new = array_diff($stw_answer, $allAnswers);
            $del = array_diff($allAnswers, $stw_answer);
        }
        else
        {
            $new = $stw_answer;
        }

        // Отмечаем удаленными соответствующие ответы из БД
        if(!$edit_group && !empty($del))
        {
            sql_param_query('UPDATE sb_tester_answers SET stw_is_delete=1 WHERE stw_id IN (?a)', $del);
        }

	    for ($i = 0; $i < count($stw_answer); $i++)
	    {
	        $stw_id = $stw_answer[$i];

	        $stw_answer_text = $_POST['stw_answer_'.$stw_id.'_answer'];
	        $stw_answer_order = $_POST['stw_answer_'.$stw_id.'_order'];
	        $stw_answer_mark = $_POST['stw_answer_'.$stw_id.'_mark'];
            $stw_answer_any = isset($_POST['stw_answer_'.$stw_id.'_any'])? isset($_POST['stw_answer_'.$stw_id.'_any']) : 0;

	        $rows = array();
	        $rows['stw_answer'] = $stw_answer_text;
	        $rows['stw_order'] = $stw_answer_order;
	        $rows['stw_mark'] = $stw_answer_mark;
            $rows['stw_any_answer'] = $stw_answer_any;

	        if($edit_group && $ch_spin_mark == 1)
	        {
	        	foreach($_GET['ids'] as $value)
	        	{
	        		$rows['stw_quest_id'] = $value;
					if (in_array($stw_id, $new))
                    {
                        sql_param_query('INSERT INTO sb_tester_answers SET ?a', $rows);
                        $id_answ = sql_insert_id();
                    }
                    else
                    {
                        $id_answ = sql_param_query('UPDATE sb_tester_answers SET ?a WHERE stw_id=?d', $rows, $stw_id);
                    }
			        if(!$id_answ)
			        {
			            sb_show_message(sprintf(PL_TESTER_EDIT_ADD_ANSWER_ERROR, $stw_answer_text), false, 'warning');
			            sb_add_system_message(sprintf(PL_TESTER_EDIT_ADD_ANSWER_SYSTEMLOG_ERROR, $stw_answer_text), SB_MSG_WARNING);

			            fTester_Edit_Question('', '', '', true);
			            return;
			        }
	        	}
			}
			else
			{
	        	$rows['stw_quest_id'] = $id;
                if(in_array($stw_id, $new))
                {
                    sql_param_query('INSERT INTO sb_tester_answers SET ?a', $rows);
                    $id_answ = sql_insert_id();
                }
                else
                {
                    $id_answ = sql_param_query('UPDATE sb_tester_answers SET ?a WHERE stw_id=?d', $rows, $stw_id);
                }

		        if(!$id_answ)
		        {
		            sb_show_message(sprintf(PL_TESTER_EDIT_ADD_ANSWER_ERROR, $stw_answer_text), false, 'warning');
		            sb_add_system_message(sprintf(PL_TESTER_EDIT_ADD_ANSWER_SYSTEMLOG_ERROR, $stw_answer_text), SB_MSG_WARNING);

		            fTester_Edit_Question('', '', '', true);
					return;
				}
			}
		}
	}
}

function fTester_Delete_Question()
{
    sql_param_query('DELETE FROM sb_tester_answers WHERE stw_quest_id= ?d', $_GET['id']);
}

function fTester_Del_Cat_With_Elems()
{
	if(isset($_GET['elems_id']) && $_GET['elems_id'] != '')
	{
		$elems = explode(',', $_GET['elems_id']);

		if(count($elems) > 0)
		{
			sql_query('DELETE FROM sb_tester_answers WHERE stw_quest_id IN (?a)', $elems);
		}
	}

	if(isset($_GET['c_ids']))
	{
		$elems = explode(',', $_GET['c_ids']);

	    sql_query('DELETE FROM sb_tester_marks_results WHERE stmr_test_id IN (?a) ', $elems);
	    sql_query('DELETE FROM sb_tester_results WHERE str_test_id IN (?a) ', $elems);
	    sql_query('DELETE FROM sb_tester_temp_interrupts WHERE stti_test_id IN (?a) ', $elems);
	    sql_query('DELETE FROM sb_tester_temp_results WHERE sttr_test_id IN (?a) ', $elems);
	    sql_query('DELETE FROM sb_tester_answers_results WHERE star_test_id IN (?a) ', $elems);
	}
}

function fTester_After_Paste_Question()
{
    if (!isset($_GET['action']) || $_GET['action'] != 'copy' || !isset($_GET['e']) || !is_array($_GET['e']) || count($_GET['e']) <= 0 || !isset($_GET['ne']) || !is_array($_GET['ne']) || count($_GET['ne']) <= 0)
        return;

    $els = array();

    foreach ($_GET['e'] as $key => $value)
    {
        $els[$_GET['e'][$key]] = $_GET['ne'][$key];
    }

    $res = sql_param_query('SELECT stw_quest_id, stw_answer, stw_order, stw_mark, stw_is_delete, stw_any_answer FROM sb_tester_answers WHERE stw_quest_id IN (?a) ', array_keys($els));
    if ($res)
    {
	    foreach ($res as $value)
	    {
            list($stw_quest_id, $stw_answer, $stw_order, $stw_mark, $stw_is_delete, $stw_any_answer) = $value;

	        $rows = array();
	        $rows['stw_quest_id'] 	= $els[$stw_quest_id];
	        $rows['stw_answer'] 	= $stw_answer;
	        $rows['stw_order'] 		= $stw_order;
	        $rows['stw_mark'] 		= $stw_mark;
	        $rows['stw_is_delete'] 	= $stw_is_delete;			
	        $rows['stw_any_answer'] = $stw_any_answer;
			

	        sql_param_query('INSERT INTO sb_tester_answers SET ?a', $rows);
	    }
    }

	if (!$_SESSION['sbPlugins']->isPluginAvailable('pl_workflow') || !$_SESSION['sbPlugins']->isPluginInWorkflow('pl_tester'))
		return;

	$res = sql_query('SELECT stq_id, stq_show FROM sb_tester_questions WHERE stq_id IN ('.implode(',', $_GET['ne']).')');

	if ($res)
	{
		foreach ($res as $value)
		{
			list($stq_id, $stq_show) = $value;

			if (!sb_workflow_status_available($stq_show, 'pl_tester', -1))
			{
				sql_query('UPDATE sb_tester_questions SET stq_show=?d WHERE stq_id=?d', current(sb_get_avail_workflow_status('pl_tester')), $stq_id);
			}
		}
	}
}

function fTester_After_Paste_Categs_With_Elements()
{
	if (!isset($_SESSION['paste_categs_with_elems_ids']) ||
		!isset($_SESSION['paste_categs_with_elems_ids']['old']) ||
		!isset($_SESSION['paste_categs_with_elems_ids']['new']))
	{
		return;
	}

	$_GET['e'] = array_values($_SESSION['paste_categs_with_elems_ids']['old']);
	$_GET['ne'] = array_values($_SESSION['paste_categs_with_elems_ids']['new']);
	$_GET['action'] = 'copy';

	fTester_After_Paste_Question();
}

function fTester_Show_Question()
{
	if (!$_SESSION['sbPlugins']->isRightAvailable('pl_news', 'elems_public'))
		return;

	sbIsGroupEdit(false);

	$date = time();
	foreach ($_GET['ids'] as $val)
    {
       	sql_query('INSERT INTO sb_catchanges (el_id, cat_ident, change_date, change_user_id, action) VALUES (?d, ?, ?d, ?d, ?)', $val, 'pl_tester', $date, $_SESSION['sbAuth']->getUserId(), 'edit');
    }

    $res = sql_param_query('UPDATE sb_tester_questions SET stq_show=IF(stq_show=0,1,0) WHERE stq_id IN (?a)', $_GET['ids'], PL_TESTER_SET_ACTIVE);
    if ($res)
    	echo 'TRUE';
}

function fTester_Test_Statistic()
{
	if(!isset($_GET['cat_id']))
		return;

	if (!fCategs_Check_Rights($_GET['cat_id']))
	{
		sb_show_message(SB_ELEMS_DENY_MSG, true, 'warning');
		return;
	}

    if (!isset($_GET['order']))
    {
		$_GET['order'] = 'r.str_time|desc';
	}
	else
    {
		$_GET['order'] = preg_replace('/[^a-zA-Z0-9_|\.]+/', '', $_GET['order']);

		if (empty($_GET['order']))
			$_GET['order'] = 'r.str_time|desc';
	}

	$order_str = explode('|', $_GET['order']);
    if (count($order_str) == 2)
    {
		$order_str = ' ORDER BY '.$order_str[0].' '.strtoupper($order_str[1]);
    }

    //изменение количества попыток у пользователя
    if(isset($_POST['user_id']) && intval($_POST['user_id']) > 0 && isset($_POST['attempts']) && $_POST['attempts'] != '')
    {
        //вытаскиваем максимально допустимое число попыток для данного теста
        $res = sql_param_query('SELECT cat_fields FROM sb_categs WHERE cat_id = ?d', intval($_GET['cat_id']));
        $test_options = array();
        if($res)
        {
            if($res[0][0] != '')
            {
                $test_options = unserialize($res[0][0]);
                if(!isset($test_options['spin_retest_num']) || $test_options['spin_retest_num'] == -1)
                {
                    $test_retest_num = PHP_INT_MAX; // чтобы не делать дополнительные проверки на -1
                }
                else
                {
                    $test_retest_num = $test_options['spin_retest_num'];
                }
            }
        }
        else
        {
            echo '<div class="sb_popup_msg_div" onclick="sbHidePopupMsgDiv();" name="sb_popup_msg_div">
            <table cellspacing="0" cellpadding="0" width="100%"><tr><td width="50">
            <img src="'.SB_CMS_IMG_URL.'/warning.png" class="sb_msg_img"></td><td class="sb_msg_text">'
            .PL_TESTER_STATISTIC_INFO_ERROR1.
            '</td></tr></table></div>';
            return;
        }

        //меняем значение только если оно не превышает допустимого
        if($_POST['attempts'] > $test_retest_num)
        {
            echo '<div class="sb_popup_msg_div" onclick="sbHidePopupMsgDiv();" name="sb_popup_msg_div">
            <table cellspacing="0" cellpadding="0" width="100%"><tr><td width="50">
            <img src="'.SB_CMS_IMG_URL.'/warning.png" class="sb_msg_img"></td><td class="sb_msg_text">'
            .sprintf(PL_TESTER_STATISTIC_INFO_ERROR2, $test_retest_num).
            '</td></tr></table></div>';
            return;
        }

        sql_param_query('UPDATE sb_tester_results SET str_num_attempts=?d WHERE str_user_id=?d AND str_test_id=?d', intval($_POST['attempts']), intval($_POST['user_id']), intval($_GET['cat_id']));
        sql_param_query('UPDATE sb_tester_answers_results SET star_attempt_id=?d WHERE star_user_id=?d AND star_test_id=?d', intval($_POST['attempts']), intval($_POST['user_id']), intval($_GET['cat_id']));

        echo '<div class="sb_popup_msg_div" onclick="sbHidePopupMsgDiv();" name="sb_popup_msg_div">
            <table cellspacing="0" cellpadding="0" width="100%"><tr><td width="50">
            <img src="'.SB_CMS_IMG_URL.'/information.png" class="sb_msg_img"></td><td class="sb_msg_text">'
            .PL_TESTER_STATISTIC_INFO_OK.
            '</td></tr></table></div>';
        return;
    }

	$href = SB_CMS_MODAL_DIALOG_FILE.'?event=pl_tester_view_test_statistic&cat_id='.$_GET['cat_id'];
	foreach ($_GET as $key => $value)
    {
		if($key != 'event' && $key != 'cat_id')
        {
			if(!is_array($value))
            {
                if($key != 'order')
                {
					$href .= '&'.$key.'='.urlencode($value);
                }
            }
            else
            {
				foreach ($value as $value2)
                {
					if ($key != 'order')
					{
						$href .= '&'.$key.'[]'.'='.urlencode($value2);
					}
				}
			}
		}
	}

	require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

	$total = true;
	require_once(SB_CMS_LIB_PATH.'/sbDBPager.inc.php');
	$pager = new sbDBPager('tester_statistic', 7, 23);

    $res = sql_param_query('SELECT DISTINCT su.su_id, su.su_name from sb_site_users su, sb_tester_results str where su.su_id = str.str_user_id');

    $filter = array(PL_TESTER_STATISTIC_ALL_USERS);
    if($res)
    {
        $count = count($res);
        for($i = 0; $i < $count; $i++)
        {
            list($user_id, $fio) = $res[$i];
            $filter[$user_id] = $fio;
        }
    }

    $filter_where = isset($_GET['filter'])? 'AND r.str_user_id='.intval($_GET['filter']).' ' : '';
	$result = $pager->init($total, 'SELECT r.str_id, su.su_name, su.su_login, su.su_email, c.cat_id, c.cat_title, r.str_time, r.str_mark, r.str_passed, r.str_certificat, r.str_test_time, r.str_ip, r.str_user_id, r.str_num_attempts
					FROM sb_categs c, sb_tester_results r LEFT JOIN sb_site_users su ON r.str_user_id = su.su_id
					WHERE c.cat_id = r.str_test_id AND r.str_test_id = ?d '.$filter_where.$order_str, $_GET['cat_id']);

	if ($result)
	{
        $filter_fld = new sbLayoutSelect($filter, 'filter', 'filter', 'onchange="Filter(this)"');
        if(isset($_GET['filter']))
        {
            $filter_fld->mSelOptions = array($_GET['filter']);
        }

		$num_list = $pager->show();
        echo '<div id="messages_block"></div>';
        echo '<center>';
        echo '<table width="98%" cellspacing="0" cellpadding="5" class="sb_filter_table"><tr><td>'.PL_TESTER_STATISTIC_FILTER.' '.$filter_fld->getField().'</td></tr></table>';
		echo '<br />'.$num_list.'<br /><br />
		<table width="98%" cellspacing="0" cellpadding="5" class="form">
		<tr>
	        <th class="header" width="10%">
	            <nobr>
					<a href="'.$href.'&order=su.su_name|asc"><img src="'.SB_CMS_IMG_URL.'/'.($_GET['order'] == 'su.su_name|asc' ? 's_asc_sel.gif' : 's_asc.gif').'" border="0" width="11" height="9"></a>
					&nbsp;'.PL_TESTER_STATISTIC_FIO.'&nbsp;
					<a href="'.$href.'&order=su.su_name|desc"><img src="'.SB_CMS_IMG_URL.'/'.($_GET['order'] == 'su.su_name|desc' ? 's_desc_sel.gif' : 's_desc.gif').'" border="0" width="11" height="9"></a>
	            </nobr>
	        </th>
	        <th class="header" width="10%">
	            <nobr>
	                <a href="'.$href.'&order=su.su_login|asc"><img src="'.SB_CMS_IMG_URL.'/'.($_GET['order'] == 'su.su_login|asc' ? 's_asc_sel.gif' : 's_asc.gif').'" border="0" width="11" height="9"></a>
	                &nbsp;'.PL_TESTER_STATISTIC_LOGIN.'&nbsp;
	                <a href="'.$href.'&order=su.su_login|desc"><img src="'.SB_CMS_IMG_URL.'/'.($_GET['order'] == 'su.su_login|desc' ? 's_desc_sel.gif' : 's_desc.gif').'" border="0" width="11" height="9"></a>
	            </nobr>
	        </th>
	        <th class="header" width="10%">
	            <nobr>
					<a href="'.$href.'&order=c.cat_title|asc"><img src="'.SB_CMS_IMG_URL.'/'.($_GET['order'] == 'c.cat_title|asc' ? 's_asc_sel.gif' : 's_asc.gif').'" border="0" width="11" height="9"></a>
					&nbsp;'.PL_TESTER_STATISTIC_TEST.'&nbsp;
					<a href="'.$href.'&order=c.cat_title|desc"><img src="'.SB_CMS_IMG_URL.'/'.($_GET['order'] == 'c.cat_title|desc' ? 's_desc_sel.gif' : 's_desc.gif').'" border="0" width="11" height="9"></a>
				</nobr>
			</th>
	        <th class="header" width="10%">
	            <nobr>
	                <a href="'.$href.'&order=r.str_time|asc"><img src="'.SB_CMS_IMG_URL.'/'.($_GET['order'] == 'r.str_time|asc' ? 's_asc_sel.gif' : 's_asc.gif').'" border="0" width="11" height="9"></a>
	                &nbsp;'.PL_TESTER_STATISTIC_DATE.'&nbsp;
	                <a href="'.$href.'&order=r.str_time|desc"><img src="'.SB_CMS_IMG_URL.'/'.($_GET['order'] == 'r.str_time|desc' ? 's_desc_sel.gif' : 's_desc.gif').'" border="0" width="11" height="9"></a>
	            </nobr>
	        </th>
	        <th class="header"  width="10%">
	            <nobr>
	                <a href="'.$href.'&order=r.str_mark|asc"><img src="'.SB_CMS_IMG_URL.'/'.($_GET['order'] == 'r.str_mark|asc' ? 's_asc_sel.gif' : 's_asc.gif').'" border="0" width="11" height="9"></a>
	                &nbsp;'.PL_TESTER_STATISTIC_BALL.'&nbsp;
	                <a href="'.$href.'&order=r.str_mark|desc"><img src="'.SB_CMS_IMG_URL.'/'.($_GET['order'] == 'r.str_mark|desc' ? 's_desc_sel.gif' : 's_desc.gif').'" border="0" width="11" height="9"></a>
	            </nobr>
	        </th>
	        <th class="header"  width="10%">
	            <nobr>
	                <a href="'.$href.'&order=r.str_passed|asc"><img src="'.SB_CMS_IMG_URL.'/'.($_GET['order'] == 'r.str_passed|asc' ? 's_asc_sel.gif' : 's_asc.gif').'" border="0" width="11" height="9"></a>
	                &nbsp;'.PL_TESTER_STATISTIC_PASSED.'&nbsp;
	                <a href="'.$href.'&order=r.str_passed|desc"><img src="'.SB_CMS_IMG_URL.'/'.($_GET['order'] == 'r.str_passed|desc' ? 's_desc_sel.gif' : 's_desc.gif').'" border="0" width="11" height="9"></a>
	            </nobr>
	        </th>
	        <th class="header"  width="10%">
	            <nobr>
	                <a href="'.$href.'&order=r.str_certificat|asc"><img src="'.SB_CMS_IMG_URL.'/'.($_GET['order'] == 'r.str_certificat|asc' ? 's_asc_sel.gif' : 's_asc.gif').'" border="0" width="11" height="9"></a>
	                &nbsp;'.PL_TESTER_STATISTIC_SERTIFICAT.'&nbsp;
	                <a href="'.$href.'&order=r.str_certificat|desc"><img src="'.SB_CMS_IMG_URL.'/'.($_GET['order'] == 'r.str_certificat|desc' ? 's_desc_sel.gif' : 's_desc.gif').'" border="0" width="11" height="9"></a>
	            </nobr>
	        </th>
	        <th class="header"  width="10%">
	            <nobr>
	                <a href="'.$href.'&order=r.str_test_time|asc"><img src="'.SB_CMS_IMG_URL.'/'.($_GET['order'] == 'r.str_test_time|asc' ? 's_asc_sel.gif' : 's_asc.gif').'" border="0" width="11" height="9"></a>
	                &nbsp;'.PL_TESTER_STATISTIC_TIME.'&nbsp;
	                <a href="'.$href.'&order=r.str_test_time|desc"><img src="'.SB_CMS_IMG_URL.'/'.($_GET['order'] == 'r.str_test_time|desc' ? 's_desc_sel.gif' : 's_desc.gif').'" border="0" width="11" height="9"></a>
	            </nobr>
	        </th>
            <th class="header"  width="10%">
	            <nobr>
	                &nbsp;'.PL_TESTER_STATISTIC_ATTEMPT.'&nbsp;
	            </nobr>
	        </th>
	        <th class="header"  width="10%">
	            <nobr>
	                <a href="'.$href.'&order=r.str_ip|asc"><img src="'.SB_CMS_IMG_URL.'/'.($_GET['order'] == 'r.str_ip|asc' ? 's_asc_sel.gif' : 's_asc.gif').'" border="0" width="11" height="9"></a>
	                &nbsp;'.PL_TESTER_STATISTIC_IP.'&nbsp;
	                <a href="'.$href.'&order=r.str_ip|desc"><img src="'.SB_CMS_IMG_URL.'/'.($_GET['order'] == 'r.str_ip|desc' ? 's_desc_sel.gif' : 's_desc.gif').'" border="0" width="11" height="9"></a>
	            </nobr>
	        </th>
            <th class="header"  width="10%">
	            <nobr>
	                '.PL_TESTER_STATISTIC_EXPORT.'
	            </nobr>
	        </th>
		</tr>';

		$view_info = $_SESSION['sbPlugins']->isRightAvailable('pl_site_users', 'read');

		$count = count($result);
		for($i = 0; $i < $count; $i++)
		{
			list($str_id, $su_name, $su_login, $su_email, $cat_id, $cat_title, $str_time, $str_mark, $str_passed, $str_certificat, $str_test_time, $str_ip, $str_user_id, $str_num_attemps) = $result[$i];

			if($su_name == '')
				$su_name = $su_email;

			if($su_login == '')
				$su_login = $su_email;

			if(isset($str_user_id) && $str_user_id > 0 && $view_info)
			{
				$su_name = '<a href="'.SB_CMS_CONTENT_FILE.'?event=pl_kernel_site_user_info&id='.$str_user_id.'">'.$su_name.'</a>';
				$su_login = '<a href="'.SB_CMS_CONTENT_FILE.'?event=pl_kernel_site_user_info&id='.$str_user_id.'">'.$su_login.'</a>';
			}

            $fld_attempts = new sbLayoutInput('text', $str_num_attemps, 'spin_attempts_'.$str_user_id, 'spin_attempts_'.$str_user_id, 'style="width:120px;" onchange="dataSend(this)"');
            $fld_attempts->mMinValue = 1;
            $fld_attempts->mMaxValue = $str_num_attemps;

			echo '<tr'.($i % 2 != 0 ? ' class="even"' : ' class="odd"').'>
			        <td style="text-align:center;">'.$su_name.'</td>
			        <td style="text-align:center;">'.$su_login.'</td>
			        <td style="text-align:center;">'.$cat_title.'</td>
			        <td style="text-align:center;">'.sb_date('d.m.Y', $str_time).'</td>
			        <td style="text-align:center;">'.$str_mark.'</td>
			        <td style="text-align:center;">'.($str_passed == 1 ? '<span style="color:#008000;">'.KERNEL_YES : '<span style="color:#FF0000;">'.KERNEL_NO).'</span></td>
			        <td style="text-align:center;">'.($str_certificat == 1 ? '<span style="color:#008000;">'.KERNEL_YES : '<span style="color:#FF0000;">'.KERNEL_NO).'</span></td>
			        <td style="text-align:center;">'.$str_test_time.' '.PL_TESTER_STATISTIC_SEK.'</td>
                    <td style="text-align:center;">'.$fld_attempts->getField().'</td>
			        <td style="text-align:center;">'.$str_ip.'</td>
                    <td style="text-align:center;">
                        <a href="'.SB_CMS_CONTENT_FILE.'?event=pl_tester_export_result&rec_id='.$str_id.'&cat_id='.$cat_id.'">
                            <img src="'.SB_CMS_IMG_URL.'/icon_excel.png" alt="'.PL_TESTER_STATISIC_EXPORT_DESC.'" title="'.PL_TESTER_STATISIC_EXPORT_DESC.'" border="0">
                        </a>
                    </td>
				</tr>';
		}
		echo '</table><br />'.$num_list.'<br /><br /></center>';
        echo $fld_attempts->getJavaScript();
        echo '<script type="text/javascript">
                function dataSend(obj)
                {
                    var cat_id = '.$_GET['cat_id'].';
                    var min = obj.getAttribute("min_value");
                    var max = obj.getAttribute("max_value");
                    var id = obj.id.split("_");
                    var data = [];

                    data[0] = "user_id="+id[id.length-1];
                    data[1] = "attempts="+obj.value;

                    sbPostAsync("'.SB_CMS_EMPTY_FILE.'?event=pl_tester_view_test_statistic&cat_id="+cat_id, data.join(\'&\'), Success);
                }

                function Success(response)
                {
                    sbGetE("messages_block").innerHTML = response;
                    sbShowPopupMsgDiv();
                }

                function Filter(obj)
                {
                    var url = window.location.href;
                    url = url.replace(/&filter=\d+/, "");

                    if(obj.value > 0)
                    {
                        window.location.replace(url+"&filter="+obj.value);
                    }
                    else
                    {
                        window.location.replace(url);
                    }
                }
            </script>';
	}
	else
    {
		sb_show_message(PL_TESTER_STATISTIC_NO_STATISTIC, true);
	}
}

function fTester_Statistic_Export()
{
    if(!isset($_GET['rec_id']) || !isset($_GET['cat_id']))
		return;

	if (!fCategs_Check_Rights($_GET['cat_id']))
	{
		sb_show_message(SB_ELEMS_DENY_MSG, true, 'warning');
		return;
	}

    $separator = ";";
    $csv = implode($separator, array(
        PL_TESTER_STATISTIC_FIO,
        PL_TESTER_STATISTIC_LOGIN,
        PL_TESTER_STATISTIC_EMAIL,
        PL_TESTER_STATISTIC_TEST,
        PL_TESTER_STATISTIC_DATE,
        PL_TESTER_STATISTIC_BALL,
        PL_TESTER_STATISTIC_TIME
    ))."\r\n";

    // выбираем общую информацию о тесте
    $res = sql_param_query('SELECT su_name, su.su_login, su.su_email, c.cat_title, r.str_time, r.str_mark, r.str_test_time, c.cat_id, su.su_id
        FROM sb_categs c, sb_tester_results r LEFT JOIN sb_site_users su ON r.str_user_id = su.su_id
        WHERE c.cat_id = r.str_test_id AND r.str_id = ?d LIMIT 1', intval($_GET['rec_id']));

    if($res)
    {
        list($su_name, $su_login, $su_email, $test_name, $test_time, $test_mark, $test_time_length, $test_id, $su_id) = $res[0];

        $csv .= implode($separator, array(
            $su_name,
            $su_login,
            $su_email,
            $test_name,
            sb_date('d.m.Y', $test_time),
            $test_mark,
            $test_time_length.' '.PL_TESTER_STATISTIC_SEK
        ));
        $csv .= "\r\n\r\n";

        //шапка для данных результатов теста
        $csv .= implode($separator, array(
            PL_TESTER_STATISTIC_QUEST_TEXT,
            PL_TESTER_STATISTIC_ANSWER_TEXT_RESULT,
            PL_TESTER_STATISTIC_ANSWER_TEXT,
            PL_TESTER_STATISTIC_ANSWER_TEXT_WRITE,
            PL_TESTER_STATISTIC_BALL,
            PL_TESTER_STATISTIC_ANSWER_DATE,
            PL_TESTER_STATISTIC_ANSWER_TIME
        ));
        $csv .= "\r\n";

        // получаем номер последней попытки
        $res = sql_param_query('SELECT max(star_attempt_id) FROM sb_tester_answers_results WHERE star_user_id=?d AND star_test_id=?d', intval($su_id), intval($test_id));
        $attempt = $res[0][0];

        // получим вопросы с номерами ответов, на которые отвечал пользователь
        $questions = sql_param_query('SELECT stq.stq_id, stq.stq_question, star.star_answer_ids, star_time, star_answer_time, star_mark, stq.stq_type FROM sb_tester_answers_results as star
            LEFT JOIN sb_tester_questions stq ON stq.stq_id=star.star_quest_id
            WHERE star.star_attempt_id = ?d AND star.star_user_id = ?d AND star.star_test_id = ?d', $attempt, intval($su_id), intval($test_id));

        //выберем все ответы из БД
        $all_answers = sql_param_query('SELECT stw_id, stw_answer, stw_any_answer FROM sb_tester_answers');

        // выбираем данные по ответам
        $pool = array();
        if ($questions)
        {
            foreach ($questions as $quest)
            {
                $answer_str = '';
                list($id, $text, $answers_list, $ans_time, $ans_time_length, $ans_mark, $stq_type) = $quest;
                if($text === null)
                {
                    $csv .= '"'.PL_TESTER_STATISTIC_QUEST_HAS_BEEN_DELETED.'"'.$separator.$separator.$separator.$separator."\r\n";
                    continue;
                }
				
				$write_answer_str = $answer_result_str = "";
				$total_awalible_mark = 0;
				//выбираем все правильные ответы
				$write_answers_this_quest = sql_query('SELECT stw_answer, stw_mark FROM sb_tester_answers WHERE stw_quest_id = '.intVal($id).' AND stw_mark > 0 AND stw_is_delete = 0');
				
				if ( $write_answers_this_quest ) {
					$write_answer_str = array();
					foreach ( $write_answers_this_quest as $write_answers_this_quest_row ) {
						$write_answer_str[] = $write_answers_this_quest_row[0];
						$total_awalible_mark = $total_awalible_mark + $write_answers_this_quest_row[1];
					}
					$write_answer_str = implode(', ', $write_answer_str);
				}

                //ищем ответ
                if($stq_type == 'inline')
                {
                    $answers_list = explode(',', $answers_list);
                    if (empty($answers_list))
                    {
                        $csv .= '"' . sb_str_replace('"', '""', html_entity_decode($text)) . '"' . $separator . $separator . $separator . $separator . "\r\n";
                    }
                    else
                    {
                        foreach($answers_list as $answers_list_tmp)
                        {
                            $answers_list_tmp = explode('^', $answers_list_tmp);
                            $answer_str .= '(';

                            foreach ($all_answers as $answer)
                            {
                                if (!in_array($answer[0], $answers_list_tmp))
                                {
                                    continue;
                                }

                                $answer_str .= $answer[1] . ' => ';
                            }
                            $answer_str = rtrim($answer_str, ' => ');
                            $answer_str .= '), ';
                        }
                    }
                }
                else
                {
                    $answers_list = explode(',', $answers_list);
                    if (empty($answers_list))
                    {
                        $csv .= '"' . sb_str_replace('"', '""', html_entity_decode($text)) . '"' . $separator . $separator . $separator . $separator . "\r\n";
                    }
                    else
                    {
                        foreach ($all_answers as $answer)
                        {
                            if (!in_array($answer[0], $answers_list))
                            {
                                continue;
                            }

                            if (1 == $answer[2])
                            {
                                $answ_text = sql_param_query('SELECT staa_answer_text FROM sb_tester_any_answers WHERE staa_answer_id=?d AND staa_user_id=?d AND staa_quest_id=?d', $answer[0], intval($su_id), $id);
                                if ($answ_text)
                                {
                                    $answer_str .= $answ_text[0][0] . ', ';
                                }
                            }
                            else
                            {
                                $answer_str .= $answer[1] . ', ';
                            }
                        }
                    }
                }
                $answer_str = rtrim($answer_str, ', ');

				if ( $ans_mark >= $total_awalible_mark or (trim($write_answer_str) == trim($answer_str) and $ans_mark > 0) ) $answer_result_str = PL_TESTER_STATISTIC_ANSWER_TEXT_RESULT_WRITE;
				elseif ( $ans_mark > 0 ) $answer_result_str = PL_TESTER_STATISTIC_ANSWER_TEXT_RESULT_MIDDLE;
				else $answer_result_str = PL_TESTER_STATISTIC_ANSWER_TEXT_RESULT_WRONG;

				

                $csv .= implode($separator, array(
                    '"' . sb_str_replace('"', '""', html_entity_decode($text)) . '"',
                    '"' . sb_str_replace('"', '""', html_entity_decode($answer_result_str)) . '"',
                    '"' . sb_str_replace('"', '""', html_entity_decode($answer_str)) . '"',
					'"' . sb_str_replace('"', '""', html_entity_decode($write_answer_str)) . '"',
					$ans_mark,
                    sb_date('d.m.Y', $ans_time),
                    $ans_time_length . ' ' . PL_TESTER_STATISTIC_SEK
                ));
                $csv .= "\r\n";
            }
        }
    }

    header('Content-Type: text/comma-separated-values; charset=windows-1251');
    header('Expires: '.gmdate('D, d M Y H:i:s').' GMT');
    header('Content-Disposition: attachment; filename="test_results.csv"');
    ob_get_clean();
    $csv = iconv('UTF-8', 'WINDOWS-1251//IGNORE//TRANSLIT', $csv);
    echo $csv;
    exit(0);
}


/**
 * Функции управления тестами
 */
function fTester_Results_Output($id)
{
	include_once(SB_CMS_LIB_PATH.'/sbCustomElements.inc.php');
	$html = '<script>
         _sbPDPreventNsDrag = function (e, call)
		{
		    if(e && e.preventDefault)
		    {
		        e.preventDefault();
		        return false;
		    }
		    return false;
		}

		sbPDDraggingEl = null;
		sbPDOverEl = null;
		sbPDTempOverEvent = new Array();

		sbPDSetBorder = function(el, border)
		{
		    if (el)
		    {
		        var childs = el.childNodes;
		        for(var i = 0; i < childs.length; i++)
		        {
		            if(childs[i].tagName == "TD")
		            {
		                if (_isIE)
		                    childs[i].style.borderTop = "1px solid " + border;
		                else
		                    childs[i].style.borderTop = "1px solid " + border;
		            }
		        }
		    }
		}

		sbPDChangeRowClass = function(el_id, cls)
		{
		    var el_tr = sbGetE(el_id);
		    if (el_tr)
		    {
		        if (!cls)
		        {
		            if (_isIE)
		                el_tr.setAttribute("className", el_tr.getAttribute("oldClassName"));
		            else
		                el_tr.setAttribute("class", el_tr.getAttribute("oldClassName"));
		        }
		        else
		        {
		            if (_isIE)
		            {
		                if (el_tr.getAttribute("className") != "drag")
		                {
		                    el_tr.setAttribute("oldClassName", el_tr.getAttribute("className"));
		                }
		            }
		            else
		            {
		                if (el_tr.getAttribute("class") != "drag")
		                {
		                    el_tr.setAttribute("oldClassName", el_tr.getAttribute("class"));
		                }
		            }

		            if (_isIE)
		                el_tr.setAttribute("className", cls);
		            else
		                el_tr.setAttribute("class", cls);
		        }
		    }
		}

		sbPDStartDrag = function(e, el)
		{
		    sbAddEvent(document, "mousemove", sbPDDrag);
		    sbAddEvent(document, "mouseup", sbPDStopDrag);

		    if (sbPDDraggingEl && sbPDDraggingEl != el)
		        sbPDChangeRowClass(sbPDDraggingEl.id, null);

		    sbPDChangeRowClass(el.id, "drag");
		    sbPDDraggingEl = el;

		    if(_isIE)
		    {
		        window.document.body.setCapture();
		    }
		    else
		    {
		        if (window.parent.frames)
		        {
		            for(var i=0; i<window.parent.frames.length; i++)
		            {
		                if(window.parent.frames[i] != window)
		                {
		                    sbPDTempOverEvent[sbPDTempOverEvent.length] = window.parent.frames[i].onmouseover;
		                    window.parent.frames[i].onmouseover = sbPDStopDrag;
		                }
		            }
		        }
		    }
		}

		sbPDDrag = function(e)
		{
		    if (!sbPDDraggingEl)
		    {
		        sbPDOverEl = null;
		        return;
		    }

		    var el = sbEventTarget(e);
		    if (!el)
		        return;

		    while(el && el.tagName != "TR")
		        el = el.parentNode;

		    if (!el)
		        return;

		    var table = el;
		    while(table.tagName != "TABLE")
		        table = table.parentNode;

		    if (table.id != "pl_tester_table")
		        return;

		    var first_tr = table.tBodies[0].childNodes[0];

		    var tab = sbGetE("sb_tabs_con");
		    var a1=sbGetAbsoluteTop(el);
		    var a2=sbGetAbsoluteTop(tab);

		    if(a1-a2-parseInt(tab.scrollTop)>parseInt(tab.offsetHeight)-50)
		        tab.scrollTop=parseInt(tab.scrollTop)+30;
		    if(a1-a2<parseInt(tab.scrollTop)+30)
		        tab.scrollTop=parseInt(tab.scrollTop)-30;

		    if (el == sbPDDraggingEl || el == sbPDDraggingEl.nextSibling || el == first_tr)
		    {
		        sbPDSetBorder(sbPDOverEl, "#FFF5E0");
		        sbPDOverEl = null;
		        return;
		    }

		    sbPDSetBorder(sbPDOverEl, "#FFF5E0");
		    sbPDSetBorder(el, "black");
		    sbPDOverEl = el;
		}

		sbPDStopDrag = function(e)
		{
            if(_isIE)
		    {
		        window.document.body.releaseCapture();
		    }
		    else
		    {
		        if (window.parent.frames)
		        {
		            for(var i=0; i<window.parent.frames.length; i++)
		            {
		                if(window.parent.frames[i] != window)
		                {
		                    window.parent.frames[i].onmouseover = sbPDTempOverEvent.shift();
		                }
		            }
		        }
		    }

		    sbRemoveEvent(document, "mousemove", sbPDDrag);
		    sbRemoveEvent(document, "mouseup", sbPDStopDrag);

		    if (!sbPDDraggingEl || !sbPDOverEl)
		        return;

		    sbPDSetBorder(sbPDOverEl, "#FFF5E0");

		    var table = sbGetE("pl_tester_table");
		    table = table.tBodies[0];
		    table.insertBefore(sbPDDraggingEl, sbPDOverEl);

		    for (var i = 1; i < table.rows.length; i++)
		    {
		        if (i % 2 == 0)
		        {
		            if (_isIE)
		                table.rows[i].setAttribute("className", "even");
		            else
		                table.rows[i].setAttribute("class", "even");
		            table.rows[i].setAttribute("oldClassName", "even");
		        }
		        else
		        {
		            if (_isIE)
		                table.rows[i].removeAttribute("className");
		            else
		                table.rows[i].removeAttribute("class");
		            table.rows[i].removeAttribute("oldClassName");
		        }
		    }
		    sbPDDraggingEl = null;
		    sbPDOverEl = null;
            resortAll();
        }

        var lastSortPosition = 0;
        var editingResultId = -1;

        function editResultClick(result_id)
        {
            var el_mark_from = sbGetE("spin_mark_from");
            var el_mark_to = sbGetE("spin_mark_to");
            var el_cur_result = sbGetE("result_" + result_id + "_result");
            var el_cur_mark_from = sbGetE("result_" + result_id + "_mark_from");
            var el_cur_mark_to = sbGetE("result_" + result_id + "_mark_to");

            editingResultId = result_id;

            var cancelButton = sbGetE("resultCancelButton");
            var addButton = sbGetE("resultAddButton");

            addButton.value = "'.PL_TESTER_CAT_EDIT_EDIT_RESULT.'";
            cancelButton.style.display = "inline";

			if (window.sbCodeditor_new_result)
           	{
				sbCodeditor_new_result.setCode(el_cur_result.innerHTML);
           	}
           	else
           	{
            	var el_result = sbGetE("new_result");
            	el_result.value = el_cur_result.innerHTML;
           	}

            el_mark_from.value = el_cur_mark_from.value;
            el_mark_to.value = el_cur_mark_to.value;

            window.scrollTo(0, 0);
            return false;
        }

		sbPDAddTr = function(id, from, to, result)
		{
	        var table = sbGetE("pl_tester_table");
	        var null_tr = sbGetE("null_tr");

		    if (!table || !null_tr)
		        return;

		    if (null_tr.className != "")
		    {
		        var cls = "even";
		        if (_isIE)
		            null_tr.removeAttribute("className");
		        else
		            null_tr.removeAttribute("class");
		    }
		    else
		    {
		        var cls = "";
		        null_tr.className = "even";
		    }
		    var tr = document.createElement("TR");

		    tr.setAttribute("id", id);
		    if (_isIE)
		        tr.setAttribute("className", cls);
		    else
		        tr.setAttribute("class", cls);
		    tr.style.cursor = "default";
		    if(_isIE)
		    {
		        tr.setAttribute("onmousedown", function(){sbPDStartDrag(event, this)});
		    }
		    else
		    {
		        tr.setAttribute("onmousedown", "sbPDStartDrag(event, this)");
		    }

            // Первая ячейка
		    var td1 = document.createElement("TD");
		    td1.innerHTML = "<textarea style=\'width:1px;height:1px;visibility:hidden;border:1px solid red;\' id=\'result_" + id + "_result\' name=\'result_" + id + "_result\' onmousedown=\'_sbPDPreventNsDrag(event, true);\' ondragstart=\'_sbPDPreventNsDrag(event);\' >"+result+"</textarea><input type=\'hidden\' name=\'results[]\' id=\'result_" + id + "_id\' value=\'" + id + "\'>";
            td1.align = "center";
		    td1.style.verticalAlign = "middle";
		    td1.style.width = "5px";
		    td1.style.borderTop = "1px solid FFF5E0";
		    tr.appendChild(td1);

		    //Результат
		    var td2 = document.createElement("TD");
		    td2.style.verticalAlign = "middle";
		    td2.style.borderTop = "1px solid FFF5E0";
		    td2.innerHTML = "<span ondblclick=\'sbPDEditName(this)\' id=\'name_" + id + "\'>" + result + "</span>";
		    tr.appendChild(td2);

		    // Итоговая оценка
		    var td3 = document.createElement("TD");
		    td3.align = "center";
		    td3.style.verticalAlign = "middle";
    	    td3.style.borderTop = "1px solid FFF5E0";
            td3.innerHTML = "<div id=\'result_"+id+"_mark_text\'>'.KERNEL_FROM.' <b>"+from+"</b> '.KERNEL_TO.' <b>"+to+"</b></div>";
		    tr.appendChild(td3);

		    // Редактировать
		    var td4 = document.createElement("TD");
		    td4.align = "center";
		    td4.style.verticalAlign = "middle";
		    td4.style.borderTop = "1px solid FFF5E0";
            td4.style.width = "30px";
	        td4.innerHTML = "<img src=\'/cms/images/btn_props.png\' title=\'Свойства\' onmousedown=\'_sbPDPreventNsDrag(event, true);\' ondragstart=\'_sbPDPreventNsDrag(event);\' onclick=\'return editResultClick("+id+");\' style=\'cursor:pointer;margin:8px;\' width=\'20\' border=\'0\' height=\'20\'>";
		    tr.appendChild(td4);

		    // Удалить
		    var td5 = document.createElement("TD");
		    td5.align = "center";
		    td5.style.verticalAlign = "middle";
		    td5.style.borderTop = "1px solid FFF5E0";
		    td5.style.width = "30px";
		    td5.innerHTML = "<img src=\'/cms/images/btn_delete.png\' title=\'Удалить\' onmousedown=\'_sbPDPreventNsDrag(event, true);\' ondragstart=\'_sbPDPreventNsDrag(event);\' onclick=\'deleteResultClick("+id+");\' style=\'cursor:pointer;margin:8px;\' width=\'20\' height=\'20\'><input type=\'hidden\' name=\'result_"+id+"_order\' id=\'result_"+id+"_order\' value=\'"+lastSortPosition+"\'><input type=\'hidden\' name=\'result_"+id+"_mark_from\' id=\'result_"+id+"_mark_from\' value=\'"+from+"\'><input type=\'hidden\' name=\'result_"+id+"_mark_to\' id=\'result_"+id+"_mark_to\' value=\'"+to+"\'>";

		    tr.appendChild(td5);

            var el = table.tBodies[0];
		    if(el)
		    {
		        el.insertBefore(tr, null_tr);
		    }
        }

        function addResultClick()
        {
			if (editingResultId == -1)
            {
				if (window.sbCodeditor_new_result)
            	{
					var el_new_result = sbCodeditor_new_result.getCode();
            	}
            	else
            	{
					var el_new_result = sbGetE("new_result").value;
            	}

                var newId = new Number(new Date());
                var el_mark_from = sbGetE("spin_mark_from");
			    var el_mark_to = sbGetE("spin_mark_to");
			    lastSortPosition++;

				var el_mark_to = sbGetE("spin_mark_to");

			    if(el_new_result == "" || el_new_result == 0)
			    {
			        sbShowMsgDiv("'.PL_TESTER_EDIT_ANSWER_NO_EXIST.'", "warning.png");
			        return false;
			    }

			    sbPDAddTr(newId, el_mark_from.value, el_mark_to.value, el_new_result);

				if (window.sbCodeditor_new_result)
            	{
					sbCodeditor_new_result.setCode("");
            	}
            	else
            	{
					sbGetE("new_result").value = "";
            	}

                el_mark_from.value = el_mark_to.value;
                el_mark_to.value = 0;

			    return false;
            }
            else
            {
                //изменение ответа
                var prevRow = sbGetE(editingResultId);
                if (prevRow)
                {
					if (window.sbCodeditor_new_result)
	            	{
						var el_result = sbCodeditor_new_result.getCode();
	            	}
	            	else
	            	{
                    	var el_result = sbGetE("new_result").value;
	            	}

                    var el_mark_from = sbGetE("spin_mark_from");
                    var el_mark_to = sbGetE("spin_mark_to");
                    var el_cur_result = sbGetE("result_" + editingResultId + "_result");
                    var el_cur_text = sbGetE("name_" + editingResultId);
                    var el_cur_mark_from = sbGetE("result_" + editingResultId + "_mark_from");
                    var el_cur_mark_to = sbGetE("result_" + editingResultId + "_mark_to");
                    var el_cur_mark_text = sbGetE("result_" + editingResultId + "_mark_text");

                    editingResultId = -1;

                    var cancelButton = sbGetE("resultCancelButton");
                    var addButton = sbGetE("resultAddButton");

                    addButton.value = "'.KERNEL_ADD.'";
                    cancelButton.style.display = "none";

                    el_cur_result.innerHTML = el_result;
                    el_cur_text.innerHTML = el_result;
                    el_cur_mark_text.innerHTML = " '.KERNEL_FROM.' <b>" + el_mark_from.value + "</b> '.KERNEL_TO.' <b>" + el_mark_to.value + "</b>";
                    el_cur_mark_from.value = el_mark_from.value;
                    el_cur_mark_to.value = el_mark_to.value;

					if (window.sbCodeditor_new_result)
	            	{
						sbCodeditor_new_result.setCode("");
	            	}
	            	else
	            	{
						sbGetE("new_result").value = "";
	            	}
                    el_mark_from.value = 0;
                    el_mark_to.value = 0;
                }
				return false;
            }
        }

        function deleteResultClick(result_id)
        {
            // определить строку в таблице
            var curRow = sbGetE(result_id);
            curRow.removeNode(true);

            var table = sbGetE("pl_tester_table");
            for (var i = 1; i < table.rows.length; i++)
            {
                if (i % 2 == 0)
                {
                    if (_isIE)
                        table.rows[i].setAttribute("className", "even");
                    else
                        table.rows[i].setAttribute("class", "even");
                    table.rows[i].setAttribute("oldClassName", "even");
                }
                else
                {
                    if (_isIE)
                        table.rows[i].removeAttribute("className");
                    else
                        table.rows[i].removeAttribute("class");
                    table.rows[i].removeAttribute("oldClassName");
                }
            }

            //Пересортировать все
            resortAll();
            return false;
        }

        function resortAll()
        {
            var els = document.getElementsByName("results[]");
            if (els)
            {
                for (var i = 0; i < els.length; i++)
                {
                    var curPos = sbGetE("result_" + els[i].value + "_order");
                    curPos.value = parseInt(i + 1);
                }
            }
            lastSortPosition = els.length;
        }

        function cancelClick()
        {
            var curRow = sbGetE(editingResultId);

            var el_mark_from = sbGetE("spin_mark_from");
            var el_mark_to = sbGetE("spin_mark_to");

            editingResultId = -1;

            var cancelButton = sbGetE("resultCancelButton");
            var addButton = sbGetE("resultAddButton");

            addButton.value = "'.KERNEL_ADD.'";
            cancelButton.style.display = "none";

			if (window.sbCodeditor_new_result)
			{
				sbCodeditor_new_result.setCode("");
	        }
			else
			{
				var el_result = sbGetE("new_result");
				el_result.value = "";
           	}

            el_mark_from.value = 0;
            el_mark_to.value = 0;

            return false;
        }
        </script>';

    $fld1 = new sbLayoutTextarea('', 'new_result', 'new_result', 'style="width:100%;height:100px"');
    $fld1->mShowToolbar = false;
    $fld1->mShowEnlargeBtn = false;
    $fld1->mShowEditorBtn = true;

    $fld2_1 = new sbLayoutInput('text', '0', 'spin_mark_from', 'spin_mark_from', 'style="width:80px;"');
    $fld2_2 = new sbLayoutInput('text', '0', 'spin_mark_to', 'spin_mark_to', 'style="width:80px;"');

    $html_1 = '<table style="margin-bottom:10px;">
			        <tr>
	                    <td>
	                       '.KERNEL_FROM.'&nbsp;
	                    </td>
			            <td>
	                       '.$fld2_1->getField().'
			            </td>
	                    <td style="padding-left:10px;">
	                       '.KERNEL_TO.'&nbsp;
	                    </td>
	                    <td >
	                       '.$fld2_2->getField().'
	                    </td>
			        </tr>
			    </table>';

    $fld2 = new sbLayoutHTML($html_1, true);

    $html .= '
        <table cellspacing="0" cellpadding="0" align="middle" width="100%">
            <tr>
                <td>
                    <div class="hint_div" style="text-align:center;">'.PL_TESTER_CAT_EDIT_MARK.'</div><br />
                </td>
            </tr>
            <tr>
                <td>
                    '.$fld2->getFull('', '', false, '', '', '').'
                </td>
            </tr>
            <tr>
                <td>
                    <div class="hint_div" style="text-align:center;">'.PL_TESTER_CAT_EDIT_RESULT.'</div><br />
                </td>
            </tr>
            <tr>
                <td>
                    '.$fld1->getField('', '', false, '', '', '').'
                </td>
            </tr>
            <tr>
                <td align="center">
                    <div style="border-bottom:1px dotted #CBB49A; margin:3px;">&nbsp;</div>
                    <button onClick="return addResultClick();" id="resultAddButton"> '.KERNEL_ADD.'</button>&nbsp;&nbsp;
                    <button onClick="return cancelClick();" style="display: none;" id="resultCancelButton"> '.KERNEL_CANCEL.'</button>
                </td>
            </tr>
        </table><br />';

    $html .= '
    <table cellspacing="0" cellpadding="5" width="100%" style="empty-cells: show; -moz-user-select: none;" id="pl_tester_table" class="form">
        <tr>
            <th class="header">&nbsp;</th>
            <th class="header">'.PL_TESTER_CAT_EDIT_RESULT.'</th>
            <th class="header" width="150">'.PL_TESTER_CAT_EDIT_MARK.'</th>
            <th class="header" width="30">&nbsp;</th>
            <th class="header" width="30">&nbsp;</th>
        </tr>';

    if($id != '')
    {
	    // список уже имеющихся типов
	    $res = sql_param_query('SELECT stmr_id, stmr_start, stmr_end, stmr_result
	                          FROM sb_tester_marks_results
	                          WHERE stmr_test_id = ?d
	                          ORDER BY stmr_order', $id);

	    if($res)
	    {
            $class = ' class="even"';
	        $count_res = count($res);

	        for ($i = 0; $i < $count_res; $i++)
	        {
	            list($stmr_id, $stmr_start, $stmr_end, $stmr_result) = $res[$i];

	            if ($class == '')
		            $class = ' class="even"';
		        else
		            $class = '';

	            $html .= '
	            <tr '.$class.'  id="'.$stmr_id.'" onmousedown="sbPDStartDrag(event, this)" style="cursor:default;">
	              <td align="center" style="vertical-align: middle; border-top: 1px solid FFF5E0; width:5px;">
                      <textarea style="width: 1px; height: 1px; visibility:hidden;" id="result_'.$stmr_id.'_result" name="result_'.$stmr_id.'_result" onmousedown="_sbPDPreventNsDrag(event, true);" ondragstart="_sbPDPreventNsDrag(event);">'.htmlspecialchars($stmr_result, ENT_QUOTES).'</textarea>
                      <input type="hidden" name="results[]" id="result_'.$stmr_id.'_id" value="'.$stmr_id.'">
	              </td>
	              <td style="vertical-align: middle; border-top: 1px solid FFF5E0;">
	                   <span id="name_'.$stmr_id.'">'.($stmr_result != '' ? $stmr_result : '&nbsp;').'</span>
	              </td>
	              <td align="center" style="vertical-align: middle; border-top: 1px solid FFF5E0; width:150px;">
	                  <div id="result_'.$stmr_id.'_mark_text">'.KERNEL_FROM.' <b>'.$stmr_start.'</b> '.KERNEL_TO.' <b>'.$stmr_end.'</b></div>
                  </td>
	              <td style="vertical-align: middle; border-top: 1px solid FFF5E0; width:30px;">
    	              <img src="/cms/images/btn_props.png" title="'.PL_TESTER_EDIT_PROPS_LABEL.'" onmousedown="_sbPDPreventNsDrag(event, true);" ondragstart="_sbPDPreventNsDrag(event);" onclick="return editResultClick(\''.$stmr_id.'\');" style="cursor: pointer; margin:8px;" width="20" border="0" height="20">
	              </td>
                  <td style="vertical-align: middle; border-top: 1px solid FFF5E0; width:30px;">
                      <img src="/cms/images/btn_delete.png" title="'.PL_TESTER_EDIT_DEL_LABEL.'" onmousedown="_sbPDPreventNsDrag(event, true);" ondragstart="_sbPDPreventNsDrag(event);" onclick="deleteResultClick(\''.$stmr_id.'\');" style="cursor: pointer; margin:8px;" width="20" height="20">

                      <input type="hidden" name="result_'.$stmr_id.'_order" id="result_'.$stmr_id.'_order" value="'.($i+1).'">
	                  <input type="hidden" name="result_'.$stmr_id.'_mark_from" id="result_'.$stmr_id.'_mark_from" value="'.$stmr_start.'">
	                  <input type="hidden" name="result_'.$stmr_id.'_mark_to" id="result_'.$stmr_id.'_mark_to" value="'.$stmr_end.'">
	              </td>
	            </tr>';
	        }
	    }
    }
    else
    {
     	$res = array();
    }

    if(isset($class) && $class != '')
    {
        $class = '';
    }
    else
    {
        $class = 'class="even"';
    }

    $html .= '
            <tr id="null_tr" '.$class.'>
                <td style=""> </td>
                <td style=""> </td>
                <td style=""> </td>
                <td style=""> </td>
                <td style=""> </td>
            </tr>
        </table>
        <script type="text/javascript" language="JavaScript">
            lastSortPosition = '.count($res).';
        </script>';

    return $html;

}

function fTester_Edit_Test($htmlStr = '', $footerStr = '')
{
	if (count($_POST) == 0 && isset($_GET['cat_id']) && $_GET['cat_id'] != '')
    {
		$result = sql_param_query('SELECT cat_title, cat_ident, cat_closed, cat_fields, cat_rights FROM sb_categs
									WHERE cat_id=?d', $_GET['cat_id']);
		if ($result)
        {
			list($cat_title, $cat_ident, $cat_closed, $cat_fields, $cat_rights) = $result[0];
        }
        else
        {
            sb_show_message(PL_TESTER_EDIT_ERROR, true, 'warning');
            return;
        }
        $cat_fields = unserialize($cat_fields);
        if ($cat_fields != '')
        {
            $retest_time = $cat_fields['spin_retest_time'];
            $criteria = $cat_fields['spin_criteria'];
            $criteria_persent = isset($cat_fields['criteria_persent'])? $cat_fields['criteria_persent'] : 0;
            $email = $cat_fields['email'];
            $answer_time = isset($cat_fields['spin_answer_time']) ? $cat_fields['spin_answer_time'] : -1;
            $retest_num = isset($cat_fields['spin_retest_num']) ? $cat_fields['spin_retest_num'] : -1;
            $num_questions = $cat_fields['spin_num_questions'];
            $num_questions_child = isset($cat_fields['spin_num_questions_child'])? $cat_fields['spin_num_questions_child'] : -1;
            $num_questions_per_page = isset($cat_fields['spin_questions_per_page']) ? $cat_fields['spin_questions_per_page'] : 1;
            $test_time = $cat_fields['spin_test_time'];
        }
        else
        {
            $retest_time = -1;
            $email = '';
            $criteria = 0;
            $criteria_persent = 0;
            $answer_time = -1;
            $retest_num = -1;
            $num_questions = 10;
            $num_questions_child = 0;
            $num_questions_per_page = 1;
            $test_time = -1;
        }
        $cat_title = htmlspecialchars($cat_title, ENT_QUOTES);
        $desc = htmlspecialchars($cat_fields['descr'], ENT_QUOTES);

        $_GET['cat_id_p'] = '';
    }
    elseif (count($_POST) > 0)
    {
        extract($_POST);
        if (!isset($_GET['id']))
            $_GET['id'] = '';
    }
    else
    {
        $cat_closed = 0;
        $cat_title = '';
        $cat_ident = '';
        $cat_fields = array();

        $res = sql_param_query('SELECT cat_closed, cat_rights FROM sb_categs WHERE cat_id=?d ', $_GET['cat_id_p']);
        list($cat_closed, $cat_rights) = $res[0];
        $cat_title = '';
        $desc = '';
        $test_time = -1;
        $answer_time = -1;
        $retest_time = -1;
        $retest_num = -1;
        $num_questions = 10;
        $num_questions_child = 0;
        $num_questions_per_page = 1;
        $email = '';
        $criteria = 0;

        $_GET['cat_id'] = '';
        $_GET['id'] = '';
    }

    echo '<script>
            function checkValues()
            {
                var title = sbGetE("cat_title");
                if (title.value == "")
		        {
		            alert("'.PL_TESTER_CAT_EDIT_NO_TITLE.'");
		            return false;
		        }
		        var cat_closed = sbGetE("cat_closed");
		        if (cat_closed && cat_closed.checked)
		        {
		            var frm = sbGetE("main");
		            for (var i = 0; i < frm.elements.length; i++)
		            {
		                if (frm.elements[i].id.indexOf("group_ids") != -1 && frm.elements[i].value == "")
		                {
		                    alert("'.PL_TESTER_CAT_EDIT_NO_GROUPS. '");
		                    return false;
		                }
		            }
		        }
            }
		    var group_ident = "";
		    function browseGroups(ident)
		    {
		        var el = sbGetE("group_names" + ident);
		        if (!el || el.disabled)
		            return;

		        group_ident = ident;
		        var group_ids = sbGetE("group_ids" + ident).value;

		        var strPage = "'.SB_CMS_MODAL_DIALOG_FILE.'?event=pl_site_users_get_groups&sel_cat_ids="+group_ids;
		        var strAttr = "resizable=1,width=500,height=500";
		        sbShowModalDialog(strPage, strAttr, afterBrowseGroups);
		    }

		    function afterBrowseGroups()
		    {
		        if (sbModalDialog.returnValue)
		        {
		            var group_ids = sbGetE("group_ids" + group_ident);
		            var group_names = sbGetE("group_names" + group_ident);

		            group_ids.value = sbModalDialog.returnValue.ids;
		            group_names.value = sbModalDialog.returnValue.text;
		        }
		        group_ident = "";
		    }

		    function changeType(el)
		    {
		        var btns = new Array();
		        var ids = new Array();
		        var names = new Array();
		        var frm = sbGetE("main");

		        for (var i = 0; i < frm.elements.length; i++)
		        {
		            if (frm.elements[i].id.indexOf("group_names") != -1)
		            {
		                if (el.checked)
		                {
		                    frm.elements[i].disabled = false;
		                }
		                else
		                {
		                    frm.elements[i].disabled = true;
		                    frm.elements[i].value = "";
		                }
		            }
		            else if (!el.checked && frm.elements[i].id.indexOf("group_ids") != -1)
		            {
		                frm.elements[i].value = "";
		            }
		        }
		    }

		    function changeInput(chk, el_id)
		    {
		        var el = sbGetE(el_id);
		        el.disabled = chk.checked;
		    }';

    if ($htmlStr != '')
    {
        echo '
            function cancel()
            {
                var res = new Object();
                res.html = "'.$htmlStr.'";
                res.footer = "'.$footerStr.'";
                res.footer_link = "";
                sbReturnValue(res);
            }
            sbAddEvent(window, "close", cancel);';
    }

    echo '</script>';

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_tester_edit_test_update'.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), 'thisDialog', 'post', 'checkValues()', 'main', 'enctype="multipart/form-data"');
    $layout->mTableWidth = '95%';
    $layout->mTitleWidth = '200';

    $layout->addTab(PL_TESTER_CAT_EDIT_TAB1);
    $layout->addHeader(PL_TESTER_CAT_EDIT_TAB1);

    $layout->addField(PL_TESTER_CAT_EDIT_TEST_NAME, new sbLayoutInput('text', $cat_title, 'cat_title', 'cat_title', 'style="width:440px;"', true));

    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea($desc, 'descr', '', 'style="height:100px; width:100%;"');
    $fld->mShowEditorBtn = true;
	$layout->addField(PL_TESTER_CAT_EDIT_CAT_DESCR, $fld);

    $fld = new sbLayoutInput('text', $num_questions, 'spin_num_questions', 'spin_num_questions', ($num_questions == -1 ? 'disabled="disabled"' : '').'style="width:80px;"');
    $fld_ch = new sbLayoutInput('checkbox', '1', 'num_questions', 'num_questions', 'onclick="changeInput(this, \'spin_num_questions\');" '.($num_questions == -1 ? 'checked="checked"' : ''));
    $html = '<table><tr><td>'.$fld->getJavaScript().' '.$fld->getField().'</td><td style="padding-left:5px;">'.$fld_ch->getField().' &nbsp;'.PL_TESTER_CAT_EDIT_UNLIM.'</td></tr></table>';
    $layout->addField(PL_TESTER_CAT_EDIT_NUM_QUESTIONS, new sbLayoutHTML($html));

    $fld = new sbLayoutInput('text', $num_questions_child, 'spin_num_questions_child', 'spin_num_questions_child', ($num_questions_child == -1 ? 'disabled="disabled"' : '').'style="width:80px;"');
    $fld_ch = new sbLayoutInput('checkbox', '1', 'num_questions_child', 'num_questions_child', 'onclick="changeInput(this, \'spin_num_questions_child\');" '.($num_questions_child == -1 ? 'checked="checked"' : ''));
    $html = '<table><tr><td>'.$fld->getJavaScript().' '.$fld->getField().'</td><td style="padding-left:5px;">'.$fld_ch->getField().' &nbsp;'.PL_TESTER_CAT_EDIT_UNLIM.'</td></tr></table>';
    $layout->addField(PL_TESTER_CAT_EDIT_NUM_QUESTIONS_CHILD, new sbLayoutHTML($html));

    // количество вопросов на странице
    $fld = new sbLayoutInput('text', $num_questions_per_page, 'spin_num_questions_per_page', 'spin_num_questions_per_page', 'style="width:80px;"');
    $fld->mMinValue = 1;
    $html = '<table><tr><td>'.$fld->getJavaScript().' '.$fld->getField().'</td><td style="padding-left:5px;"></td></tr></table>';
    $layout->addField(PL_TESTER_CAT_EDIT_NUM_QUESTIONS_PER_PAGE, new sbLayoutHTML($html));

    $fld = new sbLayoutInput('text', $test_time, 'spin_test_time', 'spin_test_time', ($test_time == -1 ? 'disabled="disabled"' : '').'style="width:80px;"');
    $fld_ch = new sbLayoutInput('checkbox', '1', 'test_time', 'test_time', 'onclick="changeInput(this, \'spin_test_time\');" '.($test_time == -1 ? 'checked="checked"' : ''));
    $html = '<table><tr><td>'.$fld->getField().'</td><td style="padding-left:5px;">'.$fld_ch->getField().' &nbsp;'.PL_TESTER_CAT_EDIT_UNLIM.'</td></tr></table>';
    $layout->addField(PL_TESTER_CAT_EDIT_TEST_TIME, new sbLayoutHTML($html));

    $fld = new sbLayoutInput('text', $answer_time, 'spin_answer_time', 'spin_answer_time', ($answer_time == -1 ? 'disabled="disabled"' : '').'style="width:80px;"');
    $fld_ch = new sbLayoutInput('checkbox', '1', 'answer_time', 'answer_time', 'onclick="changeInput(this, \'spin_answer_time\');" '.($answer_time == -1 ? 'checked="checked"' : ''));
    $html = '<table><tr><td>'.$fld->getField().'</td><td style="padding-left:5px;">'.$fld_ch->getField().' &nbsp;'.PL_TESTER_CAT_EDIT_UNLIM.'</td></tr></table>';
    $layout->addField(PL_TESTER_CAT_EDIT_ANSWER_TIME, new sbLayoutHTML($html));

    $fld = new sbLayoutInput('text', $retest_num, 'spin_retest_num', 'spin_retest_num', ($retest_num == -1 ? 'disabled="disabled"' : '').'style="width:80px;"');
    $fld_ch = new sbLayoutInput('checkbox', '1', 'retest_num', 'retest_num', 'onclick="changeInput(this, \'spin_retest_num\');" '.($retest_num == -1 ? 'checked="checked"' : ''));
    $html = '<table><tr><td>'.$fld->getField().'</td><td style="padding-left:5px;">'.$fld_ch->getField().' &nbsp;'.PL_TESTER_CAT_EDIT_UNLIM.'</td></tr></table>';
    $layout->addField(PL_TESTER_CAT_EDIT_RETEST_NUM, new sbLayoutHTML($html));

    $fld = new sbLayoutInput('text', $retest_time, 'spin_retest_time', 'spin_retest_time', ($retest_time == -1 ? 'disabled="disabled"' : '' ).'style="width:80px;"');
    $fld_ch = new sbLayoutInput('checkbox', '1', 'retest_time', 'retest_time', 'onclick="changeInput(this, \'spin_retest_time\');" '.($retest_time == -1 ? 'checked = "checked"' : ''));
    $html = '<table><tr><td>'.$fld->getField().'</td><td style="padding-left:5px;">'.$fld_ch->getField().' &nbsp;'.PL_TESTER_CAT_EDIT_UNLIM.'</td></tr></table>';
    $layout->addField(PL_TESTER_CAT_EDIT_RETEST_TIME, new sbLayoutHTML($html));

    $fld = new sbLayoutInput('checkbox', '1', 'criteria_persent', '', ($criteria_persent == 1)? 'checked="checked"' : '');
    $layout->addField(PL_TESTER_CAT_EDIT_CRITERIA_PERSENT, $fld);

    $fld = new sbLayoutInput('text', $criteria, 'spin_criteria', 'spin_criteria', 'style="width:80px;"');
    $html = '<table><tr><td>'.$fld->getField().'</td><td></td></tr></table>';
    $layout->addField(PL_TESTER_CAT_EDIT_CRITERIA, new sbLayoutHTML($html));

    $layout->addField('', new sbLayoutInput('hidden', $_GET['cat_id_p'], 'cat_id_p'));
    $layout->addField(PL_TESTER_CAT_EDIT_EMAIL, new sbLayoutInput('text', $email, 'email', '', ''));

    $layout->addField('', new sbLayoutDelim());

    $layout->getPluginFields('pl_tester', $_GET['cat_id'], '', true);

    $layout->addTab(PL_TESTER_CAT_EDIT_TAB2);
    $layout->addHeader(PL_TESTER_CAT_EDIT_TAB2);

    $html = fTester_Results_Output($_GET['cat_id']);
    $layout->addField('', new sbLayoutHTML($html, true));

    $layout->addTab(PL_TESTER_CAT_EDIT_RIGHTS_TAB);
    $layout->addHeader(PL_TESTER_CAT_EDIT_RIGHTS_TAB);

    $layout->addField(PL_TESTER_CAT_EDIT_CLOSED_TEST, new sbLayoutInput('checkbox', '1', 'cat_closed', '', 'onclick="changeType(this);"'.($cat_closed ? ' checked="checked"' : '')));

	if(isset($_GET['cat_id']) && $_GET['cat_id'] != '')
	{
		$layout->addField(PL_TESTER_CAT_EDIT_CLOSED_TEST_SUB, new sbLayoutInput('checkbox', '1', 'cat_close_sub'));
	}

	$layout->addField('', new sbLayoutDelim());

    if(count($_SESSION['sb_categs_closed_descr']['pl_tester']) == 0)
    {
        $_SESSION['sb_categs_closed_descr']['pl_tester']['read'] = PL_TESTER_CAT_EDIT_GROUPS;
    }

    foreach ($_SESSION['sb_categs_closed_descr']['pl_tester'] as $ident => $name)
    {
		$ident = 'pl_tester_'.$ident;
        if (isset($_POST['group_ids'.$ident]) && isset($_POST['group_names'.$ident]))
        {
            $group_ids = $_POST['group_ids'.$ident];
            $group_names = $_POST['group_names'.$ident];
        }
        else
        {
            $group_ids = '';
            $group_names = '';
            $cat_id = isset($_GET['cat_id']) && $_GET['cat_id'] != '' ? $_GET['cat_id'] : $_GET['cat_id_p'];
            $res = sql_param_query('SELECT group_ids FROM sb_catrights
                                        WHERE cat_id=?d AND right_ident=?', $cat_id, $ident);
            if($res)
            {
                list($group_ids) = $res[0];
            	$ids = explode('^', trim($group_ids, '^'));

            	$res = sql_param_query('SELECT cat_title FROM sb_categs WHERE cat_id IN (?a)', $ids);
                if ($res)
                {
                    $group_names = array();
                    foreach($res as $value)
                    {
                        $group_names[] = $value[0];
                    }
                    $group_names = implode(', ', $group_names);
                }
                else
                {
                    $group_ids = '';
                }
            }
        }
		$layout->addField('', new sbLayoutInput('hidden', $ident, 'ident_catrights'));
        $layout->addField('', new sbLayoutInput('hidden', $group_ids, 'group_ids'.$ident));
        $layout->addField($name, new sbLayoutHTML('<input id="group_names'.$ident.'" name="group_names'.$ident.'" readonly="readonly"'.(!$cat_closed ? ' disabled="disabled"' : '').' style="width:75%;" value="'.$group_names.'">&nbsp;&nbsp;<img class="button" src="'.SB_CMS_IMG_URL.'/users.png" width="20" height="20" align="absmiddle" id="group_btn'.$ident.'" onmouseup="sbPress(this, false);" onmousedown="sbPress(this, true);" onclick="browseGroups(\''.$ident.'\');" title="'.KERNEL_BROWSE.'" />'));
    }

    $layout->addField('', new sbLayoutInput('hidden', $cat_rights, 'cat_rights'));

    $layout->addButton('submit', KERNEL_SAVE, 'btn_save', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_tester', 'elems_edit') ? '' : 'disabled="disabled"'));
    $layout->addButton('button', KERNEL_CANCEL, '', '', $htmlStr != '' ? 'onclick="cancel()"' : 'onclick="sbCloseDialog()"');

    $layout->show();
}

function fTester_Edit_Test_Update()
{
	$answer_time = $retest_time = $retest_num = $num_questions = $test_time = $cat_closed = $spin_criteria =
	$criteria_persent = $cat_rights = $cat_rubrik = $cat_close_sub = $cat_id_p = 0;
	$cat_title = $descr = $email = $group_ids_read = $group_idspl_tester_read = $num_questions_child = '';
    $results = array();

    extract($_POST);

    if ($cat_title == '')
    {
        sb_show_message(PL_TESTER_CAT_EDIT_NO_TITLE_MSG, false, 'warning');
        fTester_Edit_Test();
        return;
    }

    if ($answer_time == 1)
    {
        $spin_answer_time = -1;
    }
    if ($test_time == 1)
    {
        $spin_test_time = -1;
    }
    if ($retest_num == 1)
    {
        $spin_retest_num = -1;
    }
    if ($retest_time == 1)
    {
        $spin_retest_time = -1;
    }
    if ($num_questions == 1)
    {
        $spin_num_questions = -1;
    }
    if ($num_questions_child == 1)
    {
        $spin_num_questions_child = -1;
    }

    //проверка пользовательских полей
    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');
    $layout = new sbLayout();
    $user_row = array();
    $user_row = $layout->checkPluginFields('pl_tester', $_GET['cat_id'], '', true);

    if ($user_row === false)
    {
       	$layout->deletePluginFieldsFiles();
       	fTester_Edit_Test();
       	return;
    }

    $cat_fields = array();
    $cat_fields['spin_test_time'] = $spin_test_time;
    $cat_fields['spin_num_questions'] = $spin_num_questions;
    $cat_fields['spin_num_questions_child'] = $spin_num_questions_child;
    $cat_fields['spin_questions_per_page'] = $spin_num_questions_per_page;
    $cat_fields['descr'] = $descr;
    $cat_fields['spin_retest_time'] = $spin_retest_time;
    $cat_fields['spin_criteria'] = $spin_criteria;
    $cat_fields['criteria_persent'] = $criteria_persent;
    $cat_fields['email'] = $email;
    $cat_fields['spin_answer_time'] = $spin_answer_time;
    $cat_fields['spin_retest_num'] = $spin_retest_num;

    $cat_fields = array_merge($cat_fields, $user_row);
    $cat_fields = serialize($cat_fields);
    $rows = array();
    $rows['cat_title'] = $cat_title;
    $rows['cat_closed'] = $cat_closed;
    $rows['cat_rights'] = $cat_rights;
    $rows['cat_fields'] = $cat_fields;

	if(isset($_GET['cat_id']) && $_GET['cat_id'] != '')
    {
        $cat_id = intval($_GET['cat_id']);

        //редактирование
        $res = sql_param_query('SELECT cat_left, cat_right, cat_rubrik FROM sb_categs WHERE cat_id=?d', $cat_id);
        if($res)
        {
	        //редактирование
	        sql_param_query('UPDATE sb_categs SET ?a WHERE cat_id=?d ', $rows, $_GET['cat_id']);
	        sql_param_query('DELETE FROM sb_tester_marks_results WHERE stmr_test_id=?d', $_GET['cat_id']);
	        sql_param_query('DELETE FROM sb_catrights WHERE cat_id=?d', $_GET['cat_id']);
	        sql_param_query('UPDATE sb_tester_results SET  str_passed = case when str_mark >= ?d then 1 ELSE 0 end WHERE str_test_id = ?d', $spin_criteria, $_GET['cat_id']);

			$cat_ids = array();

			list($cat_left, $cat_right, $cat_rubrik) = $res[0];

    		$rows['cat_rubrik'] = $cat_rubrik;
			if ($cat_close_sub == 1)
			{
				$res_sub = sql_query('SELECT cat_id, cat_title FROM sb_categs WHERE cat_left > '.$cat_left.' AND cat_right < '.$cat_right.' AND cat_ident="pl_tester"');
				if ($res_sub)
	        	{
					foreach ($res_sub as $value)
	        		{
						$cat_ids[] = $value[0];
						sql_param_query('UPDATE sb_categs SET cat_closed=?d WHERE cat_id=?d', $cat_closed, $value[0], sprintf(PL_TESTER_SYSLOG_RIGHTS_OK, $value[1]));
					}
	        	}
			}

			$cat_ids[] = $cat_id;
			sql_param_query('DELETE FROM sb_catrights WHERE cat_id IN (?a)', $cat_ids);

			if ($cat_closed == 1)
            {
            	// закрытый раздел
                foreach ($_SESSION['sb_categs_closed_descr']['pl_tester'] as $ident => $name)
                {
					$ident = 'pl_tester_'.$ident;
                    foreach ($cat_ids as $value)
                    {
						sql_param_query('INSERT INTO sb_catrights (cat_id, group_ids, right_ident)
								VALUES (?d, ?, ?)', $value, $_POST['group_ids'.$ident], $ident);
					}
				}
			}

	        $res = sql_param_query('SELECT cat_left, cat_right, cat_title FROM sb_categs WHERE cat_id=?d', $_GET['cat_id']);
	        list($cat_left, $cat_right, $cat_title) = $res[0];

		    $count_res = sql_query('SELECT COUNT(*) FROM sb_categs categs, sb_catlinks links WHERE categs.cat_ident=\'pl_tester\' AND categs.cat_left >= '.$cat_left.' AND categs.cat_right <= '.$cat_right.' AND links.link_cat_id=categs.cat_id');
		    if ($count_res)
		    {
		        $cat_count = $count_res[0][0];
		    }
		    else
		    {
		        $cat_count = 0;
		    }

			echo '<script>
		        var res = new Object();
		        res.cat_title = "'.str_replace('"', '\\"', $cat_title).' ['.$cat_count.']";
		        res.cat_closed = '.(isset($cat_closed) ? $cat_closed : 0).';
				res.cat_rubrik = "'.(isset($cat_rubrik) ? $cat_rubrik : 0).'";
				res.cat_close_sub = '.$cat_close_sub.';
				sbReturnValue(res);
		    </script>';
        }
    }
    else
    {
        //добавление
        require_once(SB_CMS_LIB_PATH.'/sbTree.inc.php');

		$cat_rub = sql_param_query('SELECT cat_rubrik FROM sb_categs WHERE cat_id=?d', $cat_id_p);
		$rows['cat_rubrik'] = (isset($cat_rub[0][0]) ? $cat_rub[0][0] : 0);

        $tree = new sbTree('pl_tester');
        $ins_id = $tree->insertNode($cat_id_p, $rows);

		if ($cat_closed == 1)
        {
			$rows = array();
            $rows['cat_id'] = $ins_id;
            $rows['group_ids'] = $group_idspl_tester_read;
            $rows['right_ident'] = 'pl_tester_read';

            sql_param_query('INSERT INTO sb_catrights SET ?a', $rows);
        }

        echo '<script>
	        var res = new Object();
            res.cat_id = '.$ins_id.';
	        res.cat_title = "'.str_replace('"', '\\"', $cat_title).' [0]";
	        res.cat_closed = '.intval($cat_closed).';
			res.cat_rubrik = "'.(isset($cat_rub[0][0]) ? $cat_rub[0][0] : 0).'";
	        sbReturnValue(res);
	    </script>';
        $_GET['cat_id'] = $ins_id;
    }

    for ($i = 0; $i < count($results); $i++)
    {
    	$result_id = $results[$i];

    	$rows = array();
        $rows['stmr_test_id'] = $_GET['cat_id'];
        $rows['stmr_start'] = ($_POST['result_'.$result_id.'_mark_from']?$_POST['result_'.$result_id.'_mark_from']:0);
        $rows['stmr_end'] = ($_POST['result_'.$result_id.'_mark_to'] != ''?$_POST['result_'.$result_id.'_mark_to']:0);
        $rows['stmr_result'] = $_POST['result_'.$result_id.'_result'];
        $rows['stmr_order'] = $_POST['result_'.$result_id.'_order'];

        sql_param_query('INSERT INTO sb_tester_marks_results SET ?a', $rows);
    }
}

function fTester_Delete_Test()
{
    sql_param_query('DELETE FROM sb_tester_marks_results WHERE stmr_test_id=?d ', $_GET['cat_id']);
    sql_param_query('DELETE FROM sb_tester_results WHERE str_test_id=?d ', $_GET['cat_id']);
    sql_param_query('DELETE FROM sb_tester_temp_interrupts WHERE stti_test_id=?d ', $_GET['cat_id']);
    sql_param_query('DELETE FROM sb_tester_temp_results WHERE sttr_test_id=?d ', $_GET['cat_id']);
    sql_param_query('DELETE FROM sb_tester_answers_results WHERE star_test_id=?d ', $_GET['cat_id']);
}

/**
 * Функции управления дизайном вывода вопросов теста
 */
function fTester_Design_Get($args)
{
    $result = '<b><a href="javascript:void(0);" onclick="sbElemsEdit(event);">'.$args['stt_title'].'</a></b>
    <div class="smalltext" style="margin-top: 7px;">';

    static $view_info_pages = null;
    static $view_info_temps = null;

    if (is_null($view_info_pages))
    {
        $view_info_pages = $_SESSION['sbPlugins']->isRightAvailable('pl_pages', 'read');
    }

    if (is_null($view_info_temps))
    {
        $view_info_temps = $_SESSION['sbPlugins']->isRightAvailable('pl_templates', 'read');
    }

    $id = intval($args['stt_id']);

    $pages = sql_query('SELECT pages.p_id, pages.p_name FROM sb_pages pages, sb_elems elems
                        WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_tester_test" AND pages.p_id=elems.e_p_id GROUP BY pages.p_id ORDER BY pages.p_name LIMIT 4');

    $temps = sql_query('SELECT temps.t_id, temps.t_name FROM sb_templates temps, sb_elems elems
                        WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_tester_test" AND temps.t_id=elems.e_p_id GROUP BY temps.t_id ORDER BY temps.t_name LIMIT 4');

    if ($pages)
    {
        $result .= '<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td valign="top" nowrap="nowrap" class="smalltext">'.KERNEL_USED_ON_PAGES.':&nbsp;</td>
                        <td class="smalltext"><span style="color:green;">';

        $num = min(3, count($pages));
        for ($i = 0; $i < $num; $i++)
        {
            list($p_id, $p_name) = $pages[$i];
            $result .= ($view_info_pages ? '<a href="'.SB_CMS_CONTENT_FILE.'?event=pl_pages_init&sb_sel_id='.$p_id.'">'.$p_name.'</a>' : $p_name).', ';
        }

        if ($num < count($pages))
            $result .= '&nbsp;<a href="javascript:void(0);" onclick="linkPages(event);">...</a>';
        else
            $result = substr($result, 0, -2);

        $result .= '</span></td></tr></table>';
    }
    else
    {
        $result .= KERNEL_USED_ON_PAGES.': <span style="color: red;">'.KERNEL_NOT_USED.'</span><br />';
    }

    if ($temps)
    {
        $result .= '<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td valign="top" nowrap="nowrap" class="smalltext">'.KERNEL_USED_ON_TEMPS.':&nbsp;</td>
                        <td class="smalltext"><span style="color:green;">';

        $num = min(3, count($temps));
        for ($i = 0; $i < $num; $i++)
        {
            list($t_id, $t_name) = $temps[$i];
            $result .= ($view_info_temps ? '<a href="'.SB_CMS_CONTENT_FILE.'?event=pl_templates_init&sb_sel_id='.$t_id.'">'.$t_name.'</a>' : $t_name).', ';
        }

        if ($num < count($temps))
            $result .= '&nbsp;<a href="javascript:void(0);" onclick="linkTemps(event);">...</a>';
        else
            $result = substr($result, 0, -2);

        $result .= '</span></td></tr></table>';
    }
    else
    {
        $result .= KERNEL_USED_ON_TEMPS.': <span style="color: red;">'.KERNEL_NOT_USED.'</span><br />';
    }

    $result .= '</div>';
    return $result;

}

function fTester_Design_Init()
{
	require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');
    $elems = new sbElements('sb_tester_temps', 'stt_id', 'stt_title', 'fTester_Design_Get', 'pl_tester_design_init', 'pl_tester_design');
    $elems->mCategsDeleteWithElementsMenu = true;

    $elems->mCategsRootName = KERNEL_DESIGN_ROOT_NAME;
    $elems->mElemsIcon = SB_CMS_IMG_URL.'/plugins/pl_tester_design_32.png';

    $elems->mElemsCutMenuTitle = KERNEL_DESIGN_ELEMS_CUT_MENU_TITLE;
    $elems->mElemsCopyMenuTitle = KERNEL_DESIGN_ELEMS_COPY_MENU_TITLE;
    $elems->mElemsEditMenuTitle = KERNEL_DESIGN_ELEMS_EDIT_MENU_TITLE;
    $elems->mElemsAddMenuTitle = KERNEL_DESIGN_ELEMS_ADD_MENU_TITLE;
    $elems->mElemsDeleteMenuTitle = KERNEL_DESIGN_ELEMS_DELETE_MENU_TITLE;
    $elems->mCategsPasteElemsMenuTitle = KERNEL_DESIGN_PASTE_ELEMS_MENU_TITLE;

    $elems->mElemsEditEvent =  'pl_tester_design_edit';
    $elems->mElemsEditDlgWidth = 900;
    $elems->mElemsEditDlgHeight = 600;

    $elems->mElemsAddEvent =  'pl_tester_design_edit';
    $elems->mElemsAddDlgWidth = 900;
    $elems->mElemsAddDlgHeight = 600;

    $elems->mElemsDeleteEvent = 'pl_tester_design_delete';

    $elems->addSorting(PL_TESTER_DESIGN_SORT_BY_TITLE, "stt_title");

    $elems->mCategsClosed = false;
    $elems->mCategsRubrikator = false;
    $elems->mElemsUseLinks = false;

    $elems->mElemsJavascriptStr .= '
            function linkPages(e)
            {
                if (!sbSelEl)
                {
                    var el = sbEventTarget(e);

                    while (el.parentNode && !el.getAttribute("true_id"))
                        el = el.parentNode;

                    var el_id = el.getAttribute("el_id");
                }
                else
                    var el_id = sbSelEl.getAttribute("el_id");

                strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_get_links&id=" + el_id + "&ident[]=pl_tester_test";
                strAttr = "resizable=1,width=800,height=600";
                sbShowModalDialog(strPage, strAttr, null, window);
            }
            function linkTemps(e)
            {
                if (!sbSelEl)
                {
                    var el = sbEventTarget(e);

                    while (el.parentNode && !el.getAttribute("true_id"))
                        el = el.parentNode;

                    var el_id = el.getAttribute("el_id");
                }
                else
                    var el_id = sbSelEl.getAttribute("el_id");

                strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_templates_get_links&id=" + el_id + "&ident[]=pl_tester_test";
                strAttr = "resizable=1,width=800,height=600";
                sbShowModalDialog(strPage, strAttr, null, window);
            }
            function editElems()
            {
                window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_tester_questions";
            }';

    $elems->addElemsMenuItem(KERNEL_LINK_PAGES_MENU_ITEM, 'linkPages();');
    $elems->addElemsMenuItem(KERNEL_LINK_TEMPS_MENU_ITEM, 'linkTemps();');
    $elems->addElemsMenuItem(PL_TESTER_DESIGN_TESTS_MENU, 'editElems()', false);
    $elems->init();
}

function fTester_Design_Edit ($htmlStr = '', $footerStr = '')
{
	// проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_tester_design'))
		return;

    if (count($_POST) == 0 && isset($_GET['id']) && $_GET['id'] != '')
    {
        $result = sql_param_query('SELECT stt_title, stt_date, stt_lang, stt_top, stt_quest, stt_chet_answer, stt_nechet_answer,
                              stt_empty_element, stt_always_chet, stt_bottom, stt_result, stt_system_message, stt_fields_temps, stt_categs_temps
                              FROM sb_tester_temps WHERE stt_id= ?d', $_GET['id']);
        if ($result)
        {
        	list($stt_title, $stt_date, $stt_lang,  $stt_top, $stt_quest, $stt_chet_answer, $stt_nechet_answer, $stt_empty_element, $stt_always_chet, $stt_bottom, $stt_result,
            $stt_system_message, $stt_fields_temps, $stt_categs_temps) = $result[0];
        }
        else
        {
            sb_show_message(PL_TESTER_DESIGN_EDIT_ERROR, true, 'warning');
            return;
        }

        if ($stt_fields_temps != '')
            $stt_fields_temps = unserialize($stt_fields_temps);
        else
            $stt_fields_temps = array();

        if ($stt_system_message != '')
            $stt_system_message = unserialize($stt_system_message);
        else
            $stt_system_message = array();

        if(!isset($stt_fields_temps['change_date']))
        	$stt_fields_temps['change_date'] = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';

        if(!isset($stt_system_message['sorting_input']))
        {
            $stt_system_message['sorting_input'] = PL_TESTER_DESIGN_SYSTEM_MSG_SORTING_INPUT;
        }

        if(!isset($stt_system_message['inline_input']))
        {
            $stt_system_message['inline_input'] = PL_TESTER_DESIGN_SYSTEM_MSG_INLINE_INPUT;
        }

        if(!isset($stt_system_message['free_input']))
        {
            $stt_system_message['free_input'] = PL_TESTER_DESIGN_SYSTEM_MSG_FREE_INPUT;
        }

        if($stt_categs_temps != '')
        {
            $stt_categs_temps = unserialize($stt_categs_temps);
        }
    }
    elseif (count($_POST) > 0)
    {
		$stt_always_chet = 0;

    	extract($_POST);

        if (!isset($_GET['id']))
            $_GET['id'] = '';
    }
    else
    {
        $stt_title = '';
        $stt_date = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';
        $stt_fields_temps['change_date'] = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';
        $stt_lang = SB_CMS_LANG;

        $stt_top = '<form action=\'{FORM_ACTION}\' method=\'POST\' enctype=\'multipart/form-data\'>{DOP_FIELDS}';
        $stt_quest = '';
        $stt_chet_answer = '';
        $stt_nechet_answer = '';
        $stt_empty_element = '';
        $stt_always_chet = 0;
        $stt_bottom = '</from>';
        $stt_result = '';

        $stt_system_message['no_access'] = PL_TESTER_DESIGN_SYSTEM_MSG_NO_ACCESS;
		$stt_system_message['test_interrupted'] = PL_TESTER_DESIGN_SYSTEM_MSG_TEST_INTERRUPTED;
		$stt_system_message['repeated_test'] = PL_TESTER_DESIGN_SYSTEM_MSG_REPEATED_TEST;
		$stt_system_message['no_limit_text'] = PL_TESTER_DESIGN_SYSTEM_MSG_NO_LIMIT_TEXT;
		$stt_system_message['end_time_answer'] = PL_TESTER_DESIGN_SYSTEM_MSG_END_TIME_ANSWER;
		$stt_system_message['no_connection'] = PL_TESTER_DESIGN_SYSTEM_MSG_NO_CONNECTION;
		$stt_system_message['letter_theme'] = '';
		$stt_system_message['letter_body'] = '';
		$stt_system_message['test_time'] = PL_TESTER_DESIGN_SYSTEM_MSG_TEST_TIME;
		$stt_system_message['attempts_limit'] = PL_TESTER_DESIGN_SYSTEM_MSG_ATTEMPTS_LIMIT;

		$stt_system_message['checkbox_input'] = PL_TESTER_DESIGN_SYSTEM_MSG_CHECKBOX_INPUT;
		$stt_system_message['radio_input'] = PL_TESTER_DESIGN_SYSTEM_MSG_RADIO_INPUT;
        $stt_system_message['sorting_input'] = PL_TESTER_DESIGN_SYSTEM_MSG_SORTING_INPUT;
        $stt_system_message['inline_input'] = PL_TESTER_DESIGN_SYSTEM_MSG_INLINE_INPUT;
        $stt_system_message['free_input'] = PL_TESTER_DESIGN_SYSTEM_MSG_FREE_INPUT;

        $stt_categs_temps = array();

		$_GET['id'] = '';
    }

    echo '<script>
            function checkValues()
            {
                var el_title = sbGetE("stt_title");
                if (el_title.value == "")
                {
                     alert("'.PL_TESTER_DESIGN_EDIT_NO_TITLE.'");
                     return false;
                }
            }';

    if ($htmlStr != '')
    {
        echo '  function cancel()
	            {
	                var res = new Object();
	                res.html = "'.$htmlStr.'";
	                res.footer = "'.$footerStr.'";
	                res.footer_link = "";
	                sbReturnValue(res);
	            }
	            sbAddEvent(window, "close", cancel);';
    }
    echo '</script>';

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_tester_design_update'.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), 'thisDialog', 'post', 'checkValues()');
    $layout->mTableWidth = '95%';
    $layout->mTitleWidth = '200';

    $layout->addTab(PL_TESTER_DESIGN_EDIT_TAB1);
    $layout->addHeader(PL_TESTER_DESIGN_EDIT_TAB1);

    $layout->addField(PL_TESTER_DESIGN_EDIT_TITLE, new sbLayoutInput('text', $stt_title, 'stt_title', '', 'style="width:450px;"', true));

    $fld = new sbLayoutTextarea($stt_date, 'stt_date', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}');
    $fld->mValues = array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG);
    $layout->addField(PL_TESTER_DESIGN_EDIT_DATE, $fld);

    //Дата последнего изменения
    $fld = new sbLayoutTextarea($stt_fields_temps['change_date'], 'stt_fields_temps[change_date]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}');
    $fld->mValues = array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG);
    $layout->addField(PL_TESTER_DESIGN_EDIT_CHANGE_DATE, $fld);

    $fld = new sbLayoutSelect($GLOBALS['sb_site_langs'], 'stt_lang');
    $fld->mSelOptions = array($stt_lang);
    $fld->mHTML = '<div class="hint_div" style="margin-top: 5px;">'.PL_TESTER_DESIGN_EDIT_LANG_LABEL.'</div>';
    $layout->addField(PL_TESTER_DESIGN_EDIT_LANG, $fld);

    $layout->addField('', new sbLayoutDelim());

    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_TESTER_DESIGN_EDIT_SYSTEM_MESSAGE.'</div>';
    $layout->addField('', new sbLayoutHTML($html, true));

    $fld = new sbLayoutTextarea($stt_system_message['no_access'], 'stt_system_message[no_access]', '', 'style="width:100%;height:70px;"');
    $fld->mShowToolbar = false;
    $layout->addField(PL_TESTER_DESIGN_EDIT_ACCESS_FORBIDDEN, $fld);

    $fld = new sbLayoutTextarea($stt_system_message['test_interrupted'], 'stt_system_message[test_interrupted]', '', 'style="width:100%;height:70px;"');
    $fld->mShowToolbar = false;
    $layout->addField(PL_TESTER_DESIGN_EDIT_TEST_INTERRUPTED, $fld);

    $fld = new sbLayoutTextarea($stt_system_message['repeated_test'], 'stt_system_message[repeated_test]', '', 'style="width:100%;height:70px;"');
    $fld->mShowToolbar = false;
    $layout->addField(PL_TESTER_DESIGN_EDIT_REPEATED_TEST, $fld);

    $fld = new sbLayoutTextarea($stt_system_message['no_limit_text'], 'stt_system_message[no_limit_text]', '', 'style="width:100%;height:70px;"');
    $fld->mShowToolbar = false;
    $layout->addField(PL_TESTER_DESIGN_EDIT_NO_LIMIT_TEXT, $fld);

    $fld = new sbLayoutTextarea($stt_system_message['end_time_answer'], 'stt_system_message[end_time_answer]', '', 'style="width:100%;height:70px;"');
    $fld->mShowToolbar = false;
    $layout->addField(PL_TESTER_DESIGN_EDIT_END_TIME_ANSWER, $fld);

    $fld = new sbLayoutTextarea($stt_system_message['no_connection'], 'stt_system_message[no_connection]', '', 'style="width:100%;height:70px;"');
    $fld->mTags = array('{NUM_ATTEMPTS}');
    $fld->mValues = array(PL_TESTER_DESIGN_EDIT_NUM_ATTEMPTS);
    $layout->addField(PL_TESTER_DESIGN_EDIT_NO_CONNECTION, $fld);

    $fld = new sbLayoutTextarea($stt_system_message['letter_theme'], 'stt_system_message[letter_theme]', '', 'style="width:100%;height:70px;"');
    $fld->mTags = array('{TEST_TITLE}', '{TEST_DESCR}', '{TEST_RESULT_MARK}', '{TEST_RESULT_TEXT}', '{TEST_TIME}', '{USER_LOGIN}', '{USER_FIO}');
    $fld->mValues = array(PL_TESTER_DESIGN_EDIT_TEST_TITLE, PL_TESTER_DESIGN_EDIT_TEST_DESCR, PL_TESTER_DESIGN_EDIT_TEST_RESULT_MARK, PL_TESTER_DESIGN_EDIT_TEST_RESULT_TEXT, PL_TESTER_DESIGN_EDIT_TEST_TIME, PL_TESTER_DESIGN_EDIT_USER_LOGIN, PL_TESTER_DESIGN_EDIT_USER_FIO);
    $layout->addField(PL_TESTER_DESIGN_EDIT_LETTER_THEME, $fld);

    $fld = new sbLayoutTextarea($stt_system_message['letter_body'], 'stt_system_message[letter_body]', '', 'style="width:100%;height:70px;"');
    $fld->mTags = array('{TEST_TITLE}', '{TEST_DESCR}', '{TEST_RESULT_MARK}', '{TEST_RESULT_TEXT}', '{TEST_TIME}', '{USER_LOGIN}', '{USER_FIO}');
    $fld->mValues = array(PL_TESTER_DESIGN_EDIT_TEST_TITLE, PL_TESTER_DESIGN_EDIT_TEST_DESCR, PL_TESTER_DESIGN_EDIT_TEST_RESULT_MARK, PL_TESTER_DESIGN_EDIT_TEST_RESULT_TEXT, PL_TESTER_DESIGN_EDIT_TEST_TIME, PL_TESTER_DESIGN_EDIT_USER_LOGIN, PL_TESTER_DESIGN_EDIT_USER_FIO);
    $layout->addField(PL_TESTER_DESIGN_EDIT_LETTER_TEXT, $fld);

    $fld = new sbLayoutTextarea($stt_system_message['test_time'], 'stt_system_message[test_time]', '', 'style="width:100%;height:70px;"');
    $fld->mShowToolbar = false;
    $layout->addField(PL_TESTER_DESIGN_EDIT_ALL_TIME_TEST, $fld);

    $fld = new sbLayoutTextarea($stt_system_message['attempts_limit'], 'stt_system_message[attempts_limit]', '', 'style="width:100%;height:70px;"');
    $fld->mTags = array('{NUM_ATTEMPTS}');
    $fld->mValues = array(PL_TESTER_DESIGN_EDIT_COUNT_ATTEMPTS);
    $layout->addField(PL_TESTER_DESIGN_EDIT_ATTEMPTS_LIMIT, $fld);

    // пользовательские поля
    //$str_categs_temps = array();
    $dop_tags = array();
    $dop_values = array();
    $res = sql_query('SELECT pd_fields, pd_categs FROM sb_plugins_data WHERE pd_plugin_ident="pl_tester"');
	if ($res)
    {
    	list($pd_fields, $pd_categs) = $res[0];

    	if ($pd_fields != '')
    	{
    		$pd_fields = unserialize($pd_fields);

	    	if ($pd_fields)
	    	{
		    	if ($pd_fields[0]['type'] != 'tab' && $pd_fields[0]['type'] != 'hr')
		    	{
					$html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_TESTER_DESIGN_EDIT_USER_TAB1.'</div>';
			    	$layout->addField('', new sbLayoutHTML($html, true));
			    	$layout->addField('', new sbLayoutDelim());
		    	}

				$layout->addPluginFieldsTemps('pl_tester', $stt_fields_temps, 'stt_', $dop_tags, $dop_values);
	    	}
    	}
    	else
    	{
    		$pd_fields = array();
    	}

    	if ($pd_categs != '')
    	{
    		$pd_categs = unserialize($pd_categs);

	    	if ($pd_categs)
	    	{
		    	if ($pd_categs[0]['type'] != 'tab' && $pd_categs[0]['type'] != 'hr')
		    	{
					$html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_TESTER_DESIGN_EDIT_USER_TAB2.'</div>';
			    	$layout->addField('', new sbLayoutHTML($html, true));
			    	$layout->addField('', new sbLayoutDelim());
		    	}

				$layout->addPluginFieldsTemps('pl_tester', $stt_categs_temps, 'stt_', $dop_tags, $dop_values, true);
	    	}
    	}
    	else
    	{
    		$pd_categs = array();
    	}
    }

    $layout->addTab(PL_TESTER_DESIGN_EDIT_TAB2);
    $layout->addHeader(PL_TESTER_DESIGN_EDIT_TAB2);

    $fld = new sbLayoutTextarea($stt_system_message['checkbox_input'], 'stt_system_message[checkbox_input]', '', 'style="width:100%;height:70px;"');
    $fld->mTags = array(PL_TESTER_DESIGN_SYSTEM_MSG_CHECKBOX_INPUT);
    $fld->mValues = array(PL_TESTER_DESIGN_EDIT_INPUT_ELEMENT_TAG);
    $layout->addField(PL_TESTER_DESIGN_EDIT_CHECKBOX, $fld);

    $fld = new sbLayoutTextarea($stt_system_message['radio_input'], 'stt_system_message[radio_input]', '', 'style="width:100%;height:70px;"');
    $fld->mTags = array(PL_TESTER_DESIGN_SYSTEM_MSG_RADIO_INPUT);
    $fld->mValues = array(PL_TESTER_DESIGN_EDIT_INPUT_ELEMENT_TAG);
    $layout->addField(PL_TESTER_DESIGN_EDIT_RADIO, $fld);

    // произвольный ответ
    $fld = new sbLayoutTextarea($stt_system_message['free_input'], 'stt_system_message[free_input]', '', 'style="width:100%;height:70px;"');
    $fld->mTags = array(PL_TESTER_DESIGN_SYSTEM_MSG_FREE_INPUT);
    $fld->mValues = array(PL_TESTER_DESIGN_EDIT_INPUT_ELEMENT_TAG);
    $layout->addField(PL_TESTER_DESIGN_EDIT_FREE, $fld);

    // расстановка ответов
    $fld = new sbLayoutTextarea($stt_system_message['sorting_input'], 'stt_system_message[sorting_input]', '', 'style="width:100%;height:70px;"');
    $fld->mTags = array(PL_TESTER_DESIGN_SYSTEM_MSG_SORTING_INPUT);
    $fld->mValues = array(PL_TESTER_DESIGN_EDIT_INPUT_ELEMENT_TAG);
    $layout->addField(PL_TESTER_DESIGN_EDIT_SORTING, $fld);

    // сопоставление ответов
    $fld = new sbLayoutTextarea($stt_system_message['inline_input'], 'stt_system_message[inline_input]', '', 'style="width:100%;height:70px;"');
    $fld->mTags = array(PL_TESTER_DESIGN_SYSTEM_MSG_INLINE_INPUT);
    $fld->mValues = array(PL_TESTER_DESIGN_EDIT_INPUT_ELEMENT_TAG);
    $layout->addField(PL_TESTER_DESIGN_EDIT_INLINE, $fld);


    $layout->addTab(PL_TESTER_DESIGN_EDIT_TAB3);
    $layout->addHeader(PL_TESTER_DESIGN_EDIT_TAB3);

    $tester_tags = array();
	$tester_tags_values = array();
	$layout->getPluginFieldsTags('pl_tester', $tester_tags, $tester_tags_values, true);

    $fld = new sbLayoutTextarea($stt_top, 'stt_top', '', 'style="width:100%;height:70px;"');
    $fld->mTags = array_merge(
            array('<input type=\'submit\' name=\'sb_tester_next\' value=\''.PL_TESTER_DESIGN_EDIT_NEXT.'\'>',
           '<input type=\'submit\' name=\'sb_tester_cancel\' value=\''.PL_TESTER_DESIGN_EDIT_CANCEL.'\'>',
           '{TEST_TITLE}', '{TEST_DESCR}',  '{TEST_TEST_TIME}', '{TEST_ANSWER_TIME}', '{TEST_REST_TIME}', '{TEST_RETEST_TIME}'),
            $tester_tags);
    $fld->mValues = array_merge(
            array(PL_TESTER_DESIGN_EDIT_NEXT_BUTTON_TAG, PL_TESTER_DESIGN_EDIT_FINISH_BUTTON_TAG, PL_TESTER_DESIGN_EDIT_TEST_TITLE_TAG, PL_TESTER_DESIGN_EDIT_TEST_DESCR_TAG,
                        PL_TESTER_DESIGN_EDIT_TEST_TIME_TAG, PL_TESTER_DESIGN_EDIT_ANSWER_TIME_TAG, PL_TESTER_DESIGN_EDIT_ANSWER_REST_TIME_TAG,
                        PL_TESTER_DESIGN_EDIT_RETEST_TIME_TAG),
            $tester_tags_values);
    $layout->addField(PL_TESTER_DESIGN_EDIT_TOP, $fld);

    //Отдельное поле для шаблона текста вопроса
    $quest_tags = array();
	$quest_tags_values = array();
	$layout->getPluginFieldsTags('pl_tester', $quest_tags, $quest_tags_values);

    $fld = new sbLayoutTextarea($stt_quest, 'stt_quest', '', 'style="width:100%;height:70px;"');
    $fld->mTags = array_merge(
                    array('{TEST_QUESTION}', '{TEST_QUEST_NUMBER}', '{TEST_QUEST_ALL_NUMBER}', '{CHANGE_DATE}'),
                    $quest_tags
    );

    $fld->mValues = array_merge(
                    array(PL_TESTER_DESIGN_EDIT_QUESTION_TAG, PL_TESTER_DESIGN_EDIT_QUEST_NUMBER_TAG, PL_TESTER_DESIGN_EDIT_QUEST_ALL_NUMBER_TAG, PL_TESTER_DESIGN_EDIT_QUEST_CHANGE_DATE_TAG),
                    $quest_tags_values
    );

    $layout->addField(PL_TESTER_DESIGN_EDIT_QUEST, $fld);

    $fld = new sbLayoutTextarea($stt_nechet_answer, 'stt_nechet_answer', '', 'style="width:100%;height:90px;"');
    $fld->mTags = array('{INPUT_ELEMENT}', '{ANSWER}', '{MARK}');
    $fld->mValues = array(PL_TESTER_DESIGN_EDIT_INPUT_ELEMENT_TAG, PL_TESTER_DESIGN_EDIT_ANSWER_TAG, PL_TESTER_DESIGN_EDIT_MARK_TAG);
    $layout->addField(PL_TESTER_DESIGN_EDIT_NECHET_ANSWER, $fld);

    $fld = new sbLayoutTextarea($stt_chet_answer, 'stt_chet_answer', '', 'style="width:100%;height:90px;"');
    $fld->mTags = array('{INPUT_ELEMENT}', '{ANSWER}', '{MARK}');
    $fld->mValues = array(PL_TESTER_DESIGN_EDIT_INPUT_ELEMENT_TAG, PL_TESTER_DESIGN_EDIT_ANSWER_TAG, PL_TESTER_DESIGN_EDIT_MARK_TAG);
    $layout->addField(PL_TESTER_DESIGN_EDIT_CHET_ANSWER, $fld);

    $layout->addField(PL_TESTER_DESIGN_EDIT_ALWAYS_CHET, new sbLayoutInput('checkbox', '1', 'stt_always_chet', '', ($stt_always_chet == 1 ?'checked="checked"':'')));

    $fld = new sbLayoutTextarea($stt_empty_element, 'stt_empty_element', '', 'style="width:100%;height:70px;"');
    $fld->mShowToolbar = false;
    $layout->addField(PL_TESTER_DESIGN_EDIT_EMPTY_ELEMENT, $fld);

    $fld = new sbLayoutTextarea($stt_bottom, 'stt_bottom', '', 'style="width:100%;height:70px;"');
    $fld->mTags = array('<input type=\'submit\' name=\'sb_tester_next\' value=\''.PL_TESTER_DESIGN_EDIT_NEXT.'\'>',
           '<input type=\'submit\' name=\'sb_tester_cancel\' value=\''.PL_TESTER_DESIGN_EDIT_CANCEL.'\'>',
           '{TEST_TITLE}', '{TEST_DESCR}', '{TEST_TEST_TIME}', '{TEST_ANSWER_TIME}', '{TEST_REST_TIME}', '{TEST_RETEST_TIME}');
    $fld->mValues = array(PL_TESTER_DESIGN_EDIT_NEXT_BUTTON_TAG, PL_TESTER_DESIGN_EDIT_FINISH_BUTTON_TAG, PL_TESTER_DESIGN_EDIT_TEST_TITLE_TAG, PL_TESTER_DESIGN_EDIT_TEST_DESCR_TAG,
                        PL_TESTER_DESIGN_EDIT_TEST_TIME_TAG, PL_TESTER_DESIGN_EDIT_ANSWER_TIME_TAG, PL_TESTER_DESIGN_EDIT_ANSWER_REST_TIME_TAG,
                        PL_TESTER_DESIGN_EDIT_RETEST_TIME_TAG);
    $layout->addField(PL_TESTER_DESIGN_EDIT_BOTTOM, $fld);

    $layout->addTab(PL_TESTER_DESIGN_EDIT_TAB4);
    $layout->addHeader(PL_TESTER_DESIGN_EDIT_TAB4);

    $fld = new sbLayoutTextarea($stt_result, 'stt_result', '', 'style="width:100%;height:270px;"');
    $fld->mTags = array('{TEST_TITLE}', '{TEST_DESCR}', '{TEST_RESULT_TEXT}', '{TEST_RESULT_MARK}', '{TEST_TEST_TIME}', '{TEST_ANSWER_TIME}',
                        '{TEST_TIME}', '{TEST_RETEST_TIME}', '{TEST_CUR_DATE}', '{TEST_ID}');
    $fld->mValues = array(PL_TESTER_DESIGN_EDIT_TEST_TITLE_TAG, PL_TESTER_DESIGN_EDIT_TEST_DESCR_TAG, PL_TESTER_DESIGN_EDIT_RESULT_TAG,
            PL_TESTER_DESIGN_EDIT_RESULT_MARK_TAG, PL_TESTER_DESIGN_EDIT_TEST_TIME_TAG, PL_TESTER_DESIGN_EDIT_ANSWER_TIME_TAG,
            PL_TESTER_DESIGN_EDIT_RESULT_TIME_TAG, PL_TESTER_DESIGN_EDIT_RETEST_TIME_TAG, PL_TESTER_DESIGN_EDIT_TEST_DATE_TAG, PL_TESTER_DESIGN_EDIT_TEST_ID_TAG);
    $layout->addField(PL_TESTER_DESIGN_EDIT_TAB4, $fld);

    $layout->addButton('submit', KERNEL_SAVE, 'btn_save', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_tester', 'design_edit') ? '' : 'disabled="disabled"'));
    if ($_GET['id'] != '')
        $layout->addButton('submit', KERNEL_APPLY, 'btn_apply', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_tester', 'design_edit') ? '' : 'disabled="disabled"'));
    $layout->addButton('button', KERNEL_CANCEL, '', '', $htmlStr != '' ? 'onclick="cancel()"' : 'onclick="sbCloseDialog()"');

    $layout->show();
}

function fTester_Design_Update()
{
	// проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_tester_design'))
		return;

    if (!isset($_GET['id']))
        $_GET['id'] = '';

    $stt_always_chet = 0;
    $stt_top = $stt_nechet_answer = $stt_chet_answer = $stt_empty_element = $stt_bottom = $stt_result = $stt_title = $stt_date =
    $stt_fields_temps = $stt_categs_temps = '';
    $stt_lang = SB_CMS_LANG;
    $stt_system_message = array();

    extract($_POST);

    if ($stt_always_chet == 0)
    {
       $stt_empty_element = '';
    }

    if ($stt_title == '')
    {
        sb_show_message(PL_TESTER_DESIGN_EDIT_NO_TITLE, false, 'warning');
        fTester_Design_Edit();
        return;
    }

    $rows = array();
    $rows['stt_title'] = $stt_title;
    $rows['stt_date'] = $stt_date;
    $rows['stt_lang'] = $stt_lang;
    $rows['stt_top'] = $stt_top;
    $rows['stt_quest'] = $stt_quest;
    $rows['stt_nechet_answer'] = $stt_nechet_answer;
    $rows['stt_chet_answer'] = $stt_chet_answer;
    $rows['stt_always_chet'] = $stt_always_chet;
    $rows['stt_empty_element'] = $stt_empty_element;
    $rows['stt_bottom'] = $stt_bottom;
    $rows['stt_result'] = $stt_result;
    $rows['stt_system_message'] = serialize($stt_system_message);
    $rows['stt_fields_temps'] = serialize($stt_fields_temps);
    $rows['stt_categs_temps'] = serialize($stt_categs_temps);

    if ($_GET['id'] != '')
    {
        $res = sql_param_query('SELECT stt_title FROM sb_tester_temps WHERE stt_id=?d', $_GET['id']);
        if ($res)
        {
            // редактирование
            list($old_title) = $res[0];

            sql_param_query('UPDATE sb_tester_temps SET ?a WHERE stt_id=?d', $rows, $_GET['id'], sprintf(PL_TESTER_DESIGN_EDIT_OK, $old_title));
            sbQueryCache::updateTemplate('sb_tester_temps', $_GET['id']);

            $footer_ar = fCategs_Edit_Elem('design_edit');
            if (!$footer_ar)
            {
                sb_show_message(PL_TESTER_DESIGN_EDIT_ERROR, false, 'warning');
                sb_add_system_message(sprintf(PL_TESTER_DESIGN_EDIT_SYSTEMLOG_ERROR, $old_title), SB_MSG_WARNING);

                fTester_Design_Edit();
                return;
            }
            $footer_str = $GLOBALS['sbSql']->escape($footer_ar[0], false, false);

            $rows['stt_id'] = intval($_GET['id']);

            $html_str = fTester_Design_Get($rows);
            $html_str = sb_str_replace(array("\r\n", "\r", "\n"), '', $html_str);
            $html_str = $GLOBALS['sbSql']->escape($html_str, false, false);

            if (!isset($_POST['btn_apply']))
            {
                echo '<script>
                        var res = new Object();
                        res.html = "'.$html_str.'";
                        res.footer = "'.$footer_str.'";
                        res.footer_link = "";
                        sbReturnValue(res);
                      </script>';
            }
            else
            {
                fTester_Design_Edit($html_str, $footer_str);
            }
        }
        else
        {
            sb_show_message(PL_TESTER_DESIGN_EDIT_ERROR, false, 'warning');
            sb_add_system_message(sprintf(PL_TESTER_DESIGN_EDIT_SYSTEMLOG_ERROR, $stt_title), SB_MSG_WARNING);

            fTester_Design_Edit();
            return;
        }
    }
    else
    {
        $error = true;
        if (sql_param_query('INSERT INTO sb_tester_temps SET ?a', $rows))
        {
            $id = sql_insert_id();

            if (fCategs_Add_Elem($id, 'design_edit'))
            {
                sbQueryCache::updateTemplate('sb_tester_temps', $id);
                sb_add_system_message(sprintf(PL_TESTER_DESIGN_ADD_OK, $stt_title));
                echo '<script>
                           sbReturnValue('.$id.');
                      </script>';

                $error = false;
            }
            else
            {
                sql_query('DELETE FROM sb_tester_temps WHERE stt_id="'.$id.'"');
            }
        }

        if ($error)
        {
            sb_show_message(sprintf(PL_TESTER_DESIGN_ADD_ERROR, $stt_title), false, 'warning');
            sb_add_system_message(sprintf(PL_TESTER_DESIGN_ADD_SYSTEMLOG_ERROR, $stt_title), SB_MSG_WARNING);

            fTester_Design_Edit();
            return;
        }
    }
}

function fTester_Design_Delete()
{
    $id = intval($_GET['id']);
    $pages = sql_query('SELECT pages.p_id FROM sb_pages pages, sb_elems elems
                        WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_tester_test" AND pages.p_id=elems.e_p_id LIMIT 1');
    $temps = false;
    if (!$pages)
    {
        $temps = sql_query('SELECT temps.t_id FROM sb_templates temps, sb_elems elems
                        WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_tester_test" AND temps.t_id=elems.e_p_id LIMIT 1');
    }

    if ($pages || $temps)
    {
        echo PL_TESTER_DESIGN_DELETE_ERROR;
    }
}


/**
 * Функции управления дизайном вывода результатов тестов
 */
function fTester_Result_Design_Get ($args)
{
    $result = '<b><a href="javascript:void(0);" onclick="sbElemsEdit(event);">'.$args['strt_title'].'</a></b>
    <div class="smalltext" style="margin-top: 7px;">';

    static $view_info_pages = null;
    static $view_info_temps = null;

    if (is_null($view_info_pages))
    {
        $view_info_pages = $_SESSION['sbPlugins']->isRightAvailable('pl_pages', 'read');
    }

    if (is_null($view_info_temps))
    {
        $view_info_temps = $_SESSION['sbPlugins']->isRightAvailable('pl_templates', 'read');
    }

    $id = intval($args['strt_id']);
    $pages = sql_query('SELECT pages.p_id, pages.p_name FROM sb_pages pages, sb_elems elems
                        WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_tester_results" AND pages.p_id=elems.e_p_id GROUP BY pages.p_id ORDER BY pages.p_name LIMIT 4');

    $temps = sql_query('SELECT temps.t_id, temps.t_name FROM sb_templates temps, sb_elems elems
                        WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_tester_results" AND temps.t_id=elems.e_p_id GROUP BY temps.t_id ORDER BY temps.t_name LIMIT 4');

    if ($pages)
    {
        $result .= '<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td valign="top" nowrap="nowrap" class="smalltext">'.KERNEL_USED_ON_PAGES.':&nbsp;</td>
                        <td class="smalltext"><span style="color:green;">';

        $num = min(3, count($pages));
        for ($i = 0; $i < $num; $i++)
        {
            list($p_id, $p_name) = $pages[$i];
            $result .= ($view_info_pages ? '<a href="'.SB_CMS_CONTENT_FILE.'?event=pl_pages_init&sb_sel_id='.$p_id.'">'.$p_name.'</a>' : $p_name).', ';
        }

        if ($num < count($pages))
            $result .= '&nbsp;<a href="javascript:void(0);" onclick="linkPages(event);">...</a>';
        else
            $result = substr($result, 0, -2);

        $result .= '</span></td></tr></table>';
    }
    else
    {
        $result .= KERNEL_USED_ON_PAGES.': <span style="color: red;">'.KERNEL_NOT_USED.'</span><br />';
    }

    if ($temps)
    {
        $result .= '<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td valign="top" nowrap="nowrap" class="smalltext">'.KERNEL_USED_ON_TEMPS.':&nbsp;</td>
                        <td class="smalltext"><span style="color:green;">';

        $num = min(3, count($temps));
        for ($i = 0; $i < $num; $i++)
        {
            list($t_id, $t_name) = $temps[$i];
            $result .= ($view_info_temps ? '<a href="'.SB_CMS_CONTENT_FILE.'?event=pl_templates_init&sb_sel_id='.$t_id.'">'.$t_name.'</a>' : $t_name).', ';
        }

        if ($num < count($temps))
            $result .= '&nbsp;<a href="javascript:void(0);" onclick="linkTemps(event);">...</a>';
        else
            $result = substr($result, 0, -2);

        $result .= '</span></td></tr></table>';
    }
    else
    {
        $result .= KERNEL_USED_ON_TEMPS.': <span style="color: red;">'.KERNEL_NOT_USED.'</span><br />';
    }

    $result .= '</div>';
    return $result;
}

function fTester_Result_Design ()
{
	require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');
    $elems = new sbElements('sb_tester_result_temps', 'strt_id', 'strt_title', 'fTester_Result_Design_Get', 'pl_tester_result_design', 'pl_tester_result_design');
    $elems->mCategsDeleteWithElementsMenu = true;

    $elems->mCategsRootName = KERNEL_DESIGN_ROOT_NAME;
    $elems->mElemsIcon = SB_CMS_IMG_URL.'/plugins/pl_tester_result_32.png';

    $elems->addSorting(PL_TESTER_DESIGN_SORT_BY_TITLE, 'strt_title');

    $elems->mElemsCutMenuTitle = KERNEL_DESIGN_ELEMS_CUT_MENU_TITLE;
    $elems->mElemsCopyMenuTitle = KERNEL_DESIGN_ELEMS_COPY_MENU_TITLE;
    $elems->mElemsEditMenuTitle = KERNEL_DESIGN_ELEMS_EDIT_MENU_TITLE;
    $elems->mElemsAddMenuTitle = KERNEL_DESIGN_ELEMS_ADD_MENU_TITLE;
    $elems->mElemsDeleteMenuTitle = KERNEL_DESIGN_ELEMS_DELETE_MENU_TITLE;
    $elems->mCategsPasteElemsMenuTitle = KERNEL_DESIGN_PASTE_ELEMS_MENU_TITLE;

    $elems->mElemsEditEvent =  'pl_tester_result_design_edit';
    $elems->mElemsEditDlgWidth = 900;
    $elems->mElemsEditDlgHeight = 600;

    $elems->mElemsAddEvent =  'pl_tester_result_design_edit';
    $elems->mElemsAddDlgWidth = 900;
    $elems->mElemsAddDlgHeight = 600;

    $elems->mElemsDeleteEvent = 'pl_tester_result_design_delete';

    $elems->mCategsClosed = false;
    $elems->mCategsRubrikator = false;
    $elems->mElemsUseLinks = false;

    $elems->mElemsJavascriptStr .= '
          function linkPages(e)
          {
              if (!sbSelEl)
              {
                  var el = sbEventTarget(e);

                  while (el.parentNode && !el.getAttribute("true_id"))
                      el = el.parentNode;

                  var el_id = el.getAttribute("el_id");
              }
              else
                  var el_id = sbSelEl.getAttribute("el_id");

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_get_links&id=" + el_id + "&ident[]=pl_tester_result";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
          }
          function linkTemps(e)
          {
              if (!sbSelEl)
              {
                  var el = sbEventTarget(e);

                  while (el.parentNode && !el.getAttribute("true_id"))
                      el = el.parentNode;

                  var el_id = el.getAttribute("el_id");
              }
              else
                  var el_id = sbSelEl.getAttribute("el_id");

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_templates_get_links&id=" + el_id + "&ident[]=pl_tester_result";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
          }
          function editElems()
          {
              window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_tester_questions";
          }';

    $elems->addElemsMenuItem(KERNEL_LINK_PAGES_MENU_ITEM, 'linkPages();');
    $elems->addElemsMenuItem(KERNEL_LINK_TEMPS_MENU_ITEM, 'linkTemps();');
    $elems->addElemsMenuItem(PL_TESTER_DESIGN_TESTS_MENU, 'editElems();', false);
    $elems->init();
}

function fTester_Result_Design_Edit ($htmlStr = '', $footerStr = '')
{
	// проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_tester_result_design'))
		return;

	if (count($_POST) == 0 && isset($_GET['id']) && $_GET['id'] != '')
    {
        $result = sql_param_query('SELECT strt_title, strt_lang, strt_date, strt_top, strt_result, strt_bottom, strt_empty, strt_delim,
            strt_count, strt_perpage, strt_pagelist_id, strt_system_message
            FROM sb_tester_result_temps
            WHERE strt_id= ?d', $_GET['id']);

        if ($result)
        {
            list($strt_title, $strt_lang, $strt_date, $strt_top, $strt_result, $strt_bottom,
            $strt_empty, $strt_delim, $strt_count, $strt_perpage, $strt_pagelist_id, $strt_system_message) = $result[0];
        }
        else
        {
            sb_show_message(PL_TESTER_DESIGN_EDIT_ERROR, true, 'warning');
            return;
        }

        if ($strt_system_message != '')
            $strt_system_message = unserialize($strt_system_message);
        else
            $strt_system_message = array();
    }
    elseif (count($_POST) > 0)
    {
        extract($_POST);
        if (!isset($_GET['id']))
            $_GET['id'] = '';
    }
    else
    {
        $strt_title = '';
        $strt_date = '{DAY}.{MONTH}.{LONG_YEAR} {HOUR}:{MINUTE}';
        $strt_lang = SB_CMS_LANG;
        $strt_pagelist_id = -1;
        $strt_perpage = 10;
        $strt_count = 1;
        $strt_system_message = array();

        $strt_system_message['certificat_vidan'] = PL_TESTER_DESIGN_RESULT_VIDAN;
        $strt_system_message['certificat_ne_vidan'] = PL_TESTER_DESIGN_RESULT_NE_VIDAN;
        $strt_system_message['test_sdan'] = PL_TESTER_DESIGN_RESULT_SDAN;
        $strt_system_message['test_ne_sdan'] = PL_TESTER_DESIGN_RESULT_NE_SDAN;
        $strt_system_message['resultatov_net'] = PL_TESTER_DESIGN_RESULT_RES_NET;
        $strt_system_message['neobxod_avtoriz'] = PL_TESTER_DESIGN_RESULT_AUTORIZ;
        $strt_system_message['retest'] = PL_TESTER_DESIGN_RESULT_RETEST;
        $strt_system_message['no_retest'] = PL_TESTER_DESIGN_RESULT_NO_RETEST;

        $strt_top = '';
        $strt_result = '';
        $strt_delim = '';
        $strt_empty = '';
        $strt_bottom = '';

        $_GET['id'] = '';
    }

    echo '<script>
            function checkValues()
            {
                var el_title = sbGetE("strt_title");
                if (el_title.value == "")
                {
                     alert("'.PL_TESTER_DESIGN_NO_TITLE_MSG.'");
                     return false;
                }
            }';

    if ($htmlStr != '')
    {
        echo '
            function cancel()
            {
                var res = new Object();
                res.html = "'.$htmlStr.'";
                res.footer = "'.$footerStr.'";
                res.footer_link = "";
                sbReturnValue(res);
            }
            sbAddEvent(window, "close", cancel);';
    }
    echo '</script>';

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_tester_result_design_update'.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), 'thisDialog', 'post', 'checkValues()');
    $layout->mTableWidth = '95%';
    $layout->mTitleWidth = '200';

    $layout->addTab(PL_TESTER_DESIGN_EDIT_TAB1);
    $layout->addHeader(PL_TESTER_DESIGN_EDIT_TAB1);

    $layout->addField(PL_TESTER_DESIGN_EDIT_TITLE, new sbLayoutInput('text', $strt_title, 'strt_title', '', 'style="width:450px;"', true));
    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea($strt_date, 'strt_date', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array('{DAY}', '{WEEKDAY}', '{WEEKDAY_SHORT}', '{MONTH}', '{MONTH_TEXTUAL}', '{LONG_YEAR}', '{SHORT_YEAR}', '{HOUR}', '{MINUTE}');
    $fld->mValues = array(KERNEL_DAY_TAG, KERNEL_WEEKDAY_TAG_FULL, KERNEL_WEEKDAY_TAG_SHORT, KERNEL_MONTH_TAG, KERNEL_MONTH_NAME_TAG, KERNEL_YEAR_4_TAG, KERNEL_YEAR_2_TAG, KERNEL_HOURS_TAG, KERNEL_MINUTES_TAG);
    $layout->addField(PL_TESTER_DESIGN_EDIT_DATE, $fld);

    $fld = new sbLayoutSelect($GLOBALS['sb_site_langs'], 'strt_lang');
    $fld->mSelOptions = array($strt_lang);
    $fld->mHTML = '<div class="hint_div" style="margin-top: 5px;">'.PL_TESTER_DESIGN_EDIT_LANG_LABEL.'</div>';
    $layout->addField(PL_TESTER_DESIGN_EDIT_LANG, $fld);

    $res = sql_query('SELECT categs.cat_title, pager.pt_id, pager.pt_title FROM sb_categs categs, sb_catlinks links, sb_pager_temps pager WHERE pager.pt_id=links.link_el_id AND categs.cat_id=links.link_cat_id AND categs.cat_ident=? ORDER BY categs.cat_left, pager.pt_title', 'pl_pager_temps');
    if ($res)
    {
        $options = array();
        $old_cat_title = '';
        foreach ($res as $value)
        {
            list($cat_title, $pt_id, $pt_title) = $value;
            if ($old_cat_title != $cat_title)
            {
                $options[uniqid()] = '-'.$cat_title;
                $old_cat_title = $cat_title;
            }
            $options[$pt_id] = $pt_title;
        }
        $fld = new sbLayoutSelect($options, 'strt_pagelist_id');
        $fld->mSelOptions = array($strt_pagelist_id);
        $layout->addField(PL_TESTER_DESIGN_EDIT_PAGER_TEMPS, $fld);
    }
    else
    {
        $layout->addField(PL_TESTER_DESIGN_EDIT_PAGER_TEMPS, new sbLayoutLabel('<div class="hint_div">'.PL_TESTER_DESIGN_EDIT_PAGER_TEMPS_LABEL.'</div>', '', '', false));
    }

    $fld = new sbLayoutInput('text', $strt_perpage, 'strt_perpage', 'spin_strt_perpage', 'style="width:100px;"');
    $fld->mMinValue = 0;
    $layout->addField(PL_TESTER_DESIGN_EDIT_PERPAGE, $fld);
    $layout->addField('', new sbLayoutLabel('<div class="hint_div" style="margin-top: 5px;">'.PL_TESTER_DESIGN_EDIT_PERPAGE_LABEL.'</div>', '', '', false));

    $layout->addField('', new sbLayoutDelim());

    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_TESTER_DESIGN_EDIT_SYSTEM_MESSAGE.'</div>';
    $layout->addField('', new sbLayoutHTML($html, true));

    $fld = new sbLayoutTextarea($strt_system_message['certificat_vidan'], 'strt_system_message[certificat_vidan]', '', 'style="width:100%;height:50px;"');
    $fld->mShowToolbar = false;
    $layout->addField(PL_TESTER_DESIGN_EDIT_SERTIFICAT_VIDAN, $fld );

    $fld = new sbLayoutTextarea($strt_system_message['certificat_ne_vidan'], 'strt_system_message[certificat_ne_vidan]', '', 'style="width:100%;height:50px;"');
    $fld->mShowToolbar = false;
    $layout->addField(PL_TESTER_DESIGN_EDIT_SERTIFICAT_NE_VIDAN, $fld );

    $fld = new sbLayoutTextarea($strt_system_message['test_sdan'], 'strt_system_message[test_sdan]', '', 'style="width:100%;height:50px;"');
    $fld->mShowToolbar = false;
    $layout->addField(PL_TESTER_DESIGN_EDIT_TEST_SDAN, $fld );

    $fld = new sbLayoutTextarea($strt_system_message['test_ne_sdan'], 'strt_system_message[test_ne_sdan]', '', 'style="width:100%;height:50px;"');
    $fld->mShowToolbar = false;
    $layout->addField(PL_TESTER_DESIGN_EDIT_TEST_NE_SDAN, $fld );

    $fld = new sbLayoutTextarea($strt_system_message['resultatov_net'], 'strt_system_message[resultatov_net]', '', 'style="width:100%;height:50px;"');
    $fld->mShowToolbar = false;
    $layout->addField(PL_TESTER_DESIGN_EDIT_RESULTATOV_NET, $fld );

    $fld = new sbLayoutTextarea($strt_system_message['neobxod_avtoriz'], 'strt_system_message[neobxod_avtoriz]', '', 'style="width:100%;height:50px;"');
    $fld->mShowToolbar = false;
    $layout->addField(PL_TESTER_DESIGN_EDIT_NEOB_AVTORIZ_NET, $fld );

    $fld = new sbLayoutTextarea($strt_system_message['retest'], 'strt_system_message[retest]', '', 'style="width:100%;height:50px;"');
    $fld->mShowToolbar = false;
    $layout->addField(PL_TESTER_DESIGN_EDIT_RETEST, $fld );

    $fld = new sbLayoutTextarea($strt_system_message['no_retest'], 'strt_system_message[no_retest]', '', 'style="width:100%;height:50px;"');
    $fld->mTags = array('{TIME_TO_RETEST}');
    $fld->mValues = array(PL_TESTER_DESIGN_TIME_TO_RETEST);
    $layout->addField(PL_TESTER_DESIGN_EDIT_NO_RETEST, $fld );

    $layout->addTab(PL_TESTER_DESIGN_EDIT_CAT_TAB3);
    $layout->addHeader(PL_TESTER_DESIGN_EDIT_CAT_TAB3);

    $layout->addField(PL_TESTER_DESIGN_EDIT_COUNT, new sbLayoutInput('text', $strt_count, 'strt_count', 'spin_strt_count', 'style="width:80px;"'));

    $fld = new sbLayoutTextarea($strt_top, 'strt_top', '', 'style="width:100%;height:100px;"');
    $fld->mTags = array('{NUM_LIST}', '{ALL_COUNT}');
    $fld->mValues = array(PL_TESTER_DESIGN_EDIT_PAGELIST_TAG, PL_TESTER_DESIGN_EDIT_ALLNUM_TAG);
    $layout->addField(PL_TESTER_DESIGN_EDIT_TOP_GENERAL, $fld);

    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea($strt_result, 'strt_result', '', 'style="width:100%;height:180px;"');
    $fld->mTags = array('-', '{TEST_TITLE}','{TEST_DESC}', '{TEST_DATE}', '{TEST_RESULT_TEXT}', '{TEST_MARK}', '{TEST_TIME}', '{TEST_CERTIF}', '{TEST_PASSED}', '{RETEST_TIME}', '{USER_IP}');
    $fld->mValues = array(PL_TESTER_DESIGN_EDIT_TAB1, PL_TESTER_DESIGN_EDIT_RESULT_TEST_TITLE_TAG, PL_TESTER_DESIGN_EDIT_RESULT_DESCR_TAG, PL_TESTER_DESIGN_EDIT_RESULT_DATE_TAG, PL_TESTER_DESIGN_EDIT_RESULT_TAG, PL_TESTER_DESIGN_EDIT_RESULT_MARK_TAG, PL_TESTER_DESIGN_EDIT_RESULT_TIME_TAG, PL_TESTER_DESIGN_EDIT_RESULT_CERTIF_TAG, PL_TESTER_DESIGN_EDIT_RESULT_TEST_OK_TAG, PL_TESTER_DESIGN_EDIT_RESULT_RETEST_TAG, PL_TESTER_DESIGN_EDIT_RESULT_IP_TAG);
    $layout->addField(PL_TESTER_CAT_EDIT_RESULT, $fld);

    $fld = new sbLayoutTextarea($strt_empty, 'strt_empty', '', 'style="width:100%;height:100px;"');
    $layout->addField(PL_TESTER_DESIGN_EDIT_EMPTY, $fld);

    $fld = new sbLayoutTextarea($strt_delim, 'strt_delim', '', 'style="width:100%;height:100px;"');
    $layout->addField(PL_TESTER_DESIGN_EDIT_DELIM, $fld);

    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea($strt_bottom, 'strt_bottom', '', 'style="width:100%;height:100px;"');
    $fld->mTags = array('{NUM_LIST}', '{ALL_COUNT}');
    $fld->mValues = array(PL_TESTER_DESIGN_EDIT_PAGELIST_TAG, PL_TESTER_DESIGN_EDIT_ALLNUM_TAG);
    $layout->addField(PL_TESTER_DESIGN_EDIT_BOTTOM_GENERAL, $fld);

    $layout->addButton('submit', KERNEL_SAVE, 'btn_save', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_tester', 'design_edit') ? '' : 'disabled="disabled"'));
    if ($_GET['id'] != '')
        $layout->addButton('submit', KERNEL_APPLY, 'btn_apply', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_tester', 'design_edit') ? '' : 'disabled="disabled"'));
    $layout->addButton('button', KERNEL_CANCEL, '', '', $htmlStr != '' ? 'onclick="cancel()"' : 'onclick="sbCloseDialog()"');

    $layout->show();
}

function fTester_Result_Design_Update ()
{
	// проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_tester_result_design'))
		return;

    if (!isset($_GET['id']))
        $_GET['id'] = '';

    $strt_pagelist_id = 0;
    $strt_lang = SB_CMS_LANG;
    $strt_perpage = 10;
    $strt_date = $strt_title = $strt_top = $strt_result = $strt_empty = $strt_delim = $strt_bottom = '';
    $strt_system_message = array();
    $strt_count = 0;

    extract($_POST);

    if ($strt_title == '')
    {
        sb_show_message(PL_TESTER_DESIGN_NO_TITLE_MSG, false, 'warning');
        fTester_Result_Design_Edit();
        return;
    }

    $row = array();
    $row['strt_title'] = $strt_title;
    $row['strt_lang'] = $strt_lang;
    $row['strt_date'] = $strt_date;
    $row['strt_count'] = $strt_count;
    $row['strt_top'] = $strt_top;
    $row['strt_result'] = $strt_result;
    $row['strt_empty'] = $strt_empty;
    $row['strt_delim'] = $strt_delim;
    $row['strt_bottom'] = $strt_bottom;
    $row['strt_pagelist_id'] = $strt_pagelist_id;
    $row['strt_perpage'] = $strt_perpage;
    $row['strt_system_message'] = serialize($strt_system_message);

    if ($_GET['id'] != '')
    {
        $res = sql_param_query('SELECT strt_title FROM sb_tester_result_temps WHERE strt_id=?d', $_GET['id']);
        if ($res)
        {
            // редактирование
            list($old_title) = $res[0];

            sql_param_query('UPDATE sb_tester_result_temps SET ?a WHERE strt_id=?d', $row, $_GET['id'], sprintf(PL_TESTER_DESIGN_EDIT_RESULT_OK, $old_title));
            sbQueryCache::updateTemplate('sb_tester_result_temps', $_GET['id']);

            $footer_ar = fCategs_Edit_Elem('design_edit');
            if (!$footer_ar)
            {
                sb_show_message(PL_TESTER_DESIGN_EDIT_ERROR, false, 'warning');
                sb_add_system_message(sprintf(PL_TESTER_DESIGN_EDIT_RESULT_SYSTEMLOG_ERROR, $old_title), SB_MSG_WARNING);

                fTester_Result_Design_Edit();
                return;
            }
            $footer_str = $GLOBALS['sbSql']->escape($footer_ar[0], false, false);

            $row['strt_id'] = intval($_GET['id']);

            $html_str = fTester_Result_Design_Get($row);
            $html_str = sb_str_replace(array("\r\n", "\r", "\n"), '', $html_str);
            $html_str = $GLOBALS['sbSql']->escape($html_str, false, false);

            if (!isset($_POST['btn_apply']))
            {
                echo '<script>
                        var res = new Object();
                        res.html = "'.$html_str.'";
                        res.footer = "'.$footer_str.'";
                        res.footer_link = "";
                        sbReturnValue(res);
                      </script>';
            }
            else
            {
                fTester_Result_Design_Edit($html_str, $footer_str);
            }
        }
        else
        {
            sb_show_message(PL_TESTER_DESIGN_EDIT_ERROR, false, 'warning');
            sb_add_system_message(sprintf(PL_TESTER_DESIGN_EDIT_RESULT_SYSTEMLOG_ERROR, $strt_title), SB_MSG_WARNING);

            fTester_Result_Design_Edit();
            return;
        }
    }
    else
    {
        $error = true;
        if (sql_param_query('INSERT INTO sb_tester_result_temps SET ?a', $row))
        {
            $id = sql_insert_id();

            if (fCategs_Add_Elem($id, 'design_edit'))
            {
                sbQueryCache::updateTemplate('sb_tester_result_temps', $id);
                sb_add_system_message(sprintf(PL_TESTER_DESIGN_EDIT_RESULT_ADD_OK, $strt_title));

                echo '<script>
                        sbReturnValue('.$id.');
                      </script>';

                $error = false;
            }
            else
            {
                sql_query('DELETE FROM sb_tester_result_temps WHERE strt_id="'.$id.'"');
            }
        }

        if ($error)
        {
            sb_show_message(sprintf(PL_TESTER_DESIGN_EDIT_RESULT_ADD_ERROR, $strt_title), false, 'warning');
            sb_add_system_message(sprintf(PL_TESTER_DESIGN_EDIT_RESULT_ADD_SYSTEMLOG_ERROR, $strt_title), SB_MSG_WARNING);

            fTester_Result_Design_Edit();
            return;
        }
    }
}

function fTester_Result_Design_Delete ()
{
    $id = intval($_GET['id']);
    $pages = sql_query('SELECT pages.p_id FROM sb_pages pages, sb_elems elems
                        WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_tester_results" AND pages.p_id=elems.e_p_id LIMIT 1');
    $temps = false;
    if (!$pages)
    {
        $temps = sql_query('SELECT temps.t_id FROM sb_templates temps, sb_elems elems
                        WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_tester_results" AND temps.t_id=elems.e_p_id LIMIT 1');
    }

    if ($pages || $temps)
    {
        echo PL_TESTER_DESIGN_DELETE_ERROR;
    }
}


/**
 * Функции управления дизайном вывода рейтинга по результатамо тестов
 */
function fTester_Rating_Design_Get ($args)
{
	$result = '<b><a href="javascript:void(0);" onclick="sbElemsEdit(event);">'.$args['strt_title'].'</a></b>
    <div class="smalltext" style="margin-top: 7px;">';

    static $view_info_pages = null;
    static $view_info_temps = null;

    if (is_null($view_info_pages))
    {
        $view_info_pages = $_SESSION['sbPlugins']->isRightAvailable('pl_pages', 'read');
    }

    if (is_null($view_info_temps))
    {
        $view_info_temps = $_SESSION['sbPlugins']->isRightAvailable('pl_templates', 'read');
    }

    $id = intval($args['strt_id']);
    $pages = sql_query('SELECT pages.p_id, pages.p_name FROM sb_pages pages, sb_elems elems
                        WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_tester_rating" AND pages.p_id=elems.e_p_id GROUP BY pages.p_id ORDER BY pages.p_name LIMIT 4');

    $temps = sql_query('SELECT temps.t_id, temps.t_name FROM sb_templates temps, sb_elems elems
                        WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_tester_rating" AND temps.t_id=elems.e_p_id GROUP BY temps.t_id ORDER BY temps.t_name LIMIT 4');

    if ($pages)
    {
        $result .= '<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td valign="top" nowrap="nowrap" class="smalltext">'.KERNEL_USED_ON_PAGES.':&nbsp;</td>
                        <td class="smalltext"><span style="color:green;">';

        $num = min(3, count($pages));
        for ($i = 0; $i < $num; $i++)
        {
            list($p_id, $p_name) = $pages[$i];
            $result .= ($view_info_pages ? '<a href="'.SB_CMS_CONTENT_FILE.'?event=pl_pages_init&sb_sel_id='.$p_id.'">'.$p_name.'</a>' : $p_name).', ';
        }

        if ($num < count($pages))
            $result .= '&nbsp;<a href="javascript:void(0);" onclick="linkPages(event);">...</a>';
        else
            $result = substr($result, 0, -2);

        $result .= '</span></td></tr></table>';
    }
    else
    {
        $result .= KERNEL_USED_ON_PAGES.': <span style="color: red;">'.KERNEL_NOT_USED.'</span><br />';
    }

    if ($temps)
    {
        $result .= '<table cellpadding="0" cellspacing="0">
                    <tr>
                        <td valign="top" nowrap="nowrap" class="smalltext">'.KERNEL_USED_ON_TEMPS.':&nbsp;</td>
                        <td class="smalltext"><span style="color:green;">';

        $num = min(3, count($temps));
        for ($i = 0; $i < $num; $i++)
        {
            list($t_id, $t_name) = $temps[$i];
            $result .= ($view_info_temps ? '<a href="'.SB_CMS_CONTENT_FILE.'?event=pl_templates_init&sb_sel_id='.$t_id.'">'.$t_name.'</a>' : $t_name).', ';
        }

        if ($num < count($temps))
            $result .= '&nbsp;<a href="javascript:void(0);" onclick="linkTemps(event);">...</a>';
        else
            $result = substr($result, 0, -2);

        $result .= '</span></td></tr></table>';
    }
    else
    {
        $result .= KERNEL_USED_ON_TEMPS.': <span style="color: red;">'.KERNEL_NOT_USED.'</span><br />';
    }

    $result .= '</div>';
    return $result;

}

function fTester_Rating_Design ()
{
	require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');
    $elems = new sbElements('sb_tester_rating_temps', 'strt_id', 'strt_title', 'fTester_Rating_Design_Get', 'pl_tester_rating_design', 'pl_tester_rating_design');
    $elems->mCategsDeleteWithElementsMenu = true;

    $elems->mCategsRootName = KERNEL_DESIGN_ROOT_NAME;
    $elems->mElemsIcon = SB_CMS_IMG_URL.'/plugins/pl_tester_rating_32.png';
    $elems->addSorting(PL_TESTER_DESIGN_SORT_BY_TITLE, 'strt_title');

    $elems->mElemsCutMenuTitle = KERNEL_DESIGN_ELEMS_CUT_MENU_TITLE;
    $elems->mElemsCopyMenuTitle = KERNEL_DESIGN_ELEMS_COPY_MENU_TITLE;
    $elems->mElemsEditMenuTitle = KERNEL_DESIGN_ELEMS_EDIT_MENU_TITLE;
    $elems->mElemsAddMenuTitle = KERNEL_DESIGN_ELEMS_ADD_MENU_TITLE;
    $elems->mElemsDeleteMenuTitle = KERNEL_DESIGN_ELEMS_DELETE_MENU_TITLE;
    $elems->mCategsPasteElemsMenuTitle = KERNEL_DESIGN_PASTE_ELEMS_MENU_TITLE;

    $elems->mElemsEditEvent =  'pl_tester_rating_design_edit';
    $elems->mElemsEditDlgWidth = 950;
    $elems->mElemsEditDlgHeight = 600;

    $elems->mElemsAddEvent =  'pl_tester_rating_design_edit';
    $elems->mElemsAddDlgWidth = 950;
    $elems->mElemsAddDlgHeight = 600;

    $elems->mElemsDeleteEvent = 'pl_tester_rating_design_delete';

    $elems->mCategsClosed = false;
    $elems->mCategsRubrikator = false;
    $elems->mElemsUseLinks = false;

    $elems->mElemsJavascriptStr .= '
          function linkPages(e)
          {
              if (!sbSelEl)
              {
                  var el = sbEventTarget(e);

                  while (el.parentNode && !el.getAttribute("true_id"))
                      el = el.parentNode;

                  var el_id = el.getAttribute("el_id");
              }
              else
                  var el_id = sbSelEl.getAttribute("el_id");

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_get_links&id=" + el_id + "&ident[]=pl_tester_rating";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
          }
          function linkTemps(e)
          {
              if (!sbSelEl)
              {
                  var el = sbEventTarget(e);

                  while (el.parentNode && !el.getAttribute("true_id"))
                      el = el.parentNode;

                  var el_id = el.getAttribute("el_id");
              }
              else
                  var el_id = sbSelEl.getAttribute("el_id");

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_templates_get_links&id=" + el_id + "&ident[]=pl_tester_rating";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
          }
          function editElems()
          {
              window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_tester_questions";
          }';

    $elems->addElemsMenuItem(KERNEL_LINK_PAGES_MENU_ITEM, 'linkPages();');
    $elems->addElemsMenuItem(KERNEL_LINK_TEMPS_MENU_ITEM, 'linkTemps();');
    $elems->addElemsMenuItem(PL_TESTER_DESIGN_TESTS_MENU, 'editElems();', false);
    $elems->init();
}

function fTester_Rating_Design_Edit($htmlStr = '', $footerStr = '')
{
	// проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_tester_rating_design'))
		return;

    if (count($_POST) == 0 && isset($_GET['id']) && $_GET['id'] != '')
    {
        $result = sql_param_query('SELECT strt_title, strt_top, strt_bottom, strt_elem, strt_delim, strt_empty,
                strt_pagelist_id, strt_perpage, strt_count, strt_categs_temps, strt_system_message, strt_system_message
                FROM sb_tester_rating_temps
                WHERE strt_id= ?d', $_GET['id']);
        if ($result)
        {
            list($strt_title, $strt_top, $strt_bottom, $strt_elem, $strt_delim, $strt_empty, $strt_pagelist_id,
                $strt_perpage, $strt_count, $strt_categs_temps, $strt_system_message, $strt_system_message) = $result[0];
        }
        else
        {
            sb_show_message(PL_TESTER_DESIGN_EDIT_ERROR, true, 'warning');
            return;
        }

        if ($strt_system_message != '')
            $strt_system_message = unserialize($strt_system_message);
        else
            $strt_system_message = array();

        if ($strt_categs_temps != '')
            $strt_categs_temps = unserialize($strt_categs_temps);
        else
            $strt_categs_temps = array();
    }
    elseif (count($_POST) > 0)
    {
        extract($_POST);
        if (!isset($_GET['id']))
            $_GET['id'] = '';
    }
    else
    {
        $strt_title = $strt_top = $strt_bottom = $strt_elem = $strt_delim = $strt_empty = '';
        $strt_pagelist_id = -1;
        $strt_perpage = 10;
        $strt_count = 1;
        $strt_categs_temps = array();
        $strt_system_message = array();
        $strt_system_message['no_rating'] = PL_TESTER_DESIGN_EDIT_NO_RATING;
        $strt_system_message['dostup_zapr'] = PL_TESTER_DESIGN_EDIT_DOSTUP_ZAPR_LABEL;

        $_GET['id'] = '';
    }

    echo '<script>
            function checkValues()
            {
                var el_title = sbGetE("strt_title");
                if (el_title.value == "")
                {
                     alert("'.PL_TESTER_DESIGN_NO_TITLE_MSG.'");
                     return false;
                }
            }';

    if ($htmlStr != '')
    {
        echo '
            function cancel()
            {
                var res = new Object();
                res.html = "'.$htmlStr.'";
                res.footer = "'.$footerStr.'";
                res.footer_link = "";
                sbReturnValue(res);
            }
            sbAddEvent(window, "close", cancel);';
    }
    echo '</script>';

    require_once(SB_CMS_LIB_PATH.'/sbLayout.inc.php');

    $layout = new sbLayout(SB_CMS_MODAL_DIALOG_FILE.'?event=pl_tester_rating_design_update'.($_SERVER['QUERY_STRING'] != '' ? '&'.$_SERVER['QUERY_STRING'] : ''), 'thisDialog', 'post', 'checkValues()');
    $layout->mTableWidth = '95%';
    $layout->mTitleWidth = '200';

    $layout->addTab(PL_TESTER_DESIGN_EDIT_TAB1);
    $layout->addHeader(PL_TESTER_DESIGN_EDIT_TAB1);

    $layout->addField(PL_TESTER_DESIGN_EDIT_TITLE, new sbLayoutInput('text', $strt_title, 'strt_title', '', 'style="width:450px;"', true));
    $layout->addField('', new sbLayoutDelim());

    $res = sql_query('SELECT categs.cat_title, pager.pt_id, pager.pt_title FROM sb_categs categs, sb_catlinks links, sb_pager_temps pager WHERE pager.pt_id=links.link_el_id AND categs.cat_id=links.link_cat_id AND categs.cat_ident=? ORDER BY categs.cat_left, pager.pt_title', 'pl_pager_temps');
    if ($res)
    {
        $options = array();
        $old_cat_title = '';
        foreach ($res as $value)
        {
            list($cat_title, $pt_id, $pt_title) = $value;
            if ($old_cat_title != $cat_title)
            {
                $options[uniqid()] = '-'.$cat_title;
                $old_cat_title = $cat_title;
            }
            $options[$pt_id] = $pt_title;
        }
        $fld = new sbLayoutSelect($options, 'strt_pagelist_id');
        $fld->mSelOptions = array($strt_pagelist_id);
        $layout->addField(PL_TESTER_DESIGN_EDIT_PAGER_TEMPS, $fld);
    }
    else
    {
        $layout->addField(PL_TESTER_DESIGN_EDIT_PAGER_TEMPS, new sbLayoutLabel('<div class="hint_div">'.PL_TESTER_DESIGN_EDIT_PAGER_TEMPS_RATING.'</div>', '', '', false));
    }

    $fld = new sbLayoutInput('text', $strt_perpage, 'strt_perpage', 'spin_strt_perpage', 'style="width:100px;"');
    $fld->mMinValue = 0;
    $layout->addField(PL_TESTER_DESIGN_EDIT_PERPAGE_RATING, $fld);
    $layout->addField('', new sbLayoutLabel('<div class="hint_div" style="margin-top: 5px;">'.PL_TESTER_DESIGN_EDIT_PERPAGE_LABEL_RATING.'</div>', '', '', false));

    $layout->addField('', new sbLayoutDelim());

    $html = '<div align="center" class="hint_div" style="margin-top: 5px;">'.PL_TESTER_DESIGN_EDIT_SYSTEM_MESSAGE.'</div>';
    $layout->addField('', new sbLayoutHTML($html, true));

    $fld = new sbLayoutTextarea($strt_system_message['no_rating'], 'strt_system_message[no_rating]', '', 'style="width:100%;height:50px;"');
    $fld->mShowToolbar = false;
    $layout->addField(PL_TESTER_DESIGN_EDIT_NO_RATING, $fld );

    $fld = new sbLayoutTextarea($strt_system_message['dostup_zapr'], 'strt_system_message[dostup_zapr]', '', 'style="width:100%;height:50px;"');
    $fld->mShowToolbar = false;
    $layout->addField(PL_TESTER_DESIGN_EDIT_DOSTUP_ZAPR, $fld );

    $layout->addTab(PL_TESTER_DESIGN_EDIT_RATING_CAT_TAB3);
    $layout->addHeader(PL_TESTER_DESIGN_EDIT_RATING_CAT_TAB3);

    $layout->addField(PL_TESTER_DESIGN_EDIT_COUNT_RATING, new sbLayoutInput('text', $strt_count, 'strt_count', 'spin_strt_count', 'style="width:80px;"'));

    $fld = new sbLayoutTextarea($strt_top, 'strt_top', '', 'style="width:100%;height:100px;"');

    $fld->mTags = array('{NUM_LIST}', '{ALL_COUNT}', "<form action='{FORM_ACTION}' method='GET'><input type='text' size='30' name='sb_tester_fio'><br /><input type='submit' value='Поиск'></form>",
'{SORT_LOGIN_ASC}', '{SORT_LOGIN_DESC}', '{SORT_FIO_ASC}', '{SORT_FIO_DESC}', '{SORT_MARK_ASC}', '{SORT_MARK_DESC}', '{SORT_TIME_ASC}', '{SORT_TIME_DESC}',
'{SORT_COUNT_TESTED_ASC}', '{SORT_COUNT_TESTED_DESC}', '{SORT_COUNT_PASSED_ASC}', '{SORT_COUNT_PASSED_DESC}');
    $fld->mValues = array(PL_TESTER_DESIGN_EDIT_PAGELIST_TAG, PL_TESTER_DESIGN_EDIT_RATING_ALLNUM_TAG, PL_TESTER_DESIGN_EDIT_SEARCH_FIO_TAG,
    PL_TESTER_DESIGN_SORT_LOGIN_ASC_TAG, PL_TESTER_DESIGN_SORT_LOGIN_DESC_TAG, PL_TESTER_DESIGN_SORT_FIO_ASC_TAG, PL_TESTER_DESIGN_SORT_FIO_DESC_TAG,
    PL_TESTER_DESIGN_SORT_SUMM_BALL_ASC_TAG, PL_TESTER_DESIGN_SORT_SUMM_BALL_DESC_TAG, PL_TESTER_DESIGN_SORT_SUMM_TIME_ASC_TAG,
    PL_TESTER_DESIGN_SORT_SUMM_TIME_DESC_TAG, PL_TESTER_DESIGN_SORT_TESTS_ASC_TAG, PL_TESTER_DESIGN_SORT_TESTS_DESC_TAG, PL_TESTER_DESIGN_SORT_TESTS_PASS_ASC_TAG,
    PL_TESTER_DESIGN_SORT_TESTS_PASS_DESC_TAG);

    $layout->addField(PL_TESTER_DESIGN_EDIT_TOP_GENERAL, $fld);

    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea($strt_elem, 'strt_elem', '', 'style="width:100%;height:180px;"');
    $fld->mTags = array('-', '{USER_LOGIN}', '{USER_FIO}', '{RATING_PLACE}', '{RATING_MARK}', '{RATING_TIME}', '{RATING_TESTED}', '{RATING_PASSED}');
    $fld->mValues = array(PL_TESTER_DESIGN_EDIT_TAB1, PL_TESTER_DESIGN_EDIT_RATING_LOGIN_TAG, PL_TESTER_DESIGN_EDIT_RATING_FIO_TAG, PL_TESTER_DESIGN_EDIT_RATING_PLACE_TAG, PL_TESTER_DESIGN_EDIT_RATING_MARK_TAG, PL_TESTER_DESIGN_EDIT_RATING_TIME_TAG, PL_TESTER_DESIGN_EDIT_RATING_COUNT_TESTED_TAG, PL_TESTER_DESIGN_EDIT_RATING_COUNT_PASSED_TAG);
    $layout->addField(PL_TESTER_DESIGN_EDIT_RATING_ELEM, $fld);

    $layout->addField('', new sbLayoutDelim());

    $fld = new sbLayoutTextarea($strt_empty, 'strt_empty', '', 'style="width:100%;height:100px;"');
    $layout->addField(PL_TESTER_DESIGN_EDIT_EMPTY, $fld);

    $fld = new sbLayoutTextarea($strt_delim, 'strt_delim', '', 'style="width:100%;height:100px;"');
    $layout->addField(PL_TESTER_DESIGN_EDIT_DELIM, $fld);

    $fld = new sbLayoutTextarea($strt_bottom, 'strt_bottom', '', 'style="width:100%;height:100px;"');
    $fld->mTags = array('{NUM_LIST}', '{ALL_COUNT}', "<form action='{FORM_ACTION}' method='GET'><input type='text' size='30' name='sb_tester_fio'><br /><input type='submit' value='Поиск'></form>",
'{SORT_LOGIN_ASC}', '{SORT_LOGIN_DESC}', '{SORT_FIO_ASC}', '{SORT_FIO_DESC}', '{SORT_MARK_ASC}', '{SORT_MARK_DESC}', '{SORT_TIME_ASC}', '{SORT_TIME_DESC}',
'{SORT_COUNT_TESTED_ASC}', '{SORT_COUNT_TESTED_DESC}', '{SORT_COUNT_PASSED_ASC}', '{SORT_COUNT_PASSED_DESC}');
    $fld->mValues = array(PL_TESTER_DESIGN_EDIT_PAGELIST_TAG, PL_TESTER_DESIGN_EDIT_RATING_ALLNUM_TAG, PL_TESTER_DESIGN_EDIT_SEARCH_FIO_TAG,
    PL_TESTER_DESIGN_SORT_LOGIN_ASC_TAG, PL_TESTER_DESIGN_SORT_LOGIN_DESC_TAG, PL_TESTER_DESIGN_SORT_FIO_ASC_TAG, PL_TESTER_DESIGN_SORT_FIO_DESC_TAG,
    PL_TESTER_DESIGN_SORT_SUMM_BALL_ASC_TAG, PL_TESTER_DESIGN_SORT_SUMM_BALL_DESC_TAG, PL_TESTER_DESIGN_SORT_SUMM_TIME_ASC_TAG,
    PL_TESTER_DESIGN_SORT_SUMM_TIME_DESC_TAG, PL_TESTER_DESIGN_SORT_TESTS_ASC_TAG, PL_TESTER_DESIGN_SORT_TESTS_DESC_TAG, PL_TESTER_DESIGN_SORT_TESTS_PASS_ASC_TAG,
    PL_TESTER_DESIGN_SORT_TESTS_PASS_DESC_TAG);
    $layout->addField(PL_TESTER_DESIGN_EDIT_BOTTOM_GENERAL, $fld);

    $layout->addButton('submit', KERNEL_SAVE, 'btn_save', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_tester', 'design_edit') ? '' : 'disabled="disabled"'));
    if ($_GET['id'] != '')
        $layout->addButton('submit', KERNEL_APPLY, 'btn_apply', '', ($_SESSION['sbPlugins']->isRightAvailable('pl_tester', 'design_edit') ? '' : 'disabled="disabled"'));
    $layout->addButton('button', KERNEL_CANCEL, '', '', $htmlStr != '' ? 'onclick="cancel()"' : 'onclick="sbCloseDialog()"');

    $layout->show();
}

function fTester_Rating_Design_Update ()
{
	// проверка прав доступа
	if (!fCategs_Check_Elems_Rights((isset($_GET['id']) && $_GET['id'] != '' ? $_GET['id'] : 0), $_GET['cat_id'], 'pl_tester_rating_design'))
		return;

    if (!isset($_GET['id']))
        $_GET['id'] = '';

    $strt_title = $strt_top = $strt_bottom = $strt_elem = $strt_delim = $strt_empty = '';
    $strt_pagelist_id = -1;
    $strt_perpage = 10;
    $strt_count = 1;
    $strt_categs_temps = array();
    $strt_system_message = array();
    $strt_system_message['no_rating'] = PL_TESTER_DESIGN_EDIT_NO_RATING;
    $strt_system_message['dostup_zapr'] = PL_TESTER_DESIGN_EDIT_DOSTUP_ZAPR_LABEL;

    extract($_POST);

    if ($strt_title == '')
    {
        sb_show_message(PL_TESTER_DESIGN_NO_TITLE_MSG, false, 'warning');
        fTester_Rating_Design_Edit();
        return;
    }

    $row = array();
    $row['strt_title'] = $strt_title;
    $row['strt_count'] = $strt_count;
    $row['strt_top'] = $strt_top;
    $row['strt_elem'] = $strt_elem;
    $row['strt_empty'] = $strt_empty;
    $row['strt_delim'] = $strt_delim;
    $row['strt_bottom'] = $strt_bottom;
    $row['strt_pagelist_id'] = $strt_pagelist_id;
    $row['strt_perpage'] = $strt_perpage;
    $row['strt_system_message'] = serialize($strt_system_message);
    $row['strt_categs_temps'] = serialize($strt_categs_temps);

    if ($_GET['id'] != '')
    {
        $res = sql_param_query('SELECT strt_title FROM sb_tester_rating_temps WHERE strt_id=?d', $_GET['id']);
        if ($res)
        {
            // редактирование
            list($old_title) = $res[0];

            sql_param_query('UPDATE sb_tester_rating_temps SET ?a WHERE strt_id=?d', $row, $_GET['id'], sprintf(PL_TESTER_DESIGN_EDIT_RATING_OK, $old_title));
            sbQueryCache::updateTemplate('sb_tester_rating_temps', $_GET['id']);

            $footer_ar = fCategs_Edit_Elem('design_edit');
            if (!$footer_ar)
            {
                sb_show_message(PL_TESTER_DESIGN_EDIT_ERROR, false, 'warning');
                sb_add_system_message(sprintf(PL_TESTER_DESIGN_EDIT_RATING_SYSTEMLOG_ERROR, $old_title), SB_MSG_WARNING);

                fTester_Rating_Design_Edit();
                return;
            }
            $footer_str = $GLOBALS['sbSql']->escape($footer_ar[0], false, false);

            $row['strt_id'] = intval($_GET['id']);

            $html_str = fTester_Rating_Design_Get($row);
            $html_str = sb_str_replace(array("\r\n", "\r", "\n"), '', $html_str);
            $html_str = $GLOBALS['sbSql']->escape($html_str, false, false);

            if (!isset($_POST['btn_apply']))
            {
                echo '<script>
                        var res = new Object();
                        res.html = "'.$html_str.'";
                        res.footer = "'.$footer_str.'";
                        res.footer_link = "";
                        sbReturnValue(res);
                      </script>';
            }
            else
            {
                fTester_Rating_Design_Edit($html_str, $footer_str);
            }
        }
        else
        {
            sb_show_message(PL_TESTER_DESIGN_EDIT_ERROR, false, 'warning');
            sb_add_system_message(sprintf(PL_TESTER_DESIGN_EDIT_RATING_SYSTEMLOG_ERROR, $strt_title), SB_MSG_WARNING);

            fTester_Rating_Design_Edit();
            return;
        }
    }
    else
    {
        $error = true;
        if (sql_param_query('INSERT INTO sb_tester_rating_temps SET ?a', $row))
        {
        	$id = sql_insert_id();

            if (fCategs_Add_Elem($id, 'design_edit'))
            {
                sbQueryCache::updateTemplate('sb_tester_rating_temps', $id);
                sb_add_system_message(sprintf(PL_TESTER_DESIGN_EDIT_RATING_ADD_OK, $strt_title));

                echo '<script>
                        sbReturnValue('.$id.');
                      </script>';

                $error = false;
            }
            else
            {
                sql_query('DELETE FROM sb_tester_rating_temps WHERE strt_id="'.$id.'"');
            }
        }

        if ($error)
        {
            sb_show_message(sprintf(PL_TESTER_DESIGN_EDIT_RATING_ADD_ERROR, $strt_title), false, 'warning');
            sb_add_system_message(sprintf(PL_TESTER_DESIGN_EDIT_RATING_ADD_SYSTEMLOG_ERROR, $strt_title), SB_MSG_WARNING);

            fTester_Rating_Design_Edit();
            return;
        }
    }
}

function fTester_Rating_Design_Delete()
{
	$id = intval($_GET['id']);
    $pages = sql_query('SELECT pages.p_id FROM sb_pages pages, sb_elems elems
                        WHERE elems.e_link="page" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_tester_rating" AND pages.p_id=elems.e_p_id LIMIT 1');
    $temps = false;
    if (!$pages)
    {
        $temps = sql_query('SELECT temps.t_id FROM sb_templates temps, sb_elems elems
                        WHERE elems.e_link="temp" AND elems.e_temp_id="'.$id.'" AND elems.e_ident="pl_tester_rating" AND temps.t_id=elems.e_p_id LIMIT 1');
    }

    if ($pages || $temps)
    {
        echo PL_TESTER_DESIGN_DELETE_ERROR;
    }
}




function fTester_Categs_Design_Get($args)
{
	return fCategs_Design_Get($args, 'pl_tester_categs', 'pl_tester');
}

function fTester_Categs_Design()
{
	require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');

	$elems = new sbElements('sb_categs_temps_list', 'ctl_id', 'ctl_title', 'fTester_Categs_Design_Get', 'pl_tester_categs_design', 'pl_tester_categs_design');
    $elems->mCategsDeleteWithElementsMenu = true;
	$elems->mCategsRootName = KERNEL_DESIGN_ROOT_NAME;
	$elems->mElemsIcon = SB_CMS_IMG_URL.'/plugins/pl_tester_categs_32.png';

	$elems->addSorting(PL_TESTER_DESIGN_SORT_BY_TITLE, 'ctl_title');
	$elems->mCategsPasteElemsMenuTitle = KERNEL_DESIGN_PASTE_ELEMS_MENU_TITLE;

	$elems->mElemsAddMenuTitle  = KERNEL_DESIGN_ELEMS_ADD_MENU_TITLE;
	$elems->mElemsEditMenuTitle = KERNEL_DESIGN_ELEMS_EDIT_MENU_TITLE;
	$elems->mElemsCopyMenuTitle = KERNEL_DESIGN_ELEMS_COPY_MENU_TITLE;
	$elems->mElemsCutMenuTitle  = KERNEL_DESIGN_ELEMS_CUT_MENU_TITLE;

	$elems->mElemsEditEvent =  'pl_tester_categs_design_edit';
	$elems->mElemsEditDlgWidth = 900;
	$elems->mElemsEditDlgHeight = 700;

	$elems->mElemsAddEvent =  'pl_tester_categs_design_edit';
	$elems->mElemsAddDlgWidth = 900;
	$elems->mElemsAddDlgHeight = 700;

	$elems->mElemsDeleteEvent = 'pl_tester_categs_design_delete';

	$elems->mCategsClosed = false;
	$elems->mCategsRubrikator = false;

    $elems->mElemsJavascriptStr .= '
          function linkPages(e)
          {
              if (!sbSelEl)
              {
                  var el = sbEventTarget(e);

                  while (el.parentNode && !el.getAttribute("true_id"))
                      el = el.parentNode;

                  var el_id = el.getAttribute("el_id");
              }
              else
                  var el_id = sbSelEl.getAttribute("el_id");

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_get_links&id=" + el_id + "&ident[]=pl_tester_categs";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
          }
          function linkTemps(e)
          {
              if (!sbSelEl)
              {
                  var el = sbEventTarget(e);

                  while (el.parentNode && !el.getAttribute("true_id"))
                      el = el.parentNode;

                  var el_id = el.getAttribute("el_id");
              }
              else
                  var el_id = sbSelEl.getAttribute("el_id");

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_templates_get_links&id=" + el_id + "&ident[]=pl_tester_categs";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
          }

          function menuList()
          {
              window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_tester_questions";
          }';

    $elems->addElemsMenuItem(KERNEL_LINK_PAGES_MENU_ITEM, 'linkPages();');
    $elems->addElemsMenuItem(KERNEL_LINK_TEMPS_MENU_ITEM, 'linkTemps();');
    $elems->addElemsMenuItem(PL_TESTER_DESIGN_TESTS_MENU, 'menuList();', false);

	$elems->init();
}

function fTester_Categs_Design_Edit()
{
    fCategs_Design_Edit('pl_tester_categs_design', 'pl_tester', 'pl_tester_categs_design_update');
}

function fTester_Categs_Design_Update()
{
    fCategs_Design_Edit_Submit('pl_tester_categs_design', 'pl_tester', 'pl_tester_categs_design_update', 'pl_tester_categs', 'pl_tester');
}

function fTester_Categs_Design_Delete()
{
	fCategs_Design_Delete('pl_tester_categs', 'pl_tester');
}



function fTester_Selcat_Design_Get($args)
{
	return fCategs_Design_Get($args, 'pl_tester_selcat', 'pl_tester', true);
}

function fTester_Selcat_Design()
{
	require_once(SB_CMS_LIB_PATH.'/sbElements.inc.php');

    $elems = new sbElements('sb_categs_temps_full', 'ctf_id', 'ctf_title', 'fTester_Selcat_Design_Get', 'pl_tester_selcat_design', 'pl_tester_selcat_design');
    $elems->mCategsDeleteWithElementsMenu = true;
	$elems->mCategsRootName = KERNEL_DESIGN_ROOT_NAME;
	$elems->mElemsIcon = SB_CMS_IMG_URL.'/plugins/pl_tester_sel_cat_32.png';

	$elems->addSorting(PL_TESTER_DESIGN_SORT_BY_TITLE, 'ctf_title');
	$elems->mCategsPasteElemsMenuTitle = KERNEL_DESIGN_PASTE_ELEMS_MENU_TITLE;

    $elems->mElemsAddMenuTitle  = KERNEL_DESIGN_ELEMS_ADD_MENU_TITLE;
    $elems->mElemsEditMenuTitle = KERNEL_DESIGN_ELEMS_EDIT_MENU_TITLE;
    $elems->mElemsCopyMenuTitle = KERNEL_DESIGN_ELEMS_COPY_MENU_TITLE;
    $elems->mElemsCutMenuTitle  = KERNEL_DESIGN_ELEMS_CUT_MENU_TITLE;

    $elems->mElemsEditEvent =  'pl_tester_selcat_design_edit';
    $elems->mElemsEditDlgWidth = 900;
    $elems->mElemsEditDlgHeight = 700;

    $elems->mElemsAddEvent =  'pl_tester_selcat_design_edit';
    $elems->mElemsAddDlgWidth = 900;
    $elems->mElemsAddDlgHeight = 700;

    $elems->mElemsDeleteEvent = 'pl_tester_selcat_design_delete';

    $elems->mCategsClosed = false;
    $elems->mCategsRubrikator = false;
    $elems->mElemsUseLinks = false;

    $elems->mElemsJavascriptStr .= '
          function linkPages(e)
          {
              if (!sbSelEl)
              {
                  var el = sbEventTarget(e);

                  while (el.parentNode && !el.getAttribute("true_id"))
                      el = el.parentNode;

                  var el_id = el.getAttribute("el_id");
              }
              else
                  var el_id = sbSelEl.getAttribute("el_id");

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_pages_get_links&id=" + el_id + "&ident[]=pl_tester_selcat";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
          }
          function linkTemps(e)
          {
              if(!sbSelEl)
              {
                  var el = sbEventTarget(e);

                  while (el.parentNode && !el.getAttribute("true_id"))
                      el = el.parentNode;

                  var el_id = el.getAttribute("el_id");
              }
			  else
				  var el_id = sbSelEl.getAttribute("el_id");

              strPage = "'.SB_CMS_DIALOG_FILE.'?event=pl_templates_get_links&id=" + el_id + "&ident[]=pl_tester_selcat";
              strAttr = "resizable=1,width=800,height=600";
              sbShowModalDialog(strPage, strAttr, null, window);
          }

          function menuList()
          {
				window.location = "'.SB_CMS_CONTENT_FILE.'?event=pl_tester_questions";
		  }';

	$elems->addElemsMenuItem(KERNEL_LINK_PAGES_MENU_ITEM, 'linkPages();');
	$elems->addElemsMenuItem(KERNEL_LINK_TEMPS_MENU_ITEM, 'linkTemps();');
	$elems->addElemsMenuItem(PL_TESTER_DESIGN_TESTS_MENU, 'menuList();', false);

	$elems->init();
}

function fTester_Selcat_Design_Edit()
{
    fCategs_Design_Sel_Cat_Edit('pl_tester_selcat_design', 'pl_tester', 'pl_tester_selcat_design_update');
}

function fTester_Selcat_Design_Update()
{
    fCategs_Design_Sel_Cat_Edit_Submit('pl_tester_selcat_design', 'pl_tester', 'pl_tester_selcat_design_update', 'pl_tester_selcat', 'pl_tester');
}

function fTester_Selcat_Design_Delete()
{
    fCategs_Design_Delete('pl_tester_selcat', 'pl_tester');
}

?>