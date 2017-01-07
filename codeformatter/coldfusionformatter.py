# @author             Avtandil Kikabidze
# @copyright         Copyright (c) 2008-2015, Avtandil Kikabidze aka LONGMAN (akalongman@gmail.com)
# @link             http://longman.me
# @license         The MIT License (MIT)

import os
import sys
import re
import sublime
import subprocess

import coldfusionbeautifier

class ColdfusionFormatter:
    def __init__(self, formatter):
        self.formatter = formatter
        self.opts = formatter.settings.get('codeformatter_coldfusion_options')

    def format(self, text):
        text = text.decode("utf-8")

        stderr = ""
        stdout = ""
        options = coldfusionbeautifier.default_options()

        if "indent_size" in self.opts:
            options.indent_size = self.opts["indent_size"]

        if "indent_char" in self.opts:
            options.indent_char = str(self.opts["indent_char"])

        if "minimum_attribute_count" in self.opts:
            options.minimum_attribute_count = self.opts["minimum_attribute_count"]

        if "first_attribute_on_new_line" in self.opts:
            options.first_attribute_on_new_line = self.opts["first_attribute_on_new_line"]

        if "indent_with_tabs" in self.opts:
            options.indent_with_tabs = self.opts["indent_with_tabs"]

        if "expand_tags" in self.opts:
            options.expand_tags = self.opts["expand_tags"]

        if "expand_javascript" in self.opts:
            options.expand_javascript = self.opts["expand_javascript"]

        if "reduce_empty_tags" in self.opts:
            options.reduce_empty_tags = self.opts["reduce_empty_tags"]

        if "reduce_whole_word_tags" in self.opts:
            options.reduce_whole_word_tags = self.opts["reduce_whole_word_tags"]

        if "exception_on_tag_mismatch" in self.opts:
            options.exception_on_tag_mismatch = self.opts["exception_on_tag_mismatch"]

        if "custom_singletons" in self.opts:
            options.custom_singletons = self.opts["custom_singletons"]

        try:
            stdout = coldfusionbeautifier.beautify(text, options)
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
