<?php 
require_once(dirname(__FILE__).'/jheader.inc.php'); 

echo '
var _isFF=false;
var _isIE=false;
var _isOpera=false;
var _isSafari=false;
var _isIE6=false;
var _browserVersion = '.browser_get_version().';
';
  
switch (browser_get_agent())
{
    case 'IE':
        echo '_isIE = true;';
        if (browser_get_version() <= 6)
        	echo '_isIE6 = true;';
        break;
        
    case 'OPERA':
        echo '_isOpera = true;';
        break;
        
    case 'MOZILLA':
        echo '_isFF = true;';
        break;
    
    case 'SAFARI':
    case 'CHROME':
        echo '_isSafari = true;';
        break;
}

echo 'var sbSpecTable = [];
	sbSpecTable[":"] = "%3A";
	sbSpecTable["/"] = "%2F";
	sbSpecTable["?"] = "%3F";
	sbSpecTable["#"] = "%23";
	sbSpecTable["&"] = "%26";
	sbSpecTable[" "] = "%20";';

if (SB_DB_CHARSET == 'cp1251')
{
	echo '
	var sbCodeTable = [];
	for (var i = 0x410; i <= 0x44F; i++)
	{
	    sbCodeTable[i] = i - 0x350;
	}
	sbCodeTable[0x401] = 0xA8;
	sbCodeTable[0x451] = 0xB8;
	
	/**
	 * Переводит строку к безопасному для передачи через cookie и GET-запрос виду
	 */
	function sbEscapeEx(str)
	{
	    var ret = "";
	    str = new String(str);
	
	    for (var i = 0; i < str.length; i++)
	    {
	        var n = str.charCodeAt(i);
	        if (typeof sbCodeTable[n] != "undefined")
	        {
	            ret += escape(String.fromCharCode(sbCodeTable[n]));
	        }
	        else
	        {
	            var chr = str.charAt(i);
	            if (typeof sbSpecTable[chr] != "undefined")
	            {
	                ret += sbSpecTable[chr];
	            }
	            else
	            {
	                ret += encodeURI(chr);
	            }
	        }
	    }
	    return ret;
	};';
}
else
{
	echo 'function sbEscapeEx(str)
	{
		var ret = "";
		var chr = "";
	    str = new String(str);
	
	    for (var i = 0; i < str.length; i++)
	    {
	        chr = str.charAt(i);
            if (typeof sbSpecTable[chr] != "undefined")
            {
                ret += sbSpecTable[chr];
            }
            else
            {
                ret += encodeURI(chr);
            }
	    }
	    return ret;
	}';
}
?>

function sbNormilizeURL(url)
{
    var url_ar = url.split('?');
    if (url_ar.length > 1)
    {
        url = url_ar[0]+'?';
        for (var i = 1; i < url_ar.length; i++)
        {
            var url_amp_ar = url_ar[i].split('&');
            for (var j = 0; j < url_amp_ar.length; j++)
            {
                url +=  sbEscapeEx(url_amp_ar[j])+(j+1 != url_amp_ar.length? '&':'');
            }
            url += (i+1 != url_ar.length? '?':'');
        }
    }

    return url;
}
/**
 * Возвращает значение cookie по его имени
 */
function sbGetCookie(c_name)
{
    var search = c_name + "="
    var returnValue = "";

    if (document.cookie.length > 0)
    {
        var offset = document.cookie.indexOf(search)
        if (offset != -1)
        {
            offset += search.length
            var end = document.cookie.indexOf(";", offset);
            if (end == -1) end = document.cookie.length;
            returnValue=unescape(document.cookie.substring(offset, end));
        }
    }
    return returnValue;
};

/**
 * Устанавливает или очищает (если c_value пустое) cookie
 */
function sbSetCookie(c_name, c_value, c_expires, c_path, c_domain, c_secure)
{
    var curCookie = c_name + "=" + sbEscapeEx(c_value) +
                ((c_expires) ? "; expires=" + c_expires.toGMTString() : "") +
                ((c_path) ? "; path=" + c_path : "") +
                ((c_domain) ? "; domain=" + c_domain : "") +
                ((c_secure) ? "; secure" : "");

    document.cookie = curCookie;
};

function sbCallerFunction(funcObject,dhtmlObject, param)
{
    this.handler=function(own_param)
    {
        funcObject(dhtmlObject, param, own_param);
        return true;
    };

    return this.handler;
};

// Функции работы с событиями DHTML-объектов
var sbAddEvent = function(o, n, f, params)
{
	if (o.attachEvent)
    {
        o.attachEvent('on'+n, f);
    }
    else if (o.addEventListener)
    {
        o.addEventListener(n,f,false);
    }
};

var sbRemoveEvent = function(o, n, f)
{
    if (o.detachEvent)
    {
        o.detachEvent('on'+n,f);
    }
    else if (o.removeEventListener)
    {
        o.removeEventListener(n,f,false);
    }
};

function sbCancelEvent(e)
{
    if (!e) e = window.event;
    
    e.returnValue = false;
    e.cancelBubble = true;
    
    if (e.preventDefault) e.preventDefault();
    if (e.stopPropagation) e.stopPropagation();

    return false;
};

function sbEventTarget(e)
{
  if(!e) e = window.event;
  if(e.target) return e.target;
  
  return e.srcElement;
}

function sbCancelSelect(e)
{
    var el = sbEventTarget(e)

    if (el.tagName != "INPUT" && el.tagName != "TEXTAREA")   
        return sbCancelEvent(e);
    else
        return true;
}

function sbDisCMenu(e)
{
    var el = sbEventTarget(e);
    var sTagName = el.tagName ;

    if ( !( ( sTagName == "INPUT" && el.type == "text" ) || sTagName == "TEXTAREA" ) )
    {
        return sbCancelEvent(e);
    }
    else
    {
        return true;
    }
};

//координата левой стороны объекта
function sbGetAbsoluteLeft(htmlObject)
{
    var xPos = htmlObject.offsetLeft;
    var temp = htmlObject.offsetParent;

    while(temp != null)
    {
        xPos+= temp.offsetLeft;
        temp = temp.offsetParent;
    }

    return xPos;
};
//координата верхней стороны объекта
function sbGetAbsoluteTop(htmlObject)
{
    var yPos = htmlObject.offsetTop;
    var temp = htmlObject.offsetParent;
    while(temp != null)
    {
        yPos+= temp.offsetTop;
        temp = temp.offsetParent;
    }

    return yPos;
};

// дополнительные свойства и методы стандартных объектов DOM
String.prototype.isArgument=function()
{
	return /^([a-zA-Z]){1,}=([0-9]){1,}$/.test(this);
}

if(window.Node)
{
    Node.prototype.removeNode = function(removeChildren)
    {
        var self = this;
        if(Boolean(removeChildren))
        {
            return this.parentNode.removeChild(self);
        }
        else
        {
            var range = document.createRange();
            range.selectNodeContents(self);
            return this.parentNode.replaceChild(range.extractContents(),self);
        }
    };

    Node.prototype.swapNode = function (oNode)
    {
        var self = this;

        var p=oNode.parentNode;
        var s=oNode.nextSibling;

        this.parentNode.replaceChild(oNode,self);
        p.insertBefore(self,s);
        return self;
    };
};

function sbGetUrlSymbol(str)
{
    if(str.indexOf("?")!=-1)
        return "&"
    else
        return "?"
}

function sbConvertStringToBoolean(inputString)
{
    if(typeof(inputString)=="string")
        inputString=inputString.toLowerCase();

    switch(inputString)
    {
        case "1":
        case "true":
        case "yes":
        case "y":
        case 1:
        case true:
            return true;
        default:
            return false;
    }
};

// функции интерфейса
function sbPress(el, on)
{
   if (el && !el.disabled && el.nodeName == 'IMG' && el.className != 'spacer')
   {
    	if (on)
		{
			el.className = "buttonPress";
		}
	    else
		{
			el.className = "button";
		}
	}
}
function sbEnable(el, on)
{
    if (el && el.nodeName == 'IMG' && el.className != 'spacer')
    {
        el.disabled = !on;
        if (el.disabled)
        {
            el.className = "buttonDisabled";
            if (typeof el.style.MozOpacity != 'undefined')
            {
                el.style.MozOpacity = 0.6;
            }
            else
            {
                el.style.filter = 'alpha(opacity=60)';
            }
        }
        else
        {
			el.className = "button";
			if (typeof el.style.MozOpacity != 'undefined')
            {
                el.style.MozOpacity = 1;
            }
            else
            {
                el.style.filter = 'alpha(opacity=100)';
            }
		}
    }
}

function sbInsertAtCaret(textEl, newText)
{
    if (_isIE && textEl.isTextEdit)
    {
        var rng = document.selection.createRange();
        rng.text = newText;
        rng.select();
    }
    else if (!_isIE)
    {
        if (typeof(textEl.setSelectionRange) != "undefined")
        {
            var oldSelectionStart = textEl.selectionStart;
            var oldSelectionEnd = textEl.selectionEnd;
            var selectedText = textEl.value.substring(oldSelectionStart, oldSelectionEnd);
            var scrollTop, scrollLeft;
            if (textEl.type == 'textarea' && typeof(textEl.scrollTop) != "undefined")
            {
                scrollTop  = textEl.scrollTop;
                scrollLeft = textEl.scrollLeft;
            }
            textEl.value = textEl.value.substring(0, oldSelectionStart) + newText + textEl.value.substring(oldSelectionEnd);

            if (typeof(scrollTop) != "undefined")
            {
                textEl.scrollTop  = scrollTop;
                textEl.scrollLeft = scrollLeft;
            }
            textEl.setSelectionRange(oldSelectionStart + newText.length, oldSelectionStart + newText.length);
        }
    }
}

function sbAddTabProc()
{
    var oTextAreas = document.getElementsByTagName('TEXTAREA');
    for (var i = 0; i < oTextAreas.length; i++)
    {
        sbTabProc(oTextAreas[i]);
    }
}

var sbTabEl = null;
function sbTabProc(el)
{
	if (!el)
		return;
		
    if( _isIE )
    {
        sbAddEvent( el, "keydown",
            function()
            {
                if( window.event.keyCode == 9 )
                {
                	var rng = document.selection.createRange();
                    if( rng.text.length )
                    {
                        if( window.event.shiftKey )
                        {
                            rng.text = sbRemoveTabs( rng.text );
                        }
                        else
                        {
                            rng.text = sbInsertTabs( rng.text );
                        }
                    }
                    else
                    {
                    	rng.text = "\t";
                    }
                    
                    return false;
                }
            }
        );
    }
    else
    {
        sbAddEvent( el, (_isSafari ? "keydown" : "keypress"),
            function(e)
            {
                if( e.keyCode == 9 )
                {
                    sbTabEl = this;
                    var iScroll_top = this.scrollTop;
                    var iStart = this.selectionStart;
                    var sA = this.value.substring( 0, iStart );
                    var sB = this.value.substring( iStart, this.selectionEnd );
                    var bSelection = false;
                    var sC = this.value.substring( this.selectionEnd, this.value.length );
                    if( sB.length )
                    {
                        bSelection = true;
                        if( e.shiftKey )
                        {
                            sB = sbRemoveTabs( sB );
                        }
                        else
                        {
                            sB = sbInsertTabs( sB );
                        }
                    }
                    else
                    {
                        sB = "\t";
                    }
                    this.value = sA + sB + sC;
                    this.focus();
                    if( bSelection )
                    {
                        this.selectionStart = iStart;
                        this.selectionEnd = iStart + sB.length;
                    }
                    else
                    {
                        this.selectionStart = ++iStart;
                        this.selectionEnd = iStart;
                    }
                    this.scrollTop = iScroll_top;

                    return sbCancelEvent( e );
                }
            }
        );
        
        sbAddEvent( el, "blur",
            function(e)
            {
                if( sbTabEl )
                {
                    setTimeout( "sbTabEl.focus();sbTabEl = null;", 1 );
                }
            }
        );
    }
}

function sbRemoveTabs( sText )
{
    return sText.replace( /(^|\n)\t/g, "$1" );
}

function sbInsertTabs( sText )
{
    return sText.replace( /(^|\n)/g, "$1\t" );
}

function sbPreventNsDrag(e)
{
    if(e && e.preventDefault)
    {
        e.preventDefault();
        return false;
    }
    
    return false;
}

function sbPreventOperaSelect(e)
{
    if( document.body.getAttribute('unselectable') == 'on' )
    {
        e.target.ownerDocument.defaultView.getSelection().removeAllRanges();
    }
}

function sbSelectField( eId )
{
	var el = sbGetE( eId ) ;
	if (!el)
		return;
		
	try
	{
		el.focus() ;
	}
	catch (e)
	{
		setTimeout("sbSelectField('" + eId + "')", 100);
	}
}

function sbGetE(eId)
{
	return document.getElementById(eId);
}

function sbGetCaretPosition (el) 
{
	var pos = 0;
	if (_isIE) 
	{
		el.focus ();
		var sel = document.selection.createRange();
		sel.moveStart ('character', -el.value.length);

		pos = sel.text.length;
	}
	else
	{
		pos = el.selectionStart;
	}
	return (pos);
}

function sb_str_bytes(s) 
{
    s = String(s);
	var	c, b = 0, l = s.length;
	while(l) 
	{
		c = s.charCodeAt(--l);
		b += (c < 128) ? 1 : ((c < 2048) ? 2 : ((c < 65536) ? 3 : 4));
	}
		
	return b;
}

// Сериализация JavaScript-массива для последующей десериализации в PHP
function sb_serialize (a)
{
    var res = "";
    var total = 0;
    for (var key in a)
    {
        total++;
        res += "s:" + sb_str_bytes(key) + ":\"" + String(key) + "\";s:" + sb_str_bytes(a[key]) + ":\"" + String(a[key]) + "\";";
    }
    res = "a:" + total + ":{" + res + "}";

    return res;
}

function sbPassStrength(pass)
{
    var password = pass;
    var spc_chars = "~!@#$%&*[]|";
    
    this.lcase_count = 0;
    this.ucase_count = 0;
    this.num_count = 0;
    this.schar_count = 0;
    this.length = 0;
    this.strength = 0;
    this.runs_score = 0;
    this.verdict = "";

    var verdict_conv = {'weak':2.7, 'medium':20, 'strong':50};

    var flc = 1.0;  // lowercase
    var fuc = 1.0;  // uppercase
    var fnm = 1.3;  // number
    var fsc = 1.5;  // special char

    this.getStrength = function()
    {
    
        if ((this.run_score = this.detectRuns()) <= 1)
        {
            return "bad";
        }

        var regex_sc = new RegExp('['+spc_chars+']', 'g');

        this.lcase_count = password.match(/[a-z<?php echo $GLOBALS['sb_reg_lower_interval']; ?>]/g);
        this.lcase_count = (this.lcase_count) ? this.lcase_count.length : 0;
        this.ucase_count = password.match(/[A-Z<?php echo $GLOBALS['sb_reg_upper_interval']; ?>]/g);
        this.ucase_count = (this.ucase_count) ? this.ucase_count.length : 0;
        this.num_count   = password.match(/[0-9]/g);
        this.num_count   = (this.num_count) ? this.num_count.length : 0;
        this.schar_count = password.match(regex_sc);
        this.schar_count = (this.schar_count) ? this.schar_count.length : 0;
        this.length = password.length;

        var avg = this.length / 4;

        this.strength = ((this.lcase_count * flc + 1) * 
                         (this.ucase_count * fuc + 1) *
                         (this.num_count * fnm + 1) * 
                         (this.schar_count * fsc + 1)) / (avg + 1);

        if (this.strength > verdict_conv.strong)
            this.verdict = "strong";
        else if (this.strength > verdict_conv.medium)
            this.verdict = "good";
        else if (this.strength > verdict_conv.weak)
            this.verdict = "weak";
        else
            this.verdict = "bad";

        return this.verdict;
    }

    this.detectRuns = function()
    {
        var parts = password.split('');
        var ords = new Array();
        for (i in parts)
        {
            ords[i] = parts[i].charCodeAt(0);
        }

        var accum = 0;
        var lasti = ords.length-1

        for (var i=0; i < lasti; ++i)
        {
            accum += Math.abs(ords[i] - ords[i+1]);
        }

        return accum/lasti;
    }
}

function sb_pass_strength(pass_id, login_id, mes_id, min_length) 
{
    var pass = sbGetE(pass_id);
    var login = sbGetE(login_id);
    var mess = sbGetE(mes_id);

	if (!pass || !login || !mess)
		return;
		
	pass = pass.value;
	if (pass == "")
	{
		mess.className = "";
		mess.innerHTML = "<?php echo SB_PASSWORD_EMPTY; ?>";
		
		return;
	}
	
	login = login.value;
	
    if (pass.length < min_length ) 
    {
    	mess.className = "short";
		mess.innerHTML = "<b><?php echo SB_PASSWORD_SHORT; ?></b>";
		
		return;
    }

    if (pass.toLowerCase() == login.toLowerCase()) 
    {
    	mess.className = "bad";
		mess.innerHTML = "<b><?php echo SB_PASSWORD_BAD; ?></b>";
		
		return;
    }

	var pw = new sbPassStrength(pass);
	var verdict = pw.getStrength();
    mess.className = verdict;
    
    var hint = "";
    if (pw.ucase_count == 0) 
    	hint += "<li><?php echo SB_PASSWORD_UPPER_HINT; ?></li>";
    if (pw.num_count == 0) 
    	hint += "<li><?php echo SB_PASSWORD_NUMBER_HINT; ?></li>";
    if (pw.schar_count == 0) 
    	hint += "<li><?php echo sprintf(SB_PASSWORD_SPEC_HINT, '~!@#$%&*[]|'); ?></li>";
    if (pw.run_score <= 1) 
    	hint += "<li><?php echo SB_PASSWORD_AVOID_HINT; ?></li>";
    	
    switch (verdict)
    {
    	case "bad":
    		verdict = "<b><?php echo SB_PASSWORD_BAD; ?></b>";
    		break;
    		
    	case "weak":
    		verdict = "<b><?php echo SB_PASSWORD_WEAK; ?></b>";
    		break;
    		
    	case "good":
    		verdict = "<b><?php echo SB_PASSWORD_GOOD; ?></b>";
    		break;
    		
    	case "strong":
    		verdict = "<b><?php echo SB_PASSWORD_STRONG; ?></b>";
    		hint = "";
    		break;
    }
    
    if (hint != "")
    	hint = "<div style='text-align:left;' class='smalltext'><b><?php echo SB_PASSWORD_HINT; ?>:</b><ul>" + hint + "</ul></div>";
    	
	mess.innerHTML = verdict + hint;
}

sbAddEvent(window, 'load', sbAddTabProc);
<?php require_once(dirname(__FILE__).'/jfooter.inc.php'); ?>