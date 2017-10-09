from mock import Mock, call


# mocked formatters
mf_php = Mock()
mf_js = Mock()
mf_html = Mock()
mf_css = Mock()
mf_scss = Mock()
mf_py = Mock()
mf_vbscript = Mock()
mf_coldfusion = Mock()

def setup_test():
    mocked_sublime = Mock()
    mocked_sublime.version = Mock(return_value=2000)
    import sys
    sys.modules['sublime'] = mocked_sublime


def mocked_view():
    mview = Mock()
    mview.return_value.settings = Mock(return_value={'syntax': 'Packages/User/PHP.sublime-syntax'})
    mview.return_value.file_name = Mock(return_value='test_file_name')
    return mview()


def default_settings():
    settings = {
        'codeformatter_php_options': {'syntaxes': 'php'},
        'codeformatter_js_options': {'syntaxes': 'javascript,json'},
        'codeformatter_css_options': {'syntaxes': 'css,less'},
        'codeformatter_html_options': {'syntaxes': 'html,asp'},
        'codeformatter_python_options': {'syntaxes': 'python'},
        'codeformatter_vbscript_options': {'syntaxes': 'vbscript'},
        'codeformatter_scss_options': {'syntaxes': 'scss'},
        'codeformatter_coldfusion_options': {'syntaxes': 'coldfusion'}
    }
    return settings


def fill_module_mocks(formatter):
    settings = default_settings()
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



def test_formatter_instance():
    setup_test()
    from codeformatter import formatter
    fill_module_mocks(formatter)

    f_instance = formatter.Formatter(mocked_view())
    assert f_instance.st_version == 2
    assert f_instance.syntax == 'php'
    assert f_instance.platform == 'platform_test'
    assert f_instance.file_name == 'test_file_name'
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


def test_formatter_format():
    setup_test()
    from codeformatter import formatter
    fill_module_mocks(formatter)

    mf_format = Mock(side_effect=[('formated', 'no error'), Exception('fake_exception')])
    mf_clean = Mock()
    mf_php.return_value.format = mf_format

    f_instance = formatter.Formatter(mocked_view())
    f_instance.clean = mf_clean
    test_text = 'testing raw string to format'
    f_instance.format(test_text)

    mf_format.assert_called_once_with(test_text)
    mf_clean.assert_has_calls([call('formated'), call('no error')])

    mf_clean.reset_mock()
    f_instance = formatter.Formatter(mocked_view())
    f_instance.clean = mf_clean
    f_instance.format('')
    mf_clean.assert_has_calls([call(''), call('fake_exception')])

