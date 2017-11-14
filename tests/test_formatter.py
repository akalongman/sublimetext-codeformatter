import pytest
from unittest.mock import Mock, call

# mocked formatters
mf_php = Mock()
mf_js = Mock()
mf_html = Mock()
mf_css = Mock()
mf_scss = Mock()
mf_py = Mock()
mf_vbscript = Mock()
mf_coldfusion = Mock()


def fill_module_mocks(formatter, settings):
    formatter.sublime.load_settings = Mock(return_value=settings)
    formatter.sublime.platform = Mock(return_value='platform_test')
    formatter.PhpFormatter = mf_php
    formatter.JsFormatter = mf_js
    formatter.HtmlFormatter = mf_html
    formatter.CssFormatter = mf_css
    formatter.ScssFormatter = mf_scss
    formatter.PyFormatter = mf_py
    formatter.VbscriptFormatter = mf_vbscript
    formatter.ColdfusionFormatter = mf_coldfusion


def test_formatter_instance(default_settings, php_view):
    from codeformatter import formatter
    fill_module_mocks(formatter, default_settings)

    f_instance = formatter.Formatter(php_view)
    assert f_instance.st_version == 3
    assert f_instance.syntax == 'php'
    assert f_instance.platform == 'platform_test'
    assert f_instance.file_name == 'php_file_name'
    assert len(f_instance.classmap.keys()) == 11
    assert f_instance.classmap['php'] is mf_php
    assert f_instance.classmap['javascript'] is mf_js
    assert f_instance.classmap['json'] is mf_js
    assert f_instance.classmap['css'] is mf_css
    assert f_instance.classmap['less'] is mf_css
    assert f_instance.classmap['html'] is mf_html
    assert f_instance.classmap['asp'] is mf_html
    assert f_instance.classmap['python'] is mf_py
    assert f_instance.classmap['vbscript'] is mf_vbscript
    assert f_instance.classmap['scss'] is mf_scss
    assert f_instance.classmap['coldfusion'] is mf_coldfusion


def test_formatter_format(default_settings, php_view):
    from codeformatter import formatter
    fill_module_mocks(formatter, default_settings)

    mf_format = Mock(return_value=('formated', 'no error'))
    mf_clean = Mock()
    mf_php.return_value.format = mf_format

    f_instance = formatter.Formatter(php_view)
    f_instance.clean = mf_clean
    test_text = 'testing raw string to format'
    f_instance.format(test_text)

    mf_format.assert_called_once_with(test_text)
    mf_clean.assert_has_calls([call('formated'), call('no error')])


def test_formatter_format_exception(default_settings, php_view):
    from codeformatter import formatter
    fill_module_mocks(formatter, default_settings)

    mf_format = Mock(side_effect=Exception('fake_exception'))
    mf_clean = Mock()
    mf_php.return_value.format = mf_format

    f_instance = formatter.Formatter(php_view)
    f_instance.clean = mf_clean
    f_instance.format('')
    mf_clean.assert_has_calls([call(''), call('fake_exception')])


def test_formatter_exists(default_settings, php_view):
    from codeformatter import formatter
    fill_module_mocks(formatter, default_settings)
    f_instance = formatter.Formatter(php_view)
    assert f_instance.exists() is True

    f_instance = formatter.Formatter(php_view, syntax='invalid_syntax')
    assert f_instance.exists() is False


def test_formatter_get_syntax(default_settings, php_view, invalid_syntax_view):
    from codeformatter import formatter
    fill_module_mocks(formatter, default_settings)

    f_instance = formatter.Formatter(php_view)
    assert f_instance.get_syntax() == 'php'

    f_instance = formatter.Formatter(invalid_syntax_view)
    assert f_instance.get_syntax() == ''


def test_formatter_format_on_save_enabled(default_settings, php_view):
    from codeformatter import formatter
    fill_module_mocks(formatter, default_settings)

    f_instance = formatter.Formatter(php_view)
    f_instance.exists = Mock(return_value=True)

    mocked_inner_formatter_call = Mock(return_value='returning_the_call')
    type(mf_php.return_value).format_on_save_enabled = mocked_inner_formatter_call

    res = f_instance.format_on_save_enabled()

    assert res == 'returning_the_call'
    mocked_inner_formatter_call.assert_called_once_with('php_file_name')


def test_formatter_format_on_save_enabled_false(default_settings, php_view):
    from codeformatter import formatter
    fill_module_mocks(formatter, default_settings)
    f_instance = formatter.Formatter(php_view)
    f_instance.exists = Mock(return_value=False)
    assert f_instance.format_on_save_enabled() is False


def test_formatter_format_clean(default_settings, php_view):
    from codeformatter import formatter
    fill_module_mocks(formatter, default_settings)
    f_instance = formatter.Formatter(php_view)

    mocked_string = Mock()
    mocked_string.decode = Mock(return_value='mocked_testing')
    res = f_instance.clean(mocked_string)

    assert res == 'mocked_testing'
    mocked_string.decode.assert_called_once_with('UTF-8', 'ignore')


@pytest.mark.parametrize("string_input,expected", [
    ('testing', 'testing'),
    ('testing\r\n', 'testing\n'),
    ('testing\r', 'testing\n'),
])
def test_formatter_format_clean_cases(string_input, expected, default_settings, php_view):
    from codeformatter import formatter
    fill_module_mocks(formatter, default_settings)
    f_instance = formatter.Formatter(php_view)

    mocked_string = Mock()
    mocked_string.decode = Mock(return_value=string_input)
    res = f_instance.clean(mocked_string)

    assert res == expected
