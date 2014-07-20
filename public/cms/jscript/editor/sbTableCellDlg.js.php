<?php require_once(dirname(__FILE__).'/../jheader.inc.php'); ?>

var wnd = (_isIE ? dialogArguments : window.opener.sbModalDialog.args);

var oBlock ;
var oCells = [] ;
var _type ;
if (document.location.search.indexOf("&block=p") != -1)
{
	var path = new wnd.sbEditorElementPath( wnd.sbEditorSelection._GetBoundaryParentElement( true ) ) ;
	oBlock = path._Block || path._BlockLimit ;

	if ( !oBlock || oBlock == wnd.sbEditorArea || oBlock.nodeName.toLowerCase() == 'body' )
		sbCloseDialog();
	else
	{
		var range = new wnd.sbEditorDomRange( wnd.sbEditorAreaWnd ) ;
		range._MoveToSelection() ;
		var bookmark = range._CreateBookmark() ;

		var iterator = new wnd.sbEditorDomRangeIterator( range ) ;
		var block ;
		while ( ( block = iterator._GetNextParagraph() ) )
		{
			oCells[ oCells.length ] = block;
		}

		range._MoveToBookmark( bookmark ) ;
		range._Select() ;
	}
	
	_type = 'p';
}
else if (document.location.search.indexOf("&block=cell") != -1)
{
	var oCells = wnd.sbEditorTableHandler._GetSelectedCells() ;
	if ( oCells.length < 1 )
		sbCloseDialog();
	else
		oBlock = oCells[0] ;
		
	_type = 'cell';
}
else
	_type = 'div';

window.onload = function()
{
	if (oBlock)
	{
		var iWidth  = (oBlock.style.width  ? oBlock.style.width  : sbGetAttribute(oBlock, "width") ) ;
		var iHeight = (oBlock.style.height ? oBlock.style.height : sbGetAttribute(oBlock, "height") ) ;

		if (iWidth.indexOf('%') >= 0)
		{
			iWidth = parseInt( iWidth.substr(0, iWidth.length - 1), 10 ) ;
			sbGetE('selWidthType').value = "percent" ;
		}
		else if (iWidth.indexOf('px') >= 0)
		{
			iWidth = iWidth.substr(0, iWidth.length - 2);
			sbGetE('selWidthType').value = "pixels" ;
		}

		if (iHeight && iHeight.indexOf('%') >= 0)
		{
			iHeight = parseInt( iHeight.substr(0, iHeight.length - 1), 10 ) ;
			sbGetE('selHeightType').value = "percent" ;
		}
		else if (iHeight && iHeight.indexOf('px') >= 0)
		{
			iHeight = iHeight.substr(0, iHeight.length - 2);
			sbGetE('selHeightType').value = "pixels" ;
		}
		
		sbGetE('spin_Width').value = iWidth || '' ;
		sbGetE('spin_Height').value = iHeight || '' ;

        // Отступы ячеек таблицы
		var padding = oBlock.style.paddingTop || oBlock.style.padding || '';
		if (padding.indexOf('px') >= 0) {
            sbGetE('spin_PaddingTop').value = padding.substr(0, padding.length - 2);
            sbGetE('selPaddingTopType').value = "pixels" ;
        } else if (padding.indexOf('%') >= 0) {
            sbGetE('spin_PaddingTop').value = padding.substr(0, padding.length - 1);
            sbGetE('selPaddingTopType').value = "percent" ;
        }

        padding = oBlock.style.paddingBottom || oBlock.style.padding || '';
        if (padding.indexOf('px') >= 0) {
            sbGetE('spin_PaddingBottom').value = padding.substr(0, padding.length - 2);
            sbGetE('selPaddingBottomType').value = "pixels" ;
        } else if (padding.indexOf('%') >= 0) {
            sbGetE('spin_PaddingBottom').value = padding.substr(0, padding.length - 1);
            sbGetE('selPaddingBottomType').value = "percent" ;
        }

        padding = oBlock.style.paddingLeft || oBlock.style.padding || '';
        if (padding.indexOf('px') >= 0) {
            sbGetE('spin_PaddingLeft').value = padding.substr(0, padding.length - 2);
            sbGetE('selPaddingLeftType').value = "pixels" ;
        } else if (padding.indexOf('%') >= 0) {
            sbGetE('spin_PaddingLeft').value = padding.substr(0, padding.length - 1);
            sbGetE('selPaddingLeftType').value = "percent" ;
        }

        padding = oBlock.style.paddingRight || oBlock.style.padding || '';
        if (padding.indexOf('px') >= 0) {
            sbGetE('spin_PaddingRight').value = padding.substr(0, padding.length - 2);
            sbGetE('selPaddingRightType').value = "pixels" ;
        } else if (padding.indexOf('%') >= 0) {
            sbGetE('spin_PaddingRight').value = padding.substr(0, padding.length - 1);
            sbGetE('selPaddingRightType').value = "percent" ;
        }

		var lineHeight = oBlock.style.lineHeight || '';
		if (lineHeight != '')
		{
			if (lineHeight == '1.2' || lineHeight == '1.5' || lineHeight == '2')
				sbGetE('selLineHeight').value = lineHeight;
			else
			{
				sbGetE('selLineHeight').value = 'other';
				sbGetE('spin_LineHeight').value = lineHeight;
				sbGetE('spin_LineHeight').disabled = false;
			}		
		}
		
		var textIdent = oBlock.style.textIndent || '';
		if (textIdent != '')
		{
			if (textIdent == '20px' || textIdent == '-20px')
				sbGetE('selTextIdent').value = textIdent;
			else
			{
				sbGetE('selTextIdent').value = 'other';
				sbGetE('spin_TextIdent').value = textIdent;
				sbGetE('spin_TextIdent').disabled = false;
			}		
		}
		
		if ( _isIE)
		{
			var brdWidth = oBlock.style.borderWidth || '' ;
		}
		else
		{
			var brdWidth = oBlock.style.borderWidth.split(" ") ;
			brdWidth = brdWidth[0] ;
		}
		
		if (brdWidth.indexOf('px') >= 0)
			brdWidth = brdWidth.substr(0, brdWidth.length - 2) ;
			
		sbGetE('spin_Border').value = brdWidth;
		
		function getHexString(clr)
    	{
    		clr = clr.split(",");
    		
      		var rStr = parseInt(clr[0]).toString(16);
      		if (rStr.length == 1)
        		rStr = '0' + rStr;
      		var gStr = parseInt(clr[1]).toString(16);
      		if (gStr.length == 1)
        		gStr = '0' + gStr;
      		var bStr = parseInt(clr[2]).toString(16);
      		if (bStr.length == 1)
        		bStr = '0' + bStr;
      		
      		return ('#' + rStr + gStr + bStr).toUpperCase();
    	}
	    	
		if (oBlock.style.borderColor.indexOf("rgb") != -1)
		{
			var _start = oBlock.style.borderColor.indexOf("(") + 1;
			var _end = oBlock.style.borderColor.indexOf(")");
	    	var brdColor = getHexString(oBlock.style.borderColor.substr( _start, _end - _start));
		}
		else
			var brdColor = oBlock.style.borderColor;
		
		if (brdColor.indexOf("-moz") != -1)
			brdColor = "";
			
		if ( _isIE)
		{
			var brdStyle = oBlock.style.borderStyle ;
		}
		else
		{
			var brdStyle = oBlock.style.borderStyle.split(" ") ;
			var brdStyle = brdStyle[0] ;
		}
			
		if (oBlock.style.backgroundColor.indexOf("rgb") != -1)
		{
			var _start = oBlock.style.backgroundColor.indexOf("(") + 1;
			var _end = oBlock.style.backgroundColor.indexOf(")");
	    	var bgColor = getHexString(oBlock.style.backgroundColor.substr( _start, _end - _start));
		}
		else
			var bgColor = oBlock.style.backgroundColor;

		var bgImage = oBlock.style.backgroundImage ;
		var _start = bgImage.indexOf("(") + 1;
		var _end = bgImage.indexOf(")");
	    var bgImage = bgImage.substr( _start, _end - _start);
		 
		sbGetE('txtBorderColor').value = brdColor ;
		sbGetE('selBorderType').value = brdStyle ;
		sbGetE('txtBackColor').value = bgColor ;
		sbGetE('txtBackImage').value = bgImage ;
		sbGetE('selBackImagePos').value = oBlock.style.backgroundRepeat ;
		sbGetE('txtAttId').value = oBlock.id ;
		
		var reg = new RegExp("(width|height|padding|padding-top|padding-bottom|padding-left|padding-right|line-height|text-indent|border|border-right|border-top|border-bottom|border-left|border-width|border-color|border-right-color|border-left-color|border-top-color|border-bottom-color|border-style|border-right-style|border-left-style|border-top-style|border-bottom-style|background-color|background-image|background-repeat)\s*:.*[;]?", "ig");
		var sti ;
		if ( _isIE )
		{
			sbGetE('txtAttClasses').value = oBlock.className || '' ;
			sti = new String(oBlock.style.cssText) ;
		}
		else
		{
			sbGetE('txtAttClasses').value = oBlock.getAttribute('class', 2) || '' ;
			sti = oBlock.getAttribute('style', 2) ;
		}
	
		if (sti)
			sbGetE('txtAttStyle').value = sti.replace(reg, "");
	
		sbSelectField( 'spin_Width' ) ;
	}
}

function changeInput(el, inputId)
{
	sbGetE(inputId).disabled = (el.value != "other");
}

function save()
{
	wnd.sbEditorUndo._SaveUndoStep() ;
	
	var bExists = ( _type != "div" ) ;

	if ( ! bExists )
	{
		oBlock = wnd.sbEditorAreaDoc.createElement( "DIV" ) ;
		oCells[ oCells.length ] = oBlock;
	}
	
	for (var i = 0; i < oCells.length; i++)
	{
		oBlock = oCells[ i ];
		
		if ( bExists && sbGetAttribute(oBlock, 'width') )		
			sbSetAttribute(oBlock, 'width', '') ;
		if ( bExists && sbGetAttribute(oBlock, 'height') )	
			sbSetAttribute(oBlock, 'height', '') ;
	
		var sWidth = sbGetE('spin_Width').value ;
		if (sWidth.length > 0)
		{
			if (sbGetE('selWidthType').value == 'percent')
				sWidth += '%';
			else
				sWidth += 'px';
		}
	
		var sHeight = sbGetE('spin_Height').value ;
		if (sHeight.length > 0)
		{
			if (sbGetE('selHeightType').value == 'percent')
				sHeight += '%';
			else
				sHeight += 'px';
		}
			
		sbSetAttribute( oBlock, 'id', sbGetE('txtAttId').value ) ;
	
		var addStyle = "";
		if (sbGetE('spin_Width').value.Trim() != "")
		    addStyle += "width:" + sWidth + ";";
		
		if (sbGetE('spin_Height').value.Trim() != "")
		    addStyle += "height:" + sHeight + ";";

		if (sbGetE('spin_PaddingTop').value.Trim() != "" && 
			sbGetE('spin_PaddingTop').value == sbGetE('spin_PaddingBottom').value &&
			sbGetE('spin_PaddingTop').value == sbGetE('spin_PaddingLeft').value &&
			sbGetE('spin_PaddingTop').value == sbGetE('spin_PaddingRight').value &&
            sbGetE('selPaddingTopType').value == sbGetE('selPaddingBottomType').value &&
            sbGetE('selPaddingTopType').value == sbGetE('selPaddingLeftType').value &&
            sbGetE('selPaddingTopType').value == sbGetE('selPaddingRightType').value)
			addStyle += "padding:" + sbGetE('spin_PaddingTop').value + (sbGetE('selPaddingTopType').value == 'percent' ? "%;" : "px;");
		else
		{
			if (sbGetE('spin_PaddingTop').value.Trim() != "")
				addStyle += "padding-top:" + sbGetE('spin_PaddingTop').value + (sbGetE('selPaddingTopType').value == 'percent' ? "%;" : "px;");
				
			if (sbGetE('spin_PaddingBottom').value.Trim() != "")
				addStyle += "padding-bottom:" + sbGetE('spin_PaddingBottom').value + (sbGetE('selPaddingBottomType').value == 'percent' ? "%;" : "px;");
				
			if (sbGetE('spin_PaddingLeft').value.Trim() != "")
				addStyle += "padding-left:" + sbGetE('spin_PaddingLeft').value + (sbGetE('selPaddingLeftType').value == 'percent' ? "%;" : "px;");
				
			if (sbGetE('spin_PaddingRight').value.Trim() != "")
				addStyle += "padding-right:" + sbGetE('spin_PaddingRight').value + (sbGetE('selPaddingRightType').value == 'percent' ? "%;" : "px;");
		}
		
		if (sbGetE('selLineHeight').value != "")
		{
			if ( sbGetE('selLineHeight').value == "other" && sbGetE('spin_LineHeight').value.Trim() != "" )
			{
				addStyle += "line-height:" + sbGetE('spin_LineHeight').value + ";";
			}
			else if ( sbGetE('selLineHeight').value != "other" )
			{
				addStyle += "line-height:" + sbGetE('selLineHeight').value + ";";
			}
		}
		 
		if (sbGetE('selTextIdent').value != "")
		{
			if ( sbGetE('selTextIdent').value == "other" && sbGetE('spin_TextIdent').value.Trim() != "" )
			{
				addStyle += "text-indent:" + sbGetE('spin_TextIdent').value + ";";
			}
			else if ( sbGetE('selTextIdent').value != "other" )
			{
				addStyle += "text-indent:" + sbGetE('selTextIdent').value + ";";
			}
		}
		  
		if (sbGetE('spin_Border').value.Trim() != "" && sbGetE('spin_Border').value != 0)
			addStyle += "border:" + sbGetE('spin_Border').value + "px " + (sbGetE('selBorderType').value != "" ? sbGetE('selBorderType').value : "solid") + (sbGetE('txtBorderColor').value != "" ? " " + sbGetE('txtBorderColor').value : "") + ";";

	    if (sbGetE('txtBackColor').value != "")
	    	addStyle += "background-color:" + sbGetE('txtBackColor').value + ";";
	    if (sbGetE('txtBackImage').value != "")
	    {
	    	addStyle += "background-image: url(" + sbGetE('txtBackImage').value + ");";
	    	if (sbGetE('selBackImagePos').value != "")
	    		addStyle += "background-repeat:" + sbGetE('selBackImagePos').value + ";";
	    }
	    	
	    var ownStyle = sbGetE('txtAttStyle').value;
	    if (ownStyle != "" && ownStyle.substr(ownStyle.length - 2, 1) != ';')
    		ownStyle += ";";
    	
		if ( _isIE )
		{
			oBlock.className = sbGetE('txtAttClasses').value ;
			oBlock.style.cssText = ownStyle + addStyle;
		}
		else
		{
			sbSetAttribute( oBlock, 'class', sbGetE('txtAttClasses').value ) ;
			sbSetAttribute( oBlock, 'style', ownStyle + addStyle) ;
		}
		
		if (! bExists)
		{
			if ( !_isIE )
				wnd.sbEditorAppendBogusBr( oBlock ) ;
	
			wnd.sbEditorInsertElement( oBlock ) ;
		}
	}
	
	sbCloseDialog();
}

<?php require_once(dirname(__FILE__).'/../jfooter.inc.php'); ?>