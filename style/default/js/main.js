/**
* flazy forum functions
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

/**
* Find a member
*/
function find_username(url) {
	'use strict';

	popup(url, 760, 570, '_usersearch');
	return false;
}

/**
* Window popup
*/
function popup(url, width, height, name) {
	'use strict';

	if (!name) {
		name = '_popup';
	}

	window.open(url.replace(/&amp;/g, '&'), name, 'height=' + height + ',resizable=yes,scrollbars=yes, width=' + width);
	return false;
}

/**
* Jump to page
*/
function pageJump(item) {
	'use strict';

	var page = item.val(),
		perPage = item.attr('data-per-page'),
		baseUrl = item.attr('data-base-url'),
		startName = item.attr('data-start-name');

	if (page !== null && !isNaN(page) && page == Math.floor(page) && page > 0) {
		if (baseUrl.indexOf('?') === -1) {
			document.location.href = baseUrl + '?' + startName + '=' + ((page - 1) * perPage);
		} else {
			document.location.href = baseUrl.replace(/&amp;/g, '&') + '&' + startName + '=' + ((page - 1) * perPage);
		}
	}
}

/**
* Mark/unmark checklist
* id = ID of parent container, name = name prefix, state = state [true/false]
*/
function marklist(id, name, state) {
	'use strict';

	jQuery('#' + id + ' input[type=checkbox][name]').each(function() {
		var $this = jQuery(this);
		if ($this.attr('name').substr(0, name.length) === name) {
			$this.prop('checked', state);
		}
	});
}

/**
* Resize viewable area for attached image or topic review panel (possibly others to come)
* e = element
*/
function viewableArea(e, itself) {
	'use strict';

	if (!e) {
		return;
	}

	if (!itself) {
		e = e.parentNode;
	}

	if (!e.vaHeight) {
		// Store viewable area height before changing style to auto
		e.vaHeight = e.offsetHeight;
		e.vaMaxHeight = e.style.maxHeight;
		e.style.height = 'auto';
		e.style.maxHeight = 'none';
		e.style.overflow = 'visible';
	} else {
		// Restore viewable area height to the default
		e.style.height = e.vaHeight + 'px';
		e.style.overflow = 'auto';
		e.style.maxHeight = e.vaMaxHeight;
		e.vaHeight = false;
	}
}

/**
* Alternate display of subPanels
*/
jQuery(function($) {
	'use strict';

	$('.sub-panels').each(function() {

		var $childNodes = $('a[data-subpanel]', this),
			panels = $childNodes.map(function () {
				return this.getAttribute('data-subpanel');
			}),
			showPanel = this.getAttribute('data-show-panel');

		if (panels.length) {
			activateSubPanel(showPanel, panels);
			$childNodes.click(function () {
				activateSubPanel(this.getAttribute('data-subpanel'), panels);
				return false;
			});
		}
	});
});

/**
* Activate specific subPanel
*/
function activateSubPanel(p, panels) {
	'use strict';

	var i, showPanel;

	if (typeof(p) === 'string') {
		showPanel = p;
	}
	$('input[name="show_panel"]').val(showPanel);

	if (typeof panels === 'undefined') {
		panels = jQuery('.sub-panels a[data-subpanel]').map(function() {
			return this.getAttribute('data-subpanel');
		});
	}

	for (i = 0; i < panels.length; i++) {
		jQuery('#' + panels[i]).css('display', panels[i] === showPanel ? 'block' : 'none');
		jQuery('#' + panels[i] + '-tab').toggleClass('activetab', panels[i] === showPanel);
	}
}

function selectCode(a) {
	'use strict';

	// Get ID of code block
	var e = a.parentNode.parentNode.getElementsByTagName('CODE')[0];
	var s, r;

	// Not IE and IE9+
	if (window.getSelection) {
		s = window.getSelection();
		// Safari and Chrome
		if (s.setBaseAndExtent) {
			var l = (e.innerText.length > 1) ? e.innerText.length - 1 : 1;
			s.setBaseAndExtent(e, 0, e, l);
		}
		// Firefox and Opera
		else {
			// workaround for bug # 42885
			if (window.opera && e.innerHTML.substring(e.innerHTML.length - 4) === '<BR>') {
				e.innerHTML = e.innerHTML + '&nbsp;';
			}

			r = document.createRange();
			r.selectNodeContents(e);
			s.removeAllRanges();
			s.addRange(r);
		}
	}
	// Some older browsers
	else if (document.getSelection) {
		s = document.getSelection();
		r = document.createRange();
		r.selectNodeContents(e);
		s.removeAllRanges();
		s.addRange(r);
	}
	// IE
	else if (document.selection) {
		r = document.body.createTextRange();
		r.moveToElementText(e);
		r.select();
	}
}

/**
* Play quicktime file by determining it's width/height
* from the displayed rectangle area
*/
function play_qt_file(obj) {
	'use strict';

	var rectangle = obj.GetRectangle();
	var width, height;

	if (rectangle) {
		rectangle = rectangle.split(',');
		var x1 = parseInt(rectangle[0], 10);
		var x2 = parseInt(rectangle[2], 10);
		var y1 = parseInt(rectangle[1], 10);
		var y2 = parseInt(rectangle[3], 10);

		width = (x1 < 0) ? (x1 * -1) + x2 : x2 - x1;
		height = (y1 < 0) ? (y1 * -1) + y2 : y2 - y1;
	} else {
		width = 200;
		height = 0;
	}

	obj.width = width;
	obj.height = height + 16;

	obj.SetControllerVisible(true);
	obj.Play();
}

var inAutocomplete = false;
var lastKeyEntered = '';

/**
* Check event key
*/
function flazyCheckKey(event) {
	'use strict';

	// Keycode is array down or up?
	if (event.keyCode && (event.keyCode === 40 || event.keyCode === 38)) {
		inAutocomplete = true;
	}

	// Make sure we are not within an "autocompletion" field
	if (inAutocomplete) {
		// If return pressed and key changed we reset the autocompletion
		if (!lastKeyEntered || lastKeyEntered === event.which) {
			inAutocomplete = false;
			return true;
		}
	}

	// Keycode is not return, then return. ;)
	if (event.which !== 13) {
		lastKeyEntered = event.which;
		return true;
	}

	return false;
}

/**
* Apply onkeypress event for forcing default submit button on ENTER key press
*/
jQuery(function($) {
	'use strict';

	$('form input[type=text], form input[type=password]').on('keypress', function (e) {
		var defaultButton = $(this).parents('form').find('input[type=submit].default-submit-action');

		if (!defaultButton || defaultButton.length <= 0) {
			return true;
		}

		if (flazyCheckKey(e)) {
			return true;
		}

		if ((e.which && e.which === 13) || (e.keyCode && e.keyCode === 13)) {
			defaultButton.click();
			return false;
		}

		return true;
	});
});

/**
* Functions for user search popup
*/
function insertUser(formId, value)
{
	'use strict';

	var $form = jQuery(formId),
		formName = $form.attr('data-form-name'),
		fieldName = $form.attr('data-field-name'),
		item = opener.document.forms[formName][fieldName];

	if (item.value.length && item.type == 'textarea') {
		value = item.value + '\n' + value;
	}

	item.value = value;
}

function insert_marked_users(formId, users) {
	'use strict';

	for (var i = 0; i < users.length; i++) {
		if (users[i].checked) {
			insertUser(formId, users[i].value);
		}
	}

	window.close();
}

function insert_single_user(formId, user) {
	'use strict';

	insertUser(formId, user);
	window.close();
}

/**
* Parse document block
*/
function parseDocument($container) {
	'use strict';

	var test = document.createElement('div'),
		oldBrowser = (typeof test.style.borderRadius == 'undefined'),
		$body = $('body');

	/**
	* Reset avatar dimensions when changing URL or EMAIL
	*/
	$container.find('input[data-reset-on-edit]').on('keyup', function() {
		$(this.getAttribute('data-reset-on-edit')).val('');
	});

	/**
	* Pagination
	*/
	$container.find('.pagination .page-jump-form :button').click(function() {
		var $input = $(this).siblings('input.inputbox');
		pageJump($input);
	});

	$container.find('.pagination .page-jump-form input.inputbox').on('keypress', function(event) {
		if (event.which === 13 || event.keyCode === 13) {
			event.preventDefault();
			pageJump($(this));
		}
	});

	$container.find('.pagination .dropdown-trigger').click(function() {
		var $dropdownContainer = $(this).parent();
		// Wait a little bit to make sure the dropdown has activated
		setTimeout(function() {
			if ($dropdownContainer.hasClass('dropdown-visible')) {
				$dropdownContainer.find('input.inputbox').focus();
			}
		}, 100);
	});

	/**
	* Adjust HTML code for IE8 and older versions
	*/
	if (oldBrowser) {
		// Fix .linklist.bulletin lists
		$container.find('ul.linklist.bulletin > li:first-child, ul.linklist.bulletin > li.rightside:last-child').addClass('no-bulletin');
	}

	/**
	* Resize navigation (breadcrumbs) block to keep all links on same line
	*/
	$container.find('.navlinks').each(function() {
		var $this = $(this),
			$left = $this.children().not('.rightside'),
			$right = $this.children('.rightside');

		if ($left.length !== 1 || !$right.length) {
			return;
		}

		function resize() {
			var width = 0,
				diff = $left.outerWidth(true) - $left.width(),
				minWidth = Math.max($this.width() / 3, 240),
				maxWidth;

			$right.each(function() {
				var $this = $(this);
				if ($this.is(':visible')) {
					width += $this.outerWidth(true);
				}
			});

			maxWidth = $this.width() - width - diff;
			$left.css('max-width', Math.floor(Math.max(maxWidth, minWidth)) + 'px');
		}

		resize();
		$(window).resize(resize);
	});

	/**
	* Makes breadcrumbs responsive
	*/
	$container.find('.breadcrumbs:not([data-skip-responsive])').each(function() {
		var $this = $(this),
			$links = $this.find('.crumb'),
			length = $links.length,
			classes = ['wrapped-max', 'wrapped-wide', 'wrapped-medium', 'wrapped-small', 'wrapped-tiny'],
			classesLength = classes.length,
			maxHeight = 0,
			lastWidth = false,
			wrapped = false;

		// Set tooltips
		$this.find('a').each(function() {
			var $link = $(this);
			$link.attr('title', $link.text());
		});

		// Function that checks breadcrumbs
		function check() {
			var height = $this.height(),
				width;

			// Test max-width set in code for .navlinks above
			width = parseInt($this.css('max-width'));
			if (!width) {
 				width = $body.width();
			}

			maxHeight = parseInt($this.css('line-height'));
			$links.each(function() {
				if ($(this).height() > 0) {
					maxHeight = Math.max(maxHeight, $(this).outerHeight(true));
				}
			});

			if (height <= maxHeight) {
				if (!wrapped || lastWidth === false || lastWidth >= width) {
					return;
				}
			}
			lastWidth = width;

			if (wrapped) {
				$this.removeClass('wrapped').find('.crumb.wrapped').removeClass('wrapped ' + classes.join(' '));
				if ($this.height() <= maxHeight) {
					return;
				}
			}

			wrapped = true;
			$this.addClass('wrapped');
			if ($this.height() <= maxHeight) {
				return;
			}

			for (var i = 0; i < classesLength; i ++) {
				for (var j = length - 1; j >= 0; j --) {
					$links.eq(j).addClass('wrapped ' + classes[i]);
					if ($this.height() <= maxHeight) {
						return;
					}
				}
			}
		}

		// Run function and set event
		check();
		$(window).resize(check);
	});

	/**
	* Responsive link lists
	*/
	$container.find('.linklist:not(.navlinks, [data-skip-responsive]), .postbody .post-buttons:not([data-skip-responsive])').each(function() {
		var $this = $(this),
			filterSkip = '.breadcrumbs, [data-skip-responsive]',
			filterLast = '.edit-icon, .quote-icon, [data-last-responsive]',
			$linksAll = $this.children(),
			$linksNotSkip = $linksAll.not(filterSkip), // All items that can potentially be hidden
			$linksFirst = $linksNotSkip.not(filterLast), // The items that will be hidden first
			$linksLast = $linksNotSkip.filter(filterLast), // The items that will be hidden last
			persistent = $this.attr('id') == 'nav-main', // Does this list already have a menu (such as quick-links)?
			html = '<li class="responsive-menu hidden"><a href="javascript:void(0);" class="responsive-menu-link">&nbsp;</a><div class="dropdown hidden"><div class="pointer"><div class="pointer-inner" /></div><ul class="dropdown-contents" /></div></li>',
			slack = 3; // Vertical slack space (in pixels). Determines how sensitive the script is in determining whether a line-break has occured.

		// Add a hidden drop-down menu to each links list (except those that already have one)
		if (!persistent) {
			if ($linksNotSkip.is('.rightside')) {
				$linksNotSkip.filter('.rightside:first').before(html);
				$this.children('.responsive-menu').addClass('rightside');
			} else {
				$this.append(html);
			}
		}

		// Set some object references and initial states
		var $menu = $this.children('.responsive-menu'),
			$menuContents = $menu.find('.dropdown-contents'),
			persistentContent = $menuContents.find('li:not(.separator)').length,
			lastWidth = false,
			compact = false,
			responsive1 = false,
			responsive2 = false,
			copied1 = false,
			copied2 = false,
			maxHeight = 0;

		// Find the tallest element in the list (we assume that all elements are roughly the same height)
		$linksAll.each(function() {
			if (!$(this).height()) {
				return;
			}
			maxHeight = Math.max(maxHeight, $(this).outerHeight(true));
		});
		if (maxHeight < 1) {
			return; // Shouldn't be possible, but just in case, abort
		} else {
			maxHeight = maxHeight + slack;
		}

		function check() {
			var width = $body.width();
			// We can't make it any smaller than this, so just skip
			if (responsive2 && compact && (width <= lastWidth)) {
				return;
			}
			lastWidth = width;

			// Reset responsive and compact layout
			if (responsive1 || responsive2) {
				$linksNotSkip.removeClass('hidden');
				$menuContents.children('.clone').addClass('hidden');
				responsive1 = responsive2 = false;
			}
			if (compact) {
				$this.removeClass('compact');
				compact = false;
			}

			// Unhide the quick-links menu if it has "persistent" content
			if (persistent && persistentContent) {
				$menu.removeClass('hidden');
			} else {
				$menu.addClass('hidden');
			}

			// Nothing to resize if block's height is not bigger than tallest element's height
			if ($this.height() <= maxHeight) {
				return;
			}

			// STEP 1: Compact
			if (!compact) {
				$this.addClass('compact');
				compact = true;
			}
			if ($this.height() <= maxHeight) {
				return;
			}

			// STEP 2: First responsive set - compact
			if (compact) {
				$this.removeClass('compact');
				compact = false;
			}
			// Copy the list items to the dropdown
			if (!copied1) {
				var $clones1 = $linksFirst.clone();
				$menuContents.prepend($clones1.addClass('clone clone-first').removeClass('leftside rightside'));

				if ($this.hasClass('post-buttons')) {
					$('.button', $menuContents).removeClass('button icon-button');
					$('.responsive-menu-link', $menu).addClass('button icon-button').prepend('<span></span>');
				}
				copied1 = true;
			}
			if (!responsive1) {
				$linksFirst.addClass('hidden');
				responsive1 = true;
				$menuContents.children('.clone-first').removeClass('hidden');
				$menu.removeClass('hidden');
			}
			if ($this.height() <= maxHeight) {
				return;
			}

			// STEP 3: First responsive set + compact
			if (!compact) {
				$this.addClass('compact');
				compact = true;
			}
			if ($this.height() <= maxHeight) {
				return;
			}

			// STEP 4: Last responsive set - compact
			if (!$linksLast.length) {
				return; // No other links to hide, can't do more
			}
			if (compact) {
				$this.removeClass('compact');
				compact = false;
			}
			// Copy the list items to the dropdown
			if (!copied2) {
				var $clones2 = $linksLast.clone();
				$menuContents.prepend($clones2.addClass('clone clone-last').removeClass('leftside rightside'));
				copied2 = true;
			}
			if (!responsive2) {
				$linksLast.addClass('hidden');
				responsive2 = true;
				$menuContents.children('.clone-last').removeClass('hidden');
			}
			if ($this.height() <= maxHeight) {
				return;
			}

			// STEP 5: Last responsive set + compact
			if (!compact) {
				$this.addClass('compact');
				compact = true;
			}
		}

		if (!persistent) {
			flazy.registerDropdown($menu.find('a.responsive-menu-link'), $menu.find('.dropdown'), false);
		}

		// If there are any images in the links list, run the check again after they have loaded
		$linksAll.find('img').each(function() {
			$(this).load(function() {
				check();
			});
		});

		check();
		$(window).resize(check);
	});

	/**
	* Do not run functions below for old browsers
	*/
	if (oldBrowser) {
		return;
	}

	/**
	* Adjust topiclist lists with check boxes
	*/
	$container.find('ul.topiclist dd.mark').siblings('dt').children('.list-inner').addClass('with-mark');

	/**
	* Appends contents of all extra columns to first column in
	* .topiclist lists for mobile devices. Copies contents as is.
	*
	* To add that functionality to .topiclist list simply add
	* responsive-show-all to list of classes
	*/
	$container.find('.topiclist.responsive-show-all > li > dl').each(function() {
		var $this = $(this),
			$block = $this.find('dt .responsive-show:last-child'),
			first = true;

		// Create block that is visible only on mobile devices
		if (!$block.length) {
			$this.find('dt > .list-inner').append('<div class="responsive-show" style="display:none;" />');
			$block = $this.find('dt .responsive-show:last-child');
		} else {
			first = ($.trim($block.text()).length === 0);
		}

		// Copy contents of each column
		$this.find('dd').not('.mark').each(function() {
			var column = $(this),
				$children = column.children(),
				html = column.html();

			if ($children.length == 1 && $children.text() == column.text()) {
				html = $children.html();
			}

			$block.append((first ? '' : '<br />') + html);

			first = false;
		});
	});

	/**
	* Same as above, but prepends text from header to each
	* column before contents of that column.
	*
	* To add that functionality to .topiclist list simply add
	* responsive-show-columns to list of classes
	*/
	$container.find('.topiclist.responsive-show-columns').each(function() {
		var $list = $(this),
			headers = [],
			headersLength = 0;

		// Find all headers, get contents
		$list.prev('.topiclist').find('li.header dd').not('.mark').each(function() {
			headers.push($(this).text());
			headersLength++;
		});

		if (!headersLength) {
			return;
		}

		// Parse each row
		$list.find('dl').each(function() {
			var $this = $(this),
				$block = $this.find('dt .responsive-show:last-child'),
				first = true;

			// Create block that is visible only on mobile devices
			if (!$block.length) {
				$this.find('dt > .list-inner').append('<div class="responsive-show" style="display:none;" />');
				$block = $this.find('dt .responsive-show:last-child');
			}
			else {
				first = ($.trim($block.text()).length === 0);
			}

			// Copy contents of each column
			$this.find('dd').not('.mark').each(function(i) {
				var column = $(this),
					children = column.children(),
					html = column.html();

				if (children.length == 1 && children.text() == column.text()) {
					html = children.html();
				}

				// Prepend contents of matching header before contents of column
				if (i < headersLength) {
					html = headers[i] + ': <strong>' + html + '</strong>';
				}

				$block.append((first ? '' : '<br />') + html);

				first = false;
			});
		});
	});

	/**
	* Responsive tables
	*/
	$container.find('table.table1').not('.not-responsive').each(function() {
		var $this = $(this),
			$th = $this.find('thead > tr > th'),
			headers = [],
			totalHeaders = 0,
			i, headersLength;

		// Find each header
		$th.each(function(column) {
			var cell = $(this),
				colspan = parseInt(cell.attr('colspan')),
				dfn = cell.attr('data-dfn'),
				text = dfn ? dfn : cell.text();

			colspan = isNaN(colspan) || colspan < 1 ? 1 : colspan;

			for (i = 0; i < colspan; i++) {
				headers.push(text);
			}
			totalHeaders++;

			if (dfn && !column) {
				$this.addClass('show-header');
			}
		});

		headersLength = headers.length;

		// Add header text to each cell as <dfn>
		$this.addClass('responsive');

		if (totalHeaders < 2) {
			$this.addClass('show-header');
			return;
		}

		$this.find('tbody > tr').each(function() {
			var row = $(this),
				cells = row.children('td'),
				column = 0;

			if (cells.length == 1) {
				row.addClass('big-column');
				return;
			}

			cells.each(function() {
				var cell = $(this),
					colspan = parseInt(cell.attr('colspan')),
					text = $.trim(cell.text());

				if (headersLength <= column) {
					return;
				}

				if ((text.length && text !== '-') || cell.children().length) {
					cell.prepend('<dfn style="display: none;">' + headers[column] + '</dfn>');
				} else {
					cell.addClass('empty');
				}

				colspan = isNaN(colspan) || colspan < 1 ? 1 : colspan;
				column += colspan;
			});
		});
	});

	/**
	* Hide empty responsive tables
	*/
	$container.find('table.responsive > tbody').not('.responsive-skip-empty').each(function() {
		var $items = $(this).children('tr');
		if (!$items.length) {
			$(this).parent('table:first').addClass('responsive-hide');
		}
	});

	/**
	* Responsive tabs
	*/
	$container.find('#tabs, #minitabs').not('[data-skip-responsive]').each(function() {
		var $this = $(this),
			$ul = $this.children(),
			$tabs = $ul.children().not('[data-skip-responsive]'),
			$links = $tabs.children('a'),
			$item = $ul.append('<li class="tab responsive-tab" style="display:none;"><a href="javascript:void(0);" class="responsive-tab-link">&nbsp;</a><div class="dropdown tab-dropdown" style="display: none;"><div class="pointer"><div class="pointer-inner" /></div><ul class="dropdown-contents" /></div></li>').find('li.responsive-tab'),
			$menu = $item.find('.dropdown-contents'),
			maxHeight = 0,
			lastWidth = false,
			responsive = false;

		$links.each(function() {
			var $this = $(this);
			maxHeight = Math.max(maxHeight, Math.max($this.outerHeight(true), $this.parent().outerHeight(true)));
		});

		function check() {
			var width = $body.width(),
				height = $this.height();

			if (!arguments.length && (!responsive || width <= lastWidth) && height <= maxHeight) {
				return;
			}

			$tabs.show();
			$item.hide();

			lastWidth = width;
			height = $this.height();
			if (height <= maxHeight) {
				if ($item.hasClass('dropdown-visible')) {
					flazy.toggleDropdown.call($item.find('a.responsive-tab-link').get(0));
				}
				return;
			}

			responsive = true;
			$item.show();
			$menu.html('');

			var $availableTabs = $tabs.filter(':not(.activetab, .responsive-tab)'),
				total = $availableTabs.length,
				i, $tab;

			for (i = total - 1; i >= 0; i --) {
				$tab = $availableTabs.eq(i);
				$menu.prepend($tab.clone(true).removeClass('tab'));
				$tab.hide();
				if ($this.height() <= maxHeight) {
					$menu.find('a').click(function() { check(true); });
					return;
				}
			}
			$menu.find('a').click(function() { check(true); });
		}

		flazy.registerDropdown($item.find('a.responsive-tab-link'), $item.find('.dropdown'), {visibleClass: 'activetab'});

		check(true);
		$(window).resize(check);
	});

	/**
	 * Hide UCP/MCP navigation if there is only 1 item
	 */
	$container.find('#navigation').each(function() {
		var $items = $(this).children('ol, ul').children('li');
		if ($items.length === 1) {
			$(this).addClass('responsive-hide');
		}
	});

	/**
	* Replace responsive text
	*/
	$container.find('[data-responsive-text]').each(function() {
		var $this = $(this),
			fullText = $this.text(),
			responsiveText = $this.attr('data-responsive-text'),
			responsive = false;

		function check() {
			if ($(window).width() > 700) {
				if (!responsive) {
					return;
				}
				$this.text(fullText);
				responsive = false;
				return;
			}
			if (responsive) {
				return;
			}
			$this.text(responsiveText);
			responsive = true;
		}

		check();
		$(window).resize(check);
	});
}

/**
* Run onload functions
*/
jQuery(function($) {
	'use strict';

	// Swap .nojs and .hasjs
	$('#flazy.nojs').toggleClass('nojs hasjs');
	$('#flazy').toggleClass('hastouch', flazy.isTouch);
	$('#flazy.hastouch').removeClass('notouch');

	// Focus forms
	$('form[data-focus]:first').each(function() {
		$('#' + this.getAttribute('data-focus')).focus();
	});

	parseDocument($('body'));
});
/* global bbfontstyle */

var flazy = {};
flazy.alertTime = 100;

(function($) {  // Avoid conflicts with other libraries

'use strict';

// define a couple constants for keydown functions.
var keymap = {
	TAB: 9,
	ENTER: 13,
	ESC: 27
};

var $dark = $('#darkenwrapper');
var $loadingIndicator;
var flazyAlertTimer = null;

flazy.isTouch = (window && typeof window.ontouchstart !== 'undefined');

/**
 * Display a loading screen
 *
 * @returns {object} Returns loadingIndicator.
 */
flazy.loadingIndicator = function() {
	if (!$loadingIndicator) {
		$loadingIndicator = $('<div />', { id: 'loading_indicator' });
		$loadingIndicator.appendTo('#page-footer');
	}

	if (!$loadingIndicator.is(':visible')) {
		$loadingIndicator.fadeIn(flazy.alertTime);
		// Wait fifteen seconds and display an error if nothing has been returned by then.
		flazy.clearLoadingTimeout();
		flazyAlertTimer = setTimeout(function() {
			var $alert = $('#flazy_alert');

			if ($loadingIndicator.is(':visible')) {
				flazy.alert($alert.attr('data-l-err'), $alert.attr('data-l-timeout-processing-req'));
			}
		}, 15000);
	}

	return $loadingIndicator;
};

/**
 * Clear loading alert timeout
*/
flazy.clearLoadingTimeout = function() {
	if (flazyAlertTimer !== null) {
		clearTimeout(flazyAlertTimer);
		flazyAlertTimer = null;
	}
};


/**
* Close popup alert after a specified delay
*
* @param {int} delay Delay in ms until darkenwrapper's click event is triggered
*/
flazy.closeDarkenWrapper = function(delay) {
	flazyAlertTimer = setTimeout(function() {
		$('#darkenwrapper').trigger('click');
	}, delay);
};

/**
 * Display a simple alert similar to JSs native alert().
 *
 * You can only call one alert or confirm box at any one time.
 *
 * @param {string} title Title of the message, eg "Information" (HTML).
 * @param {string} msg Message to display (HTML).
 *
 * @returns {object} Returns the div created.
 */
flazy.alert = function(title, msg) {
	var $alert = $('#flazy_alert');
	$alert.find('.alert_title').html(title);
	$alert.find('.alert_text').html(msg);

	$(document).on('keydown.flazy.alert', function(e) {
		if (e.keyCode === keymap.ENTER || e.keyCode === keymap.ESC) {
			flazy.alert.close($alert, true);
			e.preventDefault();
			e.stopPropagation();
		}
	});
	flazy.alert.open($alert);

	return $alert;
};



/**
* Handler for opening an alert box.
*
* @param {jQuery} $alert jQuery object.
*/
flazy.alert.open = function($alert) {
	if (!$dark.is(':visible')) {
		$dark.fadeIn(flazy.alertTime);
	}

	if ($loadingIndicator && $loadingIndicator.is(':visible')) {
		$loadingIndicator.fadeOut(flazy.alertTime, function() {
			$dark.append($alert);
			$alert.fadeIn(flazy.alertTime);
		});
	} else if ($dark.is(':visible')) {
		$dark.append($alert);
		$alert.fadeIn(flazy.alertTime);
	} else {
		$dark.append($alert);
		$alert.show();
		$dark.fadeIn(flazy.alertTime);
	}

	$alert.on('click', function(e) {
		e.stopPropagation();
	});

	$dark.one('click', function(e) {
		flazy.alert.close($alert, true);
		e.preventDefault();
		e.stopPropagation();
	});

	$alert.find('.alert_close').one('click', function(e) {
		flazy.alert.close($alert, true);
		e.preventDefault();
	});
};

/**
* Handler for closing an alert box.
*
* @param {jQuery} $alert jQuery object.
* @param {bool} fadedark Whether to remove dark background.
*/
flazy.alert.close = function($alert, fadedark) {
	var $fade = (fadedark) ? $dark : $alert;

	$fade.fadeOut(flazy.alertTime, function() {
		$alert.hide();
	});

	$alert.find('.alert_close').off('click');
	$(document).off('keydown.flazy.alert');
};

/**
 * Display a simple yes / no box to the user.
 *
 * You can only call one alert or confirm box at any one time.
 *
 * @param {string} msg Message to display (HTML).
 * @param {function} callback Callback. Bool param, whether the user pressed
 *     yes or no (or whatever their language is).
 * @param {bool} fadedark Remove the dark background when done? Defaults
 *     to yes.
 *
 * @returns {object} Returns the div created.
 */
flazy.confirm = function(msg, callback, fadedark) {
	var $confirmDiv = $('#flazy_confirm');
	$confirmDiv.find('.alert_text').html(msg);
	fadedark = fadedark || true;

	$(document).on('keydown.flazy.alert', function(e) {
		if (e.keyCode === keymap.ENTER || e.keyCode === keymap.ESC) {
			var name = (e.keyCode === keymap.ENTER) ? 'confirm' : 'cancel';

			$('input[name="' + name + '"]').trigger('click');
			e.preventDefault();
			e.stopPropagation();
		}
	});

	$confirmDiv.find('input[type="button"]').one('click.flazy.confirmbox', function(e) {
		var confirmed = this.name === 'confirm';

		if (confirmed) {
			callback(true);
		}
		$confirmDiv.find('input[type="button"]').off('click.flazy.confirmbox');
		flazy.alert.close($confirmDiv, fadedark || !confirmed);

		e.preventDefault();
		e.stopPropagation();
	});

	flazy.alert.open($confirmDiv);

	return $confirmDiv;
};

/**
 * Turn a querystring into an array.
 *
 * @argument {string} string The querystring to parse.
 * @returns {object} The object created.
 */
flazy.parseQuerystring = function(string) {
	var params = {}, i, split;

	string = string.split('&');
	for (i = 0; i < string.length; i++) {
		split = string[i].split('=');
		params[split[0]] = decodeURIComponent(split[1]);
	}
	return params;
};


/**
 * Makes a link use AJAX instead of loading an entire page.
 *
 * This function will work for links (both standard links and links which
 * invoke confirm_box) and forms. It will be called automatically for links
 * and forms with the data-ajax attribute set, and will call the necessary
 * callback.
 *
 * For more info, view the following page on the flazy wiki:
 * http://wiki.flazy.com/JavaScript_Function.flazy.ajaxify
 *
 * @param {object} options Options.
 */
flazy.ajaxify = function(options) {
	var $elements = $(options.selector),
		refresh = options.refresh,
		callback = options.callback,
		overlay = (typeof options.overlay !== 'undefined') ? options.overlay : true,
		isForm = $elements.is('form'),
		isText = $elements.is('input[type="text"], textarea'),
		eventName;

	if (isForm) {
		eventName = 'submit';
	} else if (isText) {
		eventName = 'keyup';
	} else {
		eventName = 'click';
	}

	$elements.on(eventName, function(event) {
		var action, method, data, submit, that = this, $this = $(this);

		if ($this.find('input[type="submit"][data-clicked]').attr('data-ajax') === 'false') {
			return;
		}

		/**
		 * Handler for AJAX errors
		 */
		function errorHandler(jqXHR, textStatus, errorThrown) {
			if (typeof console !== 'undefined' && console.log) {
				console.log('AJAX error. status: ' + textStatus + ', message: ' + errorThrown);
			}
			flazy.clearLoadingTimeout();
			var responseText, errorText = false;
			try {
				responseText = JSON.parse(jqXHR.responseText);
				responseText = responseText.message;
			} catch (e) {}
			if (typeof responseText === 'string' && responseText.length > 0) {
				errorText = responseText;
			} else if (typeof errorThrown === 'string' && errorThrown.length > 0) {
				errorText = errorThrown;
			} else {
				errorText = $dark.attr('data-ajax-error-text-' + textStatus);
				if (typeof errorText !== 'string' || !errorText.length) {
					errorText = $dark.attr('data-ajax-error-text');
				}
			}
			flazy.alert($dark.attr('data-ajax-error-title'), errorText);
		}

		/**
		 * This is a private function used to handle the callbacks, refreshes
		 * and alert. It calls the callback, refreshes the page if necessary, and
		 * displays an alert to the user and removes it after an amount of time.
		 *
		 * It cannot be called from outside this function, and is purely here to
		 * avoid repetition of code.
		 *
		 * @param {object} res The object sent back by the server.
		 */
		function returnHandler(res) {
			var alert;

			flazy.clearLoadingTimeout();

			// Is a confirmation required?
			if (typeof res.S_CONFIRM_ACTION === 'undefined') {
				// If a confirmation is not required, display an alert and call the
				// callbacks.
				if (typeof res.MESSAGE_TITLE !== 'undefined') {
					alert = flazy.alert(res.MESSAGE_TITLE, res.MESSAGE_TEXT);
				} else {
					$dark.fadeOut(flazy.alertTime);
				}

				if (typeof flazy.ajaxCallbacks[callback] === 'function') {
					flazy.ajaxCallbacks[callback].call(that, res);
				}

				// If the server says to refresh the page, check whether the page should
				// be refreshed and refresh page after specified time if required.
				if (res.REFRESH_DATA) {
					if (typeof refresh === 'function') {
						refresh = refresh(res.REFRESH_DATA.url);
					} else if (typeof refresh !== 'boolean') {
						refresh = false;
					}

					flazyAlertTimer = setTimeout(function() {
						if (refresh) {
							window.location = res.REFRESH_DATA.url;
						}

						// Hide the alert even if we refresh the page, in case the user
						// presses the back button.
						$dark.fadeOut(flazy.alertTime, function() {
							if (typeof alert !== 'undefined') {
								alert.hide();
							}
						});
					}, res.REFRESH_DATA.time * 1000); // Server specifies time in seconds
				}
			} else {
				// If confirmation is required, display a dialog to the user.
				flazy.confirm(res.MESSAGE_BODY, function(del) {
					if (!del) {
						return;
					}

					flazy.loadingIndicator();
					data =  $('<form>' + res.S_HIDDEN_FIELDS + '</form>').serialize();
					$.ajax({
						url: res.S_CONFIRM_ACTION,
						type: 'POST',
						data: data + '&confirm=' + res.YES_VALUE + '&' + $('form', '#flazy_confirm').serialize(),
						success: returnHandler,
						error: errorHandler
					});
				}, false);
			}
		}

		// If the element is a form, POST must be used and some extra data must
		// be taken from the form.
		var runFilter = (typeof options.filter === 'function');
		data = {};

		if (isForm) {
			action = $this.attr('action').replace('&amp;', '&');
			data = $this.serializeArray();
			method = $this.attr('method') || 'GET';

			if ($this.find('input[type="submit"][data-clicked]')) {
				submit = $this.find('input[type="submit"][data-clicked]');
				data.push({
					name: submit.attr('name'),
					value: submit.val()
				});
			}
		} else if (isText) {
			var name = $this.attr('data-name') || this.name;
			action = $this.attr('data-url').replace('&amp;', '&');
			data[name] = this.value;
			method = 'POST';
		} else {
			action = this.href;
			data = null;
			method = 'GET';
		}

		var sendRequest = function() {
			var dataOverlay = $this.attr('data-overlay');
			if (overlay && (typeof dataOverlay === 'undefined' || dataOverlay === 'true')) {
				flazy.loadingIndicator();
			}

			var request = $.ajax({
				url: action,
				type: method,
				data: data,
				success: returnHandler,
				error: errorHandler,
				cache: false
			});
			request.always(function() {
				$loadingIndicator.fadeOut(flazy.alertTime);
			});
		};

		// If filter function returns false, cancel the AJAX functionality,
		// and return true (meaning that the HTTP request will be sent normally).
		if (runFilter && !options.filter.call(this, data, event, sendRequest)) {
			return;
		}

		sendRequest();
		event.preventDefault();
	});

	if (isForm) {
		$elements.find('input:submit').click(function () {
			var $this = $(this);

			// Remove data-clicked attribute from any submit button of form
			$this.parents('form:first').find('input:submit[data-clicked]').removeAttr('data-clicked');

			$this.attr('data-clicked', 'true');
		});
	}

	return this;
};

flazy.search = {
	cache: {
		data: []
	},
	tpl: [],
	container: []
};

/**
 * Get cached search data.
 *
 * @param {string} id Search ID.
 * @returns {bool|object} Cached data object. Returns false if no data exists.
 */
flazy.search.cache.get = function(id) {
	if (this.data[id]) {
		return this.data[id];
	}
	return false;
};

/**
 * Set search cache data value.
 *
 * @param {string} id		Search ID.
 * @param {string} key		Data key.
 * @param {string} value	Data value.
 */
flazy.search.cache.set = function(id, key, value) {
	if (!this.data[id]) {
		this.data[id] = { results: [] };
	}
	this.data[id][key] = value;
};

/**
 * Cache search result.
 *
 * @param {string} id		Search ID.
 * @param {string} keyword	Keyword.
 * @param {Array} results	Search results.
 */
flazy.search.cache.setResults = function(id, keyword, results) {
	this.data[id].results[keyword] = results;
};

/**
 * Trim spaces from keyword and lower its case.
 *
 * @param {string} keyword Search keyword to clean.
 * @returns {string} Cleaned string.
 */
flazy.search.cleanKeyword = function(keyword) {
	return $.trim(keyword).toLowerCase();
};

/**
 * Get clean version of search keyword. If textarea supports several keywords
 * (one per line), it fetches the current keyword based on the caret position.
 *
 * @param {jQuery} $input	Search input|textarea.
 * @param {string} keyword	Input|textarea value.
 * @param {bool} multiline	Whether textarea supports multiple search keywords.
 *
 * @returns string Clean string.
 */
flazy.search.getKeyword = function($input, keyword, multiline) {
	if (multiline) {
		var line = flazy.search.getKeywordLine($input);
		keyword = keyword.split('\n').splice(line, 1);
	}
	return flazy.search.cleanKeyword(keyword);
};

/**
 * Get the textarea line number on which the keyword resides - for textareas
 * that support multiple keywords (one per line).
 *
 * @param {jQuery} $textarea Search textarea.
 * @returns {int} The line number.
 */
flazy.search.getKeywordLine = function ($textarea) {
	var selectionStart = $textarea.get(0).selectionStart;
	return $textarea.val().substr(0, selectionStart).split('\n').length - 1;
};

/**
 * Set the value on the input|textarea. If textarea supports multiple
 * keywords, only the active keyword is replaced.
 *
 * @param {jQuery} $input	Search input|textarea.
 * @param {string} value	Value to set.
 * @param {bool} multiline	Whether textarea supports multiple search keywords.
 */
flazy.search.setValue = function($input, value, multiline) {
	if (multiline) {
		var line = flazy.search.getKeywordLine($input),
			lines = $input.val().split('\n');
		lines[line] = value;
		value = lines.join('\n');
	}
	$input.val(value);
};

/**
 * Sets the onclick event to set the value on the input|textarea to the
 * selected search result.
 *
 * @param {jQuery} $input		Search input|textarea.
 * @param {object} value		Result object.
 * @param {jQuery} $row			Result element.
 * @param {jQuery} $container	jQuery object for the search container.
 */
flazy.search.setValueOnClick = function($input, value, $row, $container) {
	$row.click(function() {
		flazy.search.setValue($input, value.result, $input.attr('data-multiline'));
		$container.hide();
	});
};

/**
 * Runs before the AJAX search request is sent and determines whether
 * there is a need to contact the server. If there are cached results
 * already, those are displayed instead. Executes the AJAX request function
 * itself due to the need to use a timeout to limit the number of requests.
 *
 * @param {Array} data				Data to be sent to the server.
 * @param {object} event			Onkeyup event object.
 * @param {function} sendRequest	Function to execute AJAX request.
 *
 * @returns {bool} Returns false.
 */
flazy.search.filter = function(data, event, sendRequest) {
	var $this = $(this),
		dataName = ($this.attr('data-name') !== undefined) ? $this.attr('data-name') : $this.attr('name'),
		minLength = parseInt($this.attr('data-min-length'), 10),
		searchID = $this.attr('data-results'),
		keyword = flazy.search.getKeyword($this, data[dataName], $this.attr('data-multiline')),
		cache = flazy.search.cache.get(searchID),
		proceed = true;
	data[dataName] = keyword;

	if (cache.timeout) {
		clearTimeout(cache.timeout);
	}

	var timeout = setTimeout(function() {
		// Check min length and existence of cache.
		if (minLength > keyword.length) {
			proceed = false;
		} else if (cache.lastSearch) {
			// Has the keyword actually changed?
			if (cache.lastSearch === keyword) {
				proceed = false;
			} else {
				// Do we already have results for this?
				if (cache.results[keyword]) {
					var response = {
						keyword: keyword,
						results: cache.results[keyword]
					};
					flazy.search.handleResponse(response, $this, true);
					proceed = false;
				}

				// If the previous search didn't yield results and the string only had characters added to it,
				// then we won't bother sending a request.
				if (keyword.indexOf(cache.lastSearch) === 0 && cache.results[cache.lastSearch].length === 0) {
					flazy.search.cache.set(searchID, 'lastSearch', keyword);
					flazy.search.cache.setResults(searchID, keyword, []);
					proceed = false;
				}
			}
		}

		if (proceed) {
			sendRequest.call(this);
		}
	}, 350);
	flazy.search.cache.set(searchID, 'timeout', timeout);

	return false;
};

/**
 * Handle search result response.
 *
 * @param {object} res			Data received from server.
 * @param {jQuery} $input		Search input|textarea.
 * @param {bool} fromCache		Whether the results are from the cache.
 * @param {function} callback	Optional callback to run when assigning each search result.
 */
flazy.search.handleResponse = function(res, $input, fromCache, callback) {
	if (typeof res !== 'object') {
		return;
	}

	var searchID = $input.attr('data-results'),
		$container = $(searchID);

	if (this.cache.get(searchID).callback) {
		callback = this.cache.get(searchID).callback;
	} else if (typeof callback === 'function') {
		this.cache.set(searchID, 'callback', callback);
	}

	if (!fromCache) {
		this.cache.setResults(searchID, res.keyword, res.results);
	}

	this.cache.set(searchID, 'lastSearch', res.keyword);
	this.showResults(res.results, $input, $container, callback);
};

/**
 * Show search results.
 *
 * @param {Array} results		Search results.
 * @param {jQuery} $input		Search input|textarea.
 * @param {jQuery} $container	Search results container element.
 * @param {function} callback	Optional callback to run when assigning each search result.
 */
flazy.search.showResults = function(results, $input, $container, callback) {
	var $resultContainer = $('.search-results', $container);
	this.clearResults($resultContainer);

	if (!results.length) {
		$container.hide();
		return;
	}

	var searchID = $container.attr('id'),
		tpl,
		row;

	if (!this.tpl[searchID]) {
		tpl = $('.search-result-tpl', $container);
		this.tpl[searchID] = tpl.clone().removeClass('search-result-tpl');
		tpl.remove();
	}
	tpl = this.tpl[searchID];

	$.each(results, function(i, item) {
		row = tpl.clone();
		row.find('.search-result').html(item.display);

		if (typeof callback === 'function') {
			callback.call(this, $input, item, row, $container);
		}
		row.appendTo($resultContainer).show();
	});
	$container.show();
};

/**
 * Clear search results.
 *
 * @param {jQuery} $container Search results container.
 */
flazy.search.clearResults = function($container) {
	$container.children(':not(.search-result-tpl)').remove();
};

$('#flazy').click(function() {
	var $this = $(this);

	if (!$this.is('.live-search') && !$this.parents().is('.live-search')) {
		$('.live-search').hide();
	}
});

flazy.history = {};

/**
* Check whether a method in the native history object is supported.
*
* @param {string} fn Method name.
* @returns {bool} Returns true if the method is supported.
*/
flazy.history.isSupported = function(fn) {
	return !(typeof history === 'undefined' || typeof history[fn] === 'undefined');
};

/**
* Wrapper for the pushState and replaceState methods of the
* native history object.
*
* @param {string} mode		Mode. Either push or replace.
* @param {string} url		New URL.
* @param {string} [title]	Optional page title.
* @param {object} [obj]		Optional state object.
*/
flazy.history.alterUrl = function(mode, url, title, obj) {
	var fn = mode + 'State';

	if (!url || !flazy.history.isSupported(fn)) {
		return;
	}
	if (!title) {
		title = document.title;
	}
	if (!obj) {
		obj = null;
	}

	history[fn](obj, title, url);
};

/**
* Wrapper for the native history.replaceState method.
*
* @param {string} url		New URL.
* @param {string} [title]	Optional page title.
* @param {object} [obj]		Optional state object.
*/
flazy.history.replaceUrl = function(url, title, obj) {
	flazy.history.alterUrl('replace', url, title, obj);
};

/**
* Wrapper for the native history.pushState method.
*
* @param {string} url		New URL.
* @param {string} [title]	Optional page title.
* @param {object} [obj]		Optional state object.
*/
flazy.history.pushUrl = function(url, title, obj) {
	flazy.history.alterUrl('push', url, title, obj);
};

/**
* Hide the optgroups that are not the selected timezone
*
* @param {bool} keepSelection Shall we keep the value selected, or shall the
* 	user be forced to repick one.
*/
flazy.timezoneSwitchDate = function(keepSelection) {
	var $timezoneCopy = $('#timezone_copy');
	var $timezone = $('#timezone');
	var $tzDate = $('#tz_date');
	var $tzSelectDateSuggest = $('#tz_select_date_suggest');

	if ($timezoneCopy.length === 0) {
		// We make a backup of the original dropdown, so we can remove optgroups
		// instead of setting display to none, because IE and chrome will not
		// hide options inside of optgroups and selects via css
		$timezone.clone()
			.attr('id', 'timezone_copy')
			.css('display', 'none')
			.attr('name', 'tz_copy')
			.insertAfter('#timezone');
	} else {
		// Copy the content of our backup, so we can remove all unneeded options
		$timezone.html($timezoneCopy.html());
	}

	if ($tzDate.val() !== '') {
		$timezone.children('optgroup').remove(':not([data-tz-value="' + $tzDate.val() + '"])');
	}

	if ($tzDate.val() === $tzSelectDateSuggest.attr('data-suggested-tz')) {
		$tzSelectDateSuggest.css('display', 'none');
	} else {
		$tzSelectDateSuggest.css('display', 'inline');
	}

	var $tzOptions = $timezone.children('optgroup[data-tz-value="' + $tzDate.val() + '"]').children('option');

	if ($tzOptions.length === 1) {
		// If there is only one timezone for the selected date, we just select that automatically.
		$tzOptions.prop('selected', true);
		keepSelection = true;
	}

	if (typeof keepSelection !== 'undefined' && !keepSelection) {
		var $timezoneOptions = $timezone.find('optgroup option');
		if ($timezoneOptions.filter(':selected').length <= 0) {
			$timezoneOptions.filter(':first').prop('selected', true);
		}
	}
};

/**
* Display the date/time select
*/
flazy.timezoneEnableDateSelection = function() {
	$('#tz_select_date').css('display', 'block');
};

/**
* Preselect a date/time or suggest one, if it is not picked.
*
* @param {bool} forceSelector Shall we select the suggestion?
*/
flazy.timezonePreselectSelect = function(forceSelector) {

	// The offset returned here is in minutes and negated.
	var offset = (new Date()).getTimezoneOffset();
	var sign = '-';

	if (offset < 0) {
		sign = '+';
		offset = -offset;
	}

	var minutes = offset % 60;
	var hours = (offset - minutes) / 60;

	if (hours < 10) {
		hours = '0' + hours.toString();
	} else {
		hours = hours.toString();
	}

	if (minutes < 10) {
		minutes = '0' + minutes.toString();
	} else {
		minutes = minutes.toString();
	}

	var prefix = 'UTC' + sign + hours + ':' + minutes;
	var prefixLength = prefix.length;
	var selectorOptions = $('option', '#tz_date');
	var i;

	var $tzSelectDateSuggest = $('#tz_select_date_suggest');

	for (i = 0; i < selectorOptions.length; ++i) {
		var option = selectorOptions[i];

		if (option.value.substring(0, prefixLength) === prefix) {
			if ($('#tz_date').val() !== option.value && !forceSelector) {
				// We do not select the option for the user, but notify him,
				// that we would suggest a different setting.
				flazy.timezoneSwitchDate(true);
				$tzSelectDateSuggest.css('display', 'inline');
			} else {
				option.selected = true;
				flazy.timezoneSwitchDate(!forceSelector);
				$tzSelectDateSuggest.css('display', 'none');
			}

			var suggestion = $tzSelectDateSuggest.attr('data-l-suggestion');

			$tzSelectDateSuggest.attr('title', suggestion.replace('%s', option.innerHTML));
			$tzSelectDateSuggest.attr('value', suggestion.replace('%s', option.innerHTML.substring(0, 9)));
			$tzSelectDateSuggest.attr('data-suggested-tz', option.innerHTML);

			// Found the suggestion, there cannot be more, so return from here.
			return;
		}
	}
};

flazy.ajaxCallbacks = {};

/**
 * Adds an AJAX callback to be used by flazy.ajaxify.
 *
 * See the flazy.ajaxify comments for information on stuff like parameters.
 *
 * @param {string} id The name of the callback.
 * @param {function} callback The callback to be called.
 */
flazy.addAjaxCallback = function(id, callback) {
	if (typeof callback === 'function') {
		flazy.ajaxCallbacks[id] = callback;
	}
	return this;
};

/**
 * This callback handles live member searches.
 */
flazy.addAjaxCallback('member_search', function(res) {
	flazy.search.handleResponse(res, $(this), false, flazy.getFunctionByName('flazy.search.setValueOnClick'));
});

/**
 * This callback alternates text - it replaces the current text with the text in
 * the alt-text data attribute, and replaces the text in the attribute with the
 * current text so that the process can be repeated.
 */
flazy.addAjaxCallback('alt_text', function() {
	var $anchor,
		updateAll = $(this).data('update-all'),
		altText;

	if (updateAll !== undefined && updateAll.length) {
		$anchor = $(updateAll);
	} else {
		$anchor = $(this);
	}

	$anchor.each(function() {
		var $this = $(this);
		altText = $this.attr('data-alt-text');
		$this.attr('data-alt-text', $this.text());
		$this.attr('title', $.trim(altText));
		$this.text(altText);
	});
});

/**
 * This callback is based on the alt_text callback.
 *
 * It replaces the current text with the text in the alt-text data attribute,
 * and replaces the text in the attribute with the current text so that the
 * process can be repeated.
 * Additionally it replaces the class of the link's parent
 * and changes the link itself.
 */
flazy.addAjaxCallback('toggle_link', function() {
	var $anchor,
		updateAll = $(this).data('update-all') ,
		toggleText,
		toggleUrl,
		toggleClass;

	if (updateAll !== undefined && updateAll.length) {
		$anchor = $(updateAll);
	} else {
		$anchor = $(this);
	}

	$anchor.each(function() {
		var $this = $(this);

		// Toggle link text
		toggleText = $this.attr('data-toggle-text');
		$this.attr('data-toggle-text', $this.text());
		$this.attr('title', $.trim(toggleText));
		$this.text(toggleText);

		// Toggle link url
		toggleUrl = $this.attr('data-toggle-url');
		$this.attr('data-toggle-url', $this.attr('href'));
		$this.attr('href', toggleUrl);

		// Toggle class of link parent
		toggleClass = $this.attr('data-toggle-class');
		$this.attr('data-toggle-class', $this.parent().attr('class'));
		$this.parent().attr('class', toggleClass);
	});
});

/**
* Automatically resize textarea
*
* This function automatically resizes textarea elements when user
* types text.
*
* @param {jQuery} $items jQuery object(s) to resize
* @param {object} [options] Optional parameter that adjusts default
* 	configuration. See configuration variable
*
* Optional parameters:
*	minWindowHeight {number} Minimum browser window height when textareas are resized. Default = 500
*	minHeight {number} Minimum height of textarea. Default = 200
*	maxHeight {number} Maximum height of textarea. Default = 500
*	heightDiff {number} Minimum difference between window and textarea height. Default = 200
*	resizeCallback {function} Function to call after resizing textarea
*	resetCallback {function} Function to call when resize has been canceled

*		Callback function format: function(item) {}
*			this points to DOM object
*			item is a jQuery object, same as this
*/
flazy.resizeTextArea = function($items, options) {
	// Configuration
	var configuration = {
		minWindowHeight: 500,
		minHeight: 200,
		maxHeight: 500,
		heightDiff: 200,
		resizeCallback: function() {},
		resetCallback: function() {}
	};

	if (flazy.isTouch) {
		return;
	}

	if (arguments.length > 1) {
		configuration = $.extend(configuration, options);
	}

	function resetAutoResize(item) {
		var $item = $(item);
		if ($item.hasClass('auto-resized')) {
			$(item)
				.css({ height: '', resize: '' })
				.removeClass('auto-resized');
			configuration.resetCallback.call(item, $item);
		}
	}
$(document).ready(function(){
    $("#close_features").click(function(){
        $("#forum_features").hide();
    });
});

	function autoResize(item) {
		function setHeight(height) {
			height += parseInt($item.css('height'), 10) - $item.height();
			$item
				.css({ height: height + 'px', resize: 'none' })
				.addClass('auto-resized');
			configuration.resizeCallback.call(item, $item);
		}

		var windowHeight = $(window).height();

		if (windowHeight < configuration.minWindowHeight) {
			resetAutoResize(item);
			return;
		}

		var maxHeight = Math.min(
				Math.max(windowHeight - configuration.heightDiff, configuration.minHeight),
				configuration.maxHeight
			),
			$item = $(item),
			height = parseInt($item.height(), 10),
			scrollHeight = (item.scrollHeight) ? item.scrollHeight : 0;

		if (height < 0) {
			return;
		}

		if (height > maxHeight) {
			setHeight(maxHeight);
		} else if (scrollHeight > (height + 5)) {
			setHeight(Math.min(maxHeight, scrollHeight));
		}
	}

	$items.on('focus change keyup', function() {
		$(this).each(function() {
			autoResize(this);
		});
	}).change();

	$(window).resize(function() {
		$items.each(function() {
			if ($(this).hasClass('auto-resized')) {
				autoResize(this);
			}
		});
	});
};

/**
* Check if cursor in textarea is currently inside a bbcode tag
*
* @param {object} textarea Textarea DOM object
* @param {Array} startTags List of start tags to look for
*		For example, Array('[code]', '[code=')
* @param {Array} endTags List of end tags to look for
*		For example, Array('[/code]')
*
* @returns {boolean} True if cursor is in bbcode tag
*/
flazy.inBBCodeTag = function(textarea, startTags, endTags) {
	var start = textarea.selectionStart,
		lastEnd = -1,
		lastStart = -1,
		i, index, value;

	if (typeof start !== 'number') {
		return false;
	}

	value = textarea.value.toLowerCase();

	for (i = 0; i < startTags.length; i++) {
		var tagLength = startTags[i].length;
		if (start >= tagLength) {
			index = value.lastIndexOf(startTags[i], start - tagLength);
			lastStart = Math.max(lastStart, index);
		}
	}
	if (lastStart === -1) {
		return false;
	}

	if (start > 0) {
		for (i = 0; i < endTags.length; i++) {
			index = value.lastIndexOf(endTags[i], start - 1);
			lastEnd = Math.max(lastEnd, index);
		}
	}

	return (lastEnd < lastStart);
};


/**
* Adjust textarea to manage code bbcode
*
* This function allows to use tab characters when typing code
* and keeps indentation of previous line of code when adding new
* line while typing code.
*
* Editor's functionality is changed only when cursor is between
* [code] and [/code] bbcode tags.
*
* @param {object} textarea Textarea DOM object to apply editor to
*/
flazy.applyCodeEditor = function(textarea) {
	// list of allowed start and end bbcode code tags, in lower case
	var startTags = ['[code]', '[code='],
		startTagsEnd = ']',
		endTags = ['[/code]'];

	if (!textarea || typeof textarea.selectionStart !== 'number') {
		return;
	}

	if ($(textarea).data('code-editor') === true) {
		return;
	}

	function inTag() {
		return flazy.inBBCodeTag(textarea, startTags, endTags);
	}

	/**
	* Get line of text before cursor
	*
	* @param {boolean} stripCodeStart If true, only part of line
	*		after [code] tag will be returned.
	*
	* @returns {string} Line of text
	*/
	function getLastLine(stripCodeStart) {
		var start = textarea.selectionStart,
			value = textarea.value,
			index = value.lastIndexOf('\n', start - 1);

		value = value.substring(index + 1, start);

		if (stripCodeStart) {
			for (var i = 0; i < startTags.length; i++) {
				index = value.lastIndexOf(startTags[i]);
				if (index >= 0) {
					var tagLength = startTags[i].length;

					value = value.substring(index + tagLength);
					if (startTags[i].lastIndexOf(startTagsEnd) !== tagLength) {
						index = value.indexOf(startTagsEnd);

						if (index >= 0) {
							value = value.substr(index + 1);
						}
					}
				}
			}
		}

		return value;
	}

	/**
	* Append text at cursor position
	*
	* @param {string} text Text to append
	*/
	function appendText(text) {
		var start = textarea.selectionStart,
			end = textarea.selectionEnd,
			value = textarea.value;

		textarea.value = value.substr(0, start) + text + value.substr(end);
		textarea.selectionStart = textarea.selectionEnd = start + text.length;
	}

	$(textarea).data('code-editor', true).on('keydown', function(event) {
		var key = event.keyCode || event.which;

		// intercept tabs
		if (key === keymap.TAB	&&
			!event.ctrlKey		&&
			!event.shiftKey		&&
			!event.altKey		&&
			!event.metaKey) {
			if (inTag()) {
				appendText('\t');
				event.preventDefault();
				return;
			}
		}

		// intercept new line characters
		if (key === keymap.ENTER) {
			if (inTag()) {
				var lastLine = getLastLine(true),
					code = '' + /^\s*/g.exec(lastLine);

				if (code.length > 0) {
					appendText('\n' + code);
					event.preventDefault();
				}
			}
		}
	});
};

/**
 * Show drag and drop animation when textarea is present
 *
 * This function will enable the drag and drop animation for a specified
 * textarea.
 *
 * @param {HTMLElement} textarea Textarea DOM object to apply editor to
 */
flazy.showDragNDrop = function(textarea) {
	if (!textarea) {
		return;
	}

	$('body').on('dragenter dragover', function () {
		$(textarea).addClass('drag-n-drop');
	}).on('dragleave dragout dragend drop', function() {
		$(textarea).removeClass('drag-n-drop');
	});
	$(textarea).on('dragenter dragover', function () {
		$(textarea).addClass('drag-n-drop-highlight');
	}).on('dragleave dragout dragend drop', function() {
		$(textarea).removeClass('drag-n-drop-highlight');
	});
};

/**
* List of classes that toggle dropdown menu,
* list of classes that contain visible dropdown menu
*
* Add your own classes to strings with comma (probably you
* will never need to do that)
*/
flazy.dropdownHandles = '.dropdown-container.dropdown-visible .dropdown-toggle';
flazy.dropdownVisibleContainers = '.dropdown-container.dropdown-visible';

/**
* Dropdown toggle event handler
* This handler is used by flazy.registerDropdown() and other functions
*/
flazy.toggleDropdown = function() {
	var $this = $(this),
		options = $this.data('dropdown-options'),
		parent = options.parent,
		visible = parent.hasClass('dropdown-visible'),
		direction;

	if (!visible) {
		// Hide other dropdown menus
		$(flazy.dropdownHandles).each(flazy.toggleDropdown);

		// Figure out direction of dropdown
		direction = options.direction;
		var verticalDirection = options.verticalDirection,
			offset = $this.offset();

		if (direction === 'auto') {
			if (($(window).width() - $this.outerWidth(true)) / 2 > offset.left) {
				direction = 'right';
			} else {
				direction = 'left';
			}
		}
		parent.toggleClass(options.leftClass, direction === 'left')
			.toggleClass(options.rightClass, direction === 'right');

		if (verticalDirection === 'auto') {
			var height = $(window).height(),
				top = offset.top - $(window).scrollTop();

			verticalDirection = (top < height * 0.7) ? 'down' : 'up';
		}
		parent.toggleClass(options.upClass, verticalDirection === 'up')
			.toggleClass(options.downClass, verticalDirection === 'down');
	}

	options.dropdown.toggle();
	parent.toggleClass(options.visibleClass, !visible)
		.toggleClass('dropdown-visible', !visible);

	// Check dimensions when showing dropdown
	// !visible because variable shows state of dropdown before it was toggled
	if (!visible) {
		var windowWidth = $(window).width();

		options.dropdown.find('.dropdown-contents').each(function() {
			var $this = $(this);

			$this.css({
				marginLeft: 0,
				left: 0,
				maxWidth: (windowWidth - 4) + 'px'
			});

			var offset = $this.offset().left,
				width = $this.outerWidth(true);

			if (offset < 2) {
				$this.css('left', (2 - offset) + 'px');
			} else if ((offset + width + 2) > windowWidth) {
				$this.css('margin-left', (windowWidth - offset - width - 2) + 'px');
			}

			// Check whether the vertical scrollbar is present.
			$this.toggleClass('dropdown-nonscroll', this.scrollHeight === $this.innerHeight());

		});
		var freeSpace = parent.offset().left - 4;

		if (direction === 'left') {
			options.dropdown.css('margin-left', '-' + freeSpace + 'px');

			// Try to position the notification dropdown correctly in RTL-responsive mode
			if (options.dropdown.hasClass('dropdown-extended')) {
				var contentWidth,
					fullFreeSpace = freeSpace + parent.outerWidth();

				options.dropdown.find('.dropdown-contents').each(function() {
					contentWidth = parseInt($(this).outerWidth(), 10);
					$(this).css({ marginLeft: 0, left: 0 });
				});

				var maxOffset = Math.min(contentWidth, fullFreeSpace) + 'px';
				options.dropdown.css({
					width: maxOffset,
					marginLeft: -maxOffset
				});
			}
		} else {
			options.dropdown.css('margin-right', '-' + (windowWidth + freeSpace) + 'px');
		}
	}

	// Prevent event propagation
	if (arguments.length > 0) {
		try {
			var e = arguments[0];
			e.preventDefault();
			e.stopPropagation();
		} catch (error) { }
	}
	return false;
};

/**
* Toggle dropdown submenu
*/
flazy.toggleSubmenu = function(e) {
	$(this).siblings('.dropdown-submenu').toggle();
	e.preventDefault();
};

/**
* Register dropdown menu
* Shows/hides dropdown, decides which side to open to
*
* @param {jQuery} toggle Link that toggles dropdown.
* @param {jQuery} dropdown Dropdown menu.
* @param {Object} options List of options. Optional.
*/
flazy.registerDropdown = function(toggle, dropdown, options) {
	var ops = {
			parent: toggle.parent(), // Parent item to add classes to
			direction: 'auto', // Direction of dropdown menu. Possible values: auto, left, right
			verticalDirection: 'auto', // Vertical direction. Possible values: auto, up, down
			visibleClass: 'visible', // Class to add to parent item when dropdown is visible
			leftClass: 'dropdown-left', // Class to add to parent item when dropdown opens to left side
			rightClass: 'dropdown-right', // Class to add to parent item when dropdown opens to right side
			upClass: 'dropdown-up', // Class to add to parent item when dropdown opens above menu item
			downClass: 'dropdown-down' // Class to add to parent item when dropdown opens below menu item
		};
	if (options) {
		ops = $.extend(ops, options);
	}
	ops.dropdown = dropdown;

	ops.parent.addClass('dropdown-container');
	toggle.addClass('dropdown-toggle');

	toggle.data('dropdown-options', ops);

	toggle.click(flazy.toggleDropdown);
	$('.dropdown-toggle-submenu', ops.parent).click(flazy.toggleSubmenu);
};

/**
* Get the HTML for a color palette table.
*
* @param {string} dir Palette direction - either v or h
* @param {int} width Palette cell width.
* @param {int} height Palette cell height.
*/
flazy.colorPalette = function(dir, width, height) {
	var r, g, b,
		numberList = new Array(6),
		color = '',
		html = '';

	numberList[0] = '00';
	numberList[1] = '40';
	numberList[2] = '80';
	numberList[3] = 'BF';
	numberList[4] = 'FF';

	var tableClass = (dir === 'h') ? 'horizontal-palette' : 'vertical-palette';
	html += '<table class="not-responsive colour-palette ' + tableClass + '" style="width: auto;">';

	for (r = 0; r < 5; r++) {
		if (dir === 'h') {
			html += '<tr>';
		}

		for (g = 0; g < 5; g++) {
			if (dir === 'v') {
				html += '<tr>';
			}

			for (b = 0; b < 5; b++) {
				color = '' + numberList[r] + numberList[g] + numberList[b];
				html += '<td style="background-color: #' + color + '; width: ' + width + 'px; height: ' +
					height + 'px;"><a href="#" data-color="' + color + '" style="display: block; width: ' +
					width + 'px; height: ' + height + 'px; " alt="#' + color + '" title="#' + color + '"></a>';
				html += '</td>';
			}

			if (dir === 'v') {
				html += '</tr>';
			}
		}

		if (dir === 'h') {
			html += '</tr>';
		}
	}
	html += '</table>';
	return html;
};

/**
* Register a color palette.
*
* @param {jQuery} el jQuery object for the palette container.
*/
flazy.registerPalette = function(el) {
	var	orientation	= el.attr('data-orientation'),
		height		= el.attr('data-height'),
		width		= el.attr('data-width'),
		target		= el.attr('data-target'),
		bbcode		= el.attr('data-bbcode');

	// Insert the palette HTML into the container.
	el.html(flazy.colorPalette(orientation, width, height));

	// Add toggle control.
	$('#color_palette_toggle').click(function(e) {
		el.toggle();
		e.preventDefault();
	});

	// Attach event handler when a palette cell is clicked.
	$(el).on('click', 'a', function(e) {
		var color = $(this).attr('data-color');

		if (bbcode) {
			bbfontstyle('[color=#' + color + ']', '[/color]');
		} else {
			$(target).val(color);
		}
		e.preventDefault();
	});
};

/**
* Set display of page element
*
* @param {string} id The ID of the element to change
* @param {int} action Set to 0 if element display should be toggled, -1 for
*			hiding the element, and 1 for showing it.
* @param {string} type Display type that should be used, e.g. inline, block or
*			other CSS "display" types
*/
flazy.toggleDisplay = function(id, action, type) {
	if (!type) {
		type = 'block';
	}

	var $element = $('#' + id);

	var display = $element.css('display');
	if (!action) {
		action = (display === '' || display === type) ? -1 : 1;
	}
	$element.css('display', ((action === 1) ? type : 'none'));
};

/**
* Toggle additional settings based on the selected
* option of select element.
*
* @param {jQuery} el jQuery select element object.
*/
flazy.toggleSelectSettings = function(el) {
	el.children().each(function() {
		var $this = $(this),
			$setting = $($this.data('toggle-setting'));
		$setting.toggle($this.is(':selected'));
	});
};

/**
* Get function from name.
* Based on http://stackoverflow.com/a/359910
*
* @param {string} functionName Function to get.
* @returns function
*/
flazy.getFunctionByName = function (functionName) {
	var namespaces = functionName.split('.'),
		func = namespaces.pop(),
		context = window;

	for (var i = 0; i < namespaces.length; i++) {
		context = context[namespaces[i]];
	}
	return context[func];
};

/**
* Register page dropdowns.
*/
flazy.registerPageDropdowns = function() {
	var $body = $('body');

	$body.find('.dropdown-container').each(function() {
		var $this = $(this),
			$trigger = $this.find('.dropdown-trigger:first'),
			$contents = $this.find('.dropdown'),
			options = {
				direction: 'auto',
				verticalDirection: 'auto'
			},
			data;

		if (!$trigger.length) {
			data = $this.attr('data-dropdown-trigger');
			$trigger = data ? $this.children(data) : $this.children('a:first');
		}

		if (!$contents.length) {
			data = $this.attr('data-dropdown-contents');
			$contents = data ? $this.children(data) : $this.children('div:first');
		}

		if (!$trigger.length || !$contents.length) {
			return;
		}

		if ($this.hasClass('dropdown-up')) {
			options.verticalDirection = 'up';
		}
		if ($this.hasClass('dropdown-down')) {
			options.verticalDirection = 'down';
		}
		if ($this.hasClass('dropdown-left')) {
			options.direction = 'left';
		}
		if ($this.hasClass('dropdown-right')) {
			options.direction = 'right';
		}

		flazy.registerDropdown($trigger, $contents, options);
	});

	// Hide active dropdowns when click event happens outside
	$body.click(function(e) {
		var $parents = $(e.target).parents();
		if (!$parents.is(flazy.dropdownVisibleContainers)) {
			$(flazy.dropdownHandles).each(flazy.toggleDropdown);
		}
	});
};

/**
* Apply code editor to all textarea elements with data-bbcode attribute
*/
$(function() {
	$('textarea[data-bbcode]').each(function() {
		flazy.applyCodeEditor(this);
	});

	flazy.registerPageDropdowns();

	$('#color_palette_placeholder').each(function() {
		flazy.registerPalette($(this));
	});

	// Update browser history URL to point to specific post in viewtopic.php
	// when using view=unread#unread link.
	flazy.history.replaceUrl($('#unread[data-url]').data('url'));

	// Hide settings that are not selected via select element.
	$('select[data-togglable-settings]').each(function() {
		var $this = $(this);

		$this.change(function() {
			flazy.toggleSelectSettings($this);
		});
		flazy.toggleSelectSettings($this);
	});
});

})(jQuery); // Avoid conflicts with other libraries
/* global flazy */

(function($) {  // Avoid conflicts with other libraries

'use strict';

// This callback will mark all forum icons read
flazy.addAjaxCallback('mark_forums_read', function(res) {
	var readTitle = res.NO_UNREAD_POSTS;
	var unreadTitle = res.UNREAD_POSTS;
	var iconsArray = {
		'forum_unread': 'forum_read',
		'forum_unread_subforum': 'forum_read_subforum',
		'forum_unread_locked': 'forum_read_locked'
	};

	$('li.row').find('dl[class*="forum_unread"]').each(function() {
		var $this = $(this);

		$.each(iconsArray, function(unreadClass, readClass) {
			if ($this.hasClass(unreadClass)) {
				$this.removeClass(unreadClass).addClass(readClass);
			}
		});
		$this.children('dt[title="' + unreadTitle + '"]').attr('title', readTitle);
	});

	// Mark subforums read
	$('a.subforum[class*="unread"]').removeClass('unread').addClass('read');

	// Mark topics read if we are watching a category and showing active topics
	if ($('#active_topics').length) {
		flazy.ajaxCallbacks.mark_topics_read.call(this, res, false);
	}

	// Update mark forums read links
	$('[data-ajax="mark_forums_read"]').attr('href', res.U_MARK_FORUMS);

	flazy.closeDarkenWrapper(3000);
});

/** 
* This callback will mark all topic icons read
*
* @param update_topic_links bool Whether "Mark topics read" links should be
*     updated. Defaults to true.
*/
flazy.addAjaxCallback('mark_topics_read', function(res, updateTopicLinks) {
	var readTitle = res.NO_UNREAD_POSTS;
	var unreadTitle = res.UNREAD_POSTS;
	var iconsArray = {
		'global_unread': 'global_read',
		'announce_unread': 'announce_read',
		'sticky_unread': 'sticky_read',
		'topic_unread': 'topic_read'
	};
	var iconsState = ['', '_hot', '_hot_mine', '_locked', '_locked_mine', '_mine'];
	var unreadClassSelectors;
	var classMap = {};
	var classNames = [];

	if (typeof updateTopicLinks === 'undefined') {
		updateTopicLinks = true;
	}

	$.each(iconsArray, function(unreadClass, readClass) {
		$.each(iconsState, function(key, value) {
			// Only topics can be hot
			if ((value === '_hot' || value === '_hot_mine') && unreadClass !== 'topic_unread') {
				return true;
			}
			classMap[unreadClass + value] = readClass + value;
			classNames.push(unreadClass + value);
		});
	});

	unreadClassSelectors = '.' + classNames.join(',.');

	$('li.row').find(unreadClassSelectors).each(function() {
		var $this = $(this);
		$.each(classMap, function(unreadClass, readClass) {
			if ($this.hasClass(unreadClass)) {
				$this.removeClass(unreadClass).addClass(readClass);
			}
		});
		$this.children('dt[title="' + unreadTitle + '"]').attr('title', readTitle);
	});

	// Remove link to first unread post
	$('a').has('span.icon_topic_newest').remove();

	// Update mark topics read links
	if (updateTopicLinks) {
		$('[data-ajax="mark_topics_read"]').attr('href', res.U_MARK_TOPICS);
	}

	flazy.closeDarkenWrapper(3000);
});

// This callback will mark all notifications read
flazy.addAjaxCallback('notification.mark_all_read', function(res) {
	if (typeof res.success !== 'undefined') {
		flazy.markNotifications($('#notification_list li.bg2'), 0);
		flazy.closeDarkenWrapper(3000);
	}
});

// This callback will mark a notification read
flazy.addAjaxCallback('notification.mark_read', function(res) {
	if (typeof res.success !== 'undefined') {
		var unreadCount = Number($('#notification_list_button strong').html()) - 1;
		flazy.markNotifications($(this).parent('li.bg2'), unreadCount);
	}
});

/**
 * Mark notification popup rows as read.
 *
 * @param {jQuery} $popup jQuery object(s) to mark read.
 * @param {int} unreadCount The new unread notifications count.
 */
flazy.markNotifications = function($popup, unreadCount) {
	// Remove the unread status.
	$popup.removeClass('bg2');
	$popup.find('a.mark_read').remove();

	// Update the notification link to the real URL.
	$popup.each(function() {
		var link = $(this).find('a');
		link.attr('href', link.attr('data-real-url'));
	});

	// Update the unread count.
	$('strong', '#notification_list_button').html(unreadCount);
	// Remove the Mark all read link if there are no unread notifications.
	if (!unreadCount) {
		$('#mark_all_notifications').remove();
	}

	// Update page title
	$('title').text(
		(unreadCount ? '(' + unreadCount + ')' : '') + $('title').text().replace(/(\(([0-9])\))/, '')
	);
};

// This callback finds the post from the delete link, and removes it.
flazy.addAjaxCallback('post_delete', function() {
	var $this = $(this),
		postId;

	if ($this.attr('data-refresh') === undefined) {
		postId = $this[0].href.split('&p=')[1];
		var post = $this.parents('#p' + postId).css('pointer-events', 'none');
		if (post.hasClass('bg1') || post.hasClass('bg2')) {
			var posts1 = post.nextAll('.bg1');
			post.nextAll('.bg2').removeClass('bg2').addClass('bg1');
			posts1.removeClass('bg1').addClass('bg2');
		}
		post.fadeOut(function() {
			$(this).remove();
		});
	}
});

// This callback removes the approve / disapprove div or link.
flazy.addAjaxCallback('post_visibility', function(res) {
	var remove = (res.visible) ? $(this) : $(this).parents('.post');
	$(remove).css('pointer-events', 'none').fadeOut(function() {
		$(this).remove();
	});

	if (res.visible) {
		// Remove the "Deleted by" message from the post on restoring.
		remove.parents('.post').find('.post_deleted_msg').css('pointer-events', 'none').fadeOut(function() {
			$(this).remove();
		});
	}
});

// This removes the parent row of the link or form that fired the callback.
flazy.addAjaxCallback('row_delete', function() {
	$(this).parents('tr').remove();
});

// This handles friend / foe additions removals.
flazy.addAjaxCallback('zebra', function(res) {
	var zebra;

	if (res.success) {
		zebra = $('.zebra');
		zebra.first().html(res.MESSAGE_TEXT);
		zebra.not(':first').html('&nbsp;').prev().html('&nbsp;');
	}
});

/**
 * This callback updates the poll results after voting.
 */
flazy.addAjaxCallback('vote_poll', function(res) {
	if (typeof res.success !== 'undefined') {
		var poll = $('.topic_poll');
		var panel = poll.find('.panel');
		var resultsVisible = poll.find('dl:first-child .resultbar').is(':visible');
		var mostVotes = 0;

		// Set min-height to prevent the page from jumping when the content changes
		var updatePanelHeight = function (height) {
			var height = (typeof height === 'undefined') ? panel.find('.inner').outerHeight() : height;
			panel.css('min-height', height);
		};
		updatePanelHeight();

		// Remove the View results link
		if (!resultsVisible) {
			poll.find('.poll_view_results').hide(500);
		}

		if (!res.can_vote) {
			poll.find('.polls, .poll_max_votes, .poll_vote, .poll_option_select').fadeOut(500, function () {
				poll.find('.resultbar, .poll_option_percent, .poll_total_votes').show();
			});
		} else {
			// If the user can still vote, simply slide down the results
			poll.find('.resultbar, .poll_option_percent, .poll_total_votes').show(500);
		}
		
		// Get the votes count of the highest poll option
		poll.find('[data-poll-option-id]').each(function() {
			var option = $(this);
			var optionId = option.attr('data-poll-option-id');
			mostVotes = (res.vote_counts[optionId] >= mostVotes) ? res.vote_counts[optionId] : mostVotes;
		});

		// Update the total votes count
		poll.find('.poll_total_vote_cnt').html(res.total_votes);

		// Update each option
		poll.find('[data-poll-option-id]').each(function() {
			var $this = $(this);
			var optionId = $this.attr('data-poll-option-id');
			var voted = (typeof res.user_votes[optionId] !== 'undefined');
			var mostVoted = (res.vote_counts[optionId] === mostVotes);
			var percent = (!res.total_votes) ? 0 : Math.round((res.vote_counts[optionId] / res.total_votes) * 100);
			var percentRel = (mostVotes === 0) ? 0 : Math.round((res.vote_counts[optionId] / mostVotes) * 100);

			$this.toggleClass('voted', voted);
			$this.toggleClass('most-votes', mostVoted);

			// Update the bars
			var bar = $this.find('.resultbar div');
			var barTimeLapse = (res.can_vote) ? 500 : 1500;
			var newBarClass = (percent === 100) ? 'pollbar5' : 'pollbar' + (Math.floor(percent / 20) + 1);

			setTimeout(function () {
				bar.animate({width: percentRel + '%'}, 500)
					.removeClass('pollbar1 pollbar2 pollbar3 pollbar4 pollbar5')
					.addClass(newBarClass)
					.html(res.vote_counts[optionId]);

				var percentText = percent ? percent + '%' : res.NO_VOTES;
				$this.find('.poll_option_percent').html(percentText);
			}, barTimeLapse);
		});

		if (!res.can_vote) {
			poll.find('.polls').delay(400).fadeIn(500);
		}

		// Display "Your vote has been cast." message. Disappears after 5 seconds.
		var confirmationDelay = (res.can_vote) ? 300 : 900;
		poll.find('.vote-submitted').delay(confirmationDelay).slideDown(200, function() {
			if (resultsVisible) {
				updatePanelHeight();
			}

			$(this).delay(5000).fadeOut(500, function() {
				resizePanel(300);
			});
		});

		// Remove the gap resulting from removing options
		setTimeout(function() {
			resizePanel(500);
		}, 1500);

		var resizePanel = function (time) {
			var panelHeight = panel.height();
			var innerHeight = panel.find('.inner').outerHeight();

			if (panelHeight != innerHeight) {
				panel.css({'min-height': '', 'height': panelHeight})
					.animate({height: innerHeight}, time, function () {
						panel.css({'min-height': innerHeight, 'height': ''});
					});
			}
		};
	}
});

/**
 * Show poll results when clicking View results link.
 */
$('.poll_view_results a').click(function(e) {
	// Do not follow the link
	e.preventDefault();

	var $poll = $(this).parents('.topic_poll');

	$poll.find('.resultbar, .poll_option_percent, .poll_total_votes').show(500);
	$poll.find('.poll_view_results').hide(500);
});

$('[data-ajax]').each(function() {
	var $this = $(this);
	var ajax = $this.attr('data-ajax');
	var filter = $this.attr('data-filter');

	if (ajax !== 'false') {
		var fn = (ajax !== 'true') ? ajax : null;
		filter = (filter !== undefined) ? flazy.getFunctionByName(filter) : null;

		flazy.ajaxify({
			selector: this,
			refresh: $this.attr('data-refresh') !== undefined,
			filter: filter,
			callback: fn
		});
	}
});


/**
 * This simply appends #preview to the action of the
 * QR action when you click the Full Editor & Preview button
 */
$('#qr_full_editor').click(function() {
	$('#qr_postform').attr('action', function(i, val) {
		return val + '#preview';
	});
});


/**
 * Make the display post links to use JS
 */
$('.display_post').click(function(e) {
	// Do not follow the link
	e.preventDefault();

	var postId = $(this).attr('data-post-id');
	$('#post_content' + postId).show();
	$('#profile' + postId).show();
	$('#post_hidden' + postId).hide();
});

/**
* Toggle the member search panel in memberlist.php.
*
* If user returns to search page after viewing results the search panel is automatically displayed.
* In any case the link will toggle the display status of the search panel and link text will be
* appropriately changed based on the status of the search panel.
*/
$('#member_search').click(function () {
	var $memberlistSearch = $('#memberlist_search');

	$memberlistSearch.slideToggle('fast');
	flazy.ajaxCallbacks.alt_text.call(this);

	// Focus on the username textbox if it's available and displayed
	if ($memberlistSearch.is(':visible')) {
		$('#username').focus();
	}
	return false;
});

/**
* Automatically resize textarea
*/
$(function() {
	flazy.resizeTextArea($('textarea:not(#message-box textarea, .no-auto-resize)'), {minHeight: 75, maxHeight: 250});
	flazy.resizeTextArea($('#message-box textarea'));
});


})(jQuery); // Avoid conflicts with other libraries