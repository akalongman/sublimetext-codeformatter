# @author         Nishutosh Sharma
# @copyright     No Copyright, use it and modify for betterment
# This is a modified version of cssformatter.py

import re
import scssbeautifier


class ScssFormatter:

    def __init__(self, formatter):
        self.formatter = formatter
        self.opts = formatter.settings.get('codeformatter_scss_options')

    def format(self, text):

        text = text.decode('utf-8')
        stderr = ''
        stdout = ''
        options = scssbeautifier.default_options()

        if ('indent_size' in self.opts and self.opts['indent_size']):
            options.indent_size = self.opts['indent_size']
        else:
            options.indent_size = 4

        if ('indent_char' in self.opts and self.opts['indent_char']):
            options.indent_char = self.opts['indent_char']
        else:
            options.indent_char = ' '

        if ('indent_with_tabs' in self.opts and self.opts['indent_with_tabs']):
            options.indent_with_tabs = True
        else:
            options.indent_with_tabs = False

        if (
            'selector_separator_newline' in self.opts and
            self.opts['selector_separator_newline']
        ):
            options.selector_separator_newline = True
        else:
            options.selector_separator_newline = False

        if ('end_with_newline' in self.opts and self.opts['end_with_newline']):
            options.end_with_newline = True
        else:
            options.end_with_newline = False

        if ('eol' in self.opts and self.opts['eol']):
            options.eol = self.opts['eol']
        else:
            options.eol = '\n'

        if ('space_around_combinator' in self.opts and self.opts['space_around_combinator']):
            options.space_around_combinator = True
        else:
            options.space_around_combinator = False

        if ('newline_between_rules' in self.opts and self.opts['newline_between_rules']):
            options.newline_between_rules = True
        else:
            options.newline_between_rules = False

        try:
            stdout = scssbeautifier.beautify(text, options)
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
