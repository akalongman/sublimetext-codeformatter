import pytest
from unittest.mock import Mock

from .scenarios import format_on_save_scenarios


def test_py_formatter_format_not_supported():
    from codeformatter.pyformatter import PyFormatter

    mocked_formatter = Mock()
    mocked_formatter.st_version = 3
    mocked_formatter.settings = {
        'codeformatter_python_options': {'syntaxes': 'python'}}

    out, err = PyFormatter(mocked_formatter).format('x=1'.encode('utf8'))
    assert out == ''
    assert 'not supported' in err


@pytest.mark.parametrize('options,filename,expected', format_on_save_scenarios)
def test_py_formatter_format_on_save_enabled(options, filename, expected):
    from codeformatter.pyformatter import PyFormatter

    mocked_formatter = Mock()
    mocked_formatter.settings = {'codeformatter_python_options': options}

    pf = PyFormatter(mocked_formatter)
    res = pf.format_on_save_enabled(filename)
    assert res is expected
