# PHP Fixers and CI Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: invoke superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** PHP files format through the project's own Pint, PHP-CS-Fixer or phpcbf, and every pull request runs the suite on Linux, Windows and macOS.

**Architecture:** `codeformatter/process.py` runs one tool process (stdin, working directory, timeout, hidden window, no forced colour). `codeformatter/phpfixer.py` holds the decisions that need no process mocking: which fixer and configuration directory, which executable, the tool version, PHP-CS-Fixer's covered files, and applying its diff. `PhpFormatter` in `codeformatter/phpformatter.py` wires them into the existing formatter contract. A GitHub Actions workflow replaces `.travis.yml`. The agreed order is CI first, then PHP.

**Tech Stack:** plugin code on Python 3.3 and 3.14; pytest 9.0.2; GitHub Actions (`actions/checkout@v7`, `actions/setup-python@v7`, `shivammathur/setup-php@v2`); PHP 8.5; Pint 1.32.1 and 1.26.0; PHP-CS-Fixer 3.95.27; PHP_CodeSniffer 4.0.4 and 3.13.6; ruff 0.17.1 and 0.1.2; vermin 1.9.2; actionlint 1.7.12.

**Spec:** `docs/superpowers/specs/2026-10-21-php-fixers-and-ci-design.md`

## Global Constraints

- Plugin code runs on Python 3.3 and 3.14: `subprocess.Popen` with `communicate(text, timeout=...)`; no `subprocess.run`, no `capture_output`, no f-strings. Gate: `uvx -q vermin==1.9.2 --target=3.3- --violations --exclude str.decode --no-tips CodeFormatter.py codeformatter` exits 0.
- Formatter contract: `format(text: bytes)` returns `(stdout, stderr)`; CodeFormatter replaces the buffer only when `stderr` is empty and `stdout` is not. An unchanged result returns the input `text` with `''`.
- Timeout: 10 seconds per process (`process.TIMEOUT = 10`); the process is killed and reaped.
- Fixer names exactly `pint`, `php-cs-fixer`, `phpcbf`; packages `laravel/pint`, `friendsofphp/php-cs-fixer`, `squizlabs/php_codesniffer`.
- Messages, verbatim:
  - Refusal: `No PHP fixer is configured for this file. Add pint.json, .php-cs-fixer.dist.php or phpcs.xml to the project, or set "fixer" in codeformatter_php_options.`
  - Not found: `<tool> not found. Run "composer require --dev <package>" in the project, or set fixer_path in codeformatter_php_options.`
  - Pint minimum: `Pint 1.26.0 or newer is needed to format from the editor. Run "composer update laravel/pint" in the project.`
  - Timeout: `<tool> did not finish in 10 seconds`
  - Unknown fixer: `Unknown fixer "<value>" in codeformatter_php_options. Use "pint", "php-cs-fixer" or "phpcbf".`
- Every option that takes a value is passed in its `--name=value` form (one argument; phpcbf's `--stdin-path` fails in the space form).
- Settings keys in `codeformatter_php_options`: `syntaxes`, `format_on_save`, `fixer`, `fixer_path`, `php_path`.
- Docs are agent-neutral; `README.md` and `messages/install.txt` get the same changes; install steps cover macOS, Linux (Debian/Ubuntu plus one fallback), Windows PowerShell and WSL.
- Commits: imperative title under 72 characters, no type prefix, path-limited (`git add <paths> && git commit -m "<title>" -- <paths>`), signed by the repository's configuration.
- Gate after every task: `make -s clean && python3 -m pytest -q -p no:cacheprovider tests` passes, and the vermin gate above exits 0.

## Review Focus

1. A path with spaces or non-ASCII characters (`/home/u/my project/módulo.php`) reaches each fixer as one argument and still matches PHP-CS-Fixer's `list-files` output. Task 4 `test_covered_path_with_space`, Task 5 `test_pint_path_with_space`.
2. `list-files` output on Windows (backslashes, case differences) still matches the open file. Task 6 runs the PHP-CS-Fixer integration tests on `windows-latest`.
3. A mistyped `fixer` value (`"php_cs_fixer"`) gives the unknown-fixer message instead of a refusal or a crash. Task 5 `test_unknown_fixer`.
4. A PHP-CS-Fixer diff that does not match the buffer leaves the buffer unchanged with an error, never half applied. Task 4 `test_diff_mismatch_raises`, Task 5 `test_php_cs_fixer_bad_diff`.
5. A `--version` output with no version number gives an error naming the tool, not a crash. Task 4 `test_version_unreadable`.

## File Structure

- `.github/workflows/tests.yml`: Task 1 (Python jobs), Task 6 (PHP jobs).
- `dev-requirements.pip`, `.travis.yml` (deleted), `tests/test_pyformatter_ruff.py`: Task 1.
- `CodeFormatter.py`, `tests/test_codeformatter_command.py`: Task 2; Task 7 removes the PHP transformations command and the `chmod`.
- `codeformatter/process.py`, `tests/test_process.py`: Task 3.
- `codeformatter/phpfixer.py`, `tests/test_phpfixer.py`: Tasks 3 and 4.
- `codeformatter/phpformatter.py`, `tests/test_phpformatter.py`, `CodeFormatter.sublime-settings`, `codeformatter/reloader.py`: Task 5.
- `tests/test_phpformatter_tools.py`: Task 6.
- Removals: Task 7. `README.md`, `messages/install.txt`, `messages.json`, `messages/4.5.0.txt`: Task 8.

---

### Task 1: CI for the Python suite

**Files:**
- Create: `.github/workflows/tests.yml`
- Modify: `dev-requirements.pip`, `tests/test_pyformatter_ruff.py`
- Delete: `.travis.yml`

**Interfaces:**
- Produces: workflow jobs `tests`, `ruff-oldest`, `python33` (Task 6 adds `php`, `php-oldest`); the contract that a real-tool test module raises at import when its tool is missing and `CODEFORMATTER_REQUIRE_TOOLS=1`.

- [ ] **Step 1: Make the ruff integration tests version-tolerant and requirable.** In `test_ruff_reports_syntax_error` and `test_ruff_error_is_plain_under_forced_color`, assert `out == ''`, `err.startswith('error:')` and `'\x1b[' not in err`. At module level, after `RUFF` is resolved: when `RUFF` is empty and `os.environ.get('CODEFORMATTER_REQUIRE_TOOLS') == '1'`, raise `RuntimeError('ruff is required (CODEFORMATTER_REQUIRE_TOOLS=1) but was not found')`.
- [ ] **Step 2: Verify against both ruff versions and both modes.**
  - `CODEFORMATTER_TEST_RUFF=$(uv run -q --no-project --with ruff==0.1.2 sh -c 'readlink -f "$(command -v ruff)"') python3 -m pytest -q -p no:cacheprovider tests/test_pyformatter_ruff.py`: `5 passed`.
  - The same with `ruff==0.17.1`: `5 passed`.
  - `env -u CODEFORMATTER_TEST_RUFF PATH=/usr/bin:/bin CODEFORMATTER_REQUIRE_TOOLS=1 python3 -m pytest -q -p no:cacheprovider tests/test_pyformatter_ruff.py`: exits non-zero with the `ruff is required` error; without `CODEFORMATTER_REQUIRE_TOOLS`: `5 skipped`.
- [ ] **Step 3: `dev-requirements.pip`** holds exactly one line, `pytest==9.0.2`.
- [ ] **Step 4: Write `.github/workflows/tests.yml`.** Triggers: `pull_request`, and `push` to `master`. Jobs:
  - `tests`: matrix `os: [ubuntu-latest, windows-latest, macos-latest]`, `fail-fast: false`; checkout, setup-python `3.14`; `python -m pip install -r dev-requirements.pip ruff==0.17.1`; `python -m pytest -q -p no:cacheprovider tests --ignore=tests/test_phpformatter_tools.py` with `CODEFORMATTER_TEST_RUFF: ruff` and `CODEFORMATTER_REQUIRE_TOOLS: '1'`.
  - `ruff-oldest`: Ubuntu; the same with `ruff==0.1.2`, running only `tests/test_pyformatter_ruff.py`.
  - `python33`: Ubuntu, setup-python `3.14`; `python -m pip install vermin==1.9.2`; `vermin --target=3.3- --violations --exclude str.decode --no-tips CodeFormatter.py codeformatter`.
- [ ] **Step 5: Delete `.travis.yml`.**
- [ ] **Step 6: Lint the workflow, after proving the linter fails.** Copy the workflow to a scratch directory with `runs-on` removed from one job; `docker run --rm -v "<scratch>:/repo" -w /repo rhysd/actionlint:1.7.12` must exit non-zero. Then `docker run --rm -v "$PWD:/repo" -w /repo rhysd/actionlint:1.7.12`: no output, exit 0.
- [ ] **Step 7: Gates, then commit.** `git add .github/workflows/tests.yml dev-requirements.pip tests/test_pyformatter_ruff.py && git add -u .travis.yml && git commit -m "Run the test suite on GitHub Actions" -- .github/workflows/tests.yml dev-requirements.pip tests/test_pyformatter_ruff.py .travis.yml`

---

### Task 2: Errors while saving go to the status bar

**Files:**
- Modify: `CodeFormatter.py` (`run_formatter`, the result branch at the end)
- Create: `tests/test_codeformatter_command.py`

**Interfaces:**
- Consumes: `run_formatter(view, edit, *args, **kwargs)` with `saving` and `quiet` keyword flags, `console_write`, `show_error`, `Formatter` imported in `CodeFormatter.py`.

- [ ] **Step 1: Write the failing tests.** A helper loads the plugin the way Sublime Text does: `monkeypatch.setitem(sys.modules, 'CodeFormatter', <ModuleType with __path__ = [repo root]>)`; `sys.modules['sublime_plugin']` is a `ModuleType` whose `TextCommand` and `EventListener` are `object` (a Mock cannot be a base class); when `importlib.util.find_spec('imp')` is `None`, `sys.modules['imp']` is a `ModuleType` with `reload = importlib.reload`; then `importlib.import_module('CodeFormatter.CodeFormatter')`. `Formatter` in that module is replaced by a fake whose `exists()` and `format_on_save_enabled()` return `True` and whose `format(text)` returns the pair under test. `view` is a `Mock` with `is_scratch()` `False`, `size()` `3`, `substr(...)` `'x=1'`. Reset the shared `sublime` mock before each test.

| Test | `format` returns | Call | Assertions |
|---|---|---|---|
| `test_saving_error_goes_to_status_bar` | `('', 'boom\nsecond line')` | `run_formatter(view, edit, saving=True)` | `sublime.status_message.assert_called_once_with('CodeFormatter: Format error: boom')`; `sublime.error_message` not called; captured stdout contains `boom\nsecond line` |
| `test_format_code_error_shows_dialog` | same | `run_formatter(view, edit)` | `sublime.error_message.assert_called_once_with('CodeFormatter\n\nFormat error:\nboom\nsecond line')`; `status_message` not called |
| `test_saving_success_replaces_buffer` | `('x = 1', '')` | `run_formatter(view, edit, saving=True)` | `view.replace` called once; neither message function called |

- [ ] **Step 2: Run them:** `python3 -m pytest -q -p no:cacheprovider tests/test_codeformatter_command.py`. Expected: the first test FAILS (a dialog is shown), the other two pass.
- [ ] **Step 3: Implement.** In `run_formatter`, between the replace branch and `elif not quiet:`, add a `saving` branch: `sublime.status_message('CodeFormatter: Format error: ' + <first line of stderr>)` and `console_write('Format error:\n' + stderr, True)`.
- [ ] **Step 4: Run them again:** `3 passed`.
- [ ] **Step 5: Gates, then commit** `CodeFormatter.py tests/test_codeformatter_command.py` as "Show format-on-save errors in the status bar".

---

### Task 3: Run a tool and pick the PHP fixer

**Files:**
- Create: `codeformatter/process.py`, `codeformatter/phpfixer.py`, `tests/test_process.py`, `tests/test_phpfixer.py`

**Interfaces:**
- Produces:
  - `process.TIMEOUT = 10`; `class process.Timeout(Exception)`; `process.run(cmd, text, cwd, platform) -> (returncode: int, stdout: bytes, stderr: str)`. `stderr` is decoded with `'utf-8', 'replace'` and stripped. The environment is `os.environ` minus `FORCE_COLOR` and `CLICOLOR_FORCE`; on `platform == 'windows'` a hidden-window `startupinfo` is passed. `OSError` from `Popen` propagates; after `TIMEOUT` seconds the process is killed and reaped and `Timeout` is raised.
  - `phpfixer.FIXERS = ('pint', 'php-cs-fixer', 'phpcbf')`; `phpfixer.CONFIG_FILES = [('pint', ['pint.json']), ('php-cs-fixer', ['.php-cs-fixer.php', '.php-cs-fixer.dist.php']), ('phpcbf', ['phpcs.xml', '.phpcs.xml', 'phpcs.xml.dist', '.phpcs.xml.dist'])]`; `phpfixer.PACKAGES = {'pint': 'laravel/pint', 'php-cs-fixer': 'friendsofphp/php-cs-fixer', 'phpcbf': 'squizlabs/php_codesniffer'}`.
  - `phpfixer.find_config(file_name, forced) -> (fixer, config_dir, config_file) | None`; `config_file` is an absolute path or `None`; `config_dir` is `None` only for a never-saved buffer with `forced` set.
  - `phpfixer.find_executable(fixer, config_dir, fixer_path, php_path) -> list | None`: `fixer_path` (with `~` expanded) as `[path]`; else the nearest `vendor/bin/<fixer>` at or above `config_dir` as `[php_path or 'php', script]`; else `shutil.which(fixer)` as `[path]`; else `None`.

- [ ] **Step 1: Write the failing tests** (projects built in `tmp_path`; a file is `proj/src/a.php` unless stated).

| Test | Layout or call | Expected |
|---|---|---|
| `test_config_file_names` (parametrized over the seven names) | `proj/<name>` | `(fixer, str(proj), str(proj/<name>))` |
| `test_nearest_config_wins` | `proj/phpcs.xml`, `proj/sub/pint.json`, file `proj/sub/x/a.php` | `('pint', str(proj/sub), ...)` |
| `test_tie_order` | `pint.json`, `.php-cs-fixer.dist.php`, `phpcs.xml` in one directory; then without `pint.json` | `'pint'`; then `'php-cs-fixer'` |
| `test_pint_fallback` | `proj/composer.json`, `proj/vendor/bin/pint`, no config | `('pint', str(proj), None)` |
| `test_pint_fallback_uses_nearest_composer_dir` | `parent/composer.json` and `parent/vendor/bin/pint`; `parent/proj/composer.json` without Pint | `None` |
| `test_no_config` | empty tree | `None` |
| `test_forced_uses_its_own_config` | `proj/pint.json`, `parent/phpcs.xml`, forced `'phpcbf'` | `('phpcbf', str(parent), str(parent/'phpcs.xml'))` |
| `test_forced_without_config` | forced `'pint'`: `proj/composer.json` only; then no `composer.json` | `('pint', str(proj), None)`; then `('pint', str(proj/src), None)` |
| `test_unsaved_buffer` | `find_config(None, None)`; `find_config(None, 'pint')` | `None`; `('pint', None, None)` |
| `test_executable_fixer_path` | `HOME` set to `/home/u`, `fixer_path='~/bin/pint'` | `['/home/u/bin/pint']` |
| `test_executable_vendor_bin` | `proj/vendor/bin/phpcbf`, `php_path=''`; then `'/opt/php'` | `['php', <script>]`; then `['/opt/php', <script>]` |
| `test_executable_vendor_bin_above_config` | `root/vendor/bin/phpcbf`, config dir `root/packages/a` | `['php', str(root/'vendor'/'bin'/'phpcbf')]` |
| `test_executable_path_lookup` | `shutil.which` in `phpfixer` patched to `'/usr/local/bin/pint'` | `['/usr/local/bin/pint']` |
| `test_executable_missing` | nothing anywhere, `which` returns `None` | `None` |

`tests/test_process.py` (`Popen` patched, as in `tests/test_pyformatter.py`): `cwd` and three pipes passed; `FORCE_COLOR`/`CLICOLOR_FORCE` absent from `env` while other variables survive; `(rc, stdout, stripped stderr)` returned; undecodable stderr gets replacement characters; `TimeoutExpired` kills, reaps and raises `process.Timeout`; `startupinfo` passed on `'windows'` only.
- [ ] **Step 2: Run them:** `python3 -m pytest -q -p no:cacheprovider tests/test_phpfixer.py tests/test_process.py`. Expected: FAIL (modules missing).
- [ ] **Step 3: Implement** `process.py` and `find_config`, `find_executable` in `phpfixer.py` per the Interfaces block and the spec's "Picking the fixer" and "Finding the fixer executable".
- [ ] **Step 4: Run them again:** all pass.
- [ ] **Step 5: Gates, then commit** the four files as "Pick the PHP fixer from the project's configuration".

---

### Task 4: Versions, PHP-CS-Fixer coverage and diffs

**Files:**
- Modify: `codeformatter/phpfixer.py`, `tests/test_phpfixer.py`

**Interfaces:**
- Consumes: `process.run` from Task 3.
- Produces:
  - `class phpfixer.FixerError(Exception)`: a message for the dialog.
  - `phpfixer.tool_version(tool, argv, cwd, platform) -> (int, int, int)`: runs `argv + ['--version']`, takes the first `X.Y.Z` in stdout then stderr, caches by `tuple(argv)` for the session; raises `FixerError('could not read the <tool> version')`.
  - `phpfixer.covered_by_php_cs_fixer(argv, config_dir, config_file, file_name, platform) -> bool`: runs `argv + ['list-files', '--no-ansi'] + (['--config=' + config_file] if config_file else [])` with `cwd=config_dir`; each output line is stripped of whitespace and surrounding single quotes and joined to `config_dir`; paths compare after `os.path.normcase(os.path.normpath(...))`. The set is cached by `(config_dir, config_file, mtime of config_file)`; a file missing from the cached set triggers one refresh. A non-zero exit raises `FixerError(stderr)`.
  - `phpfixer.apply_unified_diff(text, diff) -> str`: applies each `@@ -a,b +c,d @@` hunk in order; a context or removed line that does not match raises `ValueError`; `\ No newline at end of file` is honoured.

- [ ] **Step 1: Write the failing tests.**

| Test | Setup | Expected |
|---|---|---|
| `test_version_phpcbf` | stdout `PHP_CodeSniffer version 4.0.4 (stable) by Squiz and PHPCSStandards` | `(4, 0, 4)` |
| `test_version_pint_cached` | stdout `Pint 1.32.1`, called twice | `(1, 32, 1)`; `process.run` called once |
| `test_version_unreadable` | stdout `''`, stderr `''` | `FixerError('could not read the phpcbf version')` |
| `test_covered_listed_file` | list output `'./src/a.php'\n'./src/b.php'\n` | `True` for `src/a.php`; one run |
| `test_covered_excluded_refreshes_once` | same output, file `legacy/l.php` | `False`; two runs |
| `test_covered_cache_and_config_change` | second call, then the config's mtime changes | no new run; then one new run |
| `test_covered_path_with_space` | output `'./my dir/módulo.php'` | `True` for that file |
| `test_covered_error` | exit 1, stderr `boom` | `FixerError('boom')` |
| `test_diff_single_hunk` | text `<?php\n$a = [ 1,2 ];\n`, diff `--- php://stdin\n+++ php://stdin\n@@ -1,2 +1,2 @@\n <?php\n-$a = [ 1,2 ];\n+$a = array( 1, 2 );\n` | `<?php\n$a = array( 1, 2 );\n` |
| `test_diff_two_hunks_and_insertions` | a 12-line text with changes at lines 2 and 10, one hunk adding a line | the hand-written expected text |
| `test_diff_no_final_newline` | the new side ends with `\ No newline at end of file` | result has no final newline |
| `test_diff_mismatch_raises` | a removed line that differs from the text | `ValueError` |

- [ ] **Step 2: Run them:** FAIL (functions missing).
- [ ] **Step 3: Implement** the three functions and `FixerError` per the Interfaces block. Clear the two caches in a test fixture.
- [ ] **Step 4: Run them again:** all pass.
- [ ] **Step 5: Gates, then commit** both files as "Read fixer versions, PHP-CS-Fixer coverage and diffs".

---

### Task 5: PhpFormatter runs the project's fixer

**Files:**
- Modify: `codeformatter/phpformatter.py` (replace the phpF body, 165 lines; keep `PhpFormatter`, its constructor and `format_on_save_enabled`), `CodeFormatter.sublime-settings` (the `codeformatter_php_options` block), `codeformatter/reloader.py` (`mods_load_order`: add `'.process'` and `'.phpfixer'` before `'.phpformatter'`)
- Create: `tests/test_phpformatter.py`

**Interfaces:**
- Consumes: `process.run`, `process.Timeout`, `process.TIMEOUT`; `phpfixer.find_config`, `find_executable`, `tool_version`, `covered_by_php_cs_fixer`, `apply_unified_diff`, `FixerError`, `PACKAGES`.
- Produces: `PhpFormatter(formatter).format(text)` per the Global Constraints contract.

Commands (working directory `config_dir`; `<file>` the file name; `--config=` only when `config_file` is set; path options left out for a never-saved buffer):
- Pint: `argv + ['--no-ansi', '--config=<config_file>', '--stdin-filename=<file>', '-']`, after `tool_version` is at least `(1, 26, 0)`.
- phpcbf: `argv + ['-q', '--no-colors', '--stdin-path=<file>', '-']`, mapped by `tool_version`'s major per the spec's table.
- PHP-CS-Fixer: for a saved file, `covered_by_php_cs_fixer` first; then `argv + ['fix', '--no-ansi', '--using-cache=no', '--diff', '--format=json', '--config=<config_file>', '-']`; exit 0 is unchanged, exit 8 applies `files[0].diff`.
- Every fixer: stderr from a successful run goes to the console with `CodeFormatter: ` before each line; `OSError` gives the not-found message; `process.Timeout` the timeout message; `FixerError` its message; another non-zero exit gives stderr, or `<tool> exited with code <N>` when stderr is empty.

- [ ] **Step 1: Write the failing tests** (`codeformatter.phpformatter.process.run` patched with a `side_effect` list; real projects in `tmp_path` with empty `vendor/bin/<tool>` files, so detection and lookup run for real).

| Test | Setup | Expected |
|---|---|---|
| `test_refuses_without_config` | no configuration | `('', <refusal>)`; `run` not called |
| `test_unknown_fixer` | `fixer: "php_cs_fixer"` | `('', <unknown-fixer message>)` |
| `test_fixer_not_found` | `pint.json`, no executable anywhere | `('', 'pint not found. Run "composer require --dev laravel/pint" in the project, or set fixer_path in codeformatter_php_options.')` |
| `test_fixer_cannot_start` | `run` raises `OSError` | the same not-found message |
| `test_fixer_timeout` | `run` raises `process.Timeout` | `('', 'pint did not finish in 10 seconds')` |
| `test_pint_formats` | versions `Pint 1.32.1`, then `(0, b'<?php\nfixed\n', '')` | `(b'<?php\nfixed\n', '')`; second call's command and `cwd` as above |
| `test_pint_path_with_space` | file `proj/my dir/módulo.php` | `--stdin-filename=<that path>` is one argument |
| `test_pint_too_old` | version `Pint 1.25.1` | `('', <Pint minimum>)`; one `run` call |
| `test_pint_fallback_has_no_config_option` | `composer.json` and `vendor/bin/pint` only | no `--config=` argument |
| `test_pint_error` | `(1, b'', '')` | `('', 'pint exited with code 1')` |
| `test_phpcbf4_results` | version `4.0.4`; fix `(0, out)`, `(2, out)`, `(16, b'', 'ERROR: No files were checked.')`, `(16, b'', 'ERROR: Ruleset x is not valid')` | `(out, '')`, `(out, '')`, `(text, '')`, `('', 'ERROR: Ruleset x is not valid')` |
| `test_phpcbf3_results` | version `3.13.6`; fix `(1, out)`, `(0, b'No fixable errors were found\n\nTime: 8ms')`, `(3, b'', 'boom')` | `(out, '')`, `(text, '')`, `('', 'boom')` |
| `test_php_cs_fixer_applies_diff` | `list-files` lists the file; fix `(8, <JSON with the Task 4 single-hunk diff>, '')` | the patched bytes, `''` |
| `test_php_cs_fixer_clean` | fix `(0, b'{"files": []}', '')` | `(text, '')` |
| `test_php_cs_fixer_excluded` | `list-files` lists another file only (twice) | `(text, '')`; no fix call |
| `test_php_cs_fixer_bad_diff` | exit 8 with a diff that does not match | `('', 'php-cs-fixer returned a diff that does not match the buffer')`; nothing partly patched |
| `test_warnings_to_console` | Pint success with stderr `a\nb` | console shows `CodeFormatter: a` and `CodeFormatter: b` |
| `test_unsaved_forced_phpcbf` | `file_name=None`, `fixer: "phpcbf"` | `cwd` is `None`; no `--stdin-path=` |

- [ ] **Step 2: Run them:** FAIL (the phpF body runs instead).
- [ ] **Step 3: Implement** `PhpFormatter.format` per the commands above; set the settings block exactly as the spec's Settings section; update the reloader list.
- [ ] **Step 4: Run them again:** all pass; then the gates.
- [ ] **Step 5: Commit** the four files as "Format PHP with the project's Pint, PHP-CS-Fixer or phpcbf".

---

### Task 6: PHP integration tests and their CI jobs

**Files:**
- Create: `tests/test_phpformatter_tools.py`
- Modify: `.github/workflows/tests.yml`

**Interfaces:**
- Consumes: `PhpFormatter` from Task 5; the require-tools contract from Task 1.
- Produces: env `CODEFORMATTER_TEST_PHP_TOOLS`, a directory with `composer.json` and `vendor/bin/<tool>` for the tools under test.

The module skips a test whose `vendor/bin/<tool>` is missing, and raises at import when `CODEFORMATTER_TEST_PHP_TOOLS` is unset and `CODEFORMATTER_REQUIRE_TOOLS=1`. Each test writes its project to a fresh directory under `<tools>/cases/` (removed afterwards) so the real `vendor/bin` lookup runs, writes the file to disk too (PHP-CS-Fixer lists files on disk), and formats from `monkeypatch.chdir(tmp_path)`. The input is `<?php\n$a = [ 1,2 ];\n`.

| Test | Configuration | Expected |
|---|---|---|
| `test_pint_applies_project_config` | `pint.json` `{"preset": "psr12", "rules": {"array_syntax": {"syntax": "long"}}}` | `b'array(' in out`, `err == ''` |
| `test_pint_leaves_excluded_file_unchanged` | same plus `"exclude": ["legacy"]`, file `legacy/l.php` | `(text, '')` |
| `test_pint_syntax_error_unchanged` | input `<?php\nfunction (\n` | `(text, '')` |
| `test_phpcbf_applies_project_ruleset` | `phpcs.xml` with `Generic.Arrays.DisallowShortArraySyntax` | `b'array(' in out` |
| `test_phpcbf_leaves_excluded_file_unchanged` | plus `<exclude-pattern>*/legacy/*</exclude-pattern>` | `(text, '')` |
| `test_phpcbf_syntax_error_unchanged` | broken input | `(text, '')` |
| `test_php_cs_fixer_applies_project_config` | `.php-cs-fixer.dist.php`: `setRules(['array_syntax' => ['syntax' => 'long']])`, Finder `in(__DIR__)->exclude('legacy')` | `b'array(' in out` |
| `test_php_cs_fixer_leaves_excluded_file_unchanged` | same, file `legacy/l.php` | `(text, '')` |
| `test_php_cs_fixer_syntax_error_unchanged` | broken input | buffer unchanged; map PHP-CS-Fixer's observed exit code in `phpformatter.py` if needed |

- [ ] **Step 1: Write the tests.** Build tool directories in the scratchpad: `composer require -q --no-plugins --no-scripts --no-interaction --dev --working-dir=<dir> laravel/pint:1.32.1 friendsofphp/php-cs-fixer:3.95.27 squizlabs/php_codesniffer:4.0.4`, and a second with `laravel/pint:1.26.0 squizlabs/php_codesniffer:3.13.6`.
- [ ] **Step 2: Run them:** `CODEFORMATTER_TEST_PHP_TOOLS=<latest dir> python3 -m pytest -q -p no:cacheprovider tests/test_phpformatter_tools.py`: `9 passed`; with `<oldest dir>` and `-k "pint or phpcbf"`: `6 passed`. Unset: `9 skipped`. Fix `phpformatter.py` (with a unit test in `tests/test_phpformatter.py`) where a real tool disagrees with the mapping.
- [ ] **Step 3: Prove a test can fail.** Remove `--config=` from Pint's command, confirm `git diff --stat` shows the change, expect `test_pint_applies_project_config` to FAIL, restore with `git checkout -- codeformatter/phpformatter.py`.
- [ ] **Step 4: Add the CI jobs.** `php`: matrix `[ubuntu-latest, windows-latest]`; setup-python `3.14`, setup-php `php-version: '8.5'` with `tools: composer`; the latest-tools `composer require` into `${{ runner.temp }}/php-tools`; `pip install -r dev-requirements.pip`; `python -m pytest -q -p no:cacheprovider tests/test_phpformatter_tools.py` with `CODEFORMATTER_TEST_PHP_TOOLS` and `CODEFORMATTER_REQUIRE_TOOLS: '1'`. `php-oldest`: Ubuntu, the oldest tools, `-k "pint or phpcbf"`. Lint with actionlint as in Task 1 Step 6.
- [ ] **Step 5: Gates, then commit** both files as "Test the PHP fixers against the real tools".

---

### Task 7: Remove phpF

**Files:**
- Delete: `codeformatter/lib/phpbeautifier/` (four `.phar` files), `PHP-Transformations.md`, `tests/data/php/`
- Modify: `CodeFormatter.py` (remove `CodeFormatterShowPhpTransformationsCommand`, lines 86-157, and the `chmod` block in `plugin_loaded`), `CodeFormatter.sublime-commands` (remove the "CodeFormatter: Show PHP Transformations" entry)

- [ ] **Step 1: Delete and edit** as listed; drop imports in `CodeFormatter.py` that nothing uses afterwards.
- [ ] **Step 2: Verify.** `python3 -I -c "import json; json.load(open('CodeFormatter.sublime-commands'))"` succeeds; `git grep -n -i -e phpbeautifier -e 'fmt.phar' -e phpf.phar -e php_transformations -e 'PHP-Transformations' -- . ':!docs/superpowers' ':!README.md' ':!messages/install.txt'` prints nothing (exit 1); `tests/test_codeformatter_command.py` still passes; gates.
- [ ] **Step 3: Commit** with `git add -u` on the deleted paths, as "Remove phpF and its PHP transformations command".

---

### Task 8: PHP docs and the upgrade message

**Files:**
- Modify: `README.md` (the PHP entry in the language list; the `### PHP` section up to the next `### ` heading), `messages/install.txt` (the same two places), `messages.json`
- Create: `messages/4.5.0.txt`

- [ ] **Step 1: Rewrite the PHP entry and section in both files with identical text,** covering the spec's Documentation list: the fixer each config file selects, the Pint fallback, the refusal and how to fix it, the three new settings (block identical to the settings file), the retired options, Composer installs (`composer require --dev laravel/pint`, `friendsofphp/php-cs-fixer` or `squizlabs/php_codesniffer`) plus a global install per platform (macOS, Debian/Ubuntu and one fallback, Windows PowerShell, WSL), the Pint 1.26.0 minimum, PHPCS 3 and 4 support, and that a User override must copy the whole `codeformatter_php_options` block.
- [ ] **Step 2: Upgrade message.** `messages.json` gains `"4.5.0": "messages/4.5.0.txt"`; the file says, in a few short paragraphs, that phpF is gone because it cannot run on PHP 8, how the fixer is now picked, which old options no longer apply, what the refusal means, and that format-on-save errors now appear in the status bar.
- [ ] **Step 3: Verify.** The two PHP sections are identical (extract and `cmp`); `python3 -I -c "import json; json.load(open('messages.json'))"` succeeds; `git grep -n -i -e phpf -e phpfmt -e 'PHP-Transformations' -- . ':!docs/superpowers'` prints nothing; no em or en dashes in the changed files; gates.
- [ ] **Step 4: Commit** the four files as "Document PHP formatting with the project's fixer".
