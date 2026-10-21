# @author             Avtandil Kikabidze
# @copyright         Copyright (c) 2008-2015, Avtandil Kikabidze aka LONGMAN (akalongman@gmail.com)
# @link             http://longman.me
# @license         The MIT License (MIT)

import json
import re

from . import phpfixer
from . import process


REFUSAL = ('No PHP fixer is configured for this file. Add pint.json, '
           '.php-cs-fixer.dist.php or phpcs.xml to the project, or set '
           '"fixer" in codeformatter_php_options.')
UNKNOWN_FIXER = ('Unknown fixer "{}" in codeformatter_php_options. Use '
                 '"pint", "php-cs-fixer" or "phpcbf".')
NOT_FOUND = ('{} not found. Run "composer require --dev {}" in the project, '
             'or set fixer_path in codeformatter_php_options.')
TIMED_OUT = '{} did not finish in {} seconds'
PINT_MINIMUM = (1, 26, 0)
PINT_TOO_OLD = ('Pint 1.26.0 or newer is needed to format from the editor. '
                'Run "composer update laravel/pint" in the project.')
BAD_DIFF = 'php-cs-fixer returned a diff that does not match the buffer'
UNREADABLE = 'php-cs-fixer printed output that is not its JSON report'
BATCH_FILE = ('{0} runs through the batch file {1}, and cmd.exe would '
              'misread a & | < > ^ % or ! in a path on its command line. '
              'Run "composer require --dev {2}" in the project and leave '
              'fixer_path empty, so {0} runs from vendor/bin.')


class PhpFormatter:

    def __init__(self, formatter):
        self.formatter = formatter
        self.opts = formatter.settings.get('codeformatter_php_options')

    def format(self, text):
        # A user override may lack keys, and may still hold the retired
        # phpF keys, which are not read.
        forced = self.opts.get('fixer', '')
        if forced and forced not in phpfixer.FIXERS:
            return '', UNKNOWN_FIXER.format(forced)

        file_name = self.formatter.file_name
        found = phpfixer.find_config(file_name, forced or None)
        if not found:
            return '', REFUSAL
        fixer, config_dir, config_file = found

        package = phpfixer.PACKAGES[fixer]
        not_found = NOT_FOUND.format(fixer, package)
        platform = self.formatter.platform
        argv = phpfixer.find_executable(
            fixer, config_dir, self.opts.get('fixer_path', ''),
            self.opts.get('php_path', ''), platform)
        if not argv:
            return '', not_found
        # Every process below starts argv, and the only paths added to its
        # command line are config_file and file_name, so one check before
        # the first one covers them all.
        if phpfixer.cmd_exe_would_misread(
                argv, [config_file, file_name], platform):
            return '', BATCH_FILE.format(fixer, argv[0], package)

        run = {
            'pint': self._pint,
            'phpcbf': self._phpcbf,
            'php-cs-fixer': self._php_cs_fixer,
        }[fixer]
        try:
            return run(argv, config_dir, config_file, file_name, text)
        except phpfixer.FixerError as e:
            return '', str(e)
        except process.Timeout:
            return '', TIMED_OUT.format(fixer, process.TIMEOUT)
        except OSError:
            # The fixer or PHP could not be started, or the configuration
            # file went away while the list of covered files was read.
            return '', not_found

    def format_on_save_enabled(self, file_name):
        format_on_save = False
        if ('format_on_save' in self.opts and self.opts['format_on_save']):
            format_on_save = self.opts['format_on_save']
        if (isinstance(format_on_save, str)):
            format_on_save = re.search(format_on_save, file_name) is not None
        return format_on_save

    def _run(self, cmd, text, config_dir):
        return process.run(cmd, text, config_dir, self.formatter.platform)

    def _pint(self, argv, config_dir, config_file, file_name, text):
        version = phpfixer.tool_version(
            'pint', argv, config_dir, self.formatter.platform)
        if version < PINT_MINIMUM:
            return '', PINT_TOO_OLD

        cmd = argv + ['--no-ansi']
        if config_file:
            cmd.append('--config=' + config_file)
        if file_name:
            cmd.append('--stdin-filename=' + file_name)
        cmd.append('-')

        returncode, stdout, stderr = self._run(cmd, text, config_dir)
        if returncode != 0:
            return '', _failed('pint', returncode, stdout, stderr)
        return _formatted(stdout, text, stderr)

    def _phpcbf(self, argv, config_dir, config_file, file_name, text):
        # config_file is not passed (no --standard=): phpcbf reads its
        # ruleset from the working directory, in its own order of names.
        major = phpfixer.tool_version(
            'phpcbf', argv, config_dir, self.formatter.platform)[0]

        cmd = argv + ['-q', '--no-colors']
        if file_name:
            cmd.append('--stdin-path=' + file_name)
        cmd.append('-')

        returncode, stdout, stderr = self._run(cmd, text, config_dir)
        if major >= 4:
            if returncode in (0, 2):
                # 2: issues remain that phpcbf cannot fix.
                return _formatted(stdout, text, stderr)
            if returncode == 16 and 'No files were checked' in stderr:
                # The ruleset excludes the file.
                return text, ''
        else:
            if returncode == 1:
                return _formatted(stdout, text, stderr)
            if returncode == 0:
                # stdout is a report, not code: nothing to fix, or the
                # ruleset excludes the file.
                return _formatted(b'', text, stderr)
        return '', _failed('phpcbf', returncode, stdout, stderr)

    def _php_cs_fixer(self, argv, config_dir, config_file, file_name, text):
        # PHP-CS-Fixer does not apply the Finder to stdin, so ask it which
        # files the configuration covers. A never-saved buffer has no path
        # to ask about.
        if file_name and not phpfixer.covered_by_php_cs_fixer(
                argv, config_dir, config_file, file_name,
                self.formatter.platform):
            return text, ''

        cmd = argv + ['fix', '--no-ansi', '--using-cache=no', '--diff',
                      '--format=json']
        if config_file:
            cmd.append('--config=' + config_file)
        cmd.append('-')

        returncode, stdout, stderr = self._run(cmd, text, config_dir)
        if returncode == 0:
            return _formatted(b'', text, stderr)
        if returncode != 8:
            return '', _failed('php-cs-fixer', returncode, stdout, stderr)

        # Exit 8: the fix is a diff against the buffer, as it cannot print
        # the fixed code for stdin.
        diff = _report_diff(stdout)
        if diff is None:
            return '', _with_first_line(UNREADABLE, stdout)
        # The buffer reaches the formatter encoded as UTF-8.
        buffer = text.decode('utf-8')
        try:
            fixed = phpfixer.apply_unified_diff(buffer, diff)
        except ValueError:
            return '', BAD_DIFF
        return _formatted(fixed.encode('utf-8'), text, stderr)


def _report_diff(stdout):
    """Return files[0].diff of a PHP-CS-Fixer JSON report, or None."""
    try:
        diff = json.loads(stdout.decode('utf-8'))['files'][0]['diff']
    except (ValueError, KeyError, IndexError, TypeError):
        return None
    return diff if isinstance(diff, str) else None


def _formatted(stdout, text, stderr):
    """The result of a successful run; its warnings go to the console."""
    for line in stderr.splitlines():
        print('CodeFormatter: ' + line)
    # Empty output and an empty error would show an empty error dialog, so
    # keep the text.
    return stdout or text, ''


def _failed(tool, returncode, stdout, stderr):
    if stderr:
        return stderr
    # Some tools print the reason on stdout instead: phpcbf 3.x its fatal
    # errors, PHP a fatal error while display_errors writes to stdout.
    return _with_first_line(
        '{} exited with code {}'.format(tool, returncode), stdout)


def _with_first_line(message, stdout):
    """Add the first non-empty line of stdout, at most 200 characters, to
    message."""
    lines = stdout.decode('utf-8', 'replace').strip().splitlines()
    first = lines[0][:200].strip() if lines else ''
    return message + (': ' + first if first else '')
