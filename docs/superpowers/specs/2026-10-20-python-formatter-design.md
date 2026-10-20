# Design: Python formatting with ruff

Status: approved by the maintainer, 2026-10-20.

Markers: **(maintainer)** is a decision the maintainer made. **(default)**
is a design default proposed here and open to review. Everything unmarked
follows from the code or from the probes below.

## Intent

Since Sublime Text 2 support and the PythonTidy formatter were removed,
running Format Code (`ctrl+alt+f`) or format on save on a Python file shows
"formatting python files on ST3 not supported!". The feature is for the
plugin's existing users **(maintainer)**: people who already use
CodeFormatter for other languages and expect the same command to work on
Python.

Success means the editor produces exactly what the project's own
`ruff format` run produces, so the editor and the project's CI never fight
over formatting.

Evidence of demand is thin: issue #332 (three users hit the "not supported"
error, 2017 to 2018) and issue #271 (a user who formats Python with another
package and wants CodeFormatter to stay out of the way). Dedicated Python
packages already exist (LSP-ruff, sublack); this feature does not compete
with them, it only removes the dead end for CodeFormatter users.

## Goals

- Format Code on a Python file pipes the whole buffer through `ruff format`
  and replaces the buffer with the result.
- The project's ruff configuration applies: the closest `.ruff.toml`,
  `ruff.toml` or `pyproject.toml` with a `[tool.ruff]` section above the
  file, including its `exclude` and `extend-exclude` lists.
- Format on save works through the existing `format_on_save` option
  (boolean, or a regular expression tested on the file name), off by
  default.
- When formatting fails, the buffer stays unchanged and the message tells
  the user what to do next.

## Non-goals

- Any formatter other than ruff, or a choice between formatters
  **(maintainer: ruff only)**.
- Finding ruff inside a project virtualenv automatically **(default)**.
- Formatting a selection. CodeFormatter has always formatted whole files.
- CodeFormatter-level style options (line length, quote style). The
  project's ruff configuration is the only source of style.
- Linting or import sorting (`ruff check --fix`).
- Windows Sublime Text running a ruff installed inside WSL. That would need
  a command with arguments in `ruff_path` (`wsl.exe ruff`) and translated
  paths for `--stdin-filename` **(default)**.

## Anti-goals

These would make the feature a failure even if it technically works:

- Output that differs from running `ruff format` on the same file from a
  terminal.
- Reformatting a file the project's ruff configuration excludes.
- An error dialog after a successful format.
- Sublime Text freezing because ruff hangs. Format on save runs
  synchronously in `on_pre_save`.
- A console window flashing on Windows on every format.
- Surprising users who format Python with another package. CodeFormatter
  acts on Python only when they run Format Code or turn `format_on_save` on,
  and `"syntaxes": ""` turns Python off in CodeFormatter entirely.

## Constraints

- **Python 3.3 and 3.14.** The package has no `.python-version` file.
  Since build 4213 (stable 4215, 2026-09-25) Sublime Text disables the
  Python 3.3 plugin host by default, so most users run the package on
  Python 3.14; Sublime Text 3, older builds, and users who set
  `"disable_plugin_host_3.3": false` still run it on 3.3. The code must
  work on both: `subprocess.Popen` with `communicate(timeout=...)`, no
  `subprocess.run`, no f-strings. The entry point's `from imp import reload`
  is not a blocker on 3.14: Sublime Text 4215 ships
  `Lib/python314/imp.py`, which re-exports `importlib.reload`.
- **The formatter contract.** `Formatter` in `codeformatter/formatter.py`
  maps the syntaxes listed in `codeformatter_python_options.syntaxes` to
  `PyFormatter`. `run_formatter` in `CodeFormatter.py` calls
  `format(text)` with the whole buffer as UTF-8 bytes and expects
  `(stdout, stderr)`. It replaces the buffer only when `stderr` is empty and
  `stdout` is not, and otherwise shows `stderr` in an error dialog.
  `format_on_save_enabled(file_name)` behaves as in the other formatters.
- **Inputs available.** The file's path is `formatter.file_name`, which is
  `None` for a buffer that was never saved. Sublime Text's working directory
  has no relation to the project.
- **Settings idiom.** `codeformatter_python_options` keeps `syntaxes` and
  `format_on_save`. The executable path follows the `php_path` idiom of the
  PHP formatter.
- **Documentation.** `messages/install.txt` repeats parts of `README.md`
  and already differs from it (no Go or Coldfusion entries, older
  credits). This work applies the Python changes to both files; resyncing
  the rest is out of scope **(default)**. Install instructions cover macOS,
  Linux (Debian/Ubuntu plus one fallback), Windows (native PowerShell) and
  WSL. For WSL that means Sublime Text and ruff on the same side: Sublime
  Text inside WSL, or Windows Sublime Text with a Windows ruff editing
  files under `\\wsl.localhost` **(default)**.

## Behaviour

### Invocation

```
<ruff_path or "ruff"> format --force-exclude --stdin-filename <file_name> -
```

The buffer goes to stdin, the result comes from stdout. For a never-saved
buffer (`file_name` is `None`), both `--force-exclude` and
`--stdin-filename` are left out; ruff then searches for configuration
upward from Sublime Text's working directory, and falls back to its
user-level configuration or its defaults **(default)**.

These probes against ruff 0.17.1 (released 2026-10-15) back the choice:

| Case | Exit | stdout | stderr |
|---|---|---|---|
| `--stdin-filename` inside a project, working directory elsewhere | 0 | formatted with the project's `ruff.toml` two folders up | empty |
| no `--stdin-filename` | 0 | formatted with defaults | empty |
| path excluded by the project, with `--force-exclude` | 0 | input unchanged | empty |
| same path without `--force-exclude` | 0 | formatted anyway | empty |
| syntax error | 2 | empty | `error: Failed to parse <path>:1:5: Expected an identifier` |
| config with a rule that conflicts with the formatter | 0 | formatted | `warning: The following rule may cause conflicts ...` |
| whitespace-only buffer ending in a newline | 0 | a single newline | empty |
| whitespace-only buffer without a trailing newline (`"   "`) | 0 | empty | empty |

### Result handling

- **Exit 0:** return ruff's stdout and an empty error, so CodeFormatter
  applies the result. Any stderr text (warnings) goes to the Sublime
  console with the `CodeFormatter: ` prefix, never to the dialog
  **(default)**. When stdout is empty (a buffer of only spaces or tabs
  without a trailing newline), return the input unchanged instead, so the
  buffer stays as it was and no dialog appears **(maintainer)**.
- **Non-zero exit:** return empty output and ruff's stderr, stripped, as the
  error. If stderr is empty, the error is `ruff exited with code <N>`.
- **ruff not found** (`Popen` raises `OSError`/`FileNotFoundError`): the
  error says `ruff not found. Install ruff, or set ruff_path in
  codeformatter_python_options to its full path.` On macOS, Sublime Text
  started from the Dock does not see the shell's `PATH`, so this is the
  expected first-run problem there.
- **Timeout:** 10 seconds **(default)**. The process is killed and the error
  says `ruff did not finish in 10 seconds`.
- **Windows:** the child process starts with a hidden window, the way
  `codeformatter/phpformatter.py` already does it.

The "not supported" error and the import-time console message in
`codeformatter/pyformatter.py` go away.

### Settings

```js
"codeformatter_python_options":
{
    "syntaxes": "python", // Syntax names which must process Python formatter
    "format_on_save": false, // Format on save. Either a boolean (true/false) or a string regexp tested on filename. Example : "^((?!.min.|vendor).)*$"
    "ruff_path": "" // Path to the ruff executable. If empty, uses "ruff" from PATH
}
```

`ruff_path` is the setting name **(default)**.

### Documentation

- The supported-languages list gains `Python - By [ruff](https://docs.astral.sh/ruff/formatter/)`.
- A Python section documents the three options, says that style comes from
  the project's ruff configuration, explains `"syntaxes": ""` for users who
  format Python elsewhere, and gives install steps for every platform listed
  under Constraints.
- The TODO entry "Python (for ST3)" is removed.
- `messages/install.txt` gets the same Python changes.

## Testing

- Unit tests with `subprocess.Popen` replaced by a test double: the command
  line with and without a file name, exit 0 with a warning on stderr,
  a non-zero exit with and without stderr, ruff not found, and a timeout.
- One integration test against a real ruff, skipped when ruff is not on
  `PATH`: a temporary project whose `ruff.toml` sets `line-length = 20`,
  formatted from a different working directory, plus an excluded path that
  must come back unchanged.
- The current `tests/test_pyformatter.py` check that formatting reports
  "not supported" is replaced; its `format_on_save_enabled` cases stay.

## Left to the builder

- Code structure inside `PyFormatter`, and message wording beyond the
  messages quoted above.
- The exact Windows process flags (copy the PHP formatter's approach).
- Test fixtures, file layout, and how the integration test locates ruff.

## Out of scope

Releasing the feature (version number, release notes) is the maintainer's
call after it is merged.
