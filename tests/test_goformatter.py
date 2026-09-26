import pytest
from unittest.mock import Mock

from .scenarios import format_on_save_scenarios


@pytest.mark.parametrize('options,filename,expected', format_on_save_scenarios)
def test_go_formatter_format_on_save_enabled(options, filename, expected):
    from codeformatter.goformatter import GoFormatter

    mocked_formatter = Mock()
    mocked_formatter.settings = {'codeformatter_go_options': options}

    gf = GoFormatter(mocked_formatter)
    res = gf.format_on_save_enabled(filename)
    assert res is expected
