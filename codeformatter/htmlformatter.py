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
libs_path = os.path.join(directory, 'lib', 'htmlbeautifier')

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
        self.options = htmlbeautifier.default_options()

        # fill custom options
        custom_options = formatter.settings.get('codeformatter_html_options')
        self.fill_custom_options(custom_options)

    def fill_custom_options(self, options):

        if not options:
            return

        custom_options = [
            'formatter_version',
            'indent_size',
            'indent_char',
            'minimum_attribute_count',
            'first_attribute_on_new_line',
            'indent_with_tabs',
            'expand_tags',
            'reduce_empty_tags',
            'reduce_whole_word_tags',
            'exception_on_tag_mismatch',
            'custom_singletons',
            'format_on_save'
        ]

        casters = {'indent_char': str}

        for key in custom_options:

            value = options.get(key)
            if value is None:
                continue

            cast = casters.get(key)
            if cast:
                value = cast(value)

            setattr(self.options, key, value)

    def format_with_bs4(self, text):
        stdout, stderr = '', ''

        p_indent_size = getattr(self.options, 'indent_size', 4)

        try:
            soup = BeautifulSoup(text, 'html.parser')
            stdout = soup.prettify(formatter=None, indent_size=p_indent_size)
        except Exception as e:
            stderr = str(e)

        return stdout, stderr

    def format_with_beautifier(self, text):
        stdout, stderr = '', ''

        try:
            stdout = htmlbeautifier.beautify(text, self.options)
        except Exception as e:
            stderr = str(e)

        if (not stderr and not stdout):
            stderr = 'Formatting error!'

        return stdout, stderr

    def format(self, text):
        text = text.decode('utf-8')

        formatter = getattr(self.options, 'formatter_version')
        if formatter == 'bs4':
            if not use_bs4:
                sublime.error_message(
                    u'CodeFormatter\n\nUnable to load BeautifulSoup HTML '
                    u'formatter. The old RegExp-based formatter was '
                    u'automatically used for you instead.'
                )
            else:
                return self.format_with_bs4(text)

        return self.format_with_beautifier(text)

    def format_on_save_enabled(self, file_name):
        format_on_save = getattr(self.options, 'format_on_save', False)
        if isinstance(format_on_save, str):
            format_on_save = re.search(format_on_save, file_name) is not None
        return format_on_save
