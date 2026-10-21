# Python Formatting with ruff Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: invoke superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Format Code and format on save run Python buffers through `ruff format`, with the project's own ruff configuration.

**Architecture:** `PyFormatter.format` pipes the buffer to `ruff format` through `subprocess.Popen`, the way `GoFormatter` pipes it to `gofmt`, and judges success by exit code instead of by empty stderr. The settings file gains `ruff_path`; the README and the install message document the formatter.

**Tech Stack:** Python 3.3 and 3.14 (Sublime Text plugin hosts), pytest 9, ruff 0.17.1 for the integration tests.

**Spec:** `docs/superpowers/specs/2026-10-20-python-formatter-design.md`

## Global Constraints

- Runs on Python 3.3 and 3.14: `subprocess.Popen` with `communicate(text, timeout=10)`. No `subprocess.run`, no `capture_output`, no f-strings, no `shell=True`.
- Formatter contract: `format(text)` receives the whole buffer as UTF-8 bytes and returns `(stdout, stderr)`. CodeFormatter applies `stdout` only when `stderr` is empty and `stdout` is not.
- Invocation: `[ruff, 'format', '--force-exclude', '--stdin-filename', file_name, '-']`, or `[ruff, 'format', '-']` when `file_name` is `None`. `ruff` is `codeformatter_python_options.ruff_path` when non-empty, else `'ruff'`.
- Messages, verbatim: `ruff not found. Install ruff, or set ruff_path in codeformatter_python_options to its full path.`, `ruff did not finish in 10 seconds`, `ruff exited with code <N>`.
- On exit 0, ruff's stderr (warnings) is printed to the console prefixed `CodeFormatter: ` and never returned as the error.
- Windows: the child process starts with a hidden window, as in `codeformatter/phpformatter.py:138-141`.
- ruff only; no CodeFormatter-level style options.
- Docs: install steps for macOS, Linux (Debian/Ubuntu plus a fallback), Windows PowerShell and WSL. `README.md` and `messages/install.txt` get the same Python changes. No assistant-specific wording.
- Commits: imperative title under 72 characters without a type prefix, path-limited (`git add <paths> && git commit -m "<title>" -- <paths>`), signed by the repository's existing configuration.

## Review Focus

1. A file name with spaces or non-ASCII characters (`/home/u/my project/módulo.py`) reaches ruff as one argument, with no shell. Pinned in Task 1 by `test_py_formatter_passes_paths_as_single_arguments`.
2. A `ruff_path` with spaces (`C:\Program Files\ruff\ruff.exe`) is used as one argument. Same test.
3. ruff stderr that is not valid UTF-8 (a Windows code page) is decoded with replacement characters and never raises. Task 1, `test_py_formatter_undecodable_stderr`.
4. An invalid project config (`line-length = "x"`) gives a non-zero exit, ruff's message in the dialog and an unchanged buffer. Task 2, `test_ruff_reports_invalid_config`.
5. A file the project excludes comes back byte-identical, so the buffer does not change and no dialog appears. Task 2, `test_ruff_leaves_excluded_file_unchanged`.

## File Structure

- `codeformatter/pyformatter.py`: builds the ruff command, runs it, maps the result onto the formatter contract (Task 1).
- `tests/test_pyformatter.py`: unit tests with `Popen` replaced (Task 1).
- `tests/test_pyformatter_ruff.py`: integration tests against a real ruff (Task 2).
- `CodeFormatter.sublime-settings`, `README.md`, `messages/install.txt`: settings and docs (Task 3).

---

### Task 1: PyFormatter runs ruff

**Files:**
- Modify: `codeformatter/pyformatter.py` (whole file, 30 lines today)
- Modify: `tests/test_pyformatter.py`

**Interfaces:**
- Consumes: `formatter.settings['codeformatter_python_options']` (dict), `formatter.file_name` (str or `None`), `formatter.platform` (`'windows'`, `'osx'` or `'linux'`), all set by `Formatter` in `codeformatter/formatter.py`.
- Produces: `PyFormatter.format(text: bytes) -> tuple`, returning `(ruff_stdout_bytes, '')` on exit 0 and `('', error_str)` otherwise. `PyFormatter.format_on_save_enabled(file_name)` is unchanged.

- [ ] **Step 1: Write the failing tests**

In `tests/test_pyformatter.py`, delete `test_py_formatter_format_not_supported` and keep `test_py_formatter_format_on_save_enabled`. Add these helpers and tests; every test except the last two takes `@patch('codeformatter.pyformatter.subprocess.Popen')`:

```python
FILE = '/proj/pkg/m.py'
NOT_FOUND = ('ruff not found. Install ruff, or set ruff_path in '
             'codeformatter_python_options to its full path.')


def run(popen, out=b'', err=b'', returncode=0, opts=None, file_name=FILE,
        platform='linux'):
    from codeformatter.pyformatter import PyFormatter
    popen.return_value.communicate.return_value = (out, err)
    popen.return_value.returncode = returncode
    mocked_formatter = Mock()
    mocked_formatter.settings = {
        'codeformatter_python_options': opts or {'syntaxes': 'python'}}
    mocked_formatter.file_name = file_name
    mocked_formatter.platform = platform
    return PyFormatter(mocked_formatter).format(b'x=1\n')


def command(popen):
    return popen.call_args[0][0]
```

| Test | Setup | Assertions |
|---|---|---|
| `test_py_formatter_runs_ruff_on_the_file` | `out=b'x = 1\n'` | result `== (b'x = 1\n', '')`; `command(popen) == ['ruff', 'format', '--force-exclude', '--stdin-filename', FILE, '-']`; `communicate.assert_called_once_with(b'x=1\n', timeout=10)`; `'startupinfo' not in popen.call_args[1]`; `popen.call_args[1].get('shell') is not True` |
| `test_py_formatter_unsaved_buffer` | `file_name=None`, `out=b'x = 1\n'` | `command(popen) == ['ruff', 'format', '-']` |
| `test_py_formatter_passes_paths_as_single_arguments` | `opts={'ruff_path': 'C:\\Program Files\\ruff\\ruff.exe'}`, `file_name='/home/u/my project/módulo.py'`, `out=b'x = 1\n'` | `command(popen) == ['C:\\Program Files\\ruff\\ruff.exe', 'format', '--force-exclude', '--stdin-filename', '/home/u/my project/módulo.py', '-']` |
| `test_py_formatter_empty_ruff_path_uses_ruff` | `opts={'ruff_path': ''}`, `out=b'x = 1\n'` | `command(popen)[0] == 'ruff'` |
| `test_py_formatter_warning_goes_to_console` (also takes `capsys`) | `out=b'x = 1\n'`, `err=b'warning: conflict\n'` | result `== (b'x = 1\n', '')`; `'CodeFormatter: warning: conflict' in capsys.readouterr().out` |
| `test_py_formatter_error_returns_stderr` | `err=b'error: Failed to parse m.py:1:5: Expected an identifier\n'`, `returncode=2` | result `== ('', 'error: Failed to parse m.py:1:5: Expected an identifier')` |
| `test_py_formatter_error_without_stderr` | `returncode=2` | result `== ('', 'ruff exited with code 2')` |
| `test_py_formatter_undecodable_stderr` | `err=b'\xff\xfe bad'`, `returncode=2` | result `== ('', '\ufffd\ufffd bad')` |
| `test_py_formatter_ruff_not_found` | `popen.side_effect = FileNotFoundError(2, 'No such file or directory')` | result `== ('', NOT_FOUND)` |
| `test_py_formatter_timeout_kills_ruff` | `communicate.side_effect = [subprocess.TimeoutExpired(['ruff'], 10), (b'', b'')]` set before calling `format` (a `side_effect` wins over `run`'s `return_value`) | result `== ('', 'ruff did not finish in 10 seconds')`; `popen.return_value.kill.assert_called_once_with()` |
| `test_py_formatter_hides_window_on_windows` | `patch.multiple('codeformatter.pyformatter.subprocess', create=True, STARTUPINFO=Mock(return_value=Mock(dwFlags=0)), STARTF_USESHOWWINDOW=1, SW_HIDE=0)` around the call, `platform='windows'`, `out=b'x = 1\n'` | `'startupinfo' in popen.call_args[1]` |

- [ ] **Step 2: Run the tests to verify they fail**

Run: `python3 -m pytest -q -p no:cacheprovider tests/test_pyformatter.py`
Expected: the 11 new tests FAIL (the current `format` returns the "not supported" error and never calls `Popen`); the 5 `format_on_save_enabled` cases pass.

- [ ] **Step 3: Implement `PyFormatter.format(self, text)` in `codeformatter/pyformatter.py`**

Remove the module-level `print` and the "not supported" return; add `import subprocess` and a module constant `TIMEOUT = 10`. In `format`: build the command from the Global Constraints; start `Popen` with `stdin`, `stdout` and `stderr` piped, adding `startupinfo` built as in `phpformatter.py:138-141` when `self.formatter.platform == 'windows'`; turn `FileNotFoundError` from `Popen` into the not-found message and let any other exception reach `Formatter.format`'s handler (`codeformatter/formatter.py:81-85`); call `communicate(text, timeout=TIMEOUT)`, and on `subprocess.TimeoutExpired` call `kill()` then `communicate()` before returning the timeout message; decode stderr with `decode('utf-8', 'replace').strip()`; on exit 0 print any stderr text with the `CodeFormatter: ` prefix and return `(stdout, '')`; otherwise return `('', stderr_text or 'ruff exited with code %d' % returncode)`.

- [ ] **Step 4: Run the tests to verify they pass**

Run: `python3 -m pytest -q -p no:cacheprovider tests/test_pyformatter.py`
Expected: `16 passed`

- [ ] **Step 5: Run the full suite**

Run: `make -s clean && python3 -m pytest -q -p no:cacheprovider tests`
Expected: `217 passed`, no warnings summary.

- [ ] **Step 6: Commit**

```bash
git add codeformatter/pyformatter.py tests/test_pyformatter.py && git commit -m "Format Python with ruff" -- codeformatter/pyformatter.py tests/test_pyformatter.py
```

---

### Task 2: Integration tests against a real ruff

**Files:**
- Create: `tests/test_pyformatter_ruff.py`

**Interfaces:**
- Consumes: `PyFormatter.format(text: bytes)` from Task 1.
- Produces: nothing for later tasks.

The tests use the binary named by the environment variable `CODEFORMATTER_TEST_RUFF`, else `shutil.which('ruff')`, and skip the module when neither exists (`pytestmark = pytest.mark.skipif(...)`, reason `ruff not installed`). Each test builds a project under `tmp_path / 'proj'`, changes the working directory to an empty `tmp_path / 'elsewhere'` with `monkeypatch.chdir`, and formats with a `PyFormatter` whose mocked formatter has `ruff_path` set to that binary, `file_name` set to the file's path, and `platform` set to `'windows'` when `os.name == 'nt'`, else `'linux'`.

- [ ] **Step 1: Write the tests**

| Test | `proj/ruff.toml` | File and input | Assertions |
|---|---|---|---|
| `test_ruff_applies_project_config` | `line-length = 20` | `proj/pkg/m.py`, `b'x = {"alpha": 1, "beta": 2}\n'` | result `== (b'x = {\n    "alpha": 1,\n    "beta": 2,\n}\n', '')` |
| `test_ruff_leaves_excluded_file_unchanged` | `line-length = 20` and `extend-exclude = ["vendor"]` | `proj/vendor/v.py`, same input | result `== (b'x = {"alpha": 1, "beta": 2}\n', '')` |
| `test_ruff_reports_invalid_config` | `line-length = "x"` | `proj/pkg/m.py`, `b'x=1\n'` | `out == ''`; `err.startswith('ruff failed')`; `'ruff.toml' in err` |
| `test_ruff_reports_syntax_error` | `line-length = 20` | `proj/pkg/m.py`, `b'def (:\n'` | `out == ''`; `err.startswith('error: Failed to parse')` |

- [ ] **Step 2: Run them against ruff 0.17.1**

Run: `CODEFORMATTER_TEST_RUFF=$(uv run -q --no-project --with ruff==0.17.1 python -c 'from ruff.__main__ import find_ruff_bin; print(find_ruff_bin())') python3 -m pytest -q -p no:cacheprovider tests/test_pyformatter_ruff.py`
Expected: `4 passed`. Without the variable and without ruff on `PATH`: `4 skipped`.

- [ ] **Step 3: Prove the tests can fail**

Remove `'--stdin-filename', file_name` from the command in `codeformatter/pyformatter.py`, confirm `git diff --stat -- codeformatter/pyformatter.py` shows the change, rerun Step 2's command, and expect `test_ruff_applies_project_config` to FAIL. Restore with `git checkout -- codeformatter/pyformatter.py`. Repeat with `'--force-exclude'` removed and expect `test_ruff_leaves_excluded_file_unchanged` to FAIL. Confirm `git status --short` lists only the new test file afterwards.

- [ ] **Step 4: Commit**

```bash
git add tests/test_pyformatter_ruff.py && git commit -m "Test the Python formatter against a real ruff" -- tests/test_pyformatter_ruff.py
```

---

### Task 3: Settings and documentation

**Files:**
- Modify: `CodeFormatter.sublime-settings` (the `codeformatter_python_options` block)
- Modify: `README.md` (language list near line 15, a section before `### Go` near line 205, the TODO list near line 268)
- Modify: `messages/install.txt` (language list after the SCSS entry near line 17, a section before `### Visual Basic/VBScript` near line 185, the TODO list near line 214)

**Interfaces:**
- Consumes: the `ruff_path` setting read by Task 1.
- Produces: nothing for later tasks.

- [ ] **Step 1: Settings**

Make the block exactly the spec's (Behaviour, Settings): `syntaxes`, `format_on_save` (comma restored), and `"ruff_path": "" // Path to the ruff executable. If empty, uses "ruff" from PATH`.

- [ ] **Step 2: README**

- Language list: add `* Python - By [ruff](https://docs.astral.sh/ruff/formatter/)` after the `CSS,LESS,SASS` entry.
- New `### Python` section before `### Go`, in the style of the Go section (`Python - used [ruff](https://docs.astral.sh/ruff/formatter/)`, then `Language specific options:` and a `js` block identical to the settings block). Then say, in plain sentences, that style comes from the project's ruff configuration (the closest `.ruff.toml`, `ruff.toml` or `pyproject.toml` with `[tool.ruff]` above the file); that files the configuration excludes are left unchanged; that a never-saved buffer uses ruff's user-level configuration or defaults; and that `"syntaxes": ""` keeps CodeFormatter away from Python files formatted by another package.
- Install steps in the same section: macOS `brew install ruff`; Debian/Ubuntu `sudo apt install pipx` then `pipx install ruff`; other Linux `curl -LsSf https://astral.sh/ruff/install.sh | sh`; Windows PowerShell `powershell -c "irm https://astral.sh/ruff/install.ps1 | iex"`; WSL: the Linux steps inside WSL when Sublime Text runs inside WSL, the Windows steps for Windows Sublime Text editing files under `\\wsl.localhost`. Add that Sublime Text started from the macOS Dock does not see the shell's `PATH`, so `ruff_path` should be set to the output of `which ruff` (`where ruff` on Windows).
- TODO list: remove `* Python (for ST3)`.

- [ ] **Step 3: Install message**

Apply the same three changes to `messages/install.txt`: the language entry after `* SCSS - By Nishutosh Sharma`, the identical section before `### Visual Basic/VBScript`, and the TODO entry removed. Leave the file's other differences from the README alone.

- [ ] **Step 4: Verify**

Run: `python3 -I -c "import json, re, sys; src = open('CodeFormatter.sublime-settings', encoding='utf-8').read(); print(json.loads(re.sub(r'(\"(?:\\\\.|[^\"\\\\])*\")|//[^\n]*', lambda m: m.group(1) or '', src))['codeformatter_python_options'])"`
Expected: `{'syntaxes': 'python', 'format_on_save': False, 'ruff_path': ''}`

Run: `git grep -n "Python (for ST3)"`
Expected: no output (exit 1).

Run: `make -s clean && python3 -m pytest -q -p no:cacheprovider tests`
Expected: `217 passed, 4 skipped` (the integration tests skip without ruff).

- [ ] **Step 5: Commit**

```bash
git add CodeFormatter.sublime-settings README.md messages/install.txt && git commit -m "Document Python formatting with ruff" -- CodeFormatter.sublime-settings README.md messages/install.txt
```
