# @author             Avtandil Kikabidze
# @copyright         Copyright (c) 2008-2015, Avtandil Kikabidze aka LONGMAN (akalongman@gmail.com)
# @link             http://longman.me
# @license         The MIT License (MIT)

import re
import jsbeautifier


class JsFormatter:

    def __init__(self, formatter):
        self.formatter = formatter
        self.opts = formatter.settings.get('codeformatter_js_options')

    def format(self, text):
        text = text.decode('utf-8')

        stderr = ''
        stdout = ''
        options = jsbeautifier.default_options()

        if ('indent_size' in self.opts and self.opts['indent_size']):
            options.indent_size = self.opts['indent_size']
        else:
            options.indent_size = 4

        if ('indent_char' in self.opts and self.opts['indent_char']):
            options.indent_char = str(self.opts['indent_char'])
        else:
            options.indent_char = ' '

        if ('indent_with_tabs' in self.opts and self.opts['indent_with_tabs']):
            options.indent_with_tabs = True
        else:
            options.indent_with_tabs = False

        if ('eol' in self.opts and self.opts['eol']):
            options.eol = self.opts['eol']
        else:
            options.eol = '\n'

        if (
            'preserve_newlines' in self.opts and
            self.opts['preserve_newlines']
        ):
            options.preserve_newlines = True
        else:
            options.preserve_newlines = False

        if (
            'max_preserve_newlines' in self.opts and
            self.opts['max_preserve_newlines']
        ):
            options.max_preserve_newlines = self.opts['max_preserve_newlines']
        else:
            options.max_preserve_newlines = 10

        if ('space_in_paren' in self.opts and self.opts['space_in_paren']):
            options.space_in_paren = True
        else:
            options.space_in_paren = False

        if (
            'space_in_empty_paren' in self.opts and
            self.opts['space_in_empty_paren']
        ):
            options.space_in_empty_paren = True
        else:
            options.space_in_empty_paren = False

        if ('e4x' in self.opts and self.opts['e4x']):
            options.e4x = True
        else:
            options.e4x = False

        if ('jslint_happy' in self.opts and self.opts['jslint_happy']):
            options.jslint_happy = True
        else:
            options.jslint_happy = False

        if ('brace_style' in self.opts and self.opts['brace_style']):
            options.brace_style = self.opts['brace_style']
        else:
            options.brace_style = 'collapse'

        if (
            'keep_array_indentation' in self.opts and
            self.opts['keep_array_indentation']
        ):
            options.keep_array_indentation = True
        else:
            options.keep_array_indentation = False

        if (
            'keep_function_indentation' in self.opts and
            self.opts['keep_function_indentation']
        ):
            options.keep_function_indentation = True
        else:
            options.keep_function_indentation = False

        if ('eval_code' in self.opts and self.opts['eval_code']):
            options.eval_code = True
        else:
            options.eval_code = False

        if ('unescape_strings' in self.opts and self.opts['unescape_strings']):
            options.unescape_strings = True
        else:
            options.unescape_strings = False

        if ('wrap_line_length' in self.opts and self.opts['wrap_line_length']):
            options.wrap_line_length = self.opts['wrap_line_length']
        else:
            options.wrap_line_length = 0

        if (
            'break_chained_methods' in self.opts and
            self.opts['break_chained_methods']
        ):
            options.break_chained_methods = True
        else:
            options.break_chained_methods = False

        if ('end_with_newline' in self.opts and self.opts['end_with_newline']):
            options.end_with_newline = True
        else:
            options.end_with_newline = False

        if ('comma_first' in self.opts and self.opts['comma_first']):
            options.comma_first = True
        else:
            options.comma_first = False

        if (
            'space_after_anon_function' in self.opts and
            self.opts['space_after_anon_function']
        ):
            options.space_after_anon_function = True
        else:
            options.space_after_anon_function = False

        if (
            'unindent_chained_methods' in self.opts and
            self.opts['unindent_chained_methods']
        ):
            options.unindent_chained_methods = True
        else:
            options.unindent_chained_methods = False

        if ('operator_position' in self.opts and self.opts['operator_position']):
            options.operator_position = self.opts['operator_position']
        else:
            options.operator_position = 'before-newline'

        try:
            stdout = jsbeautifier.beautify(text, options)
        except Exception as e:
            stderr = str(e)

        # return ', '

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
