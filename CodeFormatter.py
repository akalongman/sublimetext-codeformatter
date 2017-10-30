# @author             Avtandil Kikabidze
# @copyright         Copyright (c) 2008-2015, Avtandil Kikabidze aka LONGMAN (akalongman@gmail.com)
# @link             http://longman.me
# @license         The MIT License (MIT)

import os
import sys
import sublime
import sublime_plugin

st_version = 2
if sublime.version() == '' or int(sublime.version()) > 3000:
    st_version = 3

reloader_name = 'codeformatter.reloader'
# ST3 loads each package as a module, so it needs an extra prefix
if st_version == 3:
    reloader_name = 'CodeFormatter.' + reloader_name
    from imp import reload

if reloader_name in sys.modules:
    reload(sys.modules[reloader_name])

try:
    # Python 3
    from .codeformatter.formatter import Formatter
except (ValueError):
    # Python 2
    from codeformatter.formatter import Formatter

# fix for ST2
cprint = globals()['__builtins__']['print']

debug_mode = False


def plugin_loaded():

    cprint('CodeFormatter: Plugin Initialized')

    # settings = sublime.load_settings('CodeFormatter.sublime-settings')
    # debug_mode = settings.get('codeformatter_debug', False)
    # if debug_mode:
    #     from pprint import pprint
    #     pprint(settings)
    #     debug_write('Debug mode enabled')
    #     debug_write('Platform ' + sublime.platform() + ' ' + sublime.arch())
    #     debug_write('Sublime Version ' + sublime.version())
    #     debug_write('Settings ' + pprint(settings))

    if (sublime.platform() != 'windows'):
        import stat
        path = (
            sublime.packages_path() +
            '/CodeFormatter/codeformatter/lib/phpbeautifier/fmt.phar'
        )
        st = os.stat(path)
        os.chmod(path, st.st_mode | stat.S_IEXEC)


if st_version == 2:
    plugin_loaded()


class CodeFormatterCommand(sublime_plugin.TextCommand):

    def run(self, edit, syntax=None, saving=None):
        run_formatter(self.view, edit, syntax=syntax, saving=saving)


class CodeFormatterOpenTabsCommand(sublime_plugin.TextCommand):

    def run(self, edit, syntax=None):
        window = sublime.active_window()
        for view in window.views():
            run_formatter(view, edit, quiet=True)


class CodeFormatterEventListener(sublime_plugin.EventListener):

    def on_pre_save(self, view):
        view.run_command('code_formatter', {'saving': True})


class CodeFormatterShowPhpTransformationsCommand(sublime_plugin.TextCommand):

    def run(self, edit, syntax=False):

        import subprocess
        import re

        platform = sublime.platform()
        settings = sublime.load_settings('CodeFormatter.sublime-settings')

        opts = settings.get('codeformatter_php_options')

        php_path = 'php'
        if ('php_path' in opts and opts['php_path']):
            php_path = opts['php_path']

        php55_compat = False
        if ('php55_compat' in opts and opts['php55_compat']):
            php55_compat = opts['php55_compat']


        cmd = []
        cmd.append(str(php_path))

        if php55_compat:
            cmd.append(
                '{}/CodeFormatter/codeformatter/lib/phpbeautifier/fmt.phar'.format(
                    sublime.packages_path()))
        else:
            cmd.append(
                '{}/CodeFormatter/codeformatter/lib/phpbeautifier/phpf.phar'.format(
                    sublime.packages_path()))


        cmd.append('--list')

        #print(cmd)

        stderr = ''
        stdout = ''
        try:
            if (platform == 'windows'):
                startupinfo = subprocess.STARTUPINFO()
                startupinfo.dwFlags |= subprocess.STARTF_USESHOWWINDOW
                startupinfo.wShowWindow = subprocess.SW_HIDE
                p = subprocess.Popen(
                    cmd, stdin=subprocess.PIPE, stdout=subprocess.PIPE,
                    stderr=subprocess.PIPE, startupinfo=startupinfo,
                    shell=False, creationflags=subprocess.SW_HIDE)
            else:
                p = subprocess.Popen(
                    cmd, stdin=subprocess.PIPE, stdout=subprocess.PIPE,
                    stderr=subprocess.PIPE)
            stdout, stderr = p.communicate()
        except Exception as e:
            stderr = str(e)

        if (not stderr and not stdout):
            stderr = 'Error while gethering list of php transformations'

        if len(stderr) == 0 and len(stdout) > 0:
            text = stdout.decode('utf-8')
            text = re.sub(
                'Usage:.*?PASSNAME', 'Available PHP Tranformations:', text)
            window = self.view.window()
            pt = window.get_output_panel('paneltranformations')
            pt.set_read_only(False)
            pt.insert(edit, pt.size(), text)
            window.run_command(
                'show_panel', {'panel': 'output.paneltranformations'})
        else:
            show_error('Formatter error:\n' + stderr)


def run_formatter(view, edit, *args, **kwargs):

    if view.is_scratch():
        show_error('File is scratch')
        return

    # default parameters
    syntax = kwargs.get('syntax')
    saving = kwargs.get('saving', False)
    quiet = kwargs.get('quiet', False)

    formatter = Formatter(view, syntax)
    if not formatter.exists():
        if not quiet and not saving:
            show_error('Formatter for this file type ({}) not found.'.format(
                formatter.syntax))
        return

    if (saving and not formatter.format_on_save_enabled()):
        return

    file_text = sublime.Region(0, view.size())
    file_text_utf = view.substr(file_text).encode('utf-8')
    if (len(file_text_utf) == 0):
        return

    stdout, stderr = formatter.format(file_text_utf)

    if len(stderr) == 0 and len(stdout) > 0:
        view.replace(edit, file_text, stdout)
    elif not quiet:
        show_error('Format error:\n' + stderr)


def console_write(text, prefix=False):
    if prefix:
        sys.stdout.write('CodeFormatter: ')
    sys.stdout.write(text + '\n')


def debug_write(text, prefix=False):
    console_write(text, True)


def show_error(text):
    sublime.error_message(u'CodeFormatter\n\n%s' % text)
