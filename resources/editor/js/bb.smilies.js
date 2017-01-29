/**
 * Смайлы.
 *@license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */


function insert_text(open, close)
{
	var docOpener = window.opener.document;
	msgfield = docOpener.getElementsByName("req_message").item(0);

	open = ' '+open
	close = close+' '

	// IE
	if (docOpener.selection && docOpener.selection.createRange)
	{
		msgfield.focus();
		sel = docOpener.selection.createRange();
		sel.text = open + sel.text + close;
		msgfield.focus();
	}
	// Moz
	else if (msgfield.selectionStart || msgfield.selectionStart == '0')
	{
		var startPos = msgfield.selectionStart;
		var endPos = msgfield.selectionEnd;

		msgfield.value = msgfield.value.substring(0, startPos) + open + msgfield.value.substring(startPos, endPos)+close+msgfield.value.substring(endPos, msgfield.value.length);
		msgfield.selectionStart = msgfield.selectionEnd = endPos+open.length+close.length;
		msgfield.focus();
	}
	// Other
	else
	{
		msgfield.value += open+close;
		msgfield.focus();
	}
	window.close();
	return;
}