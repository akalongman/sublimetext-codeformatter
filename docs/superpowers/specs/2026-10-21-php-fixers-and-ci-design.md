# Design: PHP fixers and CI

Status: approved by the maintainer, 2026-10-21. Revised 2026-10-22 to
match the implementation: decisions the maintainer took during the build
are marked **(maintainer)**, and facts the build measured on the real tools
are added to the probes.

Markers: **(maintainer)** is a decision the maintainer made. **(default)**
is a design default proposed here and open to review. Everything unmarked
follows from the code or from the probes below.

Context: the branch `feature/php8-and-ci` already carries a cleanup batch
(the `OSError` message for a bad `ruff_path`, a console prefix on every
line of a multi-line ruff warning, `CODEFORMATTER_TEST_RUFF` accepting a
bare name, and the README describing a Sublime Text 3/4 plugin). This
document covers the two remaining parts, which ship on the same branch and
in one pull request **(maintainer)**.

## Part 1: PHP formatting with the project's fixer

### Intent

PHP formatting fails for every user on PHP 8 or later. Both bundled
engines, `phpf.phar` (the default) and `fmt.phar`, die with
`Call to undefined function each()`, a function PHP 8.0 removed. The
bundled `php-cs-fixer.phar` refuses to start on anything newer than
PHP 7.3, and no code calls it. phpF's upstream was last pushed in 2017.
This is behind the most-discussed open issue, #355 (37 comments), and
#410, #416, #417, #421 and #422.

The fix follows the shape of the Python formatter that shipped in 4.4.0:
CodeFormatter runs the fixer the project already uses, with the project's
configuration, so the editor's output equals the project's own fixer run
and its CI never fights it **(maintainer)**. PHP projects use one of three
fixers: PHP_CodeSniffer's `phpcbf` (the maintainer's own
`longman/php-code-style` is a PHPCS standard), Laravel Pint (shipped with
Laravel), and PHP-CS-Fixer.

### Goals

- Format Code on a PHP file runs the project's fixer and replaces the
  buffer with the result.
- The fixer is picked from the project's configuration file, with a
  setting to force one **(maintainer)**.
- The project's configuration and its exclusions apply.
- Format on save works through the existing `format_on_save` option.

### Non-goals

- phpF and its options: `passes`, `excludes`, `indent_with_space`,
  `enable_auto_align`, `visibility_order`, `smart_linebreak_after_curly`,
  `php55_compat`. They are retired **(maintainer)**.
- A default style for a project with no fixer configuration. CodeFormatter
  refuses instead **(maintainer)**.
- Bundling any PHP fixer.
- Formatting a selection.

### Anti-goals

- Output that differs from running the same fixer from the project's
  configuration directory.
- Reformatting a file the project's configuration excludes.
- Applying a style the project does not use.
- An error dialog after a successful format.
- Sublime Text freezing because a fixer hangs.
- A console window flashing on Windows on every format.

### Picking the fixer

Walk up from the file's directory to the filesystem root, the way phpcs
searches for its ruleset **(default)**. The first directory that holds any
of these files decides the fixer, and becomes the *configuration
directory*. Within one directory the order below breaks ties
**(default)**:

| File | Fixer |
|---|---|
| `pint.json` | Pint |
| `.php-cs-fixer.php`, `.php-cs-fixer.dist.php` | PHP-CS-Fixer |
| `phpcs.xml`, `.phpcs.xml`, `phpcs.xml.dist`, `.phpcs.xml.dist` | phpcbf |

If no file is found, but the nearest directory above the file that holds a
`composer.json` also has `vendor/bin/pint`, the fixer is Pint with its
default (Laravel) preset and that directory is the configuration
directory. This is what `./vendor/bin/pint` does in a Laravel project's
CI **(default)**.

If nothing is found, formatting stops with this error **(maintainer)**:
`No PHP fixer is configured for this file. Add pint.json,
.php-cs-fixer.dist.php or phpcs.xml to the project, or set "fixer" in
codeformatter_php_options.`

The `fixer` setting (`"pint"`, `"php-cs-fixer"` or `"phpcbf"`) skips the
choice. The configuration directory is then the nearest directory with
that fixer's configuration file, else the nearest with a `composer.json`,
else the file's directory, and the fixer runs with its own defaults when
it has no configuration. A never-saved buffer cannot be located, so it is
refused with the same message unless `fixer` is set; with `fixer` set, the
fixer runs from Sublime Text's working directory with its defaults
**(default)**.

### Finding the fixer executable

1. `fixer_path`, when non-empty (with `~` expanded), runs as given.
2. Otherwise the nearest `vendor/bin/<tool>` at or above the configuration
   directory runs as `[php, -d, display_errors=stderr, <script>]`, where
   `php` is `php_path` or `"php"`. Composer's bin proxies are PHP scripts,
   so this works on Windows too. The `-d` option keeps PHP notices off
   stdout, which carries the formatted code: PHP without a `php.ini`, and
   Homebrew's development `php.ini`, print them to stdout
   **(maintainer)**.
3. Otherwise `<tool>` from `PATH` runs as given.

On Windows a `.bat` or `.cmd` file from step 1 or 3 runs through cmd.exe,
which parses its command line again. Python does not escape a command
line for cmd.exe (gh-114539), so a `&` in a path splits the command and
`%NAME%` is expanded even inside quotes. When the file without that
extension sits beside it and is a PHP script (its first line starts with
`<?php` or is a shebang naming php), that script runs as in step 2, which
is what Composer's batch proxy runs itself. Otherwise, when the batch
file's path, the configuration file or the file name holds `&`, `|`, `<`,
`>`, `^`, `%` or `!`, formatting stops with an error that names the batch
file and suggests installing the fixer in the project **(maintainer)**.
The PHP-script check matters because a Composer proxy from before 2.2 is
a shell script, which PHP would copy to stdout as if it were the result.
`php_path` therefore applies to step 2 and to this redirect only; a
`fixer_path` or `PATH` executable starts PHP through its own first line.

`<tool>` is `pint`, `php-cs-fixer` or `phpcbf`. When none is found, or the
executable cannot be started, the error is `<tool> not found. Run
"composer require --dev <package>" in the project, or set fixer_path in
codeformatter_php_options.`, with `laravel/pint`,
`friendsofphp/php-cs-fixer` or `squizlabs/php_codesniffer` as
`<package>`. When a command that runs a script through PHP fails because
PHP itself is missing, the error is `<php> not found. Set php_path in
codeformatter_php_options to your PHP executable.` instead **(default)**.
That needs the error to name PHP as the missing program: Python 3.6 and
newer set its file name, and Python 3.3 to 3.5 append the program to its
message. On Windows neither names it, so the first message stays.

### Running the fixers

Every process runs with the configuration directory as its working
directory, because phpcbf and Pint read their configuration from the
working directory, not from the file path (probe below). Each gets the
buffer on stdin, a 10 second timeout after which it is killed, a hidden
window on Windows, and colour output turned off (`--no-ansi` for Pint and
PHP-CS-Fixer, `--no-colors` for phpcbf) **(default)**. Text on stderr from
a successful run goes to the console with the `CodeFormatter: ` prefix on
every line, never to the dialog.

| Fixer | Command | Result |
|---|---|---|
| Pint | `pint --no-ansi [--config=<file>] --stdin-filename=<file_name> -` | Exit 0: stdout is the result. Other exits: error. |
| phpcbf | `phpcbf -q --no-colors --stdin-path=<file_name> -` | depends on the major version, below |
| PHP-CS-Fixer | see below | |

Every option that takes a value is passed in its `--name=value` form, as
one argument; phpcbf's `--stdin-path` fails in the space-separated form on
both majors. A never-saved buffer gets no path options.

A failed run shows its stderr. When stderr is empty, the error is `<tool>
exited with code <N>`, followed by `: ` and the first non-empty line of
stdout (at most 200 characters) when there is one, because phpcbf 3.x
prints its fatal errors on stdout **(default)**.

**Minimum versions and version handling (maintainer).** PHP_CodeSniffer
3.x and 4.x are both supported, because 3.x is still in wide use and its
exit codes differ. The formatter reads the major version once per
executable path with `phpcbf --version` and keeps it for the session. A
major of 4 or above reads the 4.x rows, any lower major the 3.x rows
**(default)**. A version is kept only from a run that exits 0, because a
failed run can print the PHP version it requires (`>= 8.2.0`); a failed
read stops formatting with `could not read the <tool> version`, followed
by `: ` and the first line of its stderr when there is one **(default)**.

| phpcbf | Exit | Meaning |
|---|---|---|
| 4.x | 0 or 2 | stdout is the result (2: issues remain that phpcbf cannot fix) |
| 4.x | 16 and stderr contains `No files were checked` | file excluded: the input, unchanged |
| 4.x | 16 otherwise, any other | error (16 also covers an unknown option or a broken ruleset; 4 to 7 follow a fixer conflict and carry code on stdout, probes below) |
| 3.x | 1 | stdout is the result, whether anything changed or not |
| 3.x | 0 | stdout is a report (`No fixable errors were found ...` on 3.11, `No violations were found ...` on 3.13), not code: the input, unchanged |
| 3.x | any other | error. 3.x never exits 2 for stdin: its stdin report ends with exit 1 for every file (`Cbf.php`) |

Pint gained stdin support in 1.26.0 (it was added in 1.25.0 and reverted
in 1.25.1). For an older Pint, read with `pint --version` once per
executable path, formatting stops with `Pint 1.26.0 or newer is needed to
format from the editor. Run "composer update laravel/pint" in the
project.` **(maintainer)**

PHP-CS-Fixer cannot print fixed code for stdin input; it only reports or
prints a diff. So the fix is preceded by an exclusion check **(maintainer)**:

1. For a saved file with a configuration file:
   `php-cs-fixer list-files --no-ansi --config=<file>` prints every file
   the configuration covers, one quoted path per line relative to the
   configuration directory (`'./src/a.php'`; PHP's `escapeshellarg` uses
   double quotes on Windows). Without a configuration file (a forced
   `fixer` in a project that has none) no Finder exists that could
   exclude the file, and `list-files` would fail with `You must call one
   of in() or append() methods before iterating over a Finder`, so the
   file counts as covered and this step is skipped **(maintainer)**.
   The list is kept per configuration file until the file's modification
   time changes. A file missing from a kept list triggers one refresh (it
   may be new); still absent, it is excluded: return the input unchanged.
   A file that the refresh misses while it exists on disk is remembered as
   excluded until the configuration changes, so saving an excluded file
   does not walk the project on every save. A file that is not on disk
   yet is never remembered, because format on save runs before Sublime
   Text writes a new file **(maintainer)**. A first read is not refreshed,
   so a new file's first save leaves it unformatted and the next save
   formats it. A run that hits the timeout stops formatting with an error
   rather than guessing.
2. The fix: `php-cs-fixer fix --no-ansi --using-cache=no --diff --format=json [--config=<file>] -`
   with the buffer on stdin. Exit 0: the input is already clean. Exit 4:
   the input has a syntax error (the only cause of 4 in
   `FixCommandExitStatusCalculator`), and it stays unchanged, as with Pint
   and phpcbf. Exit 8: apply the unified diff in `files[0].diff` to the
   input; the diff is computed against exactly that input, so applying it
   needs no fuzz, and a hunk range without a count (`@@ -5 +5 @@`) means
   one line. Other exits: error. Output that is not the JSON report, or a
   diff that does not match the input, stops formatting with an error and
   leaves the buffer unchanged, never partly patched.

A dry run with `--path-mode=intersection` cannot replace step 1: its JSON
lists only files that would change, so an excluded file and a clean one
both report zero files.

`--config=<file>` names the configuration file found, for Pint and
PHP-CS-Fixer, when there is one. phpcbf gets no `--standard=`: it reads
its ruleset from the working directory, in its own order of names, which
differs from the detection order above. A syntax error leaves the buffer
unchanged: Pint and phpcbf return their input untouched, and PHP-CS-Fixer
exits 4.

These probes on PHP 8.5.4, against Pint 1.32.1, PHP-CS-Fixer 3.95.27 and
PHP_CodeSniffer 4.0.4 unless a row names another version, back the tables:

| Case | Result |
|---|---|
| Pint, `--stdin-filename` absolute, working directory = project | `pint.json` applied |
| Pint, same, working directory elsewhere | `pint.json` ignored, Laravel preset applied |
| Pint, `--config <pint.json>`, working directory elsewhere | `pint.json` applied |
| Pint, path in `pint.json` `exclude` | input unchanged, exit 0 |
| Pint, syntax error | input unchanged, exit 0 |
| phpcbf, working directory = project | `phpcs.xml` applied |
| phpcbf, absolute `--stdin-path`, working directory elsewhere | `phpcs.xml` ignored, default standard applied |
| phpcbf 4.0.4, path matching an `exclude-pattern` | exit 16, no stdout, `No files were checked` on stderr |
| phpcbf 4.0.4, unknown option / broken ruleset | exit 16, an `ERROR:` line on stderr |
| phpcbf 4.0.4, issues fixed / clean / unfixable issue left | exit 0 / 0 / 2, stdout always the result |
| phpcbf 4.0.4, syntax error | input unchanged, exit 0 |
| phpcbf 3.11.3, issues fixed / clean | exit 1 / 1, stdout the result |
| phpcbf 3.11.3, path matching an `exclude-pattern` | exit 0, stdout `No fixable errors were found` and a timing line |
| phpcbf 3.13.6, path matching an `exclude-pattern` | exit 0, stdout `No violations were found` and a timing line |
| phpcbf 3.13.6, syntax error | input unchanged, exit 1 |
| phpcbf 4.0.4, fixer conflict | exit 5 or 7 (4 or 6 with `ignore_errors_on_exit` or `ignore_warnings_on_exit`), stdout the partly fixed code |
| Pint 1.20.0 | no `--stdin-filename` option |
| Pint 1.32.1 and 1.26.0, path in `exclude` / syntax error | input unchanged, exit 0 |
| PHP-CS-Fixer, `list-files` with a Finder that excludes `legacy` | `'./src/a.php'` only, exit 0 |
| PHP-CS-Fixer 3.7.0, `list-files` without a configuration file (the same code path in current releases) | `You must call one of in() or append() methods before iterating over a Finder`, exit 1 |
| PHP-CS-Fixer, `fix -` | no fixed code on stdout, `1 of 1 files that can be fixed`, exit 8 |
| PHP-CS-Fixer, `fix --diff --format=json -` | JSON with a unified diff for `php://stdin`, exit 8 |
| PHP-CS-Fixer, `fix --format=json -` on a syntax error | empty `files`, exit 4 |
| PHP-CS-Fixer, dry-run `--path-mode=intersection` on an excluded path | zero files, exit 0 |
| PHP-CS-Fixer, any `fix` run | a header on stderr (version, runtime, loaded configuration, core count); with the warnings below, about seven console lines on every successful format |
| PHP-CS-Fixer, any run without a `composer.json` | warnings on stderr |

### Errors while saving

Today `run_formatter` in `CodeFormatter.py` shows every format error in a
modal dialog, on save too. A user who kept `format_on_save: true` from the
phpF days would get a dialog on every save in a project without a fixer
configuration. When the format runs from a save, an error now goes to the
status bar (`sublime.status_message`) and the console instead, for every
formatter; Format Code keeps the dialog **(maintainer)**. A failed format
never blocks the save.

### Settings

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

Setting names are **(default)**. User settings that still hold the retired
phpF keys are ignored.

### Removed

- The phpF code in `codeformatter/phpformatter.py`. The module and its
  `PhpFormatter` class stay, since `formatter.py` and the reloader name
  them.
- `CodeFormatterShowPhpTransformationsCommand` (`CodeFormatter.py:86-157`)
  and its "CodeFormatter: Show PHP Transformations" entry in
  `CodeFormatter.sublime-commands`.
- The `chmod` of `codeformatter/lib/phpbeautifier/fmt.phar` in
  `plugin_loaded()` (`CodeFormatter.py`), which would raise once the file
  is gone.
- `PHP-Transformations.md`.
- `codeformatter/lib/phpbeautifier/` (`fmt.phar`, `fmt-php55.phar`,
  `phpf.phar`, `php-cs-fixer.phar`).
- `tests/data/php/` (no test reads it).
- The phpF options in `CodeFormatter.sublime-settings`, and the PHP
  sections of `README.md` and `messages/install.txt`, which are rewritten.

### Documentation and upgrade message

- `README.md` and `messages/install.txt` get the same rewritten PHP entry
  and section: how the fixer is picked, the refusal and how to fix it, the
  three settings, install steps (`composer require --dev ...` in the
  project, plus a global install for each platform: macOS, Linux
  (Debian/Ubuntu plus one fallback), Windows PowerShell and WSL), and that
  a User override of `codeformatter_php_options` must copy the whole
  block.
- PHP users who update through Package Control get an upgrade message
  saying that the phpF options no longer apply and how the fixer is now
  picked: a `messages.json` entry for the release version. The file is
  named for 4.5.0 and renamed at release if the maintainer picks another
  number **(default)**.

## Part 2: Continuous integration

### Intent

There is no working CI. `.travis.yml` targets Python 3.3 on Travis CI,
which no longer runs open-source projects. A pull request from the
maintainer or a contributor should go red before merge when it breaks
formatting, on Linux, Windows and macOS, not only on the maintainer's
machine **(maintainer)**.

### Goals

- Run on every pull request and on every push to `master`.
- The test suite on Ubuntu, Windows and macOS with Python 3.14, the
  version current Sublime Text runs packages on, with a real ruff so the
  ruff integration tests run instead of skipping.
- The ruff integration tests against the oldest supported ruff on Ubuntu.
- The PHP integration tests against real Pint, PHP-CS-Fixer and phpcbf on
  Ubuntu and Windows (Windows for Composer's bin proxies and paths).
- A static check that plugin code and bundled libraries use nothing newer
  than Python 3.3.

### Non-goals

- Running tests on Python 3.3. GitHub's runners cannot install it.
  Python 3.3 support stays **(maintainer)**, so the static check stands in.
- Branch protection that blocks merging on red CI. It is a repository
  setting, offered separately.
- Publishing releases from CI.

### Anti-goals

- A green run in which real-tool tests skipped because a tool was missing.
- A check that passes on code that would fail to load on Python 3.3.

### Behaviour

- Workflow: GitHub Actions, using `actions/checkout@v7`,
  `actions/setup-python@v7` and `shivammathur/setup-php@v2`.
- Tests job: matrix `ubuntu-latest`, `windows-latest`, `macos-latest`;
  Python 3.14; `pip install -r dev-requirements.pip ruff==0.17.1`;
  `python -m pytest -p no:cacheprovider tests` with the ruff integration
  tests pointed at that ruff.
- Oldest ruff job: Ubuntu, `ruff==0.1.2` (the first stable `ruff format`),
  ruff integration tests only. On 0.1.2 a syntax error reads
  `error: Failed to format <path>: source contains syntax errors` instead
  of `error: Failed to parse ...`, so those assertions check for a plain
  `error:` prefix and no ANSI escape code instead of the exact wording.
  Every other ruff integration test already passes on 0.1.2.
- PHP job: matrix `ubuntu-latest`, `windows-latest`; PHP 8.5 with
  Composer; Pint 1.32.1, PHP-CS-Fixer 3.95.27 and PHP_CodeSniffer 4.0.4
  installed into a scratch Composer project; the PHP integration tests
  pointed at them.
- Oldest PHP tools job: Ubuntu, Pint 1.26.0 and PHP_CodeSniffer 3.13.6,
  the PHP integration tests for those two fixers only, so both PHPCS
  majors and the Pint minimum run in CI.
- Python 3.3 job: `vermin --target=3.3- --violations --exclude str.decode`
  over `CodeFormatter.py` and `codeformatter/`, with vermin 1.9.2.
  `--exclude str.decode` removes one false positive: vermin reads
  `bytes.decode()` as Python 2's `str.decode`. The bundled libraries
  already meet the 3.3 target.
- Real-tool tests skip when a tool is missing on a developer machine, but
  fail instead of skipping when the environment variable
  `CODEFORMATTER_REQUIRE_TOOLS=1` is set, which every CI job that installs
  a tool sets **(default)**.
- `dev-requirements.pip` pins `pytest==9.0.2` and drops the `py` pin.
- `.travis.yml` is deleted.

## Constraints (both parts)

- Plugin code runs on Python 3.3 and 3.14: `subprocess.Popen` with
  `communicate(timeout=...)`, no `subprocess.run`, no f-strings. The
  Python 3.3 job enforces it.
- The formatter contract in `codeformatter/formatter.py` and
  `CodeFormatter.py`: `format(text)` receives the whole buffer as UTF-8
  bytes and returns `(stdout, stderr)`; the buffer is replaced only when
  `stderr` is empty and `stdout` is not. The only change to
  `run_formatter` is where an error is shown while saving (Errors while
  saving).
- `README.md` and `messages/install.txt` get the same changes.

## Testing

- PHP unit tests with `subprocess.Popen` replaced: every configuration
  file name, the tie order, nearest-directory wins, Pint from `vendor/bin`
  without `pint.json`, the forced fixer, the refusal, a never-saved buffer,
  executable lookup (`fixer_path`, `vendor/bin` through `php_path`, `PATH`,
  not found), each command line and working directory, each exit-code
  mapping for both phpcbf majors (including exit 16 without `No files
  were checked`), the version read once per executable, the Pint
  minimum-version refusal, the PHP-CS-Fixer `list-files` cache (hit,
  refresh for a new file, excluded file, a changed configuration file,
  timeout) and diff application (several hunks, a missing final newline),
  warnings to the console, the timeout, the hidden window, and colour
  flags.
- `run_formatter`: an error while saving goes to the status bar and
  console, and an error from Format Code to the dialog.
- PHP integration tests against the real tools, run from a working
  directory outside the project: each fixer applies the project's
  configuration, an excluded file comes back unchanged, and a syntax error
  leaves the buffer unchanged. The oldest-tools job runs the Pint and
  phpcbf ones against Pint 1.26.0 and PHP_CodeSniffer 3.13.6.
- The ruff integration tests' two syntax-error assertions become
  version-tolerant, as described under Part 2.

## Left to the builder

- Module layout for the three fixers and the diff applier, and message
  wording beyond the messages quoted above.
- Workflow and job names, caching, and the environment variable names the
  PHP integration tests use to find the tools.
- PHP-CS-Fixer's exit code for a syntax error, and its mapping to an
  unchanged buffer. Settled: exit 4, the input unchanged (Running the
  fixers).

## Out of scope

The release (version number, release notes) is the maintainer's call
after the merge.
