# @author             Avtandil Kikabidze
# @copyright         Copyright (c) 2008-2015, Avtandil Kikabidze aka LONGMAN (akalongman@gmail.com)
# @link             http://longman.me
# @license         The MIT License (MIT)

import os, sys, re, sublime

directory = os.path.dirname(os.path.realpath(__file__))
libs_path = os.path.join(directory, "lib")

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
    def __init__(self, view=False, file_name=False, syntax=False, saving=False):
        self.platform = sublime.platform()
        self.classmap = {}
        self.st_version = 2
        if sublime.version() == '' or int(sublime.version()) > 3000:
            self.st_version = 3

        self.file_name = file_name
        self.settings = sublime.load_settings('CodeFormatter.sublime-settings')
        self.packages_path = sublime.packages_path()

        self.syntax_file = view.settings().get('syntax')
        if syntax == False:
            self.syntax = self.getSyntax()
        else:
            self.syntax = syntax

        self.saving = saving

        # PHP
        opts = self.settings.get('codeformatter_php_options')
        if ("syntaxes" in opts and opts["syntaxes"]):
            for _formatter in opts["syntaxes"].split(","):
                self.classmap[_formatter.strip()] = PhpFormatter

        # Javascript
        opts = self.settings.get('codeformatter_js_options')
        if ("syntaxes" in opts and opts["syntaxes"]):
            for _formatter in opts["syntaxes"].split(","):
                self.classmap[_formatter.strip()] = JsFormatter

        # CSS
        opts = self.settings.get('codeformatter_css_options')
        if ("syntaxes" in opts and opts["syntaxes"]):
            for _formatter in opts["syntaxes"].split(","):
                self.classmap[_formatter.strip()] = CssFormatter

        # HTML
        opts = self.settings.get('codeformatter_html_options')
        if ("syntaxes" in opts and opts["syntaxes"]):
            for _formatter in opts["syntaxes"].split(","):
                self.classmap[_formatter.strip()] = HtmlFormatter

        # Python
        opts = self.settings.get('codeformatter_python_options')
        print(opts)
        if ("syntaxes" in opts and opts["syntaxes"]):
            for _formatter in opts["syntaxes"].split(","):
                self.classmap[_formatter.strip()] = PyFormatter

        # VBScript
        opts = self.settings.get('codeformatter_vbscript_options')
        if ("syntaxes" in opts and opts["syntaxes"]):
            for _formatter in opts["syntaxes"].split(","):
                self.classmap[_formatter.strip()] = VbscriptFormatter

        # SCSS
        opts = self.settings.get('codeformatter_scss_options')
        if ("syntaxes" in opts and opts["syntaxes"]):
            for _formatter in opts["syntaxes"].split(","):
                self.classmap[_formatter.strip()] = ScssFormatter

        # COLDFUSION
        opts = self.settings.get('codeformatter_coldfusion_options')
        if ("syntaxes" in opts and opts["syntaxes"]):
            for _formatter in opts["syntaxes"].split(","):
                self.classmap[_formatter.strip()] = ColdfusionFormatter




    def format(self, text):
        formatter = self.classmap[self.syntax](self)
        try:
            stdout, stderr = formatter.format(text)
        except Exception as e:
            stdout = ""
            stderr = str(e)

        return self.clean(stdout), self.clean(stderr)


    def exists(self):
        if self.syntax in self.classmap:
            return True
        else:
            return False

    def getSyntax(self):
        pattern = re.compile(r"Packages/.*/(.+?).(?=tmLanguage|sublime-syntax)")
        m = pattern.search(self.syntax_file)
        found = ""
        if (m):
            for s in m.groups():
                found = s
                break
        return found.lower()




    def formatOnSaveEnabled(self):
        if (not self.exists()):
            return False
        formatter = self.classmap[self.syntax](self)
        return formatter.formatOnSaveEnabled(self.file_name)




    def clean(self, string):
        if hasattr(string, 'decode'):
            string = string.decode('UTF-8', 'ignore')

        return re.sub(r'\r\n|\r', '\n', string)
