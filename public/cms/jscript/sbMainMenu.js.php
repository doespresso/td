<?php require_once(dirname(__FILE__).'/jheader.inc.php'); ?>
var isDOM = document.getElementById ? 1 : 0, isIE = document.all ? 1 : 0, isNS4 = navigator.appName == 'Netscape' && !isDOM ? 1 : 0, isIE4 = isIE && !isDOM ? 1 : 0, isOp = self.opera ? 1 : 0, isDyn = isDOM || isIE || isNS4;
function getRef(i, p)
{
	p = !p ? document : p.navigator ? p.document : p;
	return isIE ? p.all[i] : isDOM ? (p.getElementById ? p : p.ownerDocument).getElementById(i) : isNS4 ? p.layers[i] : null;
};
function getSty(i, p)
{
	var r = getRef(i, p);
	return r ? isNS4 ? r : r.style : null;
};
if (!self.LayerObj)
	var LayerObj = new Function('i', 'p', 'this.ref=getRef(i,p);this.sty=getSty(i,p);return this');
function getLyr(i, p)
{
	return new LayerObj(i, p);
};
function LyrFn(n, f)
{
	LayerObj.prototype[n] = new Function('var a=arguments,p=a[0],px=isNS4||isOp?0:"px";with(this){' + f + '}');
};LyrFn('x', 'if(!isNaN(p)){sty.left=p+px;return sty.left;}else return parseInt(sty.left)');
LyrFn('y', 'if(!isNaN(p)){sty.top=p+px;return sty.top;}else return parseInt(sty.top)');
LyrFn('vis', 'sty.visibility=p');
LyrFn('bgColor', 'if(isNS4)sty.bgColor=p?p:null;else sty.background=p?p:"transparent"');
LyrFn('bgImage', 'if(isNS4)sty.background.src=p?p:null;else sty.background=p?"url("+p+")":"transparent"');
LyrFn('clip', 'if(isNS4)with(sty.clip){left=a[0];top=a[1];right=a[2];bottom=a[3]}else sty.clip="rect("+a[1]+"px "+a[2]+"px "+a[3]+"px "+a[0]+"px)" ');
LyrFn('write', 'if(isNS4)with(ref.document){write(p);close()}else ref.innerHTML=p');
LyrFn('alpha', 'var f=ref.filters,d=(p==null),o=d?"inherit":p/100;if(f){if(!d&&sty.filter.indexOf("alpha")==-1)sty.filter+=" alpha(opacity="+p+")";else if(f.length&&f.alpha)with(f.alpha){if(d)enabled=false;else{opacity=p;enabled=true}}}else if(isDOM)sty.opacity=sty.MozOpacity=o');
function setLyr(v, dw, p)
{
	if (!setLyr.seq)
		setLyr.seq = 0;
	if (!dw)
		dw = 0;
	var o = !p ? isNS4 ? self : document.body : !isNS4 && p.navigator ? p.document.body : p, IA = 'insertAdjacentHTML', AC = 'appendChild', id = '_sl_' + setLyr.seq++;
	if (o[IA])
		o[IA]('beforeEnd', '<div id="' + id + '" style="position:absolute"></div>');
	else
	if (o[AC])
	{
		var n = document.createElement('div');
		o[AC](n);
		n.id = id;
		n.style.position = 'absolute';
	}
	else
	if (isNS4)
	{
		var n = new Layer(dw, o);
		id = n.id;
	}
	var l = getLyr(id, p);
	with (l)
	if (ref)
	{
		vis(v);
		x(0);
		y(0);
		sty.width = dw + ( isNS4 ? 0 : 'px');
	}
	return l;
};
if (!self.page)
	var page =
	{
		win : self,
		minW : 0,
		minH : 0,
		MS : isIE && !isOp
	};
page.db = function(p)
{
	with (this.win.document)
	return ( isDOM ? documentElement[p] : 0) || body[p] || 0;
};
page.winW = function()
{
	with (this)
	return Math.max(minW, MS ? db('clientWidth') : win.innerWidth);
};
page.winH = function()
{
	with (this)
	return Math.max(minH, MS ? db('clientHeight') : win.innerHeight);
};
page.scrollX = function()
{
	with (this)
	return MS ? db('scrollLeft') : win.pageXOffset;
};
page.scrollY = function()
{
	with (this)
	return MS ? db('scrollTop') : win.pageYOffset;
};
function addProps(obj, data, names, addNull)
{
	for (var i = 0; i < names.length; i++)
		if (i < data.length || addNull)
			obj[names[i]] = data[i];
};
function PopupMenu(myName)
{
	this.myName = myName;
	this.showTimer = this.hideTimer = this.showDelay = 0;
	this.hideDelay = 500;
	this.menu =
	{
	};
	this.litNow =
	{
	};
	this.litOld =
	{
	};
	this.nextMenu = '';
	this.overM = '';
	this.overI = 0;
	this.hideDocClick = 0;
	this.actMenu = null;
	PopupMenu.list[myName] = this;
};
PopupMenu.list =
{
};
var PmPt = PopupMenu.prototype;
PmPt.callEvt = function(mN, iN, evt)
{
	var i = this.menu[mN][iN], r1 = this[evt] ? this[evt](mN, iN) : 0, r2;
	if (i[evt])
	{
		if (i[evt].substr)
			i[evt] = new Function('mN', 'iN', i[evt]);
		r2 = i[evt](mN, iN);
	}
	return typeof r2 == 'boolean' ? r2 : r1;
};
PmPt.over = function(mN, iN)
{
	with (this)
	{
		clearTimeout(hideTimer);
		overM = mN;
		overI = iN;
		var evtRtn = iN ? callEvt(mN, iN, 'onmouseover') : 0, rtn = evtRtn || false;
		litOld = litNow;
		litNow =
		{
		};
		var litM = mN, litI = iN;
		if (mN)
			do
			{
				litNow[litM] = litI;
				litI = menu[litM][0].parentItem;
				litM = menu[litM][0].parentMenu;
			} while(litM);
		var same = 1;
		for (var z in menu)
		same &= ( typeof (litNow[z]) != 'undefined' && litNow[z] == litOld[z]);
		if (same)
			return rtn;
		clearTimeout(showTimer);
		for (var thisM in menu)
		with (menu[thisM][0])
		{
			if (!lyr)
				continue;
			var lI = litNow[thisM];
			var oI = litOld[thisM];
			if (lI != oI)
			{
				if (lI)
					changeCol(thisM, lI);
				if (oI)
					changeCol(thisM, oI);
			}
			if (!lI)
				clickDone = 0;
			if (isRoot)
				continue;
			if (lI && !visNow)
				doVis(thisM, 1);
			if (!lI && visNow)
				doVis(thisM, 0);
		}
		nextMenu = '';
		if (menu[mN] && menu[mN][iN].sm && (evtRtn + '' != 'false'))
		{
			var m = menu[mN], t = m[iN].sm;
			if (!menu[t])
				return rtn;
			if (m[0].clickSubs && !m[0].clickDone)
				return rtn;
			nextMenu = t;
			if (showDelay)
				showTimer = setTimeout(myName + '.doVis("' + t + '",1)', showDelay);
			else
				doVis(t, 1);
		}
		return rtn;
	}
};
PmPt.out = function(mN, iN)
{
	with (this)
	{
		if (mN != overM || iN != overI)
			return;
		var thisI = menu[mN][iN], evtRtn = iN ? callEvt(mN, iN, 'onmouseout') : 0;
		if (thisI.sm != nextMenu)
		{
			clearTimeout(showTimer);
			nextMenu = '';
		}
		if (hideDelay && (evtRtn + '' != 'false'))
		{
			var delay = menu[mN][0].isRoot && !thisI.sm ? 50 : hideDelay;
			hideTimer = setTimeout(myName + '.over("",0)', delay);
		}
		overM = '';
		overI = 0;
	}
};
PmPt.click = function(mN, iN)
{
	with (this)
	{
		var m = menu[mN], evtRtn = callEvt(mN, iN, 'onclick'), hm = 1;
		if (evtRtn + '' == 'false')
			return false;
		with (m[iN])
		{
			if (type == 'js:')
				eval(href);
			else
			{
				if (sm && m[0].clickSubs)
				{
					m[0].clickDone = 1;
					doVis(sm, 1);
					hm = 0;
				}
				if (href)
				{
					if (type == '_blank')
					{
						window.open(href, '_blank');
					}
					else
					{
						type = type || 'window';
						eval(type + '.location.href="' + href + '"');
					}
				}
			}
		}
		if (hm)
			over('', 0);
		return evtRtn || false;
	}
};
PmPt.changeCol = function(mN, iN, fc)
{
	with (this.menu[mN][iN])
	{
		if (!lyr || !lyr.ref)
			return;
		var bgFn = outCol != overCol ? (outCol.indexOf('.') == -1 ? 'bgColor' : 'bgImage') : 0;
		var ovr = ( typeof (this.litNow[mN]) != 'undefined' && this.litNow[mN] == iN) ? 1 : 0, doFX = (!fc && ( typeof (this.litNow[mN]) == 'undefined' || this.litNow[mN] != this.litOld[mN]));
		var col = ovr ? overCol : outCol;
		if (fade[0])
		{
			clearTimeout(timer);
			col = '#';
			count = Math.max(0, Math.min(count + (2 * ovr - 1) * parseInt(fade[ovr][0]), 100));
			var oc, nc, hexD = '0123456789ABCDEF';
			for (var i = 1; i < 4; i++)
			{
				oc = parseInt('0x' + fade[0][i]);
				nc = parseInt(oc + (parseInt('0x' + fade[1][i]) - oc) * (count / 100));
				col += hexD.charAt(Math.floor(nc / 16)).toString() + hexD.charAt(nc % 16);
			}
			if (count % 100 > 0)
				timer = setTimeout(this.myName + '.changeCol("' + mN + '",' + iN + ',1)', 50);
		}
		if (bgFn && isNS4)
			lyr[bgFn](col);
		var reCSS = (overClass != outClass || outBorder != overBorder);
		if (doFX)
			with (lyr)
			{
				if (!this.noRW && (overText || overInd || isNS4 && reCSS))
					write(this.getHTML(mN, iN, ovr));
				if (!isNS4 && reCSS)
				{
					ref.className = ( ovr ? overBorder : outBorder);
					var chl = ( isDOM ? ref.childNodes : ref.children);
					if (chl && !overText)
						for (var i = 0; i < chl.length; i++)
							chl[i].className = ovr ? overClass : outClass;
				}
			}
		if (bgFn && !isNS4)
			lyr[bgFn](col);
		if (doFX && outAlpha != overAlpha)
			lyr.alpha( ovr ? overAlpha : outAlpha);
	}
};
PmPt.position = function(posMN)
{
	with (this)
	{
		for (mN in menu)
		if (!posMN || posMN == mN)
			with (menu[mN][0])
			{
				if (!lyr || !lyr.ref || !visNow)
					continue;
				var pM, pI, newX = eval(offX), newY = eval(offY);
				if (!isRoot)
				{
					pM = menu[parentMenu];
					pI = pM[parentItem].lyr;
					if (!pI)
						continue;
				}
				var eP = eval(par), pW = (eP && eP.navigator ? eP : window);
				with (pW.page)
				var sX = scrollX(), wX = sX + winW() || 9999, sY = scrollY(), wY = winH() + sY || 9999;
				var sb = page.MS ? 5 : 20;
				if (pM && typeof (offX) == 'number')
					newX = Math.max(sX, Math.min(newX + pM[0].lyr.x() + pI.x(), wX - menuW - sb));
				if (pM && typeof (offY) == 'number')
					newY = Math.max(sY, Math.min(newY + pM[0].lyr.y() + pI.y(), wY - menuH - sb));
				lyr.x(newX);
				lyr.y(newY);
			}
	}
};
PmPt.doVis = function(mN, show)
{
	with (this)
	{
		var m = menu[mN], sh = ( show ? 'show' : 'hide'), mA = sh + 'Menu', mE = 'on' + sh;
		m[0].visNow = show;
		if (m && m[0].lyr && m[0].lyr.ref)
		{
			if (show)
				position(mN);
			var p = m[0].parentMenu;
			if (p)
				m[0].lyr.sty.zIndex = m[0].zIndex = show ? menu[p][0].zIndex + 2 : 1;
			if (this[mE])
				this[mE](mN);
			if (this[mA])
				this[mA](mN);
			else
				m[0].lyr.vis( show ? 'visible' : 'hidden');
		}
	}
};
function ItemStyle()
{
	var names = ['len', 'spacing', 'popInd', 'popPos', 'pad', 'outCol', 'overCol', 'outClass', 'overClass', 'outBorder', 'overBorder', 'outAlpha', 'overAlpha', 'normCursor', 'nullCursor'];
	addProps(this, arguments, names, 1);
};
PmPt.startMenu = function(mName)
{
	with (this)
	{
		if (!menu[mName])
			menu[mName] = [
			{
			}];
		actMenu = menu[mName];
		var aM = actMenu[0];
		actMenu.length = 1;
		var names = ['name', 'isVert', 'offX', 'offY', 'width', 'itemSty', 'par', 'clickSubs', 'clickDone', 'visNow', 'parentMenu', 'parentItem', 'oncreate', 'isRoot'];
		addProps(aM, arguments, names, 1);
		aM.extraHTML = '';
		aM.menuW = aM.menuH = 0;
		aM.zIndex = 1001;
		if (!aM.lyr)
			aM.lyr = null;
		if (mName.substring(0, 4) == 'root')
		{
			aM.isRoot = 1;
			aM.oncreate = new Function('obj', 'this.visNow=1;obj.position("' + mName + '");this.lyr.vis("visible")');
		}
		return aM;
	}
};
PmPt.addItem = function()
{
	with (this)
	with (actMenu[0])
	{
		var aI = actMenu[actMenu.length] =
		{
		};
		var names = ['text', 'href', 'type', 'itemSty', 'len', 'spacing', 'popInd', 'popPos', 'pad', 'outCol', 'overCol', 'outClass', 'overClass', 'outBorder', 'overBorder', 'outAlpha', 'overAlpha', 'normCursor', 'nullCursor', 'iX', 'iY', 'iW', 'iH', 'fW', 'fH', 'overText', 'overInd', 'sm', 'lyr', 'onclick', 'onmouseover', 'onmouseout'];
		addProps(aI, arguments, names, 1);
		var iSty = arguments[3] ? arguments[3] : actMenu[0].itemSty;
		for (prop in iSty)
		if (aI[prop] + '' == 'undefined')
			aI[prop] = iSty[prop];
		if (aI.type == 'sm:')
		{
			aI.sm = aI.href;
			aI.href = '';
		}
		var r = RegExp, re = /^SWAP:(.*)\^(.*)$/;
		if (aI.text.match(re))
		{
			aI.text = r.$1;
			aI.overText = r.$2;
		}
		if (aI.popInd.match(re))
		{
			aI.popInd = r.$1;
			aI.overInd = r.$2;
		}
		aI.timer = aI.count = 0;
		aI.fade = [];
		for (var i = 0; i < 2; i++)
		{
			var oC = i ? 'overCol' : 'outCol';
			if (aI[oC].match(/^(\d+)\#(..)(..)(..)$/))
			{
				aI[oC] = '#' + r.$2 + r.$3 + r.$4;
				aI.fade[i] = [r.$1, r.$2, r.$3, r.$4];
			}
		}
		if (aI.outBorder && isNS4)
			aI.pad++;
		if (!isIE)
		{
			if (aI.normCursor == 'hand')
				aI.normCursor = 'pointer';
			if (aI.nullCursor == 'hand')
				aI.nullCursor = 'pointer';
		}
		aI.iW = isVert ? width : aI.len;
		aI.iH = isVert ? aI.len : width;
		var lastGap = actMenu.length > 2 ? actMenu[actMenu.length - 2].spacing : 0;
		var spc = aI.outBorder && actMenu.length > 2 ? 1 : 0;
		if (isVert)
		{
			menuH += lastGap - spc;
			aI.iX = 0;
			aI.iY = menuH;
			menuW = width;
			menuH += aI.iH;
		}
		else
		{
			menuW += lastGap - spc;
			aI.iX = menuW;
			aI.iY = 0;
			menuW += aI.iW;
			menuH = width;
		}
		return aI;
	}
};
PmPt.getHTML = function(mN, iN, isOver)
{
	with (this)
	{
		var itemStr = '';
		with (menu[mN][iN])
		{
			var tC = isOver ? overClass : outClass, txt = isOver && overText ? overText : text, popI = isOver && overInd ? overInd : popInd, ln = '<a href="#" onclick="return false;" onfocus="this.blur()" class="' + tC + ( isNS4 ? '" onmouseover="' + myName + '.over(\'' + mN + '\',' + iN + ')"' : '"');
			if (popI && sm)
			{
				if (isNS4)
					itemStr += '<layer class="' + tC + '" left="' + ((popPos + fW) % fW) + '" top="' + pad + '" height="' + (fH - 2 * pad) + '">' + popI + '</layer>';
				else
					itemStr += '<div class="' + tC + '" style="position:absolute;left:' + ((popPos + fW) % fW) + 'px;top:' + pad + 'px;height:' + (fH - 2 * pad) + 'px">' + popI + '</div>';
			}
			if (isNS4)
				itemStr += ( outBorder ? '<span class="' + ( isOver ? overBorder : outBorder) + '"><spacer type="block" width="' + (fW - 8) + '" height="' + (fH - 8) + '"></span>' : '') + '<layer left="' + pad + '" top="' + pad + '" width="' + (fW - 2 * pad) + '" height="' + (fH - 2 * pad) + '">' + ln + '>' + txt + '</a></layer>';
			else
			{
				itemStr += ( isIE4 ? '<div class="' + tC + '" ' : ln) + ' style="position:absolute;left:' + pad + 'px;top:' + pad + 'px;width:' + (fW - 2 * pad) + 'px;height:' + (fH - 2 * pad) + 'px;cursor:' + ( href ? normCursor : nullCursor) + '">' + txt + ( isIE4 ? '</div>' : '</a>');
			}
		}
		return itemStr;
	}
};
PmPt.update = function(docWrite, upMN)
{
	with (this)
	{
		if (!isDyn)
			return;
		for (mN in menu)
		with (menu[mN][0])
		{
			if (upMN && upMN != mN)
				continue;
			var str = '', eP = eval(par);
			with (
			eP && eP.navigator ? eP : self)
			var dC = document.compatMode, dT = document.doctype;
			var dFix = (dC && dC.indexOf('CSS') > -1 || isOp && !dC || dT && dT.name.indexOf('.dtd') > -1 || isDOM && !isIE) ? 2 : 0;
			for (var iN = 1; iN < menu[mN].length; iN++)
				with (menu[mN][iN])
				{
					var tM = menu[sm], itemID = myName + '-' + mN + '-' + iN;
					if (sm && tM)
					{
						tM[0].parentMenu = mN;
						tM[0].parentItem = iN;
					}
					if (outBorder)
					{
						fW = iW - dFix;
						fH = iH - dFix;
					}
					else
					{
						fW = iW;
						fH = iH;
					}
					var isImg = (outCol.indexOf('.') != -1);
					if (isDOM || isIE4)
					{
						str += '<div id="' + itemID + '" ' + ( outBorder ? 'class="' + outBorder + '" ' : '') + 'style="position:absolute;left:' + iX + 'px;top:' + iY + 'px;width:' + fW + 'px;height:' + fH + 'px;z-index:1000;' + ( outCol ? 'background:' + ( isImg ? 'url(' + outCol + ')' : outCol) : '') + ( typeof (outAlpha) == 'number' ? ';filter:alpha(opacity=' + outAlpha + ');-moz-opacity:' + outAlpha + '%;opacity:' + (outAlpha / 100) : '') + ';cursor:' + ( href ? normCursor : nullCursor) + '" ';
					}
					else
					if (isNS4)
					{
						str += '<layer id="' + itemID + '" left="' + iX + '" top="' + iY + '" width="' + fW + '" height="' + fH + '" z-index="' + zIndex + '" ' + ( outCol ? ( isImg ? 'background="' : 'bgcolor="') + outCol + '" ' : '');
					}
					var evtMN = "('" + mN + "'," + iN + ")";
					str += 'onmouseover="return ' + myName + '.over' + evtMN + '" onmouseout="' + myName + '.out' + evtMN + '" onclick="return ' + myName + '.click' + evtMN + '">' + getHTML(mN, iN, 0) + ( isNS4 ? '</layer>' : '</div>');
				}
			var sR = myName + '.setupRef(' + ( docWrite ? 1 : 0) + ',"' + mN + '")';
			if (isOp)
				setTimeout(sR, 1000);
			var mVis = isOp && isRoot ? 'visible' : 'hidden';
			if (docWrite)
			{
				var targFr = eP && eP.navigator ? eP : window;
				targFr.document.write('<div id="' + myName + '-' + mN + '" style="position:absolute;visibility:' + mVis + ';left:' + ( isOp ? -1000 : 0) + '0px;top:0px;width:' + (menuW + 2) + 'px;height:' + (menuH + 2) + 'px;z-index:1">' + str + extraHTML + '</div>');
			}
			else
			{
				if (!lyr || !lyr.ref)
					lyr = setLyr(mVis, menuW, eP);
				else
				if (isIE4)
					setTimeout(myName + '.menu.' + mN + '[0].lyr.sty.width=' + (menuW + 2), 50);
				with (lyr)
				{
					sty.zIndex = 22;
					write(str + extraHTML);
				}
			}
			if (!isOp)
				setTimeout(sR, 100);
		}
	}
};
PmPt.setupRef = function(docWrite, mN)
{
	with (this)
	with (menu[mN][0])
	{
		var eP = eval(par);
		if (docWrite || !lyr || !lyr.ref)
			lyr = getLyr(myName + '-' + mN, eP);
		for (var i = 1; i < menu[mN].length; i++)
			menu[mN][i].lyr = getLyr(myName + '-' + mN + '-' + i, isNS4 ? lyr.ref : eP);
		lyr.clip(0, 0, menuW + 2, menuH + 2);
		if (oncreate)
			oncreate(this);
	}
};
<?php require_once(dirname(__FILE__).'/jfooter.inc.php'); ?>