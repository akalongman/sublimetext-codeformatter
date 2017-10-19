import pytest
from unittest.mock import Mock


@pytest.fixture
def php_view():
    mview = Mock()
    mview.return_value.settings = Mock(return_value={'syntax': 'Packages/User/PHP.sublime-syntax'})
    mview.return_value.file_name = Mock(return_value='php_file_name')
    return mview()


@pytest.fixture
def invalid_syntax_view():
    mview = Mock()
    mview.return_value.settings = Mock(return_value={'syntax': 'invalid_syntax'})
    mview.return_value.file_name = Mock(return_value='invalid_file_name')
    return mview()


@pytest.fixture
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
