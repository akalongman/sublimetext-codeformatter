import os
import sys
import pytest
from unittest.mock import Mock

# mocking sublime
mocked_sublime = Mock()
mocked_sublime.version = Mock(return_value=3001)
sys.modules['sublime'] = mocked_sublime

# adding lib folder to syspath
directory = os.path.dirname(os.path.realpath(__file__))
libs_path = os.path.join(directory, '..', 'codeformatter', 'lib')
if libs_path not in sys.path:
    sys.path.append(libs_path)


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
