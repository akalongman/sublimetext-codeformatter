[![Tests](https://github.com/akalongman/sublimetext-codeformatter/actions/workflows/tests.yml/badge.svg)](https://github.com/akalongman/sublimetext-codeformatter/actions/workflows/tests.yml)

CodeFormatter
=============


CodeFormatter is a Sublime Text 3/4 plugin that supports format (beautify) source code.


CodeFormatter has support for the following languages:

* PHP - By the project's own [Pint](https://laravel.com/docs/pint), [PHP-CS-Fixer](https://cs.symfony.com/) or phpcbf from [PHP_CodeSniffer](https://github.com/PHPCSStandards/PHP_CodeSniffer)
* JavaScript/JSON - By JSBeautifier
* HTML - By [Custom fork of BeautifulSoup](https://github.com/akalongman/python-beautifulsoup)
* CSS,LESS,SASS - By JSBeautifier
* Python - By [ruff](https://docs.astral.sh/ruff/formatter/)
* Go - By [gofmt](https://golang.org/cmd/gofmt/)
* Visual Basic/VBScript
* Coldfusion/Railo/Lucee


Sponsors
-----
No sponsors yet.. :(

If you like the software, don't forget to donate to further development of it!

[![PayPal donate button](https://www.paypalobjects.com/webstatic/en_US/btn/btn_donate_pp_142x27.png)](https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=akalongman@gmail.com&item_name=Donation%20to%20Sublime%20Text%20-%20CodeFormatter&item_number=1&no_shipping=1 "Donate to this project using Paypal")


Installing
----------
**With the Package Control plugin:** The easiest way to install CodeFormatter is through Package Control, which can be found at this site: http://wbond.net/sublime_packages/package_control

Once you install Package Control, restart Sublime Text and bring up the Command Palette (`Command+Shift+P` on OS X, `Control+Shift+P` on Linux/Windows). Select "Package Control: Install Package", wait while Package Control fetches the latest package list, then select CodeFormatter when the list appears. The advantage of using this method is that Package Control will automatically keep CodeFormatter up to date with the latest version.

**Without Git:** Download the latest source from [GitHub](https://github.com/akalongman/sublimetext-codeformatter) and copy the CodeFormatter folder to your Sublime Text "Packages" directory.

**With Git:** Clone the repository in your Sublime Text "Packages" directory:

    git clone https://github.com/akalongman/sublimetext-codeformatter.git CodeFormatter


The "Packages" directory is located at:

* OS X:

        ST4: ~/Library/Application Support/Sublime Text/Packages/
        ST3: ~/Library/Application Support/Sublime Text 3/Packages/

* Linux:

        ST4: ~/.config/sublime-text/Packages/
        ST3: ~/.config/sublime-text-3/Packages/

* Windows:

        ST4: %APPDATA%/Sublime Text/Packages/
        ST3: %APPDATA%/Sublime Text 3/Packages/

## Configuration

To change the default configurations you have to update the **CodeFormatter - User Preferences** file. You can find this file in the Sublime Text menu bar under: `Sublime Text > Package Settings > CodeFormatter > Settings - User`. 

Make sure that you wrap all the configurations into a single root object.

```js
{
   "codeformatter_php_options": {...},
   "codeformatter_js_options": {...},
   ..
}
```

## Formatter-specific notes
Following are notes specific to individual formatters that you should be aware of:
### PHP
PHP - used the project's own [Pint](https://laravel.com/docs/pint), [PHP-CS-Fixer](https://cs.symfony.com/) or phpcbf from [PHP_CodeSniffer](https://github.com/PHPCSStandards/PHP_CodeSniffer)

Language specific options:
```js
    "codeformatter_php_options":
    {
        "syntaxes": "php", // Syntax names which must process PHP formatter
        "format_on_save": false, // Format on save. Either a boolean (true/false) or a string regexp tested on filename. Example : "^((?!.min.|vendor).)*$"
        "fixer": "", // "pint", "php-cs-fixer" or "phpcbf" to force one. If empty, picks the fixer from the project's configuration file
        "fixer_path": "", // Path to the fixer executable. If empty, uses the project's vendor/bin, then PATH
        "php_path": "" // PHP used to run a fixer from vendor/bin. If empty, uses "php" from PATH
    }
```

Style comes from the project's fixer configuration. CodeFormatter has no PHP style options of its own: the options `passes`, `excludes`, `indent_with_space`, `enable_auto_align`, `visibility_order`, `smart_linebreak_after_curly`, `php55_compat`, `psr1`, `psr1_naming` and `psr2` no longer apply, and are ignored if your user settings still hold them.

CodeFormatter looks for one of these configuration files in the file's directory, then in each directory above it. The first directory that holds one decides the fixer, and the fixer runs in that directory:

* `pint.json`: Pint
* `.php-cs-fixer.php` or `.php-cs-fixer.dist.php`: PHP-CS-Fixer
* `phpcs.xml`, `.phpcs.xml`, `phpcs.xml.dist` or `.phpcs.xml.dist`: phpcbf

When one directory holds files for more than one fixer, the first in this list wins. When no configuration file is found, but the closest directory above the file that holds a `composer.json` also holds `vendor/bin/pint`, Pint formats the file with its default (Laravel) preset, as `./vendor/bin/pint` does in a Laravel project. Otherwise formatting stops with this error:

    No PHP fixer is configured for this file. Add pint.json, .php-cs-fixer.dist.php or phpcs.xml to the project, or set "fixer" in codeformatter_php_options.

To fix it, add the fixer's configuration file to the project, or set `fixer` to `"pint"`, `"php-cs-fixer"` or `"phpcbf"`. With `fixer` set, only that fixer's configuration files count: it runs in the closest directory above the file that holds one, else the closest that holds a `composer.json`, else the file's own directory, and it uses its own defaults when there is no configuration file. Any other value of `fixer` stops formatting with an error that names the three values.

A buffer that was never saved is refused with the same error unless `fixer` is set. With `fixer` set, it is formatted by the fixer from `fixer_path` or `PATH`, run from Sublime Text's working directory, which is usually not the project folder, so the fixer usually runs with its defaults. Save the file inside the project to format it with the project's style.

CodeFormatter runs the first of these that it finds:

1. `fixer_path`, when it is set.
2. The closest `vendor/bin/pint`, `vendor/bin/php-cs-fixer` or `vendor/bin/phpcbf` (for the fixer picked) in or above the directory the fixer runs in. Composer's `vendor/bin` files are PHP scripts, so CodeFormatter runs them through PHP: `php_path`, or `php` from `PATH`.
3. `pint`, `php-cs-fixer` or `phpcbf` from `PATH`.

When none is found, formatting stops with an error such as `pint not found. Run "composer require --dev laravel/pint" in the project, or set fixer_path in codeformatter_php_options.` The same error appears when PHP or the fixer cannot be started, for example when `php` is not on `PATH`, which `php_path` fixes.

The fixer gets the buffer on standard input and 10 seconds to finish, after which it is stopped with an error such as `pint did not finish in 10 seconds`. When CodeFormatter runs a PHP script through PHP, it passes `-d display_errors=stderr`, so PHP notices never end up in the file. Warnings that a fixer prints while it succeeds go to the Sublime Text console. When a format on save fails, the error appears in the status bar, with the full text in the console, and the file is saved as it is; Format Code shows the error in a dialog.

PHP-CS-Fixer does not apply its configuration's list of files (its Finder) to code it reads from standard input. So when the project has a PHP-CS-Fixer configuration file, CodeFormatter first asks `php-cs-fixer list-files` which files the configuration covers, and leaves a file that it does not cover unchanged. The list is kept until the configuration file changes, and read again when a file is missing from it. Format on save runs before Sublime Text writes the file, so the first save of a new file leaves it unchanged, because the list cannot hold the file yet; the next save formats it if the configuration covers it. Without a configuration file (with `"fixer": "php-cs-fixer"`), PHP-CS-Fixer formats any file.

Pint 1.26.0 or newer is needed, which needs PHP 8.2 or newer. With an older Pint the error is `Pint 1.26.0 or newer is needed to format from the editor. Run "composer update laravel/pint" in the project.` PHP_CodeSniffer 3 and 4 are both supported.

If you format PHP files with another package, set `"syntaxes": ""` in `codeformatter_php_options` so CodeFormatter leaves them alone.

Installing a fixer: add it to the project with Composer, so that everyone working on the project uses the same version:

* Pint: `composer require --dev laravel/pint`
* PHP-CS-Fixer: `composer require --dev friendsofphp/php-cs-fixer`
* phpcbf: `composer require --dev squizlabs/php_codesniffer`

To install a fixer for all your projects instead, install PHP and Composer with the steps below, run `composer global require laravel/pint` (or `friendsofphp/php-cs-fixer`, or `squizlabs/php_codesniffer`), and put Composer's global bin directory, which `composer global config bin-dir --absolute` prints, on `PATH`. A global fixer is used only when the project's `vendor/bin` has none, and the project still needs the fixer's configuration file, or `fixer` must be set.

* macOS: `brew install php composer`, then add the line `export PATH="<bin directory>:$PATH"` to `~/.zprofile`
* Debian/Ubuntu: `sudo apt install php-cli php-xml php-mbstring composer`, then add the line `export PATH="<bin directory>:$PATH"` to `~/.profile`, then log out and back in so that Sublime Text started from the desktop sees it
* Other Linux: install PHP with its `mbstring` and `xml` extensions from your distribution's packages, and Composer with the steps at https://getcomposer.org/download/, then add the bin directory to `PATH` in `~/.profile` as above
* Windows (PowerShell): install Scoop with the steps at https://scoop.sh, then `scoop install php composer`. Scoop puts Composer's global bin directory on `PATH`. In a new terminal, run `php --ini`, and in the `php.ini` file it names, remove the `;` in front of `extension=openssl` and `extension=zip`, which Composer uses to download and unpack packages, and `extension=mbstring`, which Pint needs. If PHP does not start, install the Visual C++ Redistributable that Scoop suggests: `scoop bucket add extras`, then `scoop install vcredist2022`.
* WSL: if Sublime Text runs inside WSL, install PHP and the fixer inside WSL with the Linux steps. If Windows Sublime Text edits files under `\\wsl.localhost`, install PHP on Windows with the Windows steps: CodeFormatter runs the project's `vendor/bin` fixer through the Windows PHP. A PHP or a fixer installed only inside WSL cannot be used by Windows Sublime Text.

Restart Sublime Text after installing PHP or a fixer, and open a new terminal before running `which` or `where.exe` below, because a program that is already running keeps the `PATH` it started with.

Sublime Text started from the macOS Dock may not see the same `PATH` as your terminal, so it may not find PHP or a global fixer. In that case, or whenever CodeFormatter reports that the fixer is not found, set `php_path` to the output of `which php` in a terminal, and for a global fixer `fixer_path` to the output of `which pint` (or `which php-cs-fixer`, `which phpcbf`).

On Windows, run `where.exe php` and `where.exe pint` (in PowerShell or the Command Prompt). With `php_path` empty, CodeFormatter starts only a `php.exe` from `PATH`, not a `php.bat` that some PHP installers put there instead; in that case set `php_path` to the `php.exe` file. Composer installs a global fixer on Windows as a `.bat` file with a PHP script of the same name beside it, and CodeFormatter runs that script through PHP (`php_path`, or `php.exe` from `PATH`), whether it finds the `.bat` file on `PATH` or in `fixer_path`. So PHP must be found as well, and `fixer_path` takes the line that ends in `.bat`. A `.bat` file with no PHP script beside it runs through cmd.exe, which would misread some characters, so CodeFormatter refuses to run it when a path on its command line holds one of `& | < > ^ % !`. Write paths with forward slashes or doubled backslashes, because a single backslash starts an escape sequence in the settings file: `"php_path": "C:/php/php.exe"` or `"php_path": "C:\\php\\php.exe"`.

Copy the whole `codeformatter_php_options` block into `Settings - User` and change `fixer`, `fixer_path` or `php_path` there, because a block that holds only those keys replaces the default block, which drops `syntaxes` and turns PHP formatting off.

### Javascript/JSON
Javascript/JSON - used [JSBeautifier] (http://jsbeautifier.org/) by Einar Lielmanis

Language specific options:
```js
    "codeformatter_js_options":
    {
        "syntaxes": "javascript,json", // Syntax names which must process JS formatter
        "format_on_save": false, // Format on save. Either a boolean (true/false) or a string regexp tested on filename. Example : "^((?!.min.|vendor).)*$"
        "indent_size": 4, // indentation size
        "indent_char": " ", // Indent character
        "indent_with_tabs": false, // Indent with one tab (overrides indent_size and indent_char options)
        "eol": "\n", // EOL symbol
        "preserve_newlines": false, // whether existing line breaks should be preserved,
        "max_preserve_newlines": 10, // maximum number of line breaks to be preserved in one chunk
        "space_in_paren": false, // Add padding spaces within paren, ie. f( a, b )
        "space_in_empty_paren": false, // Add padding spaces within paren if parent empty, ie. f(  )
        "e4x": false, // Pass E4X xml literals through untouched
        "jslint_happy": false, // if true, then jslint-stricter mode is enforced. Example function () vs function()
        "brace_style": "collapse", // "collapse" | "expand" | "end-expand". put braces on the same line as control statements (default), or put braces on own line (Allman / ANSI style), or just put end braces on own line.
        "keep_array_indentation": false, // keep array indentation.
        "keep_function_indentation": false, // keep function indentation.
        "eval_code": false, // eval code
        "unescape_strings": false, // Decode printable characters encoded in xNN notation
        "wrap_line_length": 0, // Wrap lines at next opportunity after N characters
        "break_chained_methods": false, // Break chained method calls across subsequent lines
        "end_with_newline": false, // Add new line at end of file
        "comma_first": false // Add comma first
    }
```

### HTML
HTML - used custom python port, please use it with caution, feature in early beta

Language specific options:
```js
    "codeformatter_html_options":
    {
        "syntaxes": "html,asp,xml", // Syntax names which must process HTML formatter
        "format_on_save": false, // Format on save. Either a boolean (true/false) or a string regexp tested on filename. Example : "^((?!.min.|vendor).)*$"
        "indent_size": 4, // indentation size
        "indent_char": " ", // Indentation character
        "indent_with_tabs": false, // Indent with one tab (overrides indent_size and indent_char options)
        "exception_on_tag_mismatch": false, // If the last closing tag is not at the same indentation level as the first opening tag, there's probably a tag mismatch in the file
        "expand_javascript": false, // Expand JavaScript inside of <script> tags (also affects CSS purely by coincidence)
        "expand_tags": false, // Expand tag attributes onto new lines
        "minimum_attribute_count": 2, // Minimum number of attributes needed before tag attributes are expanded to new lines
        "first_attribute_on_new_line": false, // Put all attributes on separate lines from the tag (only uses 1 indentation unit as opposed to lining all attributes up with the first)
        "reduce_empty_tags": false, // Put closing tags on same line as opening tag if there is no content between them
        "reduce_whole_word_tags": false, // Put closing tags on same line as opening tag if there is whole word between them
        "custom_singletons": "" // Custom singleton tags for various template languages outside of the HTML5 spec
    }
```

### CSS
CSS - used [JSBeautifier] (http://jsbeautifier.org/) by Einar Lielmanis and Style Css by Harutyun Amirjanyan

Language specific options:
```js
    "codeformatter_css_options":
    {
        "syntaxes": "css,less", // Syntax names which must process CSS formatter
        "format_on_save": false, // Format on save. Either a boolean (true/false) or a string regexp tested on filename. Example : "^((?!.min.|vendor).)*$"
        "indent_size": 4, // Indentation size
        "indent_char": " ", // Indentation character
        "indent_with_tabs": false, // Indent with one tab (overrides indent_size and indent_char options)
        "selector_separator_newline": false, // Add new lines after selector separators
        "end_with_newline": false, // Add new line of end in file
        "newline_between_rules": false, // Add new line between rules
        "eol": "\n" // EOL symbol
    }
```
### SCSS
SCSS - Simply modified the CSS formatter module as per the response from scss-lint (Gives way to modify further)

Language specific options:
```js
    "codeformatter_scss_options":
    {
        "syntaxes": "scss", // Indentation size
        "indent_size": 2, // Indentation size
        "format_on_save": false, // Format on save. Either a boolean (true/false) or a string regexp tested on filename. Example : "^((?!.min.|vendor).)*$"
        "indent_char": " ", // Indentation character
        "indent_with_tabs": false, // Indent with one tab (overrides indent_size and indent_char options)
        "selector_separator_newline": true, // Add new lines after selector separators
        "newline_between_rules": true, // Add new line between rules
        "end_with_newline": true // Add new line of end in file
    }
```

### Python
Python - used [ruff](https://docs.astral.sh/ruff/formatter/)

Language specific options:
```js
    "codeformatter_python_options":
    {
        "syntaxes": "python", // Syntax names which must process Python formatter
        "format_on_save": false, // Format on save. Either a boolean (true/false) or a string regexp tested on filename. Example : "^((?!.min.|vendor).)*$"
        "ruff_path": "" // Path to the ruff executable. If empty, uses "ruff" from PATH
    }
```

Style comes from the project's ruff configuration: the closest `.ruff.toml`, `ruff.toml` or `pyproject.toml` with a `[tool.ruff]` section above the file. CodeFormatter has no Python style options of its own. Files that this configuration excludes are left unchanged: its `exclude` list (by default ruff's own list, such as `.venv`, `dist` and `node_modules`), its `extend-exclude` list, and its `[tool.ruff.format] exclude` list (`[format]` in a `ruff.toml`).

For a buffer that was never saved, ruff looks for a configuration upward from Sublime Text's working directory, which is usually not the project folder, and otherwise falls back to its user-level configuration or its defaults. Save the file inside the project to format it with the project's style.

If you format Python files with another package, set `"syntaxes": ""` in `codeformatter_python_options` so CodeFormatter leaves them alone.

Installing ruff:

* macOS: `brew install ruff`
* Debian/Ubuntu: `sudo apt install pipx`, then `pipx install ruff`, then `pipx ensurepath`, then log out and back in so that Sublime Text started from the desktop sees `~/.local/bin`
* Other Linux: `curl -LsSf https://astral.sh/ruff/install.sh | sh`
* Windows (PowerShell): `powershell -c "irm https://astral.sh/ruff/install.ps1 | iex"`
* WSL: if Sublime Text runs inside WSL, install ruff inside WSL with the Linux steps. If Windows Sublime Text edits files under `\\wsl.localhost`, install ruff on Windows with the Windows steps. A ruff installed only inside WSL cannot be used by Windows Sublime Text.

Restart Sublime Text after installing ruff, and open a new terminal before running `which ruff` or `where.exe ruff` below, because a program that is already running keeps the `PATH` it started with.

Sublime Text started from the macOS Dock may not see the same `PATH` as your terminal, so it may not find ruff. In that case, or whenever CodeFormatter reports that ruff is not found, set `ruff_path` to the output of `which ruff` in a terminal. On Windows, run `where.exe ruff` (in PowerShell or the Command Prompt) and use the first line it prints. Write that path with forward slashes or doubled backslashes, because a single backslash starts an escape sequence in the settings file: `"ruff_path": "C:/Users/you/.local/bin/ruff.exe"` or `"ruff_path": "C:\\Users\\you\\.local\\bin\\ruff.exe"`. Copy the whole `codeformatter_python_options` block into `Settings - User` and change `ruff_path` there, because a block that holds only `ruff_path` replaces the default block, which drops `syntaxes` and turns Python formatting off.

### Go
Go - used [gofmt](https://golang.org/src/cmd/gofmt/gofmt.go)

Currently no options are supported:
```js
    "codeformatter_go_options":
    {
        "syntaxes": "go",
        "format_on_save": false // Format on save. Either a boolean (true/false) or a string regexp tested on filename. Example : "^((?!.min.|vendor).)*$"
    }
```

### Visual Basic/VBScript
Visual Basic/VBScript - used custom approach using the HTML beautifier as a guide

Language specific options:
```js
    "codeformatter_vbscript_options":
    {
        "syntaxes": "vbscript", // Syntax names which must process VBScript formatter
        "format_on_save": false, // Format on save. Either a boolean (true/false) or a string regexp tested on filename. Example : "^((?!.min.|vendor).)*$"
        "indent_size": 1, // indentation size
        "indent_char": "\t", // Indentation character
        "indent_with_tabs": true, // Indent with one tab (overrides indent_size and indent_char options)
        "preserve_newlines": true, // Preserve existing line-breaks
        "max_preserve_newlines": 10, // Maximum number of line-breaks to be preserved in one chunk
        "opening_tags": "^(Function .*|Sub .*|If .* Then|For .*|Do While .*|Select Case.*)", // List of keywords which open a new block
        "middle_tags": "^(Else|ElseIf .* Then|Case .*)$", // List of keywords which divide a block, but neither open or close the block
        "closing_tags": "(End Function|End Sub|End If|Next|Loop|End Select)$" // List of keywords which close an open block
    }
```

### Coldfusion Markup Language

Language specific options:
```js
    "codeformatter_coldfusion_options":
    {
        "syntaxes": "coldfusion,cfm,cfml", // Syntax names which must process Coldfusion Markup Language formatter
        "format_on_save": false, // Format on save. Either a boolean (true/false) or a string regexp tested on filename. Example : "^((?!.min.|vendor).)*$"
        "indent_size": 2, // indentation size
        "indent_char": " ", // Indentation character
        "indent_with_tabs": false, // Indent with one tab (overrides indent_size and indent_char options)
        "exception_on_tag_mismatch": false, // If the last closing tag is not at the same indentation level as the first opening tag, there's probably a tag mismatch in the file
        "expand_javascript": false, // Expand JavaScript inside of <script> tags (also affects CSS purely by coincidence)
        "expand_tags": false, // Expand tag attributes onto new lines
        "minimum_attribute_count": 2, // Minimum number of attributes needed before tag attributes are expanded to new lines
        "first_attribute_on_new_line": false, // Put all attributes on separate lines from the tag (only uses 1 indentation unit as opposed to lining all attributes up with the first)
        "reduce_empty_tags": false, // Put closing tags on same line as opening tag if there is no content between them
        "reduce_whole_word_tags": false, // Put closing tags on same line as opening tag if there is whole word between them
        "custom_singletons": "" // Custom singleton tags for various template languages outside of the HTML5 spec
    }
```

Usage
-----
Tools -> Command Palette (`Cmd+Shift+P` or `Ctrl+Shift+P`) and type `Format Code`.

You can set up your own key combo for this, by going to Preferences -> Key Bindings - User, and adding a command in that huge array: `{ "keys": ["ctrl+alt+f"], "command": "code_formatter" },`. Default keybinding is `ctrl+alt+f`. You can use any other key you want, thought most of them are already taken.

## TODO

Add other languages support:
* Perl
* Ruby

Add tests

## Troubleshooting

If you like living on the edge, please report any bugs you find on the [CodeFormatter issues](https://github.com/akalongman/sublimetext-codeformatter/issues) page.

## Contributing

Pull requests are welcome.
See [CONTRIBUTING.md](CONTRIBUTING.md) for information.

## License

Please see the [LICENSE](LICENSE.md) included in this repository for a full copy of the MIT license,
which this project is licensed under.

