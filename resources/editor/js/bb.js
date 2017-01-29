/**
 * ББ-коды.
 *@license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */


var form_name = 'post';
var text_name = 'req_message';
var clientVer = parseInt(navigator.appVersion); // Get browser version
var ua = navigator.userAgent.toLowerCase();
var is_ie = (ua.indexOf('msie') != -1 && ua.indexOf('opera') == -1);
var is_safari = ua.indexOf('safari') != -1;
var is_gecko = (ua.indexOf('gecko') != -1 && !is_safari);
var is_win = ((ua.indexOf('win') != -1) || (ua.indexOf('16bit') != -1));
var baseHeight;
var speller = new Speller({url:"../../../include/vendors/speller"});

// Apply bbcodes. Code from phpBB
function bbcode(bbopen, bbclose)
{
	theSelection = false;
	var textarea = document.forms[form_name].elements[text_name];
	textarea.focus();

	if ((clientVer >= 4) && is_ie && is_win)
	{
		theSelection = document.selection.createRange().text;
		if (theSelection)
		{
			// Add tags around selection
			document.selection.createRange().text = bbopen+theSelection+bbclose;
			document.forms[form_name].elements[text_name].focus();
			theSelection = '';
			return;
		}
	}
	else if (document.forms[form_name].elements[text_name].selectionEnd && (document.forms[form_name].elements[text_name].selectionEnd - document.forms[form_name].elements[text_name].selectionStart > 0))
	{
		mozWrap(document.forms[form_name].elements[text_name], bbopen, bbclose);
		document.forms[form_name].elements[text_name].focus();
		theSelection = '';
		return;
	}
	//The new position for the cursor after adding the bbcode
	var caret_pos = getCaretPosition(textarea).start;
	var new_pos = caret_pos+bbopen.length;
	// Open tag
	insert(bbopen+bbclose);
	// Center the cursor when we don't have a selection
	if (!isNaN(textarea.selectionStart))
	{
		textarea.selectionStart = new_pos;
		textarea.selectionEnd = new_pos;
	}
	else if (document.selection)
	{
		var range = textarea.createTextRange();
		range.move("character", new_pos);
		range.select();
		storeCaret(textarea);
	}
	textarea.focus();
	return;
}
// Insert text at position. Code from phpBB
function insert(text, spaces, popup)
{
	var textarea;
	
	if (!popup)
		textarea = document.forms[form_name].elements[text_name];
	else
		textarea = opener.document.forms[form_name].elements[text_name];
	if (spaces)
		text = ' '+text+' ';
	if (!isNaN(textarea.selectionStart))
	{
		var sel_start = textarea.selectionStart;
		var sel_end = textarea.selectionEnd;
		mozWrap(textarea, text, '')
		textarea.selectionStart = sel_start+text.length;
		textarea.selectionEnd = sel_end+text.length;
	}
	else if (textarea.createTextRange && textarea.caretPos)
	{
		if (baseHeight != textarea.caretPos.boundingHeight)
		{
			textarea.focus();
			storeCaret(textarea);
		}
		var caret_pos = textarea.caretPos;
		caret_pos.text = caret_pos.text.charAt(caret_pos.text.length - 1) == ' ' ? caret_pos.text+text+' ' : caret_pos.text+text;
	}
	else
		textarea.value = textarea.value+text;
	if (!popup)
		textarea.focus();
}
function mozWrap(txtarea, open, close)
{
	var selLength = txtarea.textLength;
	var selStart = txtarea.selectionStart;
	var selEnd = txtarea.selectionEnd;
	var scrollTop = txtarea.scrollTop;

	if (selEnd == 1 || selEnd == 2)
		selEnd = selLength;

	var s1 = (txtarea.value).substring(0,selStart);
	var s2 = (txtarea.value).substring(selStart, selEnd)
	var s3 = (txtarea.value).substring(selEnd, selLength);

	txtarea.value = s1+open+s2+close+s3;
	txtarea.selectionStart = selEnd+open.length+close.length;
	txtarea.selectionEnd = txtarea.selectionStart;
	txtarea.focus();
	txtarea.scrollTop = scrollTop;
	return;
}
// Insert at Caret position.
function storeCaret(textEl)
{
	if (textEl.createTextRange)
		textEl.caretPos = document.selection.createRange().duplicate();
}
// Caret Position object.
function caretPosition()
{
	var start = null;
	var end = null;
}
// Get the caret position in an textarea.
function getCaretPosition(txtarea)
{
	var caretPos = new caretPosition();
	
	if(txtarea.selectionStart || txtarea.selectionStart == 0)
	{
		caretPos.start = txtarea.selectionStart;
		caretPos.end = txtarea.selectionEnd;
	}
	else if(document.selection)
	{
		var range = document.selection.createRange();
		var range_all = document.body.createTextRange();
		range_all.moveToElementText(txtarea);
		var sel_start;
		for (sel_start = 0; range_all.compareEndPoints('StartToStart', range) < 0; sel_start++)
			range_all.moveStart('character', 1);
	
		txtarea.sel_start = sel_start;
		caretPos.start = txtarea.sel_start;
		caretPos.end = txtarea.sel_start;
	}
	return caretPos;
}
function smile(code, popup)
{
	return insert(code, true, popup);
}
function smile_pop(desktopURL, alternateWidth, alternateHeight, noScrollbars)
{
	if ((alternateWidth && self.screen.availWidth * 0.8 < alternateWidth) || (alternateHeight && self.screen.availHeight * 0.8 < alternateHeight))
	{
		noScrollbars = false;
		alternateWidth = Math.min(alternateWidth, self.screen.availWidth * 0.8);
		alternateHeight = Math.min(alternateHeight, self.screen.availHeight * 0.8);
	}
	else
		noScrollbars = typeof(noScrollbars) != "undefined" && noScrollbars == true;

	window.open(desktopURL, 'requested_popup', 'toolbar=no,location=no,status=no,menubar=no,scrollbars='+(noScrollbars ? 'no' : 'yes')+',width='+(alternateWidth ? alternateWidth : 700)+',height='+(alternateHeight ? alternateHeight : 300)+',resizable=no');
	return false;
}
function visibility(id)
{
	var obj = document.getElementById(id);
	
	if (obj == null || typeof(obj) == "undefined")
		return;
	
	var current = obj.style.display;
	var change = {
		"none":{"display": "block"},
		"block":{"display": "none"}
	}
	obj.style.display = change[current]["display"];
	return;
}
function SelectedText()
{
	var txt = '';
	var textarea = document.forms[form_name].elements[text_name];
	if (document.selection)
		txt = document.selection.createRange().text;
	else if (document.getSelection)
		txt = textarea.value.substring(textarea.selectionStart, textarea.selectionEnd);
	else if (window.getSelection)
		txt = window.getSelection().toString();
	else
		return txt;
	return txt;
}
function tag(bbopen, bbclose, tag)
{
	var txt = SelectedText();
	if (txt != '')
		bbcode(bbopen, bbclose);
	else
		tag();
}
function tag_url()
{
	var enterURL = prompt("Link to web page", "http://");
	if (!enterURL)
	{
		alert("Error! Link isn't valid");
		return false;
	}
	var enterTITLE = prompt("Enter name to link", "Example text");
	if (!enterTITLE || enterTITLE == "Example text")
		insert('[url]'+enterURL+'[/url]');
	else
		insert('[url='+enterURL+']'+enterTITLE+'[/url]');	
}
function tag_email()
{
	var enter = prompt("Enter E-mail address.", "");
	if (!enter)
	{
		alert("Wrong E-mail'а");
		return false;
	}
	insert('[email]'+enter+'[/email]');
}
function tag_image()
{
	var image = prompt("Въведете пълния URL на изображението", "http://");
	if (!image)
	{
		alert("Грешка! Няма връзка");
		return false;
	}
	var desc = prompt("Въведете описание", "Описание");
	if (!desc || desc == "Описание")
		insert('[img]'+image+'[/img]');
	else
		insert('[img='+desc+']'+image+'[/img]');
}
function tag_video()
{
	var enter = prompt("Връзка към видео", "http://");
	if (!enter)
	{
		alert("Грешка! Няма връзка");
		return;
	}
	insert('[video]'+enter+'[/video]');
}
function tag_hide()
{
	var enter = prompt("Въведете минималните мнения, за да видите текста (0 — далеч от страна на гостите)", "");
	if (!enter)
		bbcode('[hide]','[/hide]');
	else
		bbcode('[hide='+enter+']','[/hide]');
}
function add_handler(event, handler)
{
	if (document.addEventListener)
		document.addEventListener(event, handler, false);
	else if (document.attachEvent)
		document.attachEvent('on'+event, handler);
	else
		return false;

	return true;
}
function key_handler(e)
{
	e = e || window.event;
	var key = e.keyCode || e.which;

	if (e.ctrlKey && (is_gecko && key == 115 || !is_gecko && key == 83))
	{
		if (e.preventDefault)
			e.preventDefault();
		e.returnValue = false;
		document.post.preview.click()
		return false;
	}
	if (e.ctrlKey && (key == 13 || key == 10))
	{
		if (e.preventDefault)
			e.preventDefault();
		e.returnValue = false;
		document.post.submit.click()
		return false;
	}
}
var result = is_ie || is_safari ? add_handler("keydown", key_handler) : add_handler("keypress", key_handler);
if (result)
{
	setTimeout("document.forms.post.submit.title='Ctrl + Enter'", 500);
	setTimeout("document.forms.post.preview.title='Ctrl + S'", 500);
}

function Speller(args) {
    args = args || new Object;
    this.url = args.url || ".";
    this.args = {
        defLang: args.lang, defOptions: args.options,
        spellDlg: args.spellDlg || { width: 440, height: 265 },
        optDlg: args.optDlg || { width: 330, height: 275 },
        userDicDlg: args.userDicDlg || { width: 270, height: 350 }
    };
}

Speller.IGNORE_UPPERCASE = 0x0001;
Speller.IGNORE_DIGITS    = 0x0002;
Speller.IGNORE_URLS      = 0x0004;
Speller.FIND_REPEAT      = 0x0008;
Speller.IGNORE_LATIN     = 0x0010;
Speller.FLAG_LATIN       = 0x0080;

Speller.prototype.check = function(ctrls) {
    this.showDialog(this.url + "/spelldlg.html", this.args.spellDlg, ctrls);
}

Speller.prototype.optionsDialog = function() {
    this.showDialog(this.url + "/spellopt.html", this.args.optDlg);
}

Speller.prototype.showDialog = function(url, size, ctrls) {
    var a = this.args;
    var args = { ctrls: ctrls, lang: a.lang, options: a.options,
        defLang: a.defLang, defOptions: a.defOptions,
        optDlg: a.optDlg, userDicDlg: a.userDicDlg };
    if (window.showModalDialog) {
        var features = "dialogWidth:" + size.width + "px;dialogHeight:" + size.height + "px;scroll:no;help:no;status:no";
        window.showModalDialog(url, args, features);
        a.lang = args.lang; a.options = args.options;
    }
    else {
        var name = url.replace(/[\/\.]/g, "");
        var features = "width=" + size.width + ",height=" + size.height + ",toolbar=no,status=no,menubar=no,directories=no,resizable=no";
        window.theDlgArgs = args;
        var dlg = window.open(url, name, features);
        dlg.onunload = function() {
            a.lang = args.lang; a.options = args.options;
        }
    }
}

function spellCheck() {
	speller.check([document.getElementById("text")]);
}
