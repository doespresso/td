var sbXMLHTTP = false;
var sbXMLHTTPSync = false;
var sbCodeTable = [];
for (var i = 0x410; i <= 0x44F; i++)
{
    sbCodeTable[i] = i - 0x350;
}
sbCodeTable[0x401] = 0xA8;
sbCodeTable[0x451] = 0xB8;
var sbSpecTable = [];
sbSpecTable[":"] = "%3A";
sbSpecTable["/"] = "%2F";
sbSpecTable["?"] = "%3F";

function sbEscapeEx(str)
{
    var ret = '';
    str = new String(str);

    for (var i = 0; i < str.length; i++)
    {
        var n = str.charCodeAt(i);
        if (typeof sbCodeTable[n] != 'undefined')
        {
            ret += escape(String.fromCharCode(sbCodeTable[n]));
        }
        else
        {
            var chr = str.charAt(i);
            if (typeof sbSpecTable[chr] != 'undefined')
                ret += sbSpecTable[chr];
            else
                ret += encodeURI(chr);
        }
    }
    return ret;
}

function sbNormilizeURL(url)
{
    url_ar = url.split('?');
    if (url_ar.length > 1)
    {
        url = url_ar[0]+'?';
        for (var i = 1; i < url_ar.length; i++)
        {
            url_amp_ar = url_ar[i].split('&');
            for (var j = 0; j < url_amp_ar.length; j++)
            {
                url +=  sbEscapeEx(url_amp_ar[j])+(j+1 != url_amp_ar.length? '&':'');
            }
            url += (i+1 != url_ar.length? '?':'');
        }
    }

    return url;
}

function sbCallerFunction(funcObject,dhtmlObject, param)
{
    this.handler=function(own_param)
    {
        funcObject(dhtmlObject, param, own_param);
        return true;
    };

    return this.handler;
}

function sbAJAXInit(sync)
{
    if (window.XMLHttpRequest)
    {
        try
        {
            if (sync)
            {
                sbXMLHTTPSync = new XMLHttpRequest();
            }
            else
            {
        	    sbXMLHTTP = new XMLHttpRequest();
            }
        }
        catch (e) {}
    }
    else if (window.ActiveXObject)
    {
       	try
    	{
    	    if (sync)
            {
                sbXMLHTTPSync = new ActiveXObject("Msxml2.XMLHTTP");
            }
            else
            {
        	    sbXMLHTTP = new ActiveXObject("Msxml2.XMLHTTP");
            }
    	}
    	catch (e)
    	{
    		try
    		{
    		    if (sync)
                {
                    sbXMLHTTPSync = new ActiveXObject("Microsoft.XMLHTTP");
                }
                else
                {
            	    sbXMLHTTP = new ActiveXObject("Microsoft.XMLHTTP");
                }
    		}
    		catch (e) {}
    	}
    }

    if (sync && !sbXMLHTTPSync)
    {
        alert("AJAX Error! Can't create sbXMLHTTPSync.");
        return;
    }
    else if (!sync && !sbXMLHTTP)
    {
        alert("AJAX Error! Can't create sbXMLHTTP.");
        return;
    }
}

function sbExecScript(text)
{
	if (!text) 
		return;
		
	if (window.execScript)
	{
		window.execScript(text);
	} 
	else 
	{
		var script = document.createElement('script');
  		script.setAttribute('type', 'text/javascript');
  		script.setAttribute('language', 'JavaScript');
  		if (_isIE)
  			script.text = text;
  		else
  			script.appendChild( document.createTextNode( text ) );
  			
  		var head = document.getElementsByTagName("head")[0] || document.documentElement;
  		head.insertBefore( script, head.firstChild );
		head.removeChild( script );
 	}
 	
 	return;
}

var sbEvalJSSrcs = [];
function sbEvalJS(s)
{
	var js_ScriptFragment = '(?:<script.*?>)((\n|\r|.)*?)(?:<\/script>)';
	var js_ScriptSrcFragment = '<script.+(src[ ]*=[ ]*\'(.*?)\'|src[ ]*=[ ]*"(.*?)").+';

	var matchAll = new RegExp(js_ScriptFragment, 'img');
    var matchOne = new RegExp(js_ScriptFragment, 'im');
    var matchSrc = new RegExp(js_ScriptSrcFragment, 'im');

    var arr = s.match(matchAll) || [];
    var JSCode = [];
	
	for (var i = 0; i < arr.length; i++)
    {
        var srcMt = arr[i].match(matchSrc);
        if (srcMt)
        {
            if (srcMt.length > 3)
            	srcMt = srcMt[3];
            else
            	srcMt = srcMt[2];
            
            if (srcMt != '')
            {
            	var found = false;
            	for (var j = 0; j < sbEvalJSSrcs.length; j++)
            	{
            		if (sbEvalJSSrcs[j] == srcMt)
            		{
            			found = true;
            			break;
            		}
            	}
            	
            	if (found)
            		continue;
            		
            	sbEvalJSSrcs[sbEvalJSSrcs.length] = srcMt;
            	var res = sbLoadSync(srcMt);
            	if (res)
	            	JSCode[JSCode.length] = res;
            }
        }

        var mtCode = arr[i].match(matchOne);
        if (mtCode && mtCode[1] != '') 
        	JSCode[JSCode.length] = mtCode[1];
    }
    
    s = s.replace(matchAll, '');
	
    for(var i = 0; i < JSCode.length; i++)
    	sbExecScript(JSCode[i]);
    
    return s;
}

function sbLoadSync(url)
{
    if (!sbXMLHTTPSync)
    {
        sbAJAXInit(true);
    }

    if (sbXMLHTTPSync)
    {
        try
        {
            url = sbNormilizeURL(url);
            
            sbXMLHTTPSync.open('GET', url, false);
	        sbXMLHTTPSync.send(null);
	        var res = sbXMLHTTPSync.responseText;
    	    
            if (((sbXMLHTTPSync.status == 200)||(sbXMLHTTPSync.status == 0)) && res != undefined)
            {
                return sbEvalJS(res);
            }
            else
            {
                return false;
            }
        }
        catch (e)
        {
            return false;
        }
	}
	else
	{
	    return false;
	}
}

function sbLoadAsync(url, pfunction)
{
    sbAJAXInit(false);

    if (sbXMLHTTP)
    {
        try
        {
            url = sbNormilizeURL(url);
		    sbXMLHTTP.onreadystatechange = new sbCallerFunction(sbAfterLoadAsync, sbXMLHTTP, pfunction);
	        sbXMLHTTP.open('GET', url, true);
	        sbXMLHTTP.send(null);
        }
        catch (e)
        {
            return false;
        }
	}
    else
    {
		return false;
    }
}

function sbAfterLoadAsync(obj, fobj)
{
    if (obj && obj.readyState == 4 && typeof(fobj) == 'function')
    {
        if ((obj.status == 200)||(obj.status == 0))
        {
            var res = obj.responseText; 
            if (res != undefined)
            {
                fobj(sbEvalJS(res));
            }
            else
            {
                fobj('');
            }
        }
        else
        {
            alert("AJAX Error! Can't get data.");
        }

        obj = null;
    }
}

function sbPostSync(url, data)
{ 
    if (!sbXMLHTTPSync)
    {
        sbAJAXInit(true);
    }
    
    if (sbXMLHTTPSync)
    {
        try
        {
            url = sbNormilizeURL(url);
            sbXMLHTTPSync.open('POST', url, false);
            sbXMLHTTPSync.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            sbXMLHTTPSync.send(data);

            if (((sbXMLHTTPSync.status == 200)||(sbXMLHTTPSync.status == 0)) && sbXMLHTTPSync.responseText != undefined)
            {
                return sbEvalJS(sbXMLHTTPSync.responseText);
            }
            else
            {
                return false;
            }
        }
        catch (e)
        {
            return false;
        }
    }
    else
    {
		return false;
    }
}

function sbPostAsync(url, data, pfunction)
{
    sbAJAXInit(false);

    if (sbXMLHTTP)
    {
        try
        {
            url = sbNormilizeURL(url);
		    sbXMLHTTP.onreadystatechange = new sbCallerFunction(sbAfterPostAsync, sbXMLHTTP, pfunction);

	        sbXMLHTTP.open('POST', url, true);
            sbXMLHTTP.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            sbXMLHTTP.send(data);
        }
        catch (e)
        {
            return false;
        }
	}
    else
    {
		return false;
    }
}

function sbAfterPostAsync(obj, func)
{
    if (obj && obj.readyState == 4 && typeof(func) == 'function')
    {
        if ((obj.status == 200)||(obj.status == 0))
        {
            if (obj.responseText != undefined)
            {
                func(sbEvalJS(obj.responseText));
            }
            else
            {
                func('');
            }
        }
        else
        {
            alert("AJAX Error! Can't get data.");
        }

        obj = null;
    }
}

function sbEncodeForm(form)
{
    if(!form || !form.elements) throw "sbEncodeForm: Fatal error, argument is not a FORM!";

    var ret=[], el;

    for(var i = 0; i < form.elements.length; i++)
    {
        el = form.elements[i];
        if("checkboxradio".indexOf(el.type) >= 0)
        {
            if(el.checked)
            {
                var val = sbGetInputValue(el);
                if (val != "") ret[ret.length] = val;
            }
        }
        else if ( el.type != "button" && el.type != "submit")
        {
            var val = sbGetInputValue(el);
            if (val != "") ret[ret.length] = val;
        }
    }
    return ret.join("&");
}

function sbGetInputValue(inp)
{
    if(typeof(inp.nodeName) == "undefined")
    {
        for(var i = 0; i < inp.length; i++)
        {
            if(inp[i].checked)
            {
                return (inp[i].name ? sbEscapeEx(inp[i].name)+"="+sbEscapeEx(inp[i].value):"");
            }
        }
        return "";
    }

    if (!inp.name)
        return "";
        
    if(inp.type == "select-multiple")
    {
        var ret=[];
        for(var i = 0; i < inp.options.length; i++)
        {
            if(inp.options[i].selected) ret[ret.length] = sbEscapeEx(inp.options[i].name)+"[]="+sbEscapeEx(inp.options[i].value);
        }
        return ret.join("&");
    }
    else if (inp.type == "select-one")
    { 
        return (inp.selectedIndex >= 0 ? sbEscapeEx(inp.name)+"="+sbEscapeEx(inp.options[inp.selectedIndex].value) : "");
    }
    if(inp.type == "image")
    {
        return sbEscapeEx(inp.name)+"="+sbEscapeEx(inp.src);
    }
    else
    {
        return sbEscapeEx(inp.name)+"="+sbEscapeEx(inp.value);
    }
}

function sbSendForm(form)
{
    return sbPostSync(form.action, sbEncodeForm(form))
}

function sbSendFormAsync(form, pfunction)
{
    return sbPostAsync(form.action, sbEncodeForm(form), pfunction)
}