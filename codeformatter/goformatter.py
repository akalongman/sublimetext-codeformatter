import sublime
import subprocess


class GoFormatter:

    def __init__(self, formatter):
        self.formatter = formatter
        self.opts = formatter.settings.get('codeformatter_go_options')

    def format(self, text):

        stderr = ''
        stdout = ''

        try:
            p = subprocess.Popen(
                ['gofmta'],
                stdin=subprocess.PIPE,
                stdout=subprocess.PIPE,
                stderr=subprocess.PIPE
            )
            stdout, stderr = p.communicate(text)
        except Exception as e:
            stderr = str(e)

        if (not stderr and not stdout):
            stderr = 'Formatting error!'

        return stdout, stderr

    def format_on_save_enabled(self, _):
        format_on_save = False
        if ('format_on_save' in self.opts and self.opts['format_on_save']):
            format_on_save = True
        return format_on_save
