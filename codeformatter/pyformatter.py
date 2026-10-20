# @author             Avtandil Kikabidze
# @copyright         Copyright (c) 2008-2015, Avtandil Kikabidze aka LONGMAN (akalongman@gmail.com)
# @link             http://longman.me
# @license         The MIT License (MIT)

import re
import subprocess


TIMEOUT = 10


class PyFormatter:

    def __init__(self, formatter):
        self.formatter = formatter
        self.opts = formatter.settings.get('codeformatter_python_options')

    def format(self, text):
        ruff = self.opts.get('ruff_path') or 'ruff'
        file_name = self.formatter.file_name
        if file_name is None:
            cmd = [ruff, 'format', '-']
        else:
            cmd = [ruff, 'format', '--force-exclude',
                   '--stdin-filename', file_name, '-']

        popen_args = {}
        if self.formatter.platform == 'windows':
            startupinfo = subprocess.STARTUPINFO()
            startupinfo.dwFlags |= subprocess.STARTF_USESHOWWINDOW
            startupinfo.wShowWindow = subprocess.SW_HIDE
            popen_args['startupinfo'] = startupinfo

        try:
            p = subprocess.Popen(
                cmd, stdin=subprocess.PIPE, stdout=subprocess.PIPE,
                stderr=subprocess.PIPE, **popen_args)
        except FileNotFoundError:
            return '', ('ruff not found. Install ruff, or set ruff_path in '
                        'codeformatter_python_options to its full path.')

        try:
            stdout, stderr = p.communicate(text, timeout=TIMEOUT)
        except subprocess.TimeoutExpired:
            p.kill()
            p.communicate()
            return '', 'ruff did not finish in %d seconds' % TIMEOUT

        stderr = stderr.decode('utf-8', 'replace').strip()
        if p.returncode == 0:
            if stderr:
                print('CodeFormatter: ' + stderr)
            # ruff prints nothing for a buffer of only spaces or tabs, and
            # the caller reports empty output as an error, so keep the text.
            return stdout or text, ''
        return '', stderr or 'ruff exited with code %d' % p.returncode

    def format_on_save_enabled(self, file_name):
        format_on_save = False
        if ('format_on_save' in self.opts and self.opts['format_on_save']):
            format_on_save = self.opts['format_on_save']
        if (isinstance(format_on_save, str)):
            format_on_save = re.search(format_on_save, file_name) is not None
        return format_on_save
