import os
import re
import shutil

from . import process


FIXERS = ('pint', 'php-cs-fixer', 'phpcbf')

# Within one directory this order breaks ties between fixers.
CONFIG_FILES = [
    ('pint', ['pint.json']),
    ('php-cs-fixer', ['.php-cs-fixer.php', '.php-cs-fixer.dist.php']),
    ('phpcbf', ['phpcs.xml', '.phpcs.xml', 'phpcs.xml.dist',
                '.phpcs.xml.dist']),
]

PACKAGES = {
    'pint': 'laravel/pint',
    'php-cs-fixer': 'friendsofphp/php-cs-fixer',
    'phpcbf': 'squizlabs/php_codesniffer',
}

VERSION = re.compile(r'([0-9]+)\.([0-9]+)\.([0-9]+)')
HUNK_HEADER = re.compile(
    r'@@ -([0-9]+)(?:,([0-9]+))? \+([0-9]+)(?:,([0-9]+))? @@')

# Kept for the session. tuple(argv) -> (major, minor, patch).
_version_cache = {}
# (config_dir, config_file) -> (mtime of config_file, set of the normalized
# paths list-files printed, set of the normalized paths remembered as
# excluded). A new mtime starts a new entry, so it drops both sets.
_coverage_cache = {}


class FixerError(Exception):
    """A message for the dialog."""


def _upward(directory):
    """Yield directory and each of its parents up to the filesystem root."""
    while True:
        yield directory
        parent = os.path.dirname(directory)
        if parent == directory:
            return
        directory = parent


def _config_in(directory, only=None):
    """Return (fixer, config_file) for the first configuration file found
    in directory, considering only the fixer named by only when given."""
    for fixer, names in CONFIG_FILES:
        if only and fixer != only:
            continue
        for name in names:
            path = os.path.join(directory, name)
            if os.path.isfile(path):
                return fixer, path
    return None


def _nearest_composer_dir(directory):
    for d in _upward(directory):
        if os.path.isfile(os.path.join(d, 'composer.json')):
            return d
    return None


def find_config(file_name, forced):
    """Pick the fixer for file_name.

    Returns (fixer, config_dir, config_file) or None when no fixer is
    configured. config_file is None when the fixer runs with its defaults;
    config_dir is None only for a never-saved buffer with a forced fixer.
    """
    if not file_name:
        return (forced, None, None) if forced else None

    file_dir = os.path.dirname(file_name)

    for d in _upward(file_dir):
        found = _config_in(d, forced)
        if found:
            return found[0], d, found[1]

    composer_dir = _nearest_composer_dir(file_dir)
    if forced:
        return forced, composer_dir or file_dir, None

    # Without a configuration file, a Composer project with Pint installed
    # formats with Pint's default preset, as ./vendor/bin/pint does.
    if composer_dir and os.path.isfile(
            os.path.join(composer_dir, 'vendor', 'bin', 'pint')):
        return 'pint', composer_dir, None
    return None


def find_executable(fixer, config_dir, fixer_path, php_path):
    """Return the command line that runs fixer, or None when not found."""
    if fixer_path:
        return [os.path.expanduser(fixer_path)]

    # Composer's bin proxies are PHP scripts, so run them through PHP; this
    # works on Windows too.
    if config_dir:
        for d in _upward(config_dir):
            script = os.path.join(d, 'vendor', 'bin', fixer)
            if os.path.isfile(script):
                return [php_path or 'php', script]

    path = shutil.which(fixer)
    if path:
        return [path]
    return None


def tool_version(tool, argv, cwd, platform):
    """Return the (major, minor, patch) that argv reports for --version.

    The first X.Y.Z on stdout wins, then the first on stderr. The version
    is kept per command line for the session; a failure keeps nothing.
    process.Timeout and OSError propagate.
    """
    key = tuple(argv)
    if key not in _version_cache:
        returncode, stdout, stderr = process.run(
            argv + ['--version'], b'', cwd, platform)
        # A failed run prints the versions it requires (">= 8.2.0"), which
        # must not be taken for the tool's own.
        match = returncode == 0 and (
            VERSION.search(stdout.decode('utf-8', 'replace')) or
            VERSION.search(stderr))
        if not match:
            raise FixerError('could not read the {} version'.format(tool))
        _version_cache[key] = tuple(int(n) for n in match.groups())
    return _version_cache[key]


def covered_by_php_cs_fixer(argv, config_dir, config_file, file_name,
                            platform):
    """Tell whether the PHP-CS-Fixer configuration covers file_name.

    Without a configuration file there is no Finder that could exclude
    the file, so it is covered and nothing runs. Otherwise the list-files
    output is kept per configuration directory and file until the file's
    mtime changes. A file missing from a kept list triggers one refresh,
    as it may be new. A file that a list read during the call misses
    while it is on disk is remembered as excluded until the mtime
    changes. A failure keeps nothing. process.Timeout and OSError
    propagate.
    """
    if not config_file:
        return True

    key = (config_dir, config_file)
    mtime = os.path.getmtime(config_file)
    target = _normalize(file_name)

    kept = _coverage_cache.get(key)
    if kept is not None and kept[0] == mtime:
        # The list comes first, so a refresh that lists a remembered
        # file makes it covered again.
        if target in kept[1]:
            return True
        if target in kept[2]:
            return False
        excluded = kept[2]
    else:
        excluded = set()

    files = _list_files(argv, config_dir, config_file, platform)
    # Only a miss for a file on disk is remembered: on_pre_save runs before
    # Sublime Text writes a new file, and remembering that miss would keep
    # the file excluded for the session.
    if target not in files and os.path.isfile(file_name):
        excluded.add(target)
    _coverage_cache[key] = (mtime, files, excluded)
    return target in files


def _list_files(argv, config_dir, config_file, platform):
    cmd = argv + ['list-files', '--no-ansi', '--config=' + config_file]
    returncode, stdout, stderr = process.run(cmd, b'', config_dir, platform)
    if returncode != 0:
        raise FixerError(stderr or 'php-cs-fixer exited with code {}'.format(
            returncode))

    files = set()
    for line in stdout.decode('utf-8', 'replace').split('\n'):
        path = _unquote(line.strip())
        if path:
            files.add(_normalize(os.path.join(config_dir, path)))
    return files


def _unquote(arg):
    """Undo PHP's escapeshellarg.

    On POSIX it single-quotes and writes an embedded ' as '\\''. On Windows
    it double-quotes and turns %, ! and " into spaces, which cannot be
    undone.
    """
    if len(arg) >= 2 and arg[0] == arg[-1] == "'":
        return arg[1:-1].replace("'\\''", "'")
    if len(arg) >= 2 and arg[0] == arg[-1] == '"':
        return arg[1:-1]
    return arg


def _normalize(path):
    return os.path.normcase(os.path.normpath(path))


def apply_unified_diff(text, diff):
    """Apply a unified diff made from text, hunk by hunk.

    Raises ValueError when the diff does not fit text; nothing is returned
    then, so a diff is never half applied.
    """
    old = _split_lines(text)
    lines = _split_lines(diff)
    result = []
    pos = 0  # old[pos] is the first line not yet copied or consumed
    i = 0

    # Lines before the first hunk are the --- and +++ file headers.
    while i < len(lines) and not lines[i].startswith('@@'):
        i += 1

    while i < len(lines):
        match = HUNK_HEADER.match(lines[i])
        if not match:
            raise ValueError('expected a hunk header: ' + lines[i])
        i += 1
        old_start = int(match.group(1))
        # A range of one line is written without its count.
        old_count = int(match.group(2) or 1)
        new_count = int(match.group(4) or 1)

        # sebastian/diff gives the line a hunk starts at even when the hunk
        # removes nothing (-1,0 for an empty text); -0,0 means the same.
        start = old_start - 1 if old_start or old_count else 0
        if start < pos or start > len(old):
            raise ValueError('hunk at line {} is out of place'.format(
                old_start))
        result.extend(old[pos:start])
        pos = start

        # Consume the hunk by its counts, so a removed line that starts
        # with -- (written ---) is not taken for a header.
        while old_count or new_count:
            if i == len(lines):
                raise ValueError('hunk ends before its counts')
            tag, content = lines[i][:1], lines[i][1:]
            i += 1
            # "\ No newline at end of file" belongs to the line above it.
            if i < len(lines) and lines[i].startswith('\\'):
                i += 1
                if content.endswith('\n'):
                    content = content[:-1]

            if tag == ' ' and old_count and new_count:
                _expect(old, pos, content)
                result.append(content)
                pos += 1
                old_count -= 1
                new_count -= 1
            elif tag == '-' and old_count:
                _expect(old, pos, content)
                pos += 1
                old_count -= 1
            elif tag == '+' and new_count:
                result.append(content)
                new_count -= 1
            else:
                raise ValueError('hunk line does not fit its counts: ' +
                                 tag + content)

    result.extend(old[pos:])
    return ''.join(result)


def _expect(old, pos, content):
    if pos >= len(old) or old[pos] != content:
        raise ValueError('line {} of the text does not match the diff'.format(
            pos + 1))


def _split_lines(text):
    """Split text after each \\n only, keeping the line endings.

    str.splitlines would also split on \\r, \\x0c, \\x85 and others, which
    sebastian/diff keeps inside a line.
    """
    lines = [line + '\n' for line in text.split('\n')]
    last = lines.pop()[:-1]
    if last:
        lines.append(last)
    return lines
