# @author             Avtandil Kikabidze
# @copyright         Copyright (c) 2008-2015, Avtandil Kikabidze aka LONGMAN (akalongman@gmail.com)
# @link             http://longman.me
# @license         The MIT License (MIT)

import os
import sys
import re
import sublime
import subprocess

import cssbeautifier

class CssFormatter:
    def __init__(self, formatter):
        self.formatter = formatter
        self.opts = formatter.settings.get('codeformatter_css_options')


    def format(self, text):
        text = text.decode("utf-8")

        stderr = ""
        stdout = ""
        options = cssbeautifier.default_options()

        if ("indent_size" in self.opts and self.opts["indent_size"]):
            options.indent_size = self.opts["indent_size"]
        else:
            options.indent_size = 4

        if ("indent_char" in self.opts and self.opts["indent_char"]):
            options.indent_char = self.opts["indent_char"]
        else:
            options.indent_char = ' '

        if ("indent_with_tabs" in self.opts and self.opts["indent_with_tabs"]):
            options.indent_with_tabs = True
        else:
            options.indent_with_tabs = False


        if ("selector_separator_newline" in self.opts and self.opts["selector_separator_newline"]):
            options.selector_separator_newline = True
        else:
            options.selector_separator_newline = False

        if ("end_with_newline" in self.opts and self.opts["end_with_newline"]):
            options.end_with_newline = True
        else:
            options.end_with_newline = False

        if ("eol" in self.opts and self.opts["eol"]):
            options.eol = self.opts["eol"]
        else:
            options.eol = "\n"


        try:
              stdout = cssbeautifier.beautify(text, options)
        except Exception as e:
             stderr = str(e)

        if (not stderr and not stdout):
            stderr = "Formatting error!"

        return stdout, stderr

    def formatOnSaveEnabled(self, file_name):
        format_on_save = False
        if ("format_on_save" in self.opts and self.opts["format_on_save"]):
            format_on_save = self.opts["format_on_save"]
        if (isinstance(format_on_save, str)):
            format_on_save = re.search(format_on_save, file_name) != None
        return format_on_save


