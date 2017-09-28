# @author          Avtandil Kikabidze
# @copyright       Copyright (c) 2008-2015, Avtandil Kikabidze aka LONGMAN (akalongman@gmail.com)
# @link            http://longman.me
# @license         The MIT License (MIT)

import os
import sys
import re
import htmlbeautifier
import sublime

directory = os.path.dirname(os.path.realpath(__file__))
libs_path = os.path.join(directory, 'lib')
libs_path = os.path.join(libs_path, 'htmlbeautifier')

if libs_path not in sys.path:
    sys.path.append(libs_path)

use_bs4 = True
try:
    from bs4 import BeautifulSoup
except:
    use_bs4 = False


class HtmlFormatter:

    def __init__(self, formatter):
        self.formatter = formatter
        self.opts = formatter.settings.get('codeformatter_html_options')

    def format(self, text):
        text = text.decode('utf-8')
        stderr = ''
        stdout = ''

        formatter = ''

        if 'formatter_version' in self.opts:
            formatter = self.opts['formatter_version']
            if use_bs4 is False and self.opts['formatter_version'] == 'bs4':
                formatter = 'regexp'
                sublime.error_message(
                    u'CodeFormatter\n\nUnable to load BeautifulSoup HTML '
                    u'formatter. The old RegExp-based formatter was '
                    u'automatically used for you instead.'
                )

        if formatter == 'bs4' and use_bs4:
            p_indent_size = 4
            if 'indent_size' in self.opts:
                p_indent_size = self.opts['indent_size']

            try:
                soup = BeautifulSoup(text, 'html.parser')
                stdout = soup.prettify(
                    formatter=None, indent_size=p_indent_size)
            except Exception as e:
                stderr = str(e)
        else:
            options = htmlbeautifier.default_options()

            if 'indent_size' in self.opts:
                options.indent_size = self.opts['indent_size']

            if 'indent_char' in self.opts:
                options.indent_char = str(self.opts['indent_char'])

            if 'minimum_attribute_count' in self.opts:
                options.minimum_attribute_count = (
                    self.opts['minimum_attribute_count']
                )

            if 'first_attribute_on_new_line' in self.opts:
                options.first_attribute_on_new_line = (
                    self.opts['first_attribute_on_new_line']
                )

            if 'indent_with_tabs' in self.opts:
                options.indent_with_tabs = self.opts['indent_with_tabs']

            if 'expand_tags' in self.opts:
                options.expand_tags = self.opts['expand_tags']

            if 'reduce_empty_tags' in self.opts:
                options.reduce_empty_tags = self.opts['reduce_empty_tags']

            if 'reduce_whole_word_tags' in self.opts:
                options.reduce_whole_word_tags = (
                    self.opts['reduce_whole_word_tags']
                )

            if 'exception_on_tag_mismatch' in self.opts:
                options.exception_on_tag_mismatch = (
                    self.opts['exception_on_tag_mismatch']
                )

            if 'custom_singletons' in self.opts:
                options.custom_singletons = self.opts['custom_singletons']

            try:
                stdout = htmlbeautifier.beautify(text, options)
            except Exception as e:
                stderr = str(e)

            if (not stderr and not stdout):
                stderr = 'Formatting error!'

        return stdout, stderr

    def format_on_save_enabled(self, file_name):
        format_on_save = False
        if ('format_on_save' in self.opts and self.opts['format_on_save']):
            format_on_save = self.opts['format_on_save']
        if (isinstance(format_on_save, str)):
            format_on_save = re.search(format_on_save, file_name) is not None
        return format_on_save
