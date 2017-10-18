# @author             Avtandil Kikabidze
# @copyright         Copyright (c) 2008-2015, Avtandil Kikabidze aka LONGMAN (akalongman@gmail.com)
# @link             http://longman.me
# @license         The MIT License (MIT)

import os
import sys
import re
import sublime

directory = os.path.dirname(os.path.realpath(__file__))
libs_path = os.path.join(directory, 'lib')

if libs_path not in sys.path:
    sys.path.append(libs_path)

try:
    # Python 3
    from .phpformatter import PhpFormatter
    from .jsformatter import JsFormatter
    from .htmlformatter import HtmlFormatter
    from .cssformatter import CssFormatter
    from .scssformatter import ScssFormatter
    from .pyformatter import PyFormatter
    from .vbscriptformatter import VbscriptFormatter
    from .coldfusionformatter import ColdfusionFormatter

except (ValueError):
    # Python 2
    from phpformatter import PhpFormatter
    from jsformatter import JsFormatter
    from htmlformatter import HtmlFormatter
    from cssformatter import CssFormatter
    from scssformatter import ScssFormatter
    from pyformatter import PyFormatter
    from vbscriptformatter import VbscriptFormatter
    from coldfusionformatter import ColdfusionFormatter


class Formatter:

    def __init__(self, view, syntax=None):

        self.platform = sublime.platform()
        self.classmap = {}
        self.st_version = 2
        if sublime.version() == '' or int(sublime.version()) > 3000:
            self.st_version = 3

        self.file_name = view.file_name()
        self.settings = sublime.load_settings('CodeFormatter.sublime-settings')
        self.packages_path = sublime.packages_path()

        self.syntax_file = view.settings().get('syntax')
        self.syntax = syntax or self.get_syntax()

        # map of settings names with related class
        map_settings_formatter = [
            ('codeformatter_php_options', PhpFormatter),
            ('codeformatter_js_options', JsFormatter),
            ('codeformatter_css_options', CssFormatter),
            ('codeformatter_html_options', HtmlFormatter),
            ('codeformatter_python_options', PyFormatter),
            ('codeformatter_vbscript_options', VbscriptFormatter),
            ('codeformatter_scss_options', ScssFormatter),
            ('codeformatter_coldfusion_options', ColdfusionFormatter),
        ]

        for name, _class in map_settings_formatter:
            syntaxes = self.settings.get(name, {}).get('syntaxes')
            if not syntaxes or not isinstance(syntaxes, str):
                continue
            for _formatter in syntaxes.split(','):
                self.classmap[_formatter.strip()] = _class

    def format(self, text):
        formatter = self.classmap[self.syntax](self)
        try:
            stdout, stderr = formatter.format(text)
        except Exception as e:
            stdout = ''
            stderr = str(e)

        return self.clean(stdout), self.clean(stderr)

    def exists(self):
        return self.syntax in self.classmap

    def get_syntax(self):
        pattern = re.compile(
            r'Packages/.*/(.+?).(?=tmLanguage|sublime-syntax)')
        m = pattern.search(self.syntax_file)
        found = ''
        if m and len(m.groups()) > 0:
            found = m.groups()[0]
        return found.lower()

    def format_on_save_enabled(self):
        if not self.exists():
            return False
        formatter = self.classmap[self.syntax](self)
        return formatter.format_on_save_enabled(self.file_name)

    def clean(self, string):
        if hasattr(string, 'decode'):
            string = string.decode('UTF-8', 'ignore')

        return re.sub(r'\r\n|\r', '\n', string)
