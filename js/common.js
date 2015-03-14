/**
 * Общий js.
 *@license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

var Forum = {
	/* attach FN to WINDOW.ONLOAD handler */
	addLoadEvent: function(fn)
	{
		var x = window.onload;
		window.onload = (x && typeof x=='function') ? function(){x();fn()} : fn;
	},
	/* return TRUE if node N has class X, else FALSE */
	hasClass: function(n, x)
	{
		return (new RegExp('\\b' + x + '\\b')).test(n.className)
	},
	/* add X class to N node, return TRUE if added, FALSE if already exists */
	addClass: function(n, x)
	{
		if (Forum.hasClass(n, x)) return false;
		else n.className += ' '+x;
		return true;
	},
	/* remove X class from N node, return TRUE if removed, FALSE if not present */
	removeClass: function(n, x)
	{
		if (!Forum.hasClass(n, x)) return false;
		x = new RegExp('\\s*\\b' + x + '\\b', 'g');
		n.className = n.className.replace(x, '');
		return true;
	},
	/* blink node N twice */
	blink: function(n, i)
	{
		if (typeof i == 'undefined') i = 2;
		var x = n.style.visibility;
		if (i && x!='hidden')
		{
			n.style.visibility = 'hidden';
			setTimeout(function(){n.style.visibility=x}, 200);
			setTimeout(function(){Forum.blink(n,i-1)}, 400);
		}
	},
	/* return true if node N scrolled into view, else false (y axis only) */
	onScreen: function(n)
	{
		function pageYOffset() // return number of pixels page has scrolled
		{
			var y = -1;
			if (self.pageYOffset) y = self.pageYOffset; // all except IE
			else if (document.documentElement && document.documentElement.scrollTop)
				y = document.documentElement.scrollTop; // IE 6 Strict
			else if (document.body) y = document.body.scrollTop; // all other IE ver
			return y;
		}
		function innerHeight() // return inner height of browser window
		{
			var y = -1;
			if (self.innerHeight) y = self.innerHeight; // all except IE
			else if (document.documentElement && document.documentElement.clientHeight)
				y = document.documentElement.clientHeight; // IE 6 Strict Mode
			else if (document.body) y = document.body.clientHeight; // all other IE ver
			return y;
		}
		function nodeYOffset(n) // return y coordinate of node N
		{
			var y = n.offsetTop;
			n = n.offsetParent;
			return n ? y += nodeYOffset(n) : y;
		}
		var screenTop = pageYOffset();
		var screenBottom = screenTop+innerHeight();
		var nodeTop = nodeYOffset(n);
		var nodeBottom = nodeTop+n.clientHeight;
		return nodeTop >= screenTop && nodeBottom < screenBottom;
	},
	/* apply FN to every ARR item, return array of results */
	map: function(fn, arr)
	{
		for (var i=0,len=arr.length; i<len; i++)
		{
			arr[i] = fn(arr[i])
		}
		return arr;
	},
	/* return first index where FN(ARR[i]) is true or -1 if none */
	find: function(fn, arr)
	{
		for (var i=0,len=arr.length; i<len; i++)
		{
			if (fn(arr[i]))
				return i;
		}
		return -1;
	},
	/* return array of elements for which FN(ARR[i]) is true */
	arrayOfMatched: function(fn, arr)
	{
		matched = [];
		for (var i=0,len=arr.length; i<len; i++)
		{
			if (fn(arr[i]))
				matched.push(arr[i])
		}
		return matched;
	},
	/* flattens multi-dimentional arrays into simple arrays */
	flatten: function(arr)
	{
		flt = [];
		for (var i=0,len=arr.length; i<len; i++)
		{
			if (typeof arr[i] == 'object' && arr.length)
			{
				flt.concat(Forum.flatten(arr[i]))
				alert('length1!!'+arr.length);
			}
			else flt.push(arr[i])
		}
		return flt
	},
	/* check FORM's required (REQ_) fields */
	validateForm: function(form)
	{
		var elements = form.elements;
		var fn = function(x) { return x.name && x.name.indexOf('req_')==0 };
		var nodes = Forum.arrayOfMatched(fn, elements);
		fn = function(x) { return /^\s*$/.test(x.value) };
		var empty = Forum.find(fn, nodes);
		if (empty > -1)
		{
			var n = document.getElementById('req-msg');
			Forum.removeClass(n, 'req-warn');
			var newlyAdded = Forum.addClass(n, 'req-error');
			if (!Forum.onScreen(n))
			{
				n.scrollIntoView(); // method not in W3C DOM, but fully cross-browser?
				setTimeout(function(){Forum.blink(n)}, 500);
			}
			else if (!newlyAdded) Forum.blink(n);
			if (Forum.onScreen(nodes[empty])) nodes[empty].focus();
			return false;
		}
		return true;
	},
	/* create a proper redirect URL (if we're using SEF friendly URLs) and go there */
	doQuickjumpRedirect: function(url, forum_names)
	{
		var selected_forum_id = document.getElementById('qjump-select')[document.getElementById('qjump-select').selectedIndex].value;
		url = url.replace('$1', selected_forum_id);
		url = url.replace('$2', forum_names[selected_forum_id]);
		document.location = url;
		return false;
	},
	/* toggle all checkboxes in the given form */
	toggleCheckboxes: function(curForm)
	{
		var inputlist = curForm.getElementsByTagName("input");
		for (i = 0; i < inputlist.length; i++)
		{
			if (inputlist[i].getAttribute("type") == 'checkbox' && inputlist[i].disabled == false)
				inputlist[i].checked = !inputlist[i].checked;
		}

		return false;
	},
	/* attach form validation function to submit-type inputs */
	attachValidateForm: function()
	{
		var forms = document.forms;
		for (var i=0,len=forms.length; i<len; i++)
		{
			var elements = forms[i].elements;
			var fn = function(x) { return x.name && x.name.indexOf('req_')==0 };
			if (Forum.find(fn, elements) > -1)
			{
				fn = function(x) { return x.type && (x.type=='submit' && x.name!='cancel') };
				var nodes = Forum.arrayOfMatched(fn, elements)
				var formRef = forms[i];
				fn = function() { return Forum.validateForm(formRef) };
				//TODO: look at passing array of node refs instead of forum ref
				//fn = function() { return Forum.checkReq(required.slice(0)) };
				nodes = Forum.map(function(x){x.onclick=fn}, nodes);
			}
		}
	},
	attachWindowOpen: function()
	{
		if (!document.getElementsByTagName) return;
		var nodes = document.getElementsByTagName('a');
		for (var i=0; i<nodes.length; i++)
		{
			if (Forum.hasClass(nodes[i], 'exthelp'))
				nodes[i].onclick = function() { window.open(this.href); return false; };
		}
	},
	autoFocus: function()
	{
		var nodes = document.getElementById('afocus');
		if (!nodes || window.location.hash.replace(/#/g,'')) return;
		nodes = nodes.all ? nodes.all : nodes.getElementsByTagName('*');
		// TODO: make sure line above gets nodes in display-order across browsers
		var fn = function(x) { return x.tagName.toUpperCase()=='TEXTAREA' || (x.tagName.toUpperCase()=='INPUT' && (x.type=='text') || (x.type=='password')) };
		var n = Forum.find(fn, nodes);
		if (n > -1) nodes[n].focus();
	},
	to: function(username)
	{
		insert('[b]'+username+'[/b]');
	},
	getSelectedText: function()
	{
		var result = undefined;
		
		if (window.getSelection)
			result = window.getSelection().toString();
		else if (document.getSelection)
			result = document.getSelection();
		else if (document.selection)
			result = document.selection.createRange().text;

		return result;
	},
	reply: function (qid, link)
	{
		var txt = Forum.getSelectedText();

		var form = document.getElementById('qq');
		if (txt == null || typeof(txt) == undefined || txt == '')
			location = link.href;
		else
		{
			document.getElementById('post_msg').value = txt;
			form.action = document.getElementById('quote_url').value.replace('$2', qid.toString());
			form.submit();
		}
	},
	quickQuote:function (username)
	{
		var txt = Forum.getSelectedText();

		if (txt == null || typeof(txt) == undefined || txt == '')
			return;
		else
			insert('[quote='+username+']'+txt+'[/quote]' + '\n');
	},
}
Forum.addLoadEvent(Forum.attachValidateForm);
Forum.addLoadEvent(Forum.attachWindowOpen);
Forum.addLoadEvent(Forum.autoFocus);