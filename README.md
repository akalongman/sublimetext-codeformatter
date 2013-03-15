CodeFormatter
=============


CodeFormatter is a Sublime Text 2/3 plugin that supports format (beautify) source code.


CodeFormatter has support for the following languages:

* PHP - By PEAR PHP_Beautifier
* JavaScript/JSON/JSONP - By JSBeautifier
* HTML - By JSBeautifier
* CSS - By JSBeautifier

Installing
----------
**With the Package Control plugin:** The easiest way to install CodeFormatter is through Package Control, which can be found at this site: http://wbond.net/sublime_packages/package_control

Once you install Package Control, restart Sublime Text and bring up the Command Palette (`Command+Shift+P` on OS X, `Control+Shift+P` on Linux/Windows). Select "Package Control: Install Package", wait while Package Control fetches the latest package list, then select CodeFormatter when the list appears. The advantage of using this method is that Package Control will automatically keep CodeFormatter up to date with the latest version.

**Without Git:** Download the latest source from [GitHub](https://github.com/akalongman/sublimetext-codeformatter) and copy the CodeFormatter folder to your Sublime Text "Packages" directory.

**With Git:** Clone the repository in your Sublime Text "Packages" directory:

    git clone https://github.com/akalongman/sublimetext-codeformatter.git


The "Packages" directory is located at:

* OS X:

        ST2: ~/Library/Application Support/Sublime Text 2/Packages/
        ST3: ~/Library/Application Support/Sublime Text 3/Packages/

* Linux:

        ST2: ~/.config/sublime-text-2/Packages/
        ST3: ~/.config/sublime-text-3/Packages/

* Windows:

        ST2: %APPDATA%/Sublime Text 2/Packages/
        ST3: %APPDATA%/Sublime Text 3/Packages/


## Formatter-specific notes
Following are notes specific to individual formatters that you should be aware of:
### PHP
PHP - Used PEAR [Php_beautifier] (http://pear.php.net/package/PHP_Beautifier) package by Claudio Bustos and Jesús Espino

Getting and installing PHP - http://www.php.net/manual/en/install.general.php

Getting and installing the PEAR package manager - http://pear.php.net/manual/en/installation.getting.php

After install PEAR Php_beautifier

Language specific options:
```json
    "codeformatter_php_options":
	{
		"indent_size": 1, // Indent size
		"indent_with_tabs": true, // Indent with tabs or spaces
		"indent_style": "k&r", // Indent the code in k&r, allman, gnu or whitesmiths style
		// Add new lines after o before specific contents The settings are 'before' and 'after'. As value, use a comma separated list of contents or tokens:
		"new_line_before": "switch,while,for,foreach,try,case,do,goto,namespace,T_INTERFACE,return,continue,endfor,endforeach,endif,endswitch,endwhile,break,throw",
		"new_line_after": "",
		"format_array_nested": true, // Indent the array structures Ex.
		"pear": true, // Format the code to make it compatible with PEAR Coding Standards
		"pear_add_header": "", // Add one of the standard PEAR comment header (php, bsd, apache, lgpl, pear) or any file as licence header.
		// Two extra options allows to break the spec about newline before braces on function and classes:
		"pear_newline_class": true,
		"pear_newline_function": true,
		"lowercase": true, // Lowercase all control structures. Such as TRUE, FALSE, SWITCH and more
		"fluent": false, // Create fluent style for multi-level object access.
		"list_class_function": false, // Create a list of functions and classes in the script By default, this Filter puts the list at the beggining of the script.
		"equals_align": false, // Align the equals symbols in contiguous lines.
		"phpbb": false // Format the code to make it compatible with phpBB Coding Standards
	}
```



### Javascript/JSON
Javascript/JSON/JSONP - used [JSBeautifier] (http://jsbeautifier.org/) by Einar Lielmanis

First of all, you must install [node.js](http://nodejs.org/#download) in order to run the javascript as command line.

Language specific options:
```json
    "codeformatter_js_options":
	{
		"indent_size": 1, // indentation size
		"indent_with_tabs": true, // Indent with tabs or spaces
		"preserve_newlines": true, // whether existing line breaks should be preserved,
		"max_preserve_newlines": 2, // maximum number of line breaks to be preserved in one chunk
		"jslint_happy": false, // if true, then jslint-stricter mode is enforced. Example function () vs function()
		"brace_style": "collapse", // "collapse" | "expand" | "end-expand". put braces on the same line as control statements (default), or put braces on own line (Allman / ANSI style), or just put end braces on own line.
		"keep_array_indentation": false // keep array identation.
	}
```

### HTML
HTML - used [JSBeautifier] (http://jsbeautifier.org/) by Einar Lielmanis and Style HTML by Nochum Sossonko

you must install node.js (see above)

Language specific options:
```json
    "codeformatter_html_options":
	{
		"indent_size": 1, // indentation size
		"indent_with_tabs": true, // Indent with tabs or spaces
		"max_char": 80, // maximum amount of characters per line,
		"brace_style": "collapse", // "collapse" | "expand" | "end-expand". put braces on the same line as control statements (default), or put braces on own line (Allman / ANSI style), or just put end braces on own line.
		"unformatted": ["a"] // list of tags, that shouldn't be reformatted. Example["a", "sub", "sup", "b", "i", "u"]
	}
```

### CSS
CSS - used [JSBeautifier] (http://jsbeautifier.org/) by Einar Lielmanis and Style Css by Harutyun Amirjanyan

you must install node.js (see above)

Language specific options:
```json
    "codeformatter_css_options":
	{
		"indent_with_tab": true, // Indent with tabs or spaces
		"openbrace": "end-of-line" // "end-of-line" | "expand". put braces on the same line as control statements (default), or put braces on own line (Allman / ANSI style).
	}
```

Usage
-----
Tools -> Command Palette (`Cmd+Shift+P` or `Ctrl+Shift+P`) and type `CodeFormat: Format`.

You can set up your own key combo for this, by going to Preferences -> Key Bindings - User, and adding a command in that huge array: `{ "keys": ["ctrl+alt+f"], "command": "code_formatter" },`. You can use any other key you want, thought most of them are already taken.

TODO
-----
Add other languages support:
* Python
* Perl
* Ruby

Troubleshooting
---------------
If you like living on the edge, please report any bugs you find on the [CodeFormatter issues](https://github.com/skalongman/sublimetext-codeformatter/issues) page.
