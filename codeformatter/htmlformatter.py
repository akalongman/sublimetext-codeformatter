# @author             Avtandil Kikabidze
# @copyright         Copyright (c) 2008-2015, Avtandil Kikabidze aka LONGMAN (akalongman@gmail.com)
# @link             http://longman.me
# @license         The MIT License (MIT)

import os
import sys
import re
import sublime
import subprocess

import htmlbeautifier

class HtmlFormatter:
    def __init__(self, formatter):
        self.formatter = formatter
        self.opts = formatter.settings.get('codeformatter_html_options')

    def format(self, text):
        text = text.decode("utf-8")

        stderr = ""
        stdout = ""
        options = htmlbeautifier.default_options()

        if "indent_size" in self.opts:
            options.indent_size = self.opts["indent_size"]

        if "indent_char" in self.opts:
            options.indent_char = str(self.opts["indent_char"])

        if "minimum_attribute_count" in self.opts:
            options.minimum_attribute_count = self.opts["minimum_attribute_count"]

        if "indent_with_tabs" in self.opts:
            options.indent_with_tabs = self.opts["indent_with_tabs"]

        if "expand_tags" in self.opts:
            options.expand_tags = self.opts["expand_tags"]

        if "expand_javascript" in self.opts:
            options.expand_javascript = self.opts["expand_javascript"]

        if "reduce_empty_tags" in self.opts:
            options.reduce_empty_tags = self.opts["reduce_empty_tags"]

        if "exception_on_tag_mismatch" in self.opts:
            options.exception_on_tag_mismatch = self.opts["exception_on_tag_mismatch"]

        try:
              stdout = htmlbeautifier.beautify(text, options)
        except Exception as e:
             stderr = str(e)

        if (not stderr and not stdout):
            stderr = "Formatting error!"

        return stdout, stderr

    def formatOnSaveEnabled(self):
        format_on_save = False
        if ("format_on_save" in self.opts and self.opts["format_on_save"]):
            format_on_save = self.opts["format_on_save"]
        return format_on_save

