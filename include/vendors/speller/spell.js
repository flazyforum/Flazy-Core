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
